<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Contact;
use App\Models\Organization;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class InboundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
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

    public function test_generic_inbound_creates_lead(): void
    {
        $organization = $this->organization('Acme');
        $endpoint = $organization->inboundEndpoints()->create([
            'name' => 'N8N', 'source' => 'generic', 'token' => 'tok-generic', 'is_active' => true,
        ]);

        $this->postJson('/hooks/tok-generic', ['name' => 'Lead Externo', 'email' => 'lead@externo.com', 'custom' => ['temperatura' => 'Morno']])
            ->assertStatus(201);

        $contact = Contact::firstOrFail();
        $this->assertSame('Lead Externo', $contact->name);
        $this->assertSame('Morno', $contact->custom['temperatura']);
        $this->assertNotNull($endpoint->fresh()->last_received_at);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->organization('Acme');
        $this->postJson('/hooks/inexistente', ['name' => 'X'])->assertStatus(404);
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_generic_inbound_validates_signature_when_secret_set(): void
    {
        $organization = $this->organization('Acme');
        $organization->inboundEndpoints()->create([
            'name' => 'Com segredo', 'source' => 'generic', 'token' => 'tok-secret', 'secret' => 'segredo', 'is_active' => true,
        ]);

        $body = json_encode(['name' => 'Assinado']);
        $this->call('POST', '/hooks/tok-secret', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], $body);
        $this->assertDatabaseCount('contacts', 0);

        $signature = hash_hmac('sha256', $body, 'segredo');
        $this->call('POST', '/hooks/tok-secret', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_QEAI_SIGNATURE' => $signature,
        ], $body);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_meta_verification_and_lead_receiving(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['João Meta']],
                    ['name' => 'email', 'values' => ['joao@meta.com']],
                    ['name' => 'temperatura', 'values' => ['Quente']],
                ],
            ], 200),
        ]);

        $organization = $this->organization('Acme');
        $organization->inboundEndpoints()->create([
            'name' => 'Meta', 'source' => 'meta_lead_ads', 'token' => 'tok-meta',
            'meta_verify_token' => 'verify-123', 'meta_page_access_token' => 'page-token', 'is_active' => true,
        ]);

        $this->get('/hooks/meta/tok-meta?hub.mode=subscribe&hub.verify_token=verify-123&hub.challenge=abc123')
            ->assertOk()->assertSee('abc123');

        $this->get('/hooks/meta/tok-meta?hub.mode=subscribe&hub.verify_token=errado&hub.challenge=abc')->assertStatus(403);

        $this->postJson('/hooks/meta/tok-meta', ['entry' => [['changes' => [['value' => ['leadgen_id' => '999']]]]]])
            ->assertOk();

        $contact = Contact::firstOrFail();
        $this->assertSame('João Meta', $contact->name);
        $this->assertSame('joao@meta.com', $contact->email);
        $this->assertSame('Quente', $contact->custom['temperatura']);
    }
}
