<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InviteTest extends TestCase
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

    private function organization(User $root): Organization
    {
        $this->actingAs($root)->post(route('panel.organizations.store'), ['name' => 'Acme']);

        return Organization::latest('id')->firstOrFail();
    }

    private function inviteToken(User $root, Organization $organization): string
    {
        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();
        $this->actingAs($root)->post(route('panel.invites.store', $organization), [
            'email' => 'convidado@empresa.com',
            'role_id' => $role->id,
            'consent' => '1',
        ])->assertRedirect();

        $link = (string) session('invite_link');

        return basename((string) parse_url($link, PHP_URL_PATH));
    }

    public function test_invite_requires_consent(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);
        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();

        $this->actingAs($root)->post(route('panel.invites.store', $organization), [
            'email' => 'sem-consentimento@empresa.com',
            'role_id' => $role->id,
        ])->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('organization_invites', 0);
    }

    public function test_invite_is_created_with_consent_recorded(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);

        $token = $this->inviteToken($root, $organization);

        $this->assertNotSame('', $token);
        $invite = OrganizationInvite::firstOrFail();
        $this->assertTrue($invite->consent_confirmed);
        $this->assertSame($root->id, $invite->consent_by);
        $this->assertNotNull($invite->consent_at);
        $this->assertSame('pending', $invite->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invite.created', 'scope' => 'organization']);
    }

    public function test_invite_has_rate_limit(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);
        $role = $organization->roles()->where('slug', 'agent')->firstOrFail();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($root)->post(route('panel.invites.store', $organization), [
                'email' => "pessoa{$i}@empresa.com",
                'role_id' => $role->id,
                'consent' => '1',
            ]);
        }

        $this->actingAs($root)->post(route('panel.invites.store', $organization), [
            'email' => 'excedente@empresa.com',
            'role_id' => $role->id,
            'consent' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('organization_invites', ['email' => 'excedente@empresa.com']);
    }

    public function test_invited_person_can_accept_and_gets_membership(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);
        $token = $this->inviteToken($root, $organization);

        $this->get(route('invite.show', $token))->assertOk()->assertSee('Participe de Acme');

        $response = $this->post('/convite/' . $token, [
            'name' => 'Pessoa Convidada',
            'password' => 'senha-do-convidado-123',
            'password_confirmation' => 'senha-do-convidado-123',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'convidado@empresa.com')->firstOrFail();
        $this->assertDatabaseHas('memberships', ['organization_id' => $organization->id, 'user_id' => $user->id, 'status' => 'active']);
        $this->assertSame('accepted', OrganizationInvite::firstOrFail()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invite.accepted']);

        // Usuário (não root) acessa a área do membro.
        $this->get(route('member.dashboard'))->assertOk()->assertSee('Acme');
    }

    public function test_recipient_can_report_unknown_invite(): void
    {
        $root = $this->root();
        $organization = $this->organization($root);
        $token = $this->inviteToken($root, $organization);

        $this->get(route('invite.report.form', $token))->assertOk()->assertSee('Não reconhece');

        $this->post(route('invite.report', $token), ['note' => 'não conheço esta empresa'])->assertOk()->assertSee('Registramos');

        $invite = OrganizationInvite::firstOrFail();
        $this->assertSame('reported', $invite->status);
        $this->assertNotNull($invite->reported_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invite.reported', 'scope' => 'platform']);

        // Convite denunciado não pode mais ser aceito.
        $this->get(route('invite.show', $token))->assertOk()->assertSee('não está mais válido');
    }

    public function test_user_without_membership_cannot_access_member_area(): void
    {
        $user = new User(['name' => 'Sem vinculo', 'email' => 'sem@test.local', 'password' => Hash::make('password-123')]);
        $user->save();

        $this->actingAs($user)->get(route('member.dashboard'))->assertForbidden();
    }
}
