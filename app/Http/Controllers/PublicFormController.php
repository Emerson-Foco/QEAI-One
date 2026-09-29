<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Support\CustomFields;
use App\Support\LeadIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicFormController extends Controller
{
    public function show(string $slug)
    {
        $form = Form::with('organization')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('public.form', [
            'form' => $form,
            'organization' => $form->organization,
            'fieldDefs' => \App\Support\CustomFields::forEntity($form->organization, 'contact'),
        ]);
    }

    public function submit(Request $request, string $slug)
    {
        $form = Form::with('organization')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $organization = $form->organization;

        // Honeypot: bots preenchem; humanos não veem.
        if ($request->filled('website')) {
            return redirect()->route('form.show', $slug);
        }

        $bucket = 'form-' . $form->id . '-' . $request->ip();
        if (RateLimiter::tooManyAttempts($bucket, 10)) {
            throw ValidationException::withMessages(['form' => 'Muitas solicitações. Tente novamente em alguns minutos.']);
        }
        RateLimiter::hit($bucket, 900);

        if ($form->consent_text && ! $request->boolean('consent')) {
            throw ValidationException::withMessages(['consent' => 'É necessário aceitar para enviar.']);
        }

        $orgFields = CustomFields::forEntity($organization, 'contact');
        $rules = [];
        $custom = [];
        foreach ($form->fields ?? [] as $field) {
            if (($field['source'] ?? '') === 'custom') {
                $definition = $orgFields->firstWhere('key', $field['key']);
                if ($definition === null) {
                    continue;
                }
                $rule = [($field['required'] ?? false) ? 'required' : 'nullable'];
                if ($definition->type === 'email') {
                    $rule[] = 'email';
                }
                if ($definition->type === 'number') {
                    $rule[] = 'numeric';
                }
                if ($definition->type === 'date') {
                    $rule[] = 'date';
                }
                if ($definition->type === 'select' && ! empty($definition->options)) {
                    $rule[] = Rule::in($definition->options);
                }
                $rules['custom.' . $field['key']] = $rule;
                $custom[] = $field['key'];
                continue;
            }

            $rule = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $rule[] = $field['key'] === 'email' ? 'email' : 'string';
            $rules[$field['key']] = $rule;
        }

        $data = $request->validate($rules);

        $contact = LeadIntake::create($organization, [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source' => 'formulário: ' . $form->name,
            'custom' => array_intersect_key($data['custom'] ?? [], array_flip($custom)),
        ], 'form', [
            'form_id' => $form->id,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 300),
            'consent' => (bool) $form->consent_text,
        ]);

        if ($form->redirect_url) {
            return redirect()->away($form->redirect_url);
        }

        return redirect()->route('form.show', $slug)->with('form_success', $form->success_message);
    }
}
