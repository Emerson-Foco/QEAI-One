<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Support\Audit;
use App\Support\CustomFields;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.leads');
    }

    private function ensure(Organization $organization, Contact $contact): void
    {
        abort_unless($contact->organization_id === $organization->id, 404);
    }

    public function index(Request $request, Organization $organization)
    {
        $this->authorize($organization);

        $fields = CustomFields::forEntity($organization, 'contact');
        $listFields = $fields->where('show_in_list', true)->values();
        $selectFields = $fields->where('type', 'select')->values();
        $dateFields = $fields->where('type', 'date')->values();

        $view = in_array($request->input('view'), ['table', 'board', 'calendar', 'timeline'], true)
            ? (string) $request->input('view')
            : 'table';

        $groupKey = (string) $request->input('group', (string) optional($selectFields->first())->key);
        $groupField = $selectFields->firstWhere('key', $groupKey);
        $dateKey = (string) $request->input('date_field', (string) optional($dateFields->first())->key);
        $dateField = $dateFields->firstWhere('key', $dateKey);
        $startKey = (string) $request->input('start_field', (string) optional($dateFields->first())->key);
        $startField = $dateFields->firstWhere('key', $startKey);
        $endKey = (string) $request->input('end_field', (string) optional($dateFields->last())->key);
        $endField = $dateFields->firstWhere('key', $endKey);

        $search = trim((string) $request->input('q'));
        $filters = (array) $request->input('filters', []);
        $contacts = $this->filteredContacts($organization, $search, $filters, $fields);

        $month = $this->parseMonth($request->input('month'));

        $table = null;
        if ($view === 'table') {
            $perPage = 25;
            $page = max(1, (int) $request->input('page', 1));
            $table = new LengthAwarePaginator(
                $contacts->forPage($page, $perPage)->values(),
                $contacts->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $boardGroups = [];
        if ($view === 'board' && $groupField !== null) {
            foreach (($groupField->options ?? []) as $option) {
                $boardGroups[$option] = $contacts
                    ->filter(fn (Contact $contact) => (string) ($contact->custom[$groupKey] ?? '') === (string) $option)
                    ->values();
            }
            $boardGroups['Sem valor'] = $contacts
                ->filter(fn (Contact $contact) => blank($contact->custom[$groupKey] ?? null))
                ->values();
        }

        $calendarDays = [];
        $calendarUndated = 0;
        if ($view === 'calendar' && $dateField !== null) {
            [$calendarDays, $calendarUndated] = $this->buildCalendar($contacts, $dateField, $month);
        }

        $timeline = [
            'bars' => [],
            'months' => [],
            'start' => $month->copy()->startOfMonth(),
            'end' => $month->copy()->startOfMonth()->addMonthsNoOverflow(3)->subDay(),
            'total_days' => 0,
            'today_left' => null,
            'undated' => 0,
        ];
        if ($view === 'timeline') {
            $timeline = $this->buildTimeline($contacts, $startField, $endField, $groupField, $month);
        }

        return view('member.crm.contacts', [
            'organization' => $organization,
            'view' => $view,
            'fields' => $fields,
            'listFields' => $listFields,
            'selectFields' => $selectFields,
            'dateFields' => $dateFields,
            'groupField' => $groupField,
            'dateField' => $dateField,
            'startField' => $startField,
            'endField' => $endField,
            'search' => $search,
            'filters' => $filters,
            'contacts' => $table,
            'filteredCount' => $contacts->count(),
            'total' => Contact::where('organization_id', $organization->id)->count(),
            'newThisMonth' => Contact::where('organization_id', $organization->id)->where('created_at', '>=', now()->startOfMonth())->count(),
            'boardGroups' => $boardGroups,
            'month' => $month,
            'calendarDays' => $calendarDays,
            'calendarUndated' => $calendarUndated,
            'timeline' => $timeline,
        ]);
    }

    public function create(Organization $organization)
    {
        $this->authorize($organization);

        return view('member.crm.lead-form', [
            'organization' => $organization,
            'contact' => null,
            'fields' => CustomFields::forEntity($organization, 'contact'),
        ]);
    }

    public function show(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        $history = AuditLog::where('scope', 'organization')
            ->where('organization_id', $organization->id)
            ->where('entity_type', 'contact')
            ->where('entity_id', $contact->id)
            ->latest('id')
            ->limit(50)
            ->get();

        return view('member.crm.lead-show', [
            'organization' => $organization,
            'contact' => $contact->load(['owner', 'company', 'tags']),
            'fields' => CustomFields::allForEntity($organization, 'contact'),
            'history' => $history,
            'actors' => User::whereIn('id', $history->pluck('actor_user_id')->filter()->unique())->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $fields = CustomFields::forEntity($organization, 'contact');
        $data = $this->validated($request, $organization, $fields);
        $custom = $this->collectCustom($request, $fields);
        $columns = $this->legacyColumns($custom);

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'company_id' => $data['company_id'] ?? null,
            ...$columns,
            'custom' => $custom,
            'owner_user_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);
        $this->syncTags($organization, $contact, (string) $request->input('tags', ''));

        Audit::log('contact.created', 'organization', $organization->id, 'contact', $contact->id, null, [
            'lead_id' => $contact->lead_id,
            'name' => $contact->displayName(),
        ]);

        return redirect()
            ->route('member.org.contacts.show', [$organization, $contact])
            ->with('status', 'Lead criado com o ID '.$contact->lead_id.'.');
    }

    public function edit(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        return view('member.crm.lead-form', [
            'organization' => $organization,
            'contact' => $contact->load('tags'),
            'fields' => CustomFields::forEntity($organization, 'contact'),
        ]);
    }

    public function update(Request $request, Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);
        $fields = CustomFields::forEntity($organization, 'contact');
        $data = $this->validated($request, $organization, $fields);

        $custom = $this->collectCustom($request, $fields, $contact->custom ?? []);
        $updates = [
            ...$this->legacyColumns($custom),
            'custom' => $custom,
        ];
        if ($request->has('company_id')) {
            $updates['company_id'] = $data['company_id'] ?? null;
        }
        $contact->update($updates);
        if ($request->has('tags')) {
            $this->syncTags($organization, $contact, (string) $request->input('tags', ''));
        }

        Audit::log('contact.updated', 'organization', $organization->id, 'contact', $contact->id, null, [
            'lead_id' => $contact->lead_id,
            'name' => $contact->displayName(),
        ]);

        return redirect()
            ->route('member.org.contacts.show', [$organization, $contact])
            ->with('status', 'Lead atualizado.');
    }

    public function destroy(Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        $name = $contact->name;
        $contactId = $contact->id;

        $contact->delete();

        Audit::log('contact.deleted', 'organization', $organization->id, 'contact', $contactId, ['name' => $name], null);

        return redirect()->route('member.org.contacts.index', $organization)->with('status', 'Lead removido.');
    }

    public function updateField(Request $request, Organization $organization, Contact $contact)
    {
        $this->authorize($organization);
        $this->ensure($organization, $contact);

        $data = $request->validate([
            'field' => ['required', 'string', 'max:120'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $field = CustomFields::forEntity($organization, 'contact')->firstWhere('key', $data['field']);
        if ($field === null || $field->type !== 'select') {
            return $this->fieldError($request, 'Campo inválido para movimentação.');
        }

        $value = trim((string) ($data['value'] ?? ''));
        $value = $value === '' ? null : $value;
        if ($value !== null && ! in_array($value, $field->options ?? [], true)) {
            return $this->fieldError($request, 'Valor inválido para este campo.');
        }
        if ($value === null && $field->required) {
            return $this->fieldError($request, 'Este campo é obrigatório e não pode ficar vazio.');
        }

        $custom = $contact->custom ?: [];
        $custom[$field->key] = $value;
        $contact->update([
            'custom' => $custom,
            ...$this->legacyColumns($custom),
        ]);

        Audit::log('contact.field_updated', 'organization', $organization->id, 'contact', $contact->id, null, [
            'lead_id' => $contact->lead_id,
            'field' => $field->key,
            'field_id' => $field->field_id,
            'value' => $value,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'field' => $field->key, 'value' => $value]);
        }

        return back()->with('status', 'Lead movimentado para “'.($value ?? 'Sem valor').'”.');
    }

    private function fieldError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => ['message' => $message]], 422);
        }

        throw ValidationException::withMessages(['field' => $message]);
    }

    private function filteredContacts(Organization $organization, string $search, array $filters, Collection $fields): Collection
    {
        $contacts = Contact::with(['company', 'tags'])
            ->where('organization_id', $organization->id)
            ->latest('id')
            ->get();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $contacts = $contacts->filter(function (Contact $contact) use ($needle) {
                if (mb_strpos(mb_strtolower((string) $contact->lead_id), $needle) !== false) {
                    return true;
                }
                if (mb_strpos(mb_strtolower($contact->displayName()), $needle) !== false) {
                    return true;
                }

                foreach (($contact->custom ?: []) as $value) {
                    if (is_scalar($value) && mb_strpos(mb_strtolower((string) $value), $needle) !== false) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        foreach ($fields as $field) {
            if (! array_key_exists($field->key, $filters)) {
                continue;
            }

            $filter = $filters[$field->key];
            if (! is_array($filter)) {
                $filter = ['value' => $filter];
            }

            if ($field->type === 'date') {
                $from = $this->parseDateInput($filter['from'] ?? null);
                $to = $this->parseDateInput($filter['to'] ?? null);
                if ($from === null && $to === null) {
                    continue;
                }

                $contacts = $contacts->filter(function (Contact $contact) use ($field, $from, $to) {
                    $value = $this->customDate($contact, $field->key);
                    if ($value === null) {
                        return false;
                    }
                    if ($from !== null && $value->lt($from)) {
                        return false;
                    }
                    if ($to !== null && $value->gt($to)) {
                        return false;
                    }

                    return true;
                })->values();

                continue;
            }

            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            if ($field->type === 'select' && ! in_array($value, $field->options ?? [], true)) {
                continue;
            }

            $contacts = $contacts->filter(function (Contact $contact) use ($field, $value) {
                $raw = $contact->custom[$field->key] ?? null;

                return match ($field->type) {
                    'select' => (string) $raw === $value,
                    'boolean' => (bool) $raw === ($value === '1'),
                    'number' => is_numeric($raw) && (float) $raw === (float) $value,
                    default => mb_stripos((string) $raw, $value) !== false,
                };
            })->values();
        }

        return $contacts;
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function buildCalendar(Collection $contacts, CustomField $dateField, Carbon $month): array
    {
        $grouped = [];
        $undated = 0;

        foreach ($contacts as $contact) {
            $date = $this->customDate($contact, $dateField->key);
            if ($date === null) {
                $undated++;

                continue;
            }
            $grouped[$date->toDateString()][] = $contact;
        }

        $gridStart = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $days = [];

        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $key = $day->toDateString();
            $days[] = [
                'date' => $day->copy(),
                'in_month' => $day->month === $month->month && $day->year === $month->year,
                'is_today' => $day->isToday(),
                'leads' => $grouped[$key] ?? [],
            ];
        }

        return [$days, $undated];
    }

    /** @return array<string, mixed> */
    private function buildTimeline(Collection $contacts, ?CustomField $startField, ?CustomField $endField, ?CustomField $colorField, Carbon $month): array
    {
        $windowStart = $month->copy()->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonthsNoOverflow(3)->subDay();
        $totalDays = (int) $windowStart->diffInDays($windowEnd) + 1;

        $palette = ['#3B82F6', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#0EA5E9', '#84CC16'];
        $colors = [];
        foreach (($colorField?->options ?? []) as $index => $option) {
            $colors[(string) $option] = $palette[$index % count($palette)];
        }

        $bars = [];
        $undated = 0;
        foreach ($contacts as $contact) {
            $start = $startField !== null ? $this->customDate($contact, $startField->key) : $contact->created_at?->copy()->startOfDay();
            if ($start === null) {
                $undated++;

                continue;
            }

            $end = $endField !== null ? $this->customDate($contact, $endField->key) : null;
            $end = $end ?? $start->copy();
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            if ($end->lt($windowStart) || $start->gt($windowEnd)) {
                continue;
            }

            $startOffset = max(0, (int) $windowStart->diffInDays($start->copy()->startOfDay(), false));
            $endOffset = min($totalDays, (int) $windowStart->diffInDays($end->copy()->startOfDay(), false) + 1);
            if ($endOffset <= $startOffset) {
                $endOffset = $startOffset + 1;
            }

            $colorValue = $colorField !== null ? (string) ($contact->custom[$colorField->key] ?? '') : '';

            $bars[] = [
                'contact' => $contact,
                'start' => $start,
                'end' => $end,
                'left' => round($startOffset / $totalDays * 100, 3),
                'width' => max(1.5, round(($endOffset - $startOffset) / $totalDays * 100, 3)),
                'color' => $colors[$colorValue] ?? '#6D3DF5',
            ];
        }

        usort($bars, fn ($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        $monthNames = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
        $months = [];
        $cursor = $windowStart->copy()->startOfMonth();
        while ($cursor->lte($windowEnd)) {
            $from = $cursor->copy()->lt($windowStart) ? $windowStart->copy() : $cursor->copy();
            $to = $cursor->copy()->endOfMonth()->gt($windowEnd) ? $windowEnd->copy() : $cursor->copy()->endOfMonth();
            $days = (int) $from->diffInDays($to) + 1;
            $months[] = [
                'label' => $monthNames[$cursor->month].' '.$cursor->year,
                'width' => round($days / $totalDays * 100, 3),
            ];
            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        $todayLeft = null;
        if (today()->betweenIncluded($windowStart, $windowEnd)) {
            $todayOffset = (int) $windowStart->diffInDays(today(), false);
            $todayLeft = round($todayOffset / $totalDays * 100, 3);
        }

        return [
            'bars' => $bars,
            'months' => $months,
            'start' => $windowStart,
            'end' => $windowEnd,
            'total_days' => $totalDays,
            'today_left' => $todayLeft,
            'undated' => $undated,
            'colors' => $colors,
        ];
    }

    private function parseMonth(mixed $value): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            try {
                return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
            } catch (\Throwable) {
                // Mantém o mês atual.
            }
        }

        return now()->startOfMonth();
    }

    private function parseDateInput(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function customDate(Contact $contact, string $key): ?Carbon
    {
        $raw = $contact->custom[$key] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function collectCustom(Request $request, Collection $fields, array $existing = []): array
    {
        $custom = CustomFields::collect($request, $fields);
        foreach ($existing as $key => $value) {
            if (! array_key_exists($key, $custom)) {
                $custom[$key] = $value;
            }
        }

        $legacyMap = [
            'name' => 'nome',
            'email' => 'email',
            'phone' => 'telefone',
            'whatsapp' => 'whatsapp',
            'source' => 'origem',
            'notes' => 'observacoes',
            'job_title' => 'cargo',
        ];

        foreach ($legacyMap as $input => $key) {
            if ($request->filled($input) && blank($custom[$key] ?? null)) {
                $custom[$key] = trim((string) $request->input($input));
            }
        }

        return $custom;
    }

    /** @return array<string, mixed> */
    private function legacyColumns(array $custom): array
    {
        return [
            'name' => mb_substr((string) (($custom['nome'] ?? null) ?: 'Lead'), 0, 180),
            'email' => $this->limitText($custom['email'] ?? null, 180),
            'phone' => $this->limitText($custom['telefone'] ?? null, 40),
            'whatsapp' => $this->limitText($custom['whatsapp'] ?? null, 40),
            'job_title' => $this->limitText($custom['cargo'] ?? null, 120),
            'source' => $this->limitText($custom['origem'] ?? null, 60),
            'notes' => $this->limitText($custom['observacoes'] ?? null, 5000),
        ];
    }

    private function limitText(mixed $value, int $max): ?string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        return mb_substr(trim((string) $value), 0, $max);
    }

    private function validated(Request $request, Organization $organization, $fields): array
    {
        $rules = [
            'name' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:60'],
            'company_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:500'],
        ];

        $data = $request->validate(array_merge($rules, CustomFields::rules($fields)));

        if (! empty($data['company_id'])) {
            $data['company_id'] = Company::where('organization_id', $organization->id)->where('id', $data['company_id'])->value('id');
        }

        return $data;
    }

    private function syncTags(Organization $organization, Contact $contact, string $input): void
    {
        $names = array_filter(array_map('trim', explode(',', $input)));
        $ids = [];
        foreach (array_slice($names, 0, 15) as $name) {
            $slug = Str::slug($name) ?: 'tag';
            $tag = Tag::firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => $slug],
                ['name' => mb_substr($name, 0, 60)]
            );
            $ids[] = $tag->id;
        }
        $contact->tags()->sync($ids);
    }
}
