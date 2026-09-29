<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Audit;
use App\Support\PlatformMail;
use Illuminate\Http\Request;

class PlatformSettingsController extends Controller
{
    public function edit()
    {
        return view('settings.index', ['settings' => $this->current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_legal_name' => ['nullable', 'string', 'max:180'],
            'company_document' => ['nullable', 'string', 'max:24'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'in:ssl,tls'],
            'smtp_username' => ['nullable', 'email', 'max:180'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:100'],
        ]);

        $before = $this->current();

        foreach ([
            'company_name', 'company_legal_name', 'company_document', 'contact_email', 'primary_color',
            'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_from_name',
        ] as $key) {
            Setting::put($key, (string) ($data[$key] ?? ''));
        }

        if (! empty($data['smtp_password'])) {
            Setting::put('smtp_password', $data['smtp_password'], true);
        }

        Audit::log('platform.settings.updated', 'platform', null, 'settings', null, [
            'company_name' => $before['company_name'] ?? null,
            'smtp_host' => $before['smtp_host'] ?? null,
        ], [
            'company_name' => (string) ($data['company_name'] ?? ''),
            'smtp_host' => (string) ($data['smtp_host'] ?? ''),
        ]);

        return back()->with('status', 'Configurações da plataforma salvas.');
    }

    public function testEmail(Request $request)
    {
        $data = $request->validate(['to' => ['required', 'email', 'max:180']]);

        if (! PlatformMail::configured()) {
            return back()->withErrors(['to' => 'Configure e salve o SMTP antes de enviar o teste.']);
        }

        try {
            PlatformMail::send(
                $data['to'],
                'Teste de e-mail — ' . Setting::get('company_name', 'QEAI One'),
                '<p>Se você recebeu este e-mail, o SMTP da plataforma está funcionando.</p>'
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['to' => 'Falha ao enviar: ' . $exception->getMessage()]);
        }

        Audit::log('platform.email_test', 'platform', null, 'settings', null, null, ['to' => $data['to']]);

        return back()->with('status', 'E-mail de teste enviado para ' . $data['to'] . '.');
    }

    /** @return array<string, string> */
    private function current(): array
    {
        return [
            'company_name' => (string) Setting::get('company_name', 'QEAI One'),
            'company_legal_name' => (string) Setting::get('company_legal_name', ''),
            'company_document' => (string) Setting::get('company_document', ''),
            'contact_email' => (string) Setting::get('contact_email', ''),
            'primary_color' => (string) Setting::get('primary_color', '#6D3DF5'),
            'smtp_host' => (string) Setting::get('smtp_host', ''),
            'smtp_port' => (string) Setting::get('smtp_port', '465'),
            'smtp_encryption' => (string) Setting::get('smtp_encryption', 'ssl'),
            'smtp_username' => (string) Setting::get('smtp_username', ''),
            'smtp_from_name' => (string) Setting::get('smtp_from_name', 'QEAI One'),
            'smtp_configured' => PlatformMail::configured() ? '1' : '',
        ];
    }
}
