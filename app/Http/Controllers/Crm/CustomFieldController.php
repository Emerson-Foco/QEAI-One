<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\CustomFields;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomFieldController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.crm_fields');
    }

    public function index(Organization $organization)
    {
        $this->authorize($organization);

        $fields = CustomFields::allForEntity($organization, 'contact');

        return view('member.crm.fields', [
            'organization' => $organization,
            'fields' => $fields,
            'types' => CustomField::types(),
            'usage' => $this->usageCounts($organization, $fields),
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(CustomField::types()))],
            'options_text' => ['nullable', 'string', 'max:2000'],
            'required' => ['nullable'],
            'show_in_list' => ['nullable'],
        ]);

        $options = $this->parseOptions((string) ($data['options_text'] ?? ''));
        if ($data['type'] === 'select' && $options === []) {
            throw ValidationException::withMessages(['options_text' => 'Informe pelo menos uma opção para o campo de seleção.']);
        }

        $base = CustomFields::slug($data['label']);
        $key = $base;
        $suffix = 2;
        while ($organization->customFields()->where('entity', 'contact')->where('key', $key)->exists()) {
            $key = $base.'-'.$suffix++;
        }

        $field = $organization->customFields()->create([
            'entity' => 'contact',
            'key' => $key,
            'label' => $data['label'],
            'type' => $data['type'],
            'options' => $data['type'] === 'select' ? $options : null,
            'required' => $request->boolean('required'),
            'show_in_list' => $request->boolean('show_in_list'),
            'is_system' => false,
            'is_active' => true,
            'position' => (int) $organization->customFields()->where('entity', 'contact')->max('position') + 1,
        ]);

        Audit::log('custom_field.created', 'organization', $organization->id, 'custom_field', $field->id, null, [
            'field_id' => $field->field_id,
            'label' => $field->label,
        ]);

        return back()->with('status', 'Campo criado. O ID '.$field->field_id.' já pode ser usado em automações.');
    }

    public function update(Request $request, Organization $organization, CustomField $field)
    {
        $this->authorize($organization);
        abort_unless($field->organization_id === $organization->id, 404);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(CustomField::types()))],
            'options_text' => ['nullable', 'string', 'max:2000'],
            'required' => ['nullable'],
            'show_in_list' => ['nullable'],
            'confirm_type_change' => ['nullable'],
        ]);

        $typeChanged = $field->type !== $data['type'];
        $usage = $this->usageCount($organization, $field);
        if ($typeChanged && $usage > 0 && ! $request->boolean('confirm_type_change')) {
            throw ValidationException::withMessages([
                'type' => 'Este campo já possui dados em '.$usage.' lead(s). Para alterar o tipo, marque a confirmação de ciência.',
            ]);
        }

        $options = $this->parseOptions((string) ($data['options_text'] ?? ''));
        if ($data['type'] === 'select' && $options === []) {
            throw ValidationException::withMessages(['options_text' => 'Informe pelo menos uma opção para o campo de seleção.']);
        }

        $before = ['label' => $field->label, 'type' => $field->type, 'options' => $field->options];

        $field->update([
            'label' => $data['label'],
            'type' => $data['type'],
            'options' => $data['type'] === 'select' ? $options : null,
            'required' => $request->boolean('required'),
            'show_in_list' => $request->boolean('show_in_list'),
        ]);

        Audit::log('custom_field.updated', 'organization', $organization->id, 'custom_field', $field->id, $before, [
            'field_id' => $field->field_id,
            'label' => $field->label,
            'type' => $field->type,
            'type_changed' => $typeChanged,
            'confirmed_type_change' => $typeChanged && $request->boolean('confirm_type_change'),
        ]);

        return back()->with('status', 'Campo atualizado. O ID '.$field->field_id.' permanece o mesmo.');
    }

    public function toggle(Organization $organization, CustomField $field)
    {
        $this->authorize($organization);
        abort_unless($field->organization_id === $organization->id, 404);

        $field->update(['is_active' => ! $field->is_active]);

        Audit::log(
            $field->is_active ? 'custom_field.activated' : 'custom_field.deactivated',
            'organization',
            $organization->id,
            'custom_field',
            $field->id,
            null,
            ['field_id' => $field->field_id, 'label' => $field->label]
        );

        return back()->with('status', $field->is_active
            ? 'Campo reativado. Ele volta a aparecer nos formulários.'
            : 'Campo desativado. Os dados já preenchidos foram preservados no histórico.');
    }

    /** @param Collection<int, CustomField> $fields */
    private function usageCounts(Organization $organization, Collection $fields): array
    {
        $counts = $fields->mapWithKeys(fn (CustomField $field) => [$field->id => 0])->all();
        if ($fields->isEmpty()) {
            return $counts;
        }

        Contact::where('organization_id', $organization->id)
            ->select('id', 'custom')
            ->chunkById(500, function (Collection $contacts) use (&$counts, $fields) {
                foreach ($contacts as $contact) {
                    $custom = $contact->custom ?: [];
                    foreach ($fields as $field) {
                        if ($this->hasValue($custom[$field->key] ?? null)) {
                            $counts[$field->id]++;
                        }
                    }
                }
            });

        return $counts;
    }

    private function usageCount(Organization $organization, CustomField $field): int
    {
        $count = 0;

        Contact::where('organization_id', $organization->id)
            ->select('id', 'custom')
            ->chunkById(500, function (Collection $contacts) use (&$count, $field) {
                foreach ($contacts as $contact) {
                    if ($this->hasValue(($contact->custom ?: [])[$field->key] ?? null)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function hasValue(mixed $value): bool
    {
        return ! ($value === null || $value === '' || $value === []);
    }

    private function parseOptions(string $text): array
    {
        $options = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $text) ?: [])));

        return array_slice(array_unique($options), 0, 50);
    }
}
