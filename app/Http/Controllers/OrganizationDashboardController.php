<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Organization;
use App\Models\Pipeline;
use App\Models\Task;
use App\Models\User;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrganizationDashboardController extends Controller
{
    public function __invoke(Request $request, Organization $organization)
    {
        $user = auth()->user();

        if (! OrgAccess::canData($user, $organization, 'org.leads')) {
            if (! $user->is_root_admin && ! OrgAccess::isMember($user, $organization)) {
                abort(403);
            }

            return view('member.dashboard-restricted', [
                'organization' => $organization,
                'isRootWithoutMembership' => $user->is_root_admin && OrgAccess::membership($user, $organization) === null,
            ]);
        }

        [$from, $to] = $this->period($request);
        $days = (int) $from->diffInDays($to) + 1;
        $previousFrom = $from->copy()->subDays($days)->startOfDay();
        $previousTo = $from->copy()->subSecond();

        $leadsTotal = Contact::where('organization_id', $organization->id)->count();
        $leadsPeriod = $this->contactsBetween($organization, $from, $to)->count();
        $leadsPrevious = $this->contactsBetween($organization, $previousFrom, $previousTo)->count();

        $openDeals = Deal::where('organization_id', $organization->id)->where('status', 'open');
        $openDealsCount = (clone $openDeals)->count();
        $openDealsValue = (int) (clone $openDeals)->sum('value_cents');
        $dealsPeriod = Deal::where('organization_id', $organization->id)->whereBetween('created_at', [$from, $to])->count();
        $dealsPrevious = Deal::where('organization_id', $organization->id)->whereBetween('created_at', [$previousFrom, $previousTo])->count();
        $wonValue = (int) Deal::where('organization_id', $organization->id)
            ->where('status', 'won')
            ->whereBetween('updated_at', [$from, $to])
            ->sum('value_cents');

        $companiesTotal = Company::where('organization_id', $organization->id)->count();
        $companiesPeriod = Company::where('organization_id', $organization->id)->whereBetween('created_at', [$from, $to])->count();
        $companiesPrevious = Company::where('organization_id', $organization->id)->whereBetween('created_at', [$previousFrom, $previousTo])->count();

        $kpis = [
            [
                'label' => 'Leads',
                'value' => number_format($leadsTotal, 0, ',', '.'),
                'icon' => 'users',
                'tone' => 'blue',
                'delta' => $this->delta($leadsPeriod, $leadsPrevious),
                'detail' => number_format($leadsPeriod, 0, ',', '.').' novo(s) no período',
            ],
            [
                'label' => 'Oportunidades',
                'value' => number_format($openDealsCount, 0, ',', '.'),
                'icon' => 'briefcase',
                'tone' => 'purple',
                'delta' => $this->delta($dealsPeriod, $dealsPrevious),
                'detail' => 'R$ '.number_format($openDealsValue / 100, 2, ',', '.').' em aberto',
            ],
            [
                'label' => 'Clientes',
                'value' => number_format($companiesTotal, 0, ',', '.'),
                'icon' => 'building',
                'tone' => 'green',
                'delta' => $this->delta($companiesPeriod, $companiesPrevious),
                'detail' => number_format($companiesPeriod, 0, ',', '.').' novo(s) no período',
            ],
            [
                'label' => 'Receita estimada',
                'value' => $this->money($openDealsValue),
                'icon' => 'trending-up',
                'tone' => 'orange',
                'delta' => ['percent' => null, 'direction' => 'flat'],
                'detail' => $this->moneyFull($wonValue).' ganhos no período',
            ],
        ];

        $pipeline = Pipeline::ensureDefaultFor($organization);
        $stages = $pipeline->stages()->orderBy('position')->get()->where('is_lost', false)->take(4)->values();
        $stageTotals = Deal::where('organization_id', $organization->id)
            ->where('pipeline_id', $pipeline->id)
            ->selectRaw('stage_id, COUNT(*) as total, COALESCE(SUM(value_cents), 0) as value')
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        $funnel = [];
        $topStage = 0;
        foreach ($stages as $index => $stage) {
            $count = (int) ($stageTotals[$stage->id]->total ?? 0);
            if ($index === 0) {
                $topStage = $count;
            }
            $funnel[] = [
                'name' => $stage->name,
                'count' => $count,
                'value' => (int) ($stageTotals[$stage->id]->value ?? 0),
                'percent' => 0,
            ];
        }
        $base = max(1, $topStage);
        foreach ($funnel as $index => $row) {
            $funnel[$index]['percent'] = $topStage > 0 ? (int) round($row['count'] / $base * 100) : 0;
            $funnel[$index]['width'] = max(32, $funnel[$index]['percent']);
        }

        $sources = $this->contactsBetween($organization, $from, $to)
            ->select('source')
            ->get()
            ->groupBy(fn (Contact $contact) => $contact->source ?: 'Não informado')
            ->map->count()
            ->sortDesc();
        $sourceTotal = (int) $sources->sum();
        $sourceEntries = $sources->take(5);
        $rest = (int) $sources->slice(5)->sum();
        if ($rest > 0) {
            $sourceEntries->put('Outros', $rest);
        }

        $palette = ['#3B82F6', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#94A3B8'];
        $sourceRows = [];
        $cursor = 0.0;
        $gradient = [];
        foreach ($sourceEntries as $name => $count) {
            $percent = $sourceTotal > 0 ? round($count / $sourceTotal * 100, 2) : 0;
            $start = $cursor;
            $cursor = min(100, $cursor + $percent);
            $color = $palette[count($sourceRows) % count($palette)];
            $gradient[] = $color.' '.$start.'% '.$cursor.'%';
            $sourceRows[] = [
                'name' => $name,
                'count' => (int) $count,
                'percent' => $percent,
                'color' => $color,
            ];
        }
        if ($sourceRows !== [] && $cursor < 100) {
            $gradient[] = $palette[count($sourceRows) % count($palette)].' '.$cursor.'% 100%';
        }
        $donutGradient = $gradient === [] ? '#EEF2F8 0% 100%' : implode(', ', $gradient);

        $tasks = Task::with(['contact', 'deal'])
            ->where('organization_id', $organization->id)
            ->where('status', 'open')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<=', today())
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        $activities = AuditLog::where('scope', 'organization')
            ->where('organization_id', $organization->id)
            ->latest('id')
            ->limit(6)
            ->get();
        $actors = User::whereIn('id', $activities->pluck('actor_user_id')->filter()->unique())->pluck('name', 'id');
        $activities = $activities->map(function (AuditLog $log) use ($actors) {
            return [
                'title' => $this->activityTitle($log->action),
                'detail' => $this->activityDetail($log, $actors[$log->actor_user_id] ?? null),
                'icon' => $this->activityIcon($log->action),
                'tone' => $this->activityTone($log->action),
                'time' => $log->created_at?->diffForHumans(),
            ];
        });

        return view('member.dashboard', [
            'organization' => $organization,
            'from' => $from,
            'to' => $to,
            'kpis' => $kpis,
            'funnel' => $funnel,
            'funnelTotal' => collect($funnel)->sum('count'),
            'funnelValue' => collect($funnel)->sum('value'),
            'sourceRows' => $sourceRows,
            'sourceTotal' => $sourceTotal,
            'donutGradient' => $donutGradient,
            'tasks' => $tasks,
            'activities' => $activities,
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(Request $request): array
    {
        $from = $this->parseDate($request->query('from'), now()->startOfMonth(), false);
        $to = $this->parseDate($request->query('to'), now()->endOfMonth(), true);

        if ($from->gt($to)) {
            return [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function parseDate(mixed $value, Carbon $default, bool $endOfDay): Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return $default;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable) {
            return $default;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function contactsBetween(Organization $organization, Carbon $from, Carbon $to)
    {
        return Contact::where('organization_id', $organization->id)->whereBetween('created_at', [$from, $to]);
    }

    /** @return array{percent: ?int, direction: string} */
    private function delta(int $current, int $previous): array
    {
        if ($previous === 0) {
            return ['percent' => null, 'direction' => $current > 0 ? 'up' : 'flat'];
        }
        $percent = (int) round((($current - $previous) / $previous) * 100);

        return [
            'percent' => $percent,
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
        ];
    }

    private function money(int $cents): string
    {
        $value = $cents / 100;
        if ($value >= 1000000) {
            return 'R$ '.number_format($value / 1000000, 1, ',', '.').' mi';
        }
        if ($value >= 100000) {
            return 'R$ '.number_format($value, 0, ',', '.');
        }

        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function moneyFull(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }

    private function activityTitle(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'contact.') => 'Lead atualizado',
            $action === 'deal.created' => 'Nova oportunidade criada',
            $action === 'deal.moved' => 'Oportunidade movida de etapa',
            str_starts_with($action, 'deal.') => 'Oportunidade atualizada',
            str_starts_with($action, 'task.') => 'Tarefa atualizada',
            str_starts_with($action, 'form.') => 'Formulário atualizado',
            str_starts_with($action, 'invite.') => 'Convite enviado',
            str_starts_with($action, 'member.') => 'Equipe atualizada',
            str_starts_with($action, 'message.') => 'Nova mensagem recebida',
            default => 'Atividade registrada',
        };
    }

    private function activityDetail(AuditLog $log, ?string $actor): string
    {
        $entity = $log->entity_type ? ucfirst(str_replace('_', ' ', $log->entity_type)) : 'registro';
        $author = $actor ?: 'Sistema';

        return $author.' · '.$entity.($log->entity_id ? ' #'.$log->entity_id : '');
    }

    private function activityIcon(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'contact.') => 'user-plus',
            str_starts_with($action, 'deal.') => 'briefcase',
            str_starts_with($action, 'task.') => 'check-square',
            str_starts_with($action, 'message.') => 'message',
            str_starts_with($action, 'form.') => 'filter',
            default => 'activity',
        };
    }

    private function activityTone(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'contact.') => 'blue',
            str_starts_with($action, 'deal.') => 'purple',
            str_starts_with($action, 'task.') => 'green',
            str_starts_with($action, 'message.') => 'pink',
            str_starts_with($action, 'form.') => 'orange',
            default => 'gray',
        };
    }
}
