<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\CustomFields;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.leads');
    }

    public function index(Organization $organization)
    {
        $this->authorize($organization);

        return view('member.crm.forms', [
            'organization' => $organization,
            'forms' => $organization->forms()->latest('id')->get(),
            'builtin' => Form::builtinFields(),
            'customFields' => CustomFields::forEntity($organization, 'contact'),
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'success_message' => ['nullable', 'string', 'max:255'],
            'redirect_url' => ['nullable', 'url', 'max:255'],
            'consent_text' => ['nullable', 'string', 'max:255'],
            'include' => ['array'],
            'include.*' => ['string'],
            'required' => ['array'],
            'required.*' => ['string'],
        ]);

        $include = $data['include'] ?? [];
        $required = $data['required'] ?? [];
        $fields = [];
        foreach ($include as $token) {
            if (str_starts_with($token, 'b:')) {
                $fields[] = ['source' => 'builtin', 'key' => substr($token, 2), 'required' => in_array($token, $required, true)];
            } elseif (str_starts_with($token, 'c:')) {
                $fields[] = ['source' => 'custom', 'key' => substr($token, 2), 'required' => in_array($token, $required, true)];
            }
        }

        if ($fields === []) {
            return back()->withErrors(['include' => 'Selecione ao menos um campo para o formulário.'])->withInput();
        }

        $form = $organization->forms()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'fields' => $fields,
            'success_message' => $data['success_message'] ?: 'Recebemos seu contato. Obrigado!',
            'redirect_url' => $data['redirect_url'] ?? null,
            'consent_text' => $data['consent_text'] ?? null,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Audit::log('form.created', 'organization', $organization->id, 'form', $form->id, null, ['name' => $form->name]);

        return back()->with('status', 'Formulário criado. Link público: ' . route('form.show', $form->slug));
    }

    public function toggle(Organization $organization, Form $form)
    {
        $this->authorize($organization);
        abort_unless($form->organization_id === $organization->id, 404);

        $form->update(['is_active' => ! $form->is_active]);
        Audit::log('form.toggled', 'organization', $organization->id, 'form', $form->id, null, ['active' => $form->is_active]);

        return back()->with('status', 'Formulário ' . ($form->is_active ? 'ativado' : 'desativado') . '.');
    }

    public function destroy(Organization $organization, Form $form)
    {
        $this->authorize($organization);
        abort_unless($form->organization_id === $organization->id, 404);

        $formId = $form->id;
        $name = $form->name;
        $form->delete();

        Audit::log('form.deleted', 'organization', $organization->id, 'form', $formId, ['name' => $name], null);

        return back()->with('status', 'Formulário removido.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'formulario';
        $slug = $base;
        while (Form::where('slug', $slug)->exists()) {
            $slug = $base . '-' . mb_strtolower(Str::random(4));
        }

        return $slug;
    }
}
