<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Deal;
use App\Models\Organization;
use App\Models\Pipeline;
use App\Models\Task;
use App\Models\User;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmPipelineTest extends TestCase
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
        Pipeline::ensureDefaultFor($organization);

        return $organization;
    }

    private function addMember(Organization $organization, User $user, string $roleSlug = 'agent'): void
    {
        $role = $organization->roles()->where('slug', $roleSlug)->firstOrFail();
        $organization->memberships()->create([
            'user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active', 'joined_at' => now(),
        ]);
    }

    public function test_default_pipeline_has_stages(): void
    {
        $organization = $this->organization('Acme');
        $pipeline = $organization->pipelines()->where('is_default', true)->firstOrFail();

        $this->assertSame(5, $pipeline->stages()->count());
        $this->assertTrue($pipeline->stages()->where('is_won', true)->exists());
        $this->assertTrue($pipeline->stages()->where('is_lost', true)->exists());
    }

    public function test_deal_creation_and_move_sets_status(): void
    {
        $user = $this->user('vendedor@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user);
        $pipeline = $organization->pipelines()->where('is_default', true)->firstOrFail();
        $novo = $pipeline->stages()->where('name', 'Novo')->firstOrFail();
        $ganho = $pipeline->stages()->where('is_won', true)->firstOrFail();

        $this->actingAs($user)->post(route('member.org.deals.store', $organization), [
            'title' => 'Proposta ACME', 'value_reais' => '1500.50', 'stage_id' => $novo->id,
        ])->assertRedirect();

        $deal = Deal::firstOrFail();
        $this->assertSame(150050, $deal->value_cents);
        $this->assertSame('open', $deal->status);

        $this->actingAs($user)->post(route('member.org.deals.move', [$organization, $deal]), ['stage_id' => $ganho->id])
            ->assertRedirect();

        $this->assertSame('won', $deal->fresh()->status);
        $this->assertSame($ganho->id, $deal->fresh()->stage_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deal.moved']);
    }

    public function test_deal_is_scoped_to_organization(): void
    {
        $user = $this->user('vendedor@empresa.com');
        $orgA = $this->organization('Empresa A');
        $orgB = $this->organization('Empresa B');
        $this->addMember($orgA, $user);
        $this->addMember($orgB, $user);

        $pipeline = $orgA->pipelines()->where('is_default', true)->firstOrFail();
        $stage = $pipeline->stages()->firstOrFail();
        $deal = Deal::create([
            'organization_id' => $orgA->id, 'pipeline_id' => $pipeline->id, 'stage_id' => $stage->id,
            'title' => 'Negócio A', 'value_cents' => 0, 'status' => 'open',
        ]);

        $this->actingAs($user)->get(route('member.org.deals.edit', [$orgB, $deal]))->assertNotFound();
        $this->actingAs($user)->delete(route('member.org.deals.destroy', [$orgB, $deal]))->assertNotFound();
    }

    public function test_tasks_create_toggle_and_delete(): void
    {
        $user = $this->user('vendedor@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user);

        $this->actingAs($user)->post(route('member.org.tasks.store', $organization), [
            'title' => 'Ligar para o cliente',
        ])->assertRedirect();

        $task = Task::firstOrFail();
        $this->assertSame('open', $task->status);

        $this->actingAs($user)->post(route('member.org.tasks.toggle', [$organization, $task]))->assertRedirect();
        $this->assertSame('done', $task->fresh()->status);

        $this->actingAs($user)->delete(route('member.org.tasks.destroy', [$organization, $task]))->assertRedirect();
        $this->assertDatabaseCount('tasks', 0);
    }
}
