<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanTest extends TestCase
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

    public function test_free_plan_is_seeded(): void
    {
        $free = Plan::where('slug', 'free')->firstOrFail();
        $this->assertTrue($free->allows('channels.email'));
        $this->assertFalse($free->allows('channels.whatsapp'));
        $this->assertSame(2, $free->limit('users'));
    }

    public function test_root_creates_plan_with_features(): void
    {
        $root = $this->root();

        $this->actingAs($root)->post(route('panel.plans.store'), [
            'name' => 'Plus',
            'price_reais' => '49.90',
            'interval' => 'month',
            'enabled' => ['channels.email', 'channels.whatsapp'],
            'limits' => ['users' => 5, 'contacts' => 5000],
        ])->assertRedirect();

        $plan = Plan::where('slug', 'plus')->firstOrFail();
        $this->assertSame(4990, $plan->price_cents);
        $this->assertTrue($plan->allows('channels.whatsapp'));
        $this->assertSame(5, $plan->limit('users'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'plan.created']);
    }

    public function test_assign_plan_to_organization(): void
    {
        $root = $this->root();
        $this->actingAs($root)->post(route('panel.organizations.store'), ['name' => 'Acme']);
        $organization = Organization::latest('id')->firstOrFail();

        $this->actingAs($root)->post(route('panel.plans.store'), [
            'name' => 'Pro', 'price_reais' => '99', 'interval' => 'month',
        ]);
        $pro = Plan::where('slug', 'pro')->firstOrFail();

        $this->actingAs($root)->put(route('panel.organizations.plan', $organization), ['plan_id' => $pro->id])->assertRedirect();

        $this->assertSame($pro->id, $organization->fresh()->plan_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.plan_changed']);
    }

    public function test_cannot_delete_plan_in_use(): void
    {
        $root = $this->root();
        $free = Plan::where('slug', 'free')->firstOrFail();

        $this->actingAs($root)->post(route('panel.organizations.store'), ['name' => 'Usa Free']);
        $organization = Organization::latest('id')->firstOrFail();
        $this->assertSame($free->id, $organization->plan_id);

        $this->actingAs($root)->delete(route('panel.plans.destroy', $free))->assertSessionHasErrors('plan');
        $this->assertDatabaseHas('plans', ['id' => $free->id]);
    }
}
