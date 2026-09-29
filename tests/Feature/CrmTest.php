<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmTest extends TestCase
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

    private function addMember(Organization $organization, User $user, string $roleSlug): void
    {
        $role = $organization->roles()->where('slug', $roleSlug)->firstOrFail();
        $organization->memberships()->create([
            'user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active', 'joined_at' => now(),
        ]);
    }

    public function test_member_with_leads_can_create_and_list_contacts(): void
    {
        $user = $this->user('vendedor@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user, 'agent');

        $this->actingAs($user)->get(route('member.org.contacts.index', $organization))->assertOk()->assertSee('Novo contato');

        $this->actingAs($user)->post(route('member.org.contacts.store', $organization), [
            'name' => 'Maria Souza',
            'email' => 'maria@cliente.com',
            'phone' => '11999998888',
            'tags' => 'cliente vip, indicação',
        ])->assertRedirect();

        $contact = Contact::firstOrFail();
        $this->assertSame('Maria Souza', $contact->name);
        $this->assertSame($organization->id, $contact->organization_id);
        $this->assertEqualsCanonicalizing(['cliente-vip', 'indicacao'], $contact->tags()->pluck('slug')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'contact.created', 'scope' => 'organization']);
    }

    public function test_contacts_are_scoped_to_organization(): void
    {
        $user = $this->user('vendedor@empresa.com');
        $orgA = $this->organization('Empresa A');
        $orgB = $this->organization('Empresa B');
        $this->addMember($orgA, $user, 'agent');
        $this->addMember($orgB, $user, 'agent');

        $contact = Contact::create(['organization_id' => $orgA->id, 'name' => 'Cliente A']);

        // Não pode editar um contato da empresa A usando a rota da empresa B.
        $this->actingAs($user)->get(route('member.org.contacts.edit', [$orgB, $contact]))->assertNotFound();
        $this->actingAs($user)->delete(route('member.org.contacts.destroy', [$orgB, $contact]))->assertNotFound();
    }

    public function test_member_without_leads_permission_is_forbidden(): void
    {
        $user = $this->user('semleads@empresa.com');
        $organization = $this->organization('Acme');
        $role = $organization->roles()->create(['name' => 'Sem Leads', 'slug' => 'sem-leads', 'permissions' => ['org.reports'], 'is_system' => false]);
        $organization->memberships()->create(['user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($user)->get(route('member.org.contacts.index', $organization))->assertForbidden();
    }

    public function test_root_without_membership_cannot_access_crm_data(): void
    {
        $root = $this->root();
        $organization = $this->organization('Acme');

        $this->actingAs($root)->get(route('member.org.contacts.index', $organization))->assertForbidden();
    }

    public function test_company_creation_and_contact_link(): void
    {
        $user = $this->user('vendedor@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $user, 'admin');

        $this->actingAs($user)->post(route('member.org.companies.store', $organization), ['name' => 'Cliente Ltda'])
            ->assertRedirect();
        $company = $organization->companies()->firstOrFail();

        $this->actingAs($user)->post(route('member.org.contacts.store', $organization), [
            'name' => 'João', 'company_id' => $company->id,
        ])->assertRedirect();

        $this->assertSame($company->id, Contact::firstOrFail()->company_id);
    }
}
