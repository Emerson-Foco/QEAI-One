<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\FieldTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FieldTemplateTest extends TestCase
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

    private function makeTemplate(User $root, string $name, array $fields): FieldTemplate
    {
        $this->actingAs($root)->post(route('panel.templates.store'), ['name' => $name, 'fields' => $fields])->assertRedirect();

        return FieldTemplate::where('slug', Str::slug($name))->firstOrFail();
    }

    private function rawOrganization(string $name): Organization
    {
        $organization = Organization::create(['name' => $name, 'slug' => Str::slug($name), 'status' => 'active']);
        foreach (OrgPermissions::defaults() as $role) {
            $organization->roles()->create($role);
        }

        return $organization;
    }

    public function test_root_creates_template_with_fields(): void
    {
        $root = $this->root();
        $template = $this->makeTemplate($root, 'Imobiliária', [
            ['label' => 'Tipo de imóvel', 'type' => 'select', 'options' => 'Casa, Apartamento', 'show_in_list' => '1'],
            ['label' => 'Orçamento', 'type' => 'number'],
        ]);

        $this->assertCount(2, $template->fields);
        $this->assertSame(['Casa', 'Apartamento'], $template->fields[0]['options']);
        $this->assertTrue($template->fields[0]['show_in_list']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'field_template.created']);
    }

    public function test_default_template_is_applied_to_new_organizations(): void
    {
        $root = $this->root();
        $template = $this->makeTemplate($root, 'Clínica', [
            ['label' => 'Convênio', 'type' => 'select', 'options' => 'Particular, Unimed'],
        ]);
        $this->actingAs($root)->post(route('panel.templates.default', $template))->assertRedirect();

        $this->actingAs($root)->post(route('panel.organizations.store'), ['name' => 'Nova Clínica'])->assertRedirect();
        $organization = Organization::where('name', 'Nova Clínica')->firstOrFail();

        $labels = $organization->customFields()->pluck('label')->all();
        $this->assertContains('Convênio', $labels);
        // O template substitui os defaults hardcoded.
        $this->assertNotContains('Status', $labels);
    }

    public function test_without_default_template_org_gets_builtin_defaults(): void
    {
        $root = $this->root();
        $this->actingAs($root)->post(route('panel.organizations.store'), ['name' => 'Sem Template'])->assertRedirect();
        $organization = Organization::where('name', 'Sem Template')->firstOrFail();

        $labels = $organization->customFields()->pluck('label')->all();
        $this->assertContains('Status', $labels);
        $this->assertContains('Temperatura', $labels);
    }

    public function test_root_applies_template_to_existing_organization(): void
    {
        $root = $this->root();
        $organization = $this->rawOrganization('Existente');
        CustomFields::ensureDefaults($organization, 'contact');

        $template = $this->makeTemplate($root, 'E-commerce', [
            ['label' => 'Pedido médio', 'type' => 'number'],
        ]);

        $this->actingAs($root)->post(route('panel.organizations.template', $organization), ['template_id' => $template->id])->assertRedirect();

        $labels = $organization->customFields()->pluck('label')->all();
        $this->assertContains('Pedido médio', $labels);
        $this->assertContains('Status', $labels);
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.template_applied']);
    }

    public function test_non_root_cannot_manage_templates(): void
    {
        $user = User::create(['name' => 'Comum', 'email' => 'comum@test.local', 'password' => Hash::make('password-123')]);

        $this->actingAs($user)->get(route('panel.templates.index'))->assertForbidden();
    }
}
