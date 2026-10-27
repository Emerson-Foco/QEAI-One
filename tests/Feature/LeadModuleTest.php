<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Contact;
use App\Models\CustomField;
use App\Models\Organization;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\LeadIntake;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeadModuleTest extends TestCase
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

    private function createField(Organization $organization, string $label = 'Segmento', string $type = 'text'): CustomField
    {
        $this->actingAs(auth()->user())->post(route('member.org.fields.store', $organization), [
            'label' => $label,
            'type' => $type,
        ])->assertRedirect();

        return CustomField::where('organization_id', $organization->id)->where('label', $label)->firstOrFail();
    }

    public function test_lead_has_permanent_public_id_and_history(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->post(route('member.org.contacts.store', $organization), [
            'name' => 'Mariana Costa',
            'email' => 'mariana@cliente.com',
            'custom' => ['temperatura' => 'Quente'],
        ])->assertRedirect();

        $lead = Contact::firstOrFail();
        $this->assertNotEmpty($lead->lead_id);
        $this->assertStringStartsWith('LD-', $lead->lead_id);

        $this->actingAs($admin)->put(route('member.org.contacts.update', [$organization, $lead]), [
            'name' => 'Mariana Costa Silva',
            'email' => 'mariana@cliente.com',
        ])->assertRedirect(route('member.org.contacts.show', [$organization, $lead]));

        $this->actingAs($admin)->get(route('member.org.contacts.show', [$organization, $lead]))
            ->assertOk()
            ->assertSee($lead->lead_id)
            ->assertSee('Histórico do lead')
            ->assertSee('Lead atualizado');

        $second = Contact::create(['organization_id' => $organization->id, 'name' => 'Outro lead']);
        $this->assertNotSame($lead->lead_id, $second->lead_id);
    }

    public function test_field_has_stable_public_id_and_can_be_deactivated_without_losing_data(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin);
        $field = $this->createField($organization, 'Segmento');
        $this->assertStringStartsWith('FLD-', $field->field_id);
        $originalId = $field->field_id;

        $this->actingAs($admin)->put(route('member.org.fields.update', [$organization, $field]), [
            'label' => 'Segmento de atuação',
            'type' => 'text',
        ])->assertRedirect();

        $field->refresh();
        $this->assertSame($originalId, $field->field_id);
        $this->assertSame('Segmento de atuação', $field->label);

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead com segmento',
            'custom' => ['segmento' => 'Varejo'],
        ]);

        $this->actingAs($admin)->post(route('member.org.fields.toggle', [$organization, $field]))->assertRedirect();
        $this->assertFalse($field->fresh()->is_active);
        $this->assertSame('Varejo', $contact->fresh()->custom['segmento']);

        $this->actingAs($admin)->get(route('member.org.contacts.create', $organization))
            ->assertOk()
            ->assertDontSee('Segmento de atuação');

        $this->actingAs($admin)->get(route('member.org.contacts.show', [$organization, $contact]))
            ->assertOk()
            ->assertSee('Segmento de atuação')
            ->assertSee('desativado');

        $this->actingAs($admin)->put(route('member.org.contacts.update', [$organization, $contact]), [
            'name' => 'Lead com segmento atualizado',
            'custom' => ['status' => 'Novo', 'temperatura' => 'Morno'],
        ])->assertRedirect();

        $this->assertSame('Varejo', $contact->fresh()->custom['segmento']);

        $this->actingAs($admin)->post(route('member.org.fields.toggle', [$organization, $field]))->assertRedirect();
        $this->assertTrue($field->fresh()->is_active);
    }

    public function test_type_change_requires_explicit_confirmation_when_field_has_data(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin);
        $field = $this->createField($organization, 'Orçamento', 'text');
        Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead com orçamento',
            'custom' => ['orcamento' => '10 mil'],
        ]);

        $this->actingAs($admin)->put(route('member.org.fields.update', [$organization, $field]), [
            'label' => 'Orçamento',
            'type' => 'number',
        ])->assertSessionHasErrors('type');

        $this->assertSame('text', $field->fresh()->type);

        $this->actingAs($admin)->put(route('member.org.fields.update', [$organization, $field]), [
            'label' => 'Orçamento',
            'type' => 'number',
            'confirm_type_change' => '1',
        ])->assertRedirect();

        $this->assertSame('number', $field->fresh()->type);
        $this->assertSame('10 mil', Contact::firstOrFail()->custom['orcamento']);
    }

    public function test_agent_cannot_manage_fields_but_can_create_leads(): void
    {
        $agent = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $agent, 'agent');

        $this->actingAs($agent)->get(route('member.org.fields.index', $organization))->assertForbidden();

        $this->actingAs($agent)->post(route('member.org.contacts.store', $organization), [
            'name' => 'Lead do agente',
        ])->assertRedirect();

        $this->assertDatabaseHas('contacts', ['organization_id' => $organization->id, 'name' => 'Lead do agente']);
    }

    public function test_fields_and_leads_are_isolated_between_organizations(): void
    {
        $user = $this->user('multi@empresa.com');
        $orgA = $this->organization('Empresa A');
        $orgB = $this->organization('Empresa B');
        $this->addMember($orgA, $user, 'owner');
        $this->addMember($orgB, $user, 'admin');

        $this->actingAs($user);
        $fieldA = $this->createField($orgA, 'Segmento A');
        $leadA = Contact::create(['organization_id' => $orgA->id, 'name' => 'Lead A']);

        $this->actingAs($user)->get(route('member.org.fields.index', $orgB))
            ->assertOk()
            ->assertDontSee('Segmento A');

        $this->actingAs($user)->get(route('member.org.contacts.show', [$orgB, $leadA]))->assertNotFound();
        $this->actingAs($user)->put(route('member.org.fields.update', [$orgB, $fieldA]), [
            'label' => 'Tentativa', 'type' => 'text',
        ])->assertNotFound();

        $this->assertSame($orgA->id, $leadA->fresh()->organization_id);
        $this->assertSame('Segmento A', $fieldA->fresh()->label);
    }

    public function test_lead_search_by_public_id(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $lead = Contact::create(['organization_id' => $organization->id, 'name' => 'Buscável']);

        $this->actingAs($admin)->get(route('member.org.contacts.index', [$organization, 'q' => $lead->lead_id]))
            ->assertOk()
            ->assertSee('Buscável')
            ->assertSee($lead->lead_id);
    }

    public function test_lead_can_be_created_with_only_id_and_timestamps(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->post(route('member.org.contacts.store', $organization), [])->assertRedirect();

        $lead = Contact::firstOrFail();
        $this->assertStringStartsWith('LD-', $lead->lead_id);
        $this->assertNotNull($lead->created_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contact.created', 'entity_id' => $lead->id]);
        $this->assertSame($lead->lead_id, $lead->displayName());
    }

    public function test_lead_form_uses_only_custom_fields(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->get(route('member.org.contacts.create', $organization))
            ->assertOk()
            ->assertSee('custom[nome]', false)
            ->assertSee('custom[data_inicial]', false)
            ->assertSee('custom[proximo_contato]', false)
            ->assertDontSee('name="name"', false)
            ->assertDontSee('name="phone"', false)
            ->assertDontSee('name="source"', false)
            ->assertDontSee('name="notes"', false);
    }

    public function test_default_lead_fields_are_not_required_and_can_be_deactivated(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $nameField = CustomField::where('organization_id', $organization->id)->where('key', 'nome')->firstOrFail();
        $this->assertFalse($nameField->required);
        $this->assertTrue($nameField->is_active);

        $this->actingAs($admin)->get(route('member.org.fields.index', $organization))
            ->assertOk()
            ->assertSee('Nome')
            ->assertSee('Data inicial')
            ->assertSee('Próximo contato');

        $this->actingAs($admin)->post(route('member.org.fields.toggle', [$organization, $nameField]))->assertRedirect();
        $this->assertFalse($nameField->fresh()->is_active);

        $this->actingAs($admin)->get(route('member.org.contacts.create', $organization))
            ->assertOk()
            ->assertDontSee('custom[nome]', false);
    }

    public function test_legacy_payload_maps_to_custom_default_fields(): void
    {
        $organization = $this->organization('Acme');

        $lead = LeadIntake::create($organization, [
            'name' => 'Lead Legado',
            'email' => 'legado@exemplo.com',
            'phone' => '11999999999',
            'source' => 'Indicação',
            'notes' => 'Observação antiga',
        ], 'api');

        $this->assertSame('Lead Legado', $lead->custom['nome']);
        $this->assertSame('legado@exemplo.com', $lead->custom['email']);
        $this->assertSame('11999999999', $lead->custom['telefone']);
        $this->assertSame('Indicação', $lead->custom['origem']);
        $this->assertSame('Observação antiga', $lead->custom['observacoes']);
        $this->assertSame('Lead Legado', $lead->displayName());
    }

    public function test_root_without_membership_cannot_manage_crm_fields(): void
    {
        $root = $this->user('root@qeai.com', true);
        $organization = $this->organization('Acme');

        $this->actingAs($root)->get(route('member.org.fields.index', $organization))->assertForbidden();
    }

    public function test_crm_field_permission_does_not_grant_access_to_lead_data(): void
    {
        $user = $this->user('config@empresa.com');
        $organization = $this->organization('Acme');
        $role = $organization->roles()->create([
            'name' => 'Configura CRM',
            'slug' => 'crm_config',
            'permissions' => ['org.crm_fields'],
            'is_system' => false,
        ]);
        $organization->memberships()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($user)->get(route('member.org.fields.index', $organization))->assertOk();
        $this->actingAs($user)->get(route('member.org.contacts.index', $organization))->assertForbidden();
        $this->actingAs($user)->post(route('member.org.contacts.store', $organization), ['name' => 'Lead indevido'])->assertForbidden();
    }

    public function test_disabled_modules_are_not_accessible_even_by_direct_url(): void
    {
        config(['modules.enabled' => ['dashboard', 'leads', 'crm_fields', 'settings', 'logs']]);

        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->get(route('member.org.pipeline.index', $organization))->assertNotFound();
        $this->actingAs($admin)->get(route('member.org.tasks.index', $organization))->assertNotFound();
        $this->actingAs($admin)->get(route('member.org.contacts.index', $organization))->assertOk();
    }
}
