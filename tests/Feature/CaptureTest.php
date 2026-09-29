<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\LeadIntake;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
    }

    private function user(string $email): User
    {
        return User::create(['name' => Str::headline(Str::before($email, '@')), 'email' => $email, 'password' => 'senha-original-123']);
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

    private function addMember(Organization $organization, User $user, string $roleSlug): void
    {
        $role = $organization->roles()->where('slug', $roleSlug)->firstOrFail();
        $organization->memberships()->create(['user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active', 'joined_at' => now()]);
    }

    private function token(Organization $organization): string
    {
        $token = 'qo_' . Str::random(40);
        $organization->apiKeys()->create(['name' => 'Site', 'prefix' => substr($token, 0, 10), 'token_hash' => hash('sha256', $token)]);

        return $token;
    }

    public function test_api_creates_lead_with_api_key(): void
    {
        $organization = $this->organization('Acme');
        $token = $this->token($organization);

        $this->withHeaders(['X-Api-Key' => $token])
            ->postJson('/api/v1/leads', ['name' => 'Lead API', 'email' => 'lead@api.com', 'custom' => ['temperatura' => 'Quente']])
            ->assertStatus(201);

        $contact = Contact::firstOrFail();
        $this->assertSame('Lead API', $contact->name);
        $this->assertSame('Quente', $contact->custom['temperatura']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'lead.created', 'entity_type' => 'contact']);
    }

    public function test_api_rejects_invalid_key(): void
    {
        $this->organization('Acme');

        $this->withHeaders(['X-Api-Key' => 'invalida'])
            ->postJson('/api/v1/leads', ['name' => 'X'])
            ->assertStatus(401);

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_public_form_creates_lead(): void
    {
        $organization = $this->organization('Acme');
        $organization->forms()->create([
            'name' => 'Contato site',
            'slug' => 'contato-site',
            'fields' => [
                ['source' => 'builtin', 'key' => 'name', 'required' => true],
                ['source' => 'builtin', 'key' => 'email', 'required' => true],
                ['source' => 'custom', 'key' => 'temperatura', 'required' => false],
            ],
            'consent_text' => 'Autorizo o contato.',
            'is_active' => true,
        ]);

        $this->get('/f/contato-site')->assertOk()->assertSee('Contato site');

        $this->post('/f/contato-site', [
            'name' => 'Maria',
            'email' => 'maria@cliente.com',
            'custom' => ['temperatura' => 'Quente'],
            'consent' => '1',
        ])->assertRedirect(route('form.show', 'contato-site'));

        $contact = Contact::firstOrFail();
        $this->assertSame('Maria', $contact->name);
        $this->assertSame('Quente', $contact->custom['temperatura']);
        $this->assertStringContainsString('formulário', (string) $contact->source);
    }

    public function test_public_form_requires_consent_and_blocks_bots(): void
    {
        $organization = $this->organization('Acme');
        $organization->forms()->create([
            'name' => 'Contato', 'slug' => 'contato',
            'fields' => [['source' => 'builtin', 'key' => 'name', 'required' => true]],
            'consent_text' => 'Autorizo.', 'is_active' => true,
        ]);

        $this->post('/f/contato', ['name' => 'Sem consentimento'])->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('contacts', 0);

        $this->post('/f/contato', ['name' => 'Bot', 'website' => 'spam', 'consent' => '1'])->assertRedirect();
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_webhook_is_dispatched_on_lead_created(): void
    {
        Http::fake();
        $organization = $this->organization('Acme');
        $endpoint = $organization->webhookEndpoints()->create([
            'url' => 'https://example.com/hook',
            'secret' => 'segredo-de-teste',
            'events' => ['lead.created'],
            'is_active' => true,
        ]);

        LeadIntake::create($organization, ['name' => 'Lead Hook'], 'api');

        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hook'
            && $request->hasHeader('X-QEAI-Signature')
            && $request->hasHeader('X-QEAI-Event'));

        $this->assertSame(200, $endpoint->fresh()->last_status);
    }

    public function test_integrations_permission_is_enforced(): void
    {
        $agent = $this->user('agente@empresa.com');
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $agent, 'agent');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($agent)->get(route('member.org.forms.index', $organization))->assertOk();
        $this->actingAs($agent)->get(route('member.org.integrations.index', $organization))->assertForbidden();
        $this->actingAs($admin)->get(route('member.org.integrations.index', $organization))->assertOk();
    }
}
