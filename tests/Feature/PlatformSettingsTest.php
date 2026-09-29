<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
    }

    private function root(): User
    {
        $user = new User(['name' => 'Root', 'email' => 'root@test.local', 'password' => Hash::make('password-123')]);
        $user->is_root_admin = true;
        $user->save();

        return $user;
    }

    public function test_root_sees_settings_page(): void
    {
        $this->actingAs($this->root())->get(route('panel.settings.edit'))->assertOk()->assertSee('E-mail da plataforma');
    }

    public function test_updates_identity_and_smtp_with_audit(): void
    {
        $root = $this->root();

        $this->actingAs($root)->put(route('panel.settings.update'), [
            'company_name' => 'QEAI One',
            'company_legal_name' => 'AQUILES VIEIRA CORREA',
            'company_document' => '60.357.082/0001-58',
            'contact_email' => 'contato@qeai.com.br',
            'primary_color' => '#123456',
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => '465',
            'smtp_encryption' => 'ssl',
            'smtp_username' => 'contato@qeai.com.br',
            'smtp_password' => 'segredo-smtp',
            'smtp_from_name' => 'QEAI One',
        ])->assertRedirect();

        $this->assertSame('QEAI One', Setting::get('company_name'));
        $this->assertSame('#123456', Setting::get('primary_color'));
        $this->assertSame('segredo-smtp', Setting::get('smtp_password'));

        // Senha fica cifrada no banco.
        $raw = \DB::table('settings')->where('name', 'smtp_password')->value('value');
        $this->assertNotSame('segredo-smtp', $raw);

        $this->assertDatabaseHas('audit_logs', ['action' => 'platform.settings.updated', 'scope' => 'platform']);
    }

    public function test_test_email_requires_smtp_configuration(): void
    {
        $root = $this->root();

        $this->actingAs($root)->post(route('panel.settings.test'), ['to' => 'destino@empresa.com'])
            ->assertSessionHasErrors('to');
    }

    public function test_test_email_sends_when_configured(): void
    {
        $root = $this->root();
        Setting::put('company_name', 'QEAI One');
        Setting::put('smtp_host', 'smtp.exemplo.com');
        Setting::put('smtp_port', '465');
        Setting::put('smtp_encryption', 'ssl');
        Setting::put('smtp_username', 'no-reply@qeai.com.br');
        Setting::put('smtp_password', 'segredo', true);

        Mail::fake();

        $this->actingAs($root)->post(route('panel.settings.test'), ['to' => 'destino@empresa.com'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => 'platform.email_test']);
    }

    public function test_non_root_cannot_access_settings(): void
    {
        $user = new User(['name' => 'Comum', 'email' => 'comum@test.local', 'password' => Hash::make('password-123')]);
        $user->save();

        $this->actingAs($user)->get(route('panel.settings.edit'))->assertForbidden();
    }
}
