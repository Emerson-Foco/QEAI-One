<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Organization;
use App\Support\CustomFields;
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

        $totalLeads = Contact::where('organization_id', $organization->id)->count();
        $leadsInPeriod = $this->contactsBetween($organization, $from, $to)->count();
        $leadsPrevious = $this->contactsBetween($organization, $previousFrom, $previousTo)->count();

        $fields = CustomFields::forEntity($organization, 'contact');

        $sources = $this->contactsBetween($organization, $from, $to)
            ->select('source', 'custom')
            ->get()
            ->groupBy(fn (Contact $contact) => (string) ($contact->custom['origem'] ?? '') ?: ($contact->source ?: 'Não informado'))
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

        $kpis = [
            [
                'label' => 'Total de leads',
                'value' => number_format($totalLeads, 0, ',', '.'),
                'icon' => 'users',
                'tone' => 'blue',
                'delta' => ['percent' => null, 'direction' => 'flat'],
                'detail' => 'base completa da organização',
            ],
            [
                'label' => 'Novos no período',
                'value' => number_format($leadsInPeriod, 0, ',', '.'),
                'icon' => 'user-plus',
                'tone' => 'purple',
                'delta' => $this->delta($leadsInPeriod, $leadsPrevious),
                'detail' => 'comparado ao período anterior',
            ],
            [
                'label' => 'Campos ativos',
                'value' => number_format($fields->count(), 0, ',', '.'),
                'icon' => 'list-check',
                'tone' => 'green',
                'delta' => ['percent' => null, 'direction' => 'flat'],
                'detail' => 'campos do CRM disponíveis',
            ],
            [
                'label' => 'Origem principal',
                'value' => $sourceRows[0]['name'] ?? '—',
                'icon' => 'target',
                'tone' => 'orange',
                'delta' => ['percent' => null, 'direction' => 'flat'],
                'detail' => $sourceTotal > 0 ? ($sourceRows[0]['percent'] ?? 0).'% dos leads do período' : 'sem dados no período',
            ],
        ];

        return view('member.dashboard', [
            'organization' => $organization,
            'from' => $from,
            'to' => $to,
            'kpis' => $kpis,
            'sourceRows' => $sourceRows,
            'sourceTotal' => $sourceTotal,
            'donutGradient' => $donutGradient,
            'recentLeads' => Contact::where('organization_id', $organization->id)->latest('id')->limit(6)->get(),
            'totalLeads' => $totalLeads,
            'fieldsCount' => $fields->count(),
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
}
