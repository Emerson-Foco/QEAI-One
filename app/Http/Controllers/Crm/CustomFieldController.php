<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\CustomFields;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomFieldController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.settings');
    }

    public function index(Organization $organization)
    {
        $this->authorize($organization);

        return view('member.crm.fields', [
            'organization' => $organization,
            'fields' => CustomFields::forEntity($organization, 'contact'),
            'types' => CustomField::types(),
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(CustomField::types()))],
            'options_text' => ['nullable', 'string', 'max:1000'],
            'required' => ['nullable'],
            'show_in_list' => ['nullable'],
        ]);

        $base = CustomFields::slug($data['label']);
        $key = $base;
        $suffix = 2;
        while ($organization->customFields()->where('entity', 'contact')->where('key', $key)->exists()) {
            $key = $base . '-' . $suffix++;
        }

        $field = $organization->customFields()->create([
            'entity' => 'contact',
            'key' => $key,
            'label' => $data['label'],
            'type' => $data['type'],
            'options' => $data['type'] === 'select' ? $this->parseOptions((string) ($data['options_text'] ?? '')) : null,
            'required' => $request->boolean('required'),
            'show_in_list' => $request->boolean('show_in_list'),
            'is_system' => false,
            'position' => (int) $organization->customFields()->where('entity', 'contact')->max('position') + 1,
        ]);

        Audit::log('custom_field.created', 'organization', $organization->id, 'custom_field', $field->id, null, ['label' => $field->label]);

        return back()->with('status', 'Campo criado.');
    }

    public function update(Request $request, Organization $organization, CustomField $field)
    {
        $this->authorize($organization);
        abort_unless($field->organization_id === $organization->id, 404);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'options_text' => ['nullable', 'string', 'max:1000'],
            'required' => ['nullable'],
            'show_in_list' => ['nullable'],
        ]);

        $field->update([
            'label' => $data['label'],
            'options' => $field->type === 'select' ? $this->parseOptions((string) ($data['options_text'] ?? '')) : $field->options,
            'required' => $request->boolean('required'),
            'show_in_list' => $request->boolean('show_in_list'),
        ]);

        Audit::log('custom_field.updated', 'organization', $organization->id, 'custom_field', $field->id, null, ['label' => $field->label]);

        return back()->with('status', 'Campo atualizado.');
    }

    public function destroy(Organization $organization, CustomField $field)
    {
        $this->authorize($organization);
        abort_unless($field->organization_id === $organization->id, 404);

        if ($field->is_system) {
            return back()->withErrors(['field' => 'Campos padrão não podem ser excluídos.']);
        }

        $fieldId = $field->id;
        $label = $field->label;
        $field->delete();

        Audit::log('custom_field.deleted', 'organization', $organization->id, 'custom_field', $fieldId, ['label' => $label], null);

        return back()->with('status', 'Campo removido.');
    }

    private function parseOptions(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $text) ?: [])));
    }
}
