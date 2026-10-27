<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
    }

    private function user(string $email, bool $root = false): User
    {
        $user = User::create([
            'name' => Str::headline(Str::before($email, '@')),
            'email' => $email,
            'password' => 'senha-original-123',
        ]);
        if ($root) {
            $user->forceFill(['is_root_admin' => true])->save();
        }

        return $user;
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
        $organization->memberships()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    public function test_member_sees_lead_dashboard(): void
    {
        $user = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user, 'admin');

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Mariana Costa',
            'email' => 'mariana@cliente.com',
            'source' => 'Site',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('member.org.dashboard', $organization))
            ->assertOk()
            ->assertSee('Total de leads')
            ->assertSee('Leads por origem')
            ->assertSee('Leads recentes')
            ->assertSee('Mariana Costa')
            ->assertSee($contact->lead_id)
            ->assertSee('Site')
            ->assertSee('Gestão de CRM');
    }

    public function test_outsider_cannot_access_dashboard(): void
    {
        $outsider = $this->user('estranho@empresa.com');
        $organization = $this->organization('Acme');

        $this->actingAs($outsider)->get(route('member.org.dashboard', $organization))->assertForbidden();
    }

    public function test_root_without_membership_sees_governance_notice_instead_of_business_data(): void
    {
        $root = $this->user('root@qeai.com.br', true);
        $organization = $this->organization('Acme');

        $this->actingAs($root)->get(route('member.org.dashboard', $organization))
            ->assertOk()
            ->assertSee('Acesso aos dados')
            ->assertSee('ADM Root');
    }

    public function test_dashboard_accepts_period_filter(): void
    {
        $user = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user, 'admin');

        $this->actingAs($user)->get(route('member.org.dashboard', [
            'organization' => $organization,
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]))->assertOk()->assertSee('2026-01-01')->assertSee('2026-01-31');
    }
}
