<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Organization;
use App\Models\User;
use App\Support\PlanResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeatureOverrideTest extends TestCase
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

    public function test_override_changes_effective_feature(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);

        $this->assertFalse(PlanResolver::enabled($organization, 'channels.whatsapp'));

        $this->actingAs($root)->put(route('panel.organizations.features', $organization), [
            'enabled' => ['channels.whatsapp' => '1'],
        ])->assertRedirect();

        $this->assertTrue(PlanResolver::enabled($organization->fresh(), 'channels.whatsapp'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.features_updated']);
    }

    public function test_user_limit_blocks_adding_member(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);
        $agent = $organization->roles()->where('slug', 'agent')->firstOrFail();

        // Override: limite de 1 usuário (o plano free permite 2).
        $this->actingAs($root)->put(route('panel.organizations.features', $organization), [
            'limits' => ['users' => '1'],
        ])->assertRedirect();

        $this->assertSame(1, PlanResolver::limit($organization->fresh(), 'users'));

        $this->actingAs($root)->post(route('panel.members.store', $organization), [
            'email' => 'primeiro@empresa.com', 'role_id' => $agent->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('memberships', ['organization_id' => $organization->id]);

        $this->actingAs($root)->post(route('panel.members.store', $organization), [
            'email' => 'segundo@empresa.com', 'role_id' => $agent->id,
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'segundo@empresa.com']);
    }

    public function test_limit_blocks_invite_acceptance(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);
        $agent = $organization->roles()->where('slug', 'agent')->firstOrFail();

        $this->actingAs($root)->put(route('panel.organizations.features', $organization), [
            'limits' => ['users' => '1'],
        ]);
        $this->actingAs($root)->post(route('panel.members.store', $organization), [
            'email' => 'ocupado@empresa.com', 'role_id' => $agent->id,
        ]);

        $raw = 'convite-limite-' . Str::random(30);
        $organization->invites()->create([
            'email' => 'novo@empresa.com', 'role_id' => $agent->id, 'token_hash' => hash('sha256', $raw),
            'status' => 'pending', 'consent_confirmed' => true, 'consent_at' => now(), 'expires_at' => now()->addDays(7),
        ]);

        $this->post('/convite/' . $raw, [
            'name' => 'Novo',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertOk()->assertSee('Limite de usuários atingido');

        $this->assertDatabaseMissing('users', ['email' => 'novo@empresa.com']);
    }
}
