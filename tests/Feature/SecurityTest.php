<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Support\Audit;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use App\Support\Webhooks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
    }

    private function user(string $email, bool $root = false): User
    {
        $user = User::create([
            'name' => Str::headline(Str::before($email, '@')),
            'email' => $email,
            'password' => 'senha-original-123',
        ]);
        if ($root) {
            $user->forceFill(['is_root_admin' => true])->save();
        }

        return $user;
    }

    private function organization(string $name): Organization
    {
        $organization = Organization::create(['name' => $name, 'slug' => Str::slug($name), 'status' => 'active']);
        foreach (OrgPermissions::defaults() as $role) {
            $organization->roles()->create($role);
        }
        CustomFields::ensureDefaults($organization, 'contact');

        return $organization;
    }

    private function addMember(Organization $organization, User $user, string $roleSlug = 'admin'): void
    {
        $role = $organization->roles()->where('slug', $roleSlug)->firstOrFail();
        $organization->memberships()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    public function test_security_headers_are_set(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $this->assertStringContainsString("frame-ancestors 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_javascript_url_in_custom_field_is_not_rendered_as_link(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin);

        $organization->customFields()->create([
            'entity' => 'contact',
            'key' => 'site',
            'label' => 'Site',
            'type' => 'url',
            'is_active' => true,
            'position' => 20,
        ]);

        $lead = Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead XSS',
            'custom' => ['site' => 'javascript:alert(1)'],
        ]);

        $this->actingAs($admin)->get(route('member.org.contacts.show', [$organization, $lead]))
            ->assertOk()
            ->assertDontSee('href="javascript:', false)
            ->assertSee('javascript:alert(1)');
    }

    public function test_csv_export_neutralizes_formula_injection(): void
    {
        $root = $this->user('root@qeai.com', true);

        AuditLog::create([
            'scope' => 'platform',
            'actor_type' => 'user',
            'actor_user_id' => $root->id,
            'action' => '=cmd|\' /C calc\'!A0',
            'entity_type' => 'test',
            'entity_id' => 1,
            'ip' => '127.0.0.1',
            'user_agent' => 'test',
        ]);

        $response = $this->actingAs($root)->get('/painel/logs?export=csv');
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString("'=cmd", $content);
    }

    public function test_view_preferences_are_scoped_by_user_and_organization(): void
    {
        $user = $this->user('multi@empresa.com');
        $orgA = $this->organization('Empresa A');
        $orgB = $this->organization('Empresa B');
        $this->addMember($orgA, $user, 'owner');
        $this->addMember($orgB, $user, 'admin');

        $orgA->customFields()->create([
            'entity' => 'contact',
            'key' => 'segmento',
            'label' => 'Segmento',
            'type' => 'select',
            'options' => ['Varejo', 'Serviços'],
            'is_active' => true,
            'show_in_list' => true,
            'position' => 20,
        ]);
        Contact::create(['organization_id' => $orgA->id, 'name' => 'Lead A', 'custom' => ['segmento' => 'Varejo']]);
        Contact::create(['organization_id' => $orgB->id, 'name' => 'Lead B']);

        $this->actingAs($user)->post(route('member.org.contacts.preference.store', $orgA), [
            'view' => 'table',
            'filters' => ['segmento' => 'Varejo'],
        ])->assertRedirect();

        $this->actingAs($user)->get(route('member.org.contacts.index', [
            'organization' => $orgB,
            'view' => 'table',
        ]))
            ->assertOk()
            ->assertSee('Lead B')
            ->assertDontSee('Lead A')
            ->assertDontSee('Preferência de filtros aplicada');
    }

    public function test_webhook_urls_block_internal_hosts(): void
    {
        $this->assertFalse(Webhooks::isSafeUrl('http://127.0.0.1/hook'));
        $this->assertFalse(Webhooks::isSafeUrl('http://localhost/hook'));
        $this->assertFalse(Webhooks::isSafeUrl('http://10.0.0.1/hook'));
        $this->assertFalse(Webhooks::isSafeUrl('file:///etc/passwd'));
        $this->assertTrue(Webhooks::isSafeUrl('https://example.com/hook'));
    }

    public function test_root_without_membership_only_sees_administrative_audit_events(): void
    {
        $root = $this->user('root@qeai.com', true);
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin);
        Audit::log('member.added', 'organization', $organization->id, 'membership', 1, null, ['email' => 'admin@empresa.com']);
        Audit::log('contact.created', 'organization', $organization->id, 'contact', 1, null, ['name' => 'Segredo do Cliente']);

        $this->actingAs($root)->get(route('panel.organizations.show', $organization))
            ->assertOk()
            ->assertSee('member.added')
            ->assertDontSee('contact.created')
            ->assertDontSee('Segredo do Cliente');

        $this->actingAs($root)->get(route('panel.organizations.logs', $organization))
            ->assertOk()
            ->assertSee('member.added')
            ->assertDontSee('contact.created')
            ->assertDontSee('Segredo do Cliente');

        $this->actingAs($root)->get(route('member.org.show', $organization))
            ->assertOk()
            ->assertSee('member.added')
            ->assertDontSee('contact.created')
            ->assertDontSee('Segredo do Cliente');
    }
}
