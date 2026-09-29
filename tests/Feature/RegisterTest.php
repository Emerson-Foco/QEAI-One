<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
    }

    public function test_registration_page_loads(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Crie sua organização');
    }

    public function test_new_company_can_register(): void
    {
        $response = $this->post(route('register'), [
            'company_name' => 'Minha Empresa',
            'document' => '60.357.082/0001-58',
            'name' => 'Fulano',
            'email' => 'fulano@empresa.com',
            'password' => 'senha-forte-123',
            'password_confirmation' => 'senha-forte-123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'fulano@empresa.com')->firstOrFail();
        $organization = Organization::where('name', 'Minha Empresa')->firstOrFail();
        $this->assertSame($user->id, $organization->owner_user_id);
        $this->assertDatabaseHas('memberships', ['organization_id' => $organization->id, 'user_id' => $user->id, 'status' => 'active']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.created', 'scope' => 'platform']);
        $this->assertTrue($organization->roles()->where('slug', 'owner')->exists());
    }

    public function test_terms_are_required(): void
    {
        $this->post(route('register'), [
            'company_name' => 'Empresa X',
            'name' => 'Fulano',
            'email' => 'sem-termos@empresa.com',
            'password' => 'senha-forte-123',
            'password_confirmation' => 'senha-forte-123',
        ])->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'sem-termos@empresa.com']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::create(['name' => 'Existente', 'email' => 'existe@empresa.com', 'password' => 'senha-forte-123']);

        $this->post(route('register'), [
            'company_name' => 'Outra',
            'name' => 'Outro',
            'email' => 'existe@empresa.com',
            'password' => 'senha-forte-123',
            'password_confirmation' => 'senha-forte-123',
            'terms' => '1',
        ])->assertSessionHasErrors('email');
    }
}
