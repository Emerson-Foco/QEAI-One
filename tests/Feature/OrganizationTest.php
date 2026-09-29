<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationTest extends TestCase
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

    private function makeOrganization(User $root, string $name = 'Acme'): Organization
    {
        $this->actingAs($root)->post(route('panel.organizations.store'), ['name' => $name])->assertRedirect();

        return Organization::query()->latest('id')->firstOrFail();
    }

    public function test_root_creates_organization_with_default_roles(): void
    {
        $root = $this->root();
        $organization = $this->makeOrganization($root);

        $this->assertSame(4, $organization->roles()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.created', 'scope' => 'platform']);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'organization', 'entity_id' => $organization->id]);
    }

    public function test_root_adds_member_and_updates_role(): void
    {
        $root = $this->root();
        $organization = $this->makeOrganization($root);

        $agent = $organization->roles()->where('slug', 'agent')->firstOrFail();
        $this->actingAs($root)->post(route('panel.members.store', $organization), [
            'email' => 'membro@empresa.com',
            'role_id' => $agent->id,
        ])->assertRedirect();

        $membership = $organization->memberships()->firstOrFail();
        $this->assertSame($agent->id, $membership->role_id);
        $this->assertDatabaseHas('users', ['email' => 'membro@empresa.com']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.added', 'scope' => 'organization']);

        $owner = $organization->roles()->where('slug', 'owner')->firstOrFail();
        $this->actingAs($root)->put(route('panel.members.update', [$organization, $membership]), [
            'role_id' => $owner->id,
            'status' => 'disabled',
        ])->assertRedirect();

        $this->assertSame($owner->id, $membership->fresh()->role_id);
        $this->assertSame('disabled', $membership->fresh()->status);
    }

    public function test_role_permissions_are_sanitized(): void
    {
        $root = $this->root();
        $organization = $this->makeOrganization($root);

        $this->actingAs($root)->post(route('panel.roles.store', $organization), [
            'name' => 'Suporte',
            'permissions' => ['org.conversations', 'permissao.invalida'],
        ])->assertRedirect();

        $role = $organization->roles()->where('slug', 'suporte')->firstOrFail();
        $this->assertSame(['org.conversations'], $role->permissions);
    }

    public function test_default_system_role_cannot_be_deleted(): void
    {
        $root = $this->root();
        $organization = $this->makeOrganization($root);
        $owner = $organization->roles()->where('slug', 'owner')->firstOrFail();

        $this->actingAs($root)->delete(route('panel.roles.destroy', [$organization, $owner]))->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $owner->id]);
    }

    public function test_non_root_cannot_access_panel(): void
    {
        $user = new User(['name' => 'Comum', 'email' => 'comum@test.local', 'password' => Hash::make('password-123')]);
        $user->save();

        $this->actingAs($user)->get(route('panel.organizations.index'))->assertForbidden();
    }
}
