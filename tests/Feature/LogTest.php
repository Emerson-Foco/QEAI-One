<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LogTest extends TestCase
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

    private function organization(User $root, string $name = 'Acme'): Organization
    {
        $this->actingAs($root)->post(route('panel.organizations.store'), ['name' => $name]);

        return Organization::latest('id')->firstOrFail();
    }

    public function test_platform_logs_show_actions(): void
    {
        $root = $this->root();
        $this->organization($root);

        $this->actingAs($root)
            ->get(route('panel.logs.index'))
            ->assertOk()
            ->assertSee('organization.created');
    }

    public function test_platform_logs_export_csv(): void
    {
        $root = $this->root();
        $this->organization($root);

        $response = $this->actingAs($root)->get(route('panel.logs.index', ['export' => 'csv']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('hash_self', $response->streamedContent());
    }

    public function test_organization_logs_page_shows_events(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);
        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();

        $this->actingAs($root)->post(route('panel.invites.store', $organization), [
            'email' => 'convidado@empresa.com', 'role_id' => $role->id, 'consent' => '1',
        ]);

        $this->actingAs($root)
            ->get(route('panel.organizations.logs', $organization))
            ->assertOk()
            ->assertSee('invite.created');
    }

    public function test_non_root_cannot_see_platform_logs(): void
    {
        $user = new User(['name' => 'Comum', 'email' => 'comum@test.local', 'password' => Hash::make('password-123')]);
        $user->save();

        $this->actingAs($user)->get(route('panel.logs.index'))->assertForbidden();
    }

    public function test_reported_invite_alerts_admins(): void
    {
        $root = $this->root();
        Setting::put('smtp_host', 'smtp.exemplo.com');
        Setting::put('smtp_port', '465');
        Setting::put('smtp_encryption', 'ssl');
        Setting::put('smtp_username', 'no-reply@qeai.com.br');
        Setting::put('smtp_password', 'segredo', true);
        Setting::put('contact_email', 'contato@qeai.com.br');
        Mail::fake();

        $organization = $this->organization($root);
        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();

        // Convite criado diretamente (com token conhecido), pois com SMTP ativo o link não fica na sessão.
        $raw = 'convite-' . \Illuminate\Support\Str::random(40);
        $organization->invites()->create([
            'email' => 'desconhecido@empresa.com',
            'role_id' => $role->id,
            'token_hash' => hash('sha256', $raw),
            'status' => 'pending',
            'consent_confirmed' => true,
            'consent_by' => $root->id,
            'consent_at' => now(),
            'invited_by' => $root->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->post(route('invite.report', $raw), ['note' => 'não reconheço'])->assertOk()->assertSee('Registramos');

        $this->assertDatabaseHas('audit_logs', ['action' => 'invite.reported', 'scope' => 'platform']);
        $this->assertSame('reported', OrganizationInvite::firstOrFail()->status);
    }
}
