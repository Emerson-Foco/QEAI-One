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

class CustomFieldTest extends TestCase
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
        $organization->memberships()->create([
            'user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active', 'joined_at' => now(),
        ]);
    }

    public function test_default_custom_fields_are_seeded(): void
    {
        $organization = $this->organization('Acme');
        $keys = CustomFields::forEntity($organization, 'contact')->pluck('key')->all();

        $this->assertEqualsCanonicalizing(['status', 'temperatura'], $keys);
    }

    public function test_admin_creates_custom_field_and_contact_stores_values(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->post(route('member.org.fields.store', $organization), [
            'label' => 'Segmento', 'type' => 'select', 'options_text' => 'Varejo, Serviços', 'show_in_list' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('custom_fields', ['organization_id' => $organization->id, 'key' => 'segmento', 'type' => 'select']);

        $this->actingAs($admin)->post(route('member.org.contacts.store', $organization), [
            'name' => 'Maria',
            'custom' => ['status' => 'Qualificado', 'temperatura' => 'Quente', 'segmento' => 'Varejo'],
        ])->assertRedirect();

        $contact = Contact::firstOrFail();
        $this->assertSame('Quente', $contact->custom['temperatura']);
        $this->assertSame('Qualificado', $contact->custom['status']);
        $this->assertSame('Varejo', $contact->custom['segmento']);
    }

    public function test_agent_cannot_manage_fields_but_can_save_contact(): void
    {
        $agent = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $agent, 'agent');

        $this->actingAs($agent)->get(route('member.org.fields.index', $organization))->assertForbidden();

        $this->actingAs($agent)->post(route('member.org.contacts.store', $organization), [
            'name' => 'João', 'custom' => ['status' => 'Novo', 'temperatura' => 'Frio'],
        ])->assertRedirect();

        $this->assertSame('Frio', Contact::firstOrFail()->custom['temperatura']);
    }

    public function test_board_view_groups_by_temperature(): void
    {
        $agent = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $agent, 'agent');

        Contact::create(['organization_id' => $organization->id, 'name' => 'Quente Um', 'custom' => ['temperatura' => 'Quente']]);
        Contact::create(['organization_id' => $organization->id, 'name' => 'Frio Um', 'custom' => ['temperatura' => 'Frio']]);

        $this->actingAs($agent)
            ->get(route('member.org.contacts.index', [$organization, 'view' => 'board', 'group' => 'temperatura']))
            ->assertOk()
            ->assertSee('Quente')
            ->assertSee('Frio')
            ->assertSee('Quente Um')
            ->assertSee('Frio Um');
    }
}
