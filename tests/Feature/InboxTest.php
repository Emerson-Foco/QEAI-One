<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InboxTest extends TestCase
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

    public function test_admin_creates_web_chat_channel_and_agent_cannot(): void
    {
        $admin = $this->user('admin@empresa.com');
        $agent = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');
        $this->addMember($organization, $agent, 'agent');

        $this->actingAs($admin)->post(route('member.org.channels.chat.store', $organization), ['name' => 'Chat do site'])->assertRedirect();
        $this->assertDatabaseHas('channels', ['organization_id' => $organization->id, 'type' => 'web_chat']);

        $this->actingAs($agent)->get(route('member.org.channels.index', $organization))->assertForbidden();
    }

    public function test_public_chat_creates_conversation_and_inbox_reply(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $channel = $organization->channels()->create([
            'type' => 'web_chat', 'name' => 'Chat do site', 'token' => 'chat-token', 'config' => [], 'is_active' => true,
        ]);

        $this->get('/chat/chat-token')->assertOk()->assertSee('Chat do site');

        $this->post('/chat/chat-token', ['name' => 'Visitante', 'email' => 'vis@site.com', 'body' => 'Olá, preciso de ajuda'])
            ->assertRedirect(route('chat.show', 'chat-token'));

        $conversation = Conversation::firstOrFail();
        $this->assertSame(1, $conversation->messages()->where('direction', 'in')->count());
        $this->assertNotNull($conversation->contact_id);

        // Inbox: listar, ver e responder.
        $this->actingAs($admin)->get(route('member.org.inbox.index', $organization))->assertOk()->assertSee('Visitante');
        $this->actingAs($admin)->get(route('member.org.inbox.show', [$organization, $conversation]))->assertOk()->assertSee('preciso de ajuda');

        $this->actingAs($admin)->post(route('member.org.inbox.reply', [$organization, $conversation]), ['body' => 'Como posso ajudar?'])->assertRedirect();
        $this->assertSame(1, $conversation->messages()->where('direction', 'out')->count());

        $this->actingAs($admin)->post(route('member.org.inbox.status', [$organization, $conversation]), ['status' => 'closed'])->assertRedirect();
        $this->assertSame('closed', $conversation->fresh()->status);
    }

    public function test_conversation_is_scoped_to_organization(): void
    {
        $admin = $this->user('admin@empresa.com');
        $orgA = $this->organization('Empresa A');
        $orgB = $this->organization('Empresa B');
        $this->addMember($orgA, $admin, 'admin');
        $this->addMember($orgB, $admin, 'admin');

        $channelA = $orgA->channels()->create(['type' => 'web_chat', 'name' => 'A', 'token' => 'ta', 'config' => [], 'is_active' => true]);
        $conversation = Conversation::create(['organization_id' => $orgA->id, 'channel_id' => $channelA->id, 'subject' => 'X', 'status' => 'open']);

        $this->actingAs($admin)->get(route('member.org.inbox.show', [$orgB, $conversation]))->assertNotFound();
    }

    public function test_member_without_conversations_permission_is_forbidden(): void
    {
        $user = $this->user('sem@empresa.com');
        $organization = $this->organization('Acme');
        $role = $organization->roles()->create(['name' => 'Sem Atendimento', 'slug' => 'sem-atendimento', 'permissions' => ['org.reports'], 'is_system' => false]);
        $organization->memberships()->create(['user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($user)->get(route('member.org.inbox.index', $organization))->assertForbidden();
    }
}
