<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationPanelTest extends TestCase
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

        return $organization;
    }

    private function addMember(Organization $organization, User $user, string $roleSlug): Membership
    {
        $role = $organization->roles()->where('slug', $roleSlug)->firstOrFail();

        return $organization->memberships()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    public function test_admin_member_can_manage_organization(): void
    {
        $user = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user, 'admin');

        $this->actingAs($user)->get(route('member.org.show', $organization))->assertOk()->assertSee('Membros');

        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();
        $this->actingAs($user)->post(route('member.org.invites.store', $organization), [
            'email' => 'novo@empresa.com', 'role_id' => $role->id, 'consent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('organization_invites', ['organization_id' => $organization->id, 'email' => 'novo@empresa.com']);
    }

    public function test_agent_can_view_but_cannot_manage(): void
    {
        $user = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user, 'agent');

        $this->actingAs($user)->get(route('member.org.show', $organization))
            ->assertOk()
            ->assertSee('não permite gerenciar');

        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();
        $this->actingAs($user)->post(route('member.org.invites.store', $organization), [
            'email' => 'x@empresa.com', 'role_id' => $role->id, 'consent' => '1',
        ])->assertForbidden();
    }

    public function test_outsider_cannot_access_organization(): void
    {
        $user = $this->user('estranho@empresa.com');
        $organization = $this->organization('Acme');

        $this->actingAs($user)->get(route('member.org.show', $organization))->assertForbidden();
    }

    public function test_user_in_two_organizations_has_permissions_per_organization(): void
    {
        $user = $this->user('multi@empresa.com');
        $orgA = $this->organization('Empresa A');
        $orgB = $this->organization('Empresa B');
        $this->addMember($orgA, $user, 'owner');   // pode gerenciar
        $this->addMember($orgB, $user, 'agent');   // não pode gerenciar

        $this->actingAs($user)->get(route('member.org.show', $orgA))->assertOk()->assertSee('Membros');
        $this->actingAs($user)->get(route('member.org.show', $orgB))->assertOk()->assertSee('não permite gerenciar');

        $agentRoleA = $orgA->roles()->where('slug', 'agent')->firstOrFail();
        $this->actingAs($user)->post(route('member.org.invites.store', $orgA), [
            'email' => 'a@empresa.com', 'role_id' => $agentRoleA->id, 'consent' => '1',
        ])->assertRedirect();

        $agentRoleB = $orgB->roles()->where('slug', 'agent')->firstOrFail();
        $this->actingAs($user)->post(route('member.org.invites.store', $orgB), [
            'email' => 'b@empresa.com', 'role_id' => $agentRoleB->id, 'consent' => '1',
        ])->assertForbidden();
    }

    public function test_existing_user_accepts_second_organization_invite_without_password_change(): void
    {
        $user = $this->user('ja-tenho-conta@empresa.com');
        $originalHash = $user->password;

        $orgA = $this->organization('Empresa A');
        $this->addMember($orgA, $user, 'agent');

        $orgB = $this->organization('Empresa B');
        $role = $orgB->roles()->where('slug', 'manager')->firstOrFail();
        $raw = 'convite-teste-' . Str::random(30);
        $orgB->invites()->create([
            'email' => $user->email,
            'role_id' => $role->id,
            'token_hash' => hash('sha256', $raw),
            'status' => 'pending',
            'consent_confirmed' => true,
            'consent_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)->post('/convite/' . $raw)->assertRedirect(route('member.dashboard'));

        $this->assertDatabaseHas('memberships', ['organization_id' => $orgB->id, 'user_id' => $user->id, 'role_id' => $role->id]);
        $this->assertSame($originalHash, $user->fresh()->password, 'a senha existente não deve mudar ao aceitar convite de outra organização');
        $this->assertSame('accepted', OrganizationInvite::firstOrFail()->status);
    }

    public function test_logged_out_existing_user_is_asked_to_login(): void
    {
        $user = $this->user('existe@empresa.com');
        $organization = $this->organization('Acme');
        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();
        $raw = 'convite-login-' . Str::random(30);
        $organization->invites()->create([
            'email' => $user->email, 'role_id' => $role->id, 'token_hash' => hash('sha256', $raw),
            'status' => 'pending', 'consent_confirmed' => true, 'consent_at' => now(), 'expires_at' => now()->addDays(7),
        ]);

        $this->get(route('invite.show', $raw))->assertOk()->assertSee('Você já tem conta');
    }
}
