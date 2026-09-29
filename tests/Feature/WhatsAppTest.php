<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppTest extends TestCase
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

    private function whatsappChannel(Organization $organization): Channel
    {
        $channel = $organization->channels()->create([
            'type' => 'whatsapp', 'name' => 'WhatsApp', 'token' => 'wa-token',
            'config' => ['phone_number_id' => '123456'], 'is_active' => true,
        ]);
        $channel->setSecrets(['access_token' => 'token-abc', 'app_secret' => '', 'verify_token' => 'verify-wa']);
        $channel->save();

        return $channel;
    }

    public function test_webhook_verification_and_incoming_message(): void
    {
        $organization = $this->organization('Acme');
        $agent = $this->user('agente@empresa.com');
        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();
        $organization->memberships()->create(['user_id' => $agent->id, 'role_id' => $role->id, 'status' => 'active', 'joined_at' => now()]);
        $this->whatsappChannel($organization);

        $this->get('/webhooks/whatsapp/wa-token?hub.mode=subscribe&hub.verify_token=verify-wa&hub.challenge=CHAL')
            ->assertOk()->assertSee('CHAL');
        $this->get('/webhooks/whatsapp/wa-token?hub.mode=subscribe&hub.verify_token=errado&hub.challenge=X')->assertStatus(403);

        $this->postJson('/webhooks/whatsapp/wa-token', [
            'entry' => [['changes' => [['value' => [
                'contacts' => [['profile' => ['name' => 'Cliente Zap']]],
                'messages' => [['from' => '5511999998888', 'id' => 'm1', 'type' => 'text', 'text' => ['body' => 'Olá pelo WhatsApp']]],
            ]]]]],
        ])->assertOk();

        $contact = Contact::firstOrFail();
        $this->assertSame('Cliente Zap', $contact->name);
        $this->assertSame('5511999998888', $contact->whatsapp);

        $conversation = Conversation::firstOrFail();
        $this->assertSame(1, $conversation->messages()->where('direction', 'in')->count());
        $this->assertDatabaseHas('notifications', ['type' => 'conversation.message']);
    }

    public function test_reply_sends_via_whatsapp_api(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid']]], 200)]);

        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $role = $organization->roles()->where('slug', 'admin')->firstOrFail();
        $organization->memberships()->create(['user_id' => $admin->id, 'role_id' => $role->id, 'status' => 'active', 'joined_at' => now()]);
        $channel = $this->whatsappChannel($organization);

        $contact = Contact::create(['organization_id' => $organization->id, 'name' => 'Cliente', 'whatsapp' => '5511999998888']);
        $conversation = Conversation::create([
            'organization_id' => $organization->id, 'channel_id' => $channel->id, 'contact_id' => $contact->id,
            'subject' => 'WhatsApp', 'status' => 'open', 'visitor_token' => '5511999998888',
        ]);

        $this->actingAs($admin)->post(route('member.org.inbox.reply', [$organization, $conversation]), ['body' => 'Respondendo pelo painel'])
            ->assertRedirect();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com') && $request['to'] === '5511999998888');
        $this->assertSame(1, $conversation->messages()->where('direction', 'out')->count());
    }
}
