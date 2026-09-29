<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\LeadIntake;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_new_lead_notifies_users_with_leads_permission(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        LeadIntake::create($organization, ['name' => 'Lead Notificado'], 'api');

        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => 'lead.created']);
    }

    public function test_chat_message_notifies_users_with_conversations_permission(): void
    {
        $agent = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $agent, 'agent');

        $organization->channels()->create(['type' => 'web_chat', 'name' => 'Chat', 'token' => 'tok', 'config' => [], 'is_active' => true]);

        $this->post('/chat/tok', ['name' => 'Visitante', 'body' => 'Preciso de ajuda'])->assertRedirect();

        $this->assertDatabaseHas('notifications', ['user_id' => $agent->id, 'type' => 'conversation.message']);
    }

    public function test_notification_center_lists_and_marks_read(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');
        LeadIntake::create($organization, ['name' => 'Lead'], 'api');

        $this->actingAs($admin)->get(route('member.notifications.index'))->assertOk()->assertSee('Novo lead');

        $this->actingAs($admin)->post(route('member.notifications.readAll'))->assertRedirect();
        $this->assertSame(0, $admin->fresh()->unreadNotificationsCount());
    }

    public function test_onboarding_checklist_appears_when_steps_pending(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->get(route('member.org.show', $organization))
            ->assertOk()
            ->assertSee('Primeiros passos')
            ->assertSee('Conectar um canal');
    }

    public function test_task_due_command_notifies_assignee(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        Task::create([
            'organization_id' => $organization->id,
            'title' => 'Tarefa urgente',
            'status' => 'open',
            'due_at' => now()->addHours(2),
            'assigned_to' => $admin->id,
        ]);

        Artisan::call('tasks:notify-due');

        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => 'task.due']);
    }
}
