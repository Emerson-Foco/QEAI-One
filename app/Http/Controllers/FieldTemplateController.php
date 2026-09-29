<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\FieldTemplate;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\CustomFields;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FieldTemplateController extends Controller
{
    public function index()
    {
        return view('templates.index', [
            'templates' => FieldTemplate::orderByDesc('is_default')->orderBy('name')->get(),
            'types' => CustomField::types(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'fields' => ['array'],
        ]);

        $template = FieldTemplate::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? '',
            'fields' => $this->parseFields($request),
            'is_default' => false,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Audit::log('field_template.created', 'platform', null, 'field_template', $template->id, null, ['name' => $template->name]);

        return back()->with('status', 'Template criado.');
    }

    public function update(Request $request, FieldTemplate $template)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'fields' => ['array'],
        ]);

        $template->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'fields' => $this->parseFields($request),
        ]);

        Audit::log('field_template.updated', 'platform', null, 'field_template', $template->id, null, ['name' => $template->name]);

        return back()->with('status', 'Template atualizado.');
    }

    public function destroy(FieldTemplate $template)
    {
        $templateId = $template->id;
        $name = $template->name;
        $template->delete();
        Audit::log('field_template.deleted', 'platform', null, 'field_template', $templateId, ['name' => $name], null);

        return back()->with('status', 'Template removido.');
    }

    public function setDefault(FieldTemplate $template)
    {
        FieldTemplate::query()->update(['is_default' => false]);
        $template->update(['is_default' => true, 'is_active' => true]);
        Audit::log('field_template.default', 'platform', null, 'field_template', $template->id, null, ['name' => $template->name]);

        return back()->with('status', 'Template definido como padrão para novas organizações.');
    }

    public function applyTo(Request $request, Organization $organization)
    {
        $data = $request->validate(['template_id' => ['required', 'integer', 'exists:field_templates,id']]);
        $template = FieldTemplate::findOrFail($data['template_id']);
        $created = CustomFields::applyTemplate($organization, $template->fields ?? []);

        Audit::log('organization.template_applied', 'platform', null, 'organization', $organization->id, null, [
            'template' => $template->name,
            'created' => $created,
        ]);

        return back()->with('status', $created . ' campo(s) aplicado(s) a partir de "' . $template->name . '".');
    }

    private function parseFields(Request $request): array
    {
        $types = array_keys(CustomField::types());
        $fields = [];

        foreach ((array) $request->input('fields', []) as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $type = in_array($row['type'] ?? 'text', $types, true) ? $row['type'] : 'text';
            $options = $type === 'select'
                ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) ($row['options'] ?? '')) ?: [])))
                : null;

            $fields[] = [
                'label' => mb_substr($label, 0, 120),
                'type' => $type,
                'options' => $options,
                'required' => ! empty($row['required']),
                'show_in_list' => ! empty($row['show_in_list']),
            ];
        }

        return $fields;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'template';
        $slug = $base;
        $suffix = 2;
        while (FieldTemplate::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
