<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\Organization;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdsTest extends TestCase
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

    public function test_metrics_import_via_webhook(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->post(route('member.org.ads.accounts.store', $organization), ['name' => 'Meta', 'provider' => 'meta'])->assertRedirect();
        $account = AdAccount::firstOrFail();

        $this->postJson('/hooks/ads/' . $account->token, [
            'date' => '2026-09-28', 'impressions' => 1000, 'clicks' => 40, 'spend' => 150.50, 'conversions' => 5,
        ])->assertStatus(201);

        $metric = AdMetric::firstOrFail();
        $this->assertSame(1000, $metric->impressions);
        $this->assertSame(15050, $metric->spend_cents);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ad_account.created']);
    }

    public function test_manual_metric_upserts_same_day(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');
        $account = $organization->adAccounts()->create(['provider' => 'meta', 'name' => 'Meta', 'token' => 'tok', 'is_active' => true]);

        $this->actingAs($admin)->post(route('member.org.ads.metrics.store', $organization), [
            'ad_account_id' => $account->id, 'date' => '2026-09-28', 'impressions' => 100, 'clicks' => 5,
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('member.org.ads.metrics.store', $organization), [
            'ad_account_id' => $account->id, 'date' => '2026-09-28', 'impressions' => 200, 'clicks' => 9,
        ])->assertRedirect();

        $this->assertDatabaseCount('ad_metrics', 1);
        $this->assertSame(200, AdMetric::firstOrFail()->impressions);
    }

    public function test_agent_without_ads_permission_is_forbidden(): void
    {
        $agent = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $agent, 'agent');

        $this->actingAs($agent)->get(route('member.org.ads.index', $organization))->assertForbidden();
    }
}
