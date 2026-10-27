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

class LeadViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => Str::headline(Str::before($email, '@')),
            'email' => $email,
            'password' => 'senha-original-123',
        ]);
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

    private function addMember(Organization $organization, User $user, string $roleSlug = 'admin'): void
    {
        $role = $organization->roles()->where('slug', $roleSlug)->firstOrFail();
        $organization->memberships()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    private function field(Organization $organization, string $key, string $label, string $type, ?array $options = null): void
    {
        $organization->customFields()->create([
            'entity' => 'contact',
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'options' => $options,
            'show_in_list' => true,
            'is_active' => true,
            'position' => 10,
        ]);
    }

    public function test_calendar_shows_leads_by_selected_date_field(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin);
        $this->field($organization, 'data-contato', 'Data de contato', 'date');

        Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead de Outubro',
            'custom' => ['data-contato' => '2026-10-15'],
        ]);
        Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead de Novembro',
            'custom' => ['data-contato' => '2026-11-10'],
        ]);

        $this->actingAs($admin)->get(route('member.org.contacts.index', [
            'organization' => $organization,
            'view' => 'calendar',
            'date_field' => 'data-contato',
            'month' => '2026-10',
        ]))
            ->assertOk()
            ->assertSee('Calendário')
            ->assertSee('Lead de Outubro')
            ->assertDontSee('Lead de Novembro')
            ->assertSee('Data de contato');
    }

    public function test_kanban_groups_by_select_field_and_moves_lead(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin);
        $this->field($organization, 'segmento', 'Segmento', 'select', ['Varejo', 'Serviços']);

        $lead = Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead Varejo',
            'custom' => ['segmento' => 'Varejo'],
        ]);
        Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead Serviços',
            'custom' => ['segmento' => 'Serviços'],
        ]);

        $this->actingAs($admin)->get(route('member.org.contacts.index', [
            'organization' => $organization,
            'view' => 'board',
            'group' => 'segmento',
        ]))
            ->assertOk()
            ->assertSee('Kanban')
            ->assertSee('Lead Varejo')
            ->assertSee('Lead Serviços')
            ->assertSee('Varejo')
            ->assertSee('Serviços');

        $this->actingAs($admin)->post(route('member.org.contacts.field', [$organization, $lead]), [
            'field' => 'segmento',
            'value' => 'Serviços',
        ])->assertRedirect();

        $this->assertSame('Serviços', $lead->fresh()->custom['segmento']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contact.field_updated',
            'entity_id' => $lead->id,
        ]);

        $this->actingAs($admin)->postJson(route('member.org.contacts.field', [$organization, $lead]), [
            'field' => 'segmento',
            'value' => 'Inválido',
        ])->assertStatus(422);
    }

    public function test_timeline_uses_start_and_end_date_fields(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin);
        $this->field($organization, 'inicio', 'Início', 'date');
        $this->field($organization, 'fim', 'Fim', 'date');

        Contact::create([
            'organization_id' => $organization->id,
            'name' => 'Lead no Cronograma',
            'custom' => ['inicio' => '2026-10-05', 'fim' => '2026-11-20'],
        ]);

        $this->actingAs($admin)->get(route('member.org.contacts.index', [
            'organization' => $organization,
            'view' => 'timeline',
            'start_field' => 'inicio',
            'end_field' => 'fim',
            'month' => '2026-10',
        ]))
            ->assertOk()
            ->assertSee('Cronograma')
            ->assertSee('Lead no Cronograma')
            ->assertSee('05/10/2026')
            ->assertSee('20/11/2026');
    }

    public function test_table_view_filters_by_custom_select_field(): void
    {
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin);
        $this->field($organization, 'segmento', 'Segmento', 'select', ['Varejo', 'Serviços']);

        Contact::create(['organization_id' => $organization->id, 'name' => 'Cliente Varejo', 'custom' => ['segmento' => 'Varejo']]);
        Contact::create(['organization_id' => $organization->id, 'name' => 'Cliente Serviços', 'custom' => ['segmento' => 'Serviços']]);

        $this->actingAs($admin)->get(route('member.org.contacts.index', [
            'organization' => $organization,
            'view' => 'table',
            'filters' => ['segmento' => 'Varejo'],
        ]))
            ->assertOk()
            ->assertSee('Cliente Varejo')
            ->assertDontSee('Cliente Serviços')
            ->assertSee('Resultado dos filtros');
    }

    public function test_field_move_is_scoped_to_organization(): void
    {
        $user = $this->user('multi@empresa.com');
        $orgA = $this->organization('Empresa A');
        $orgB = $this->organization('Empresa B');
        $this->addMember($orgA, $user, 'owner');
        $this->addMember($orgB, $user, 'admin');
        $this->field($orgA, 'segmento', 'Segmento', 'select', ['Varejo', 'Serviços']);

        $leadA = Contact::create(['organization_id' => $orgA->id, 'name' => 'Lead A', 'custom' => ['segmento' => 'Varejo']]);

        $this->actingAs($user)->postJson(route('member.org.contacts.field', [$orgB, $leadA]), [
            'field' => 'segmento',
            'value' => 'Serviços',
        ])->assertNotFound();

        $this->assertSame('Varejo', $leadA->fresh()->custom['segmento']);
    }
}
