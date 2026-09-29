<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Support\Audit;
use App\Support\OrgAccess;
use App\Support\PlanResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganizationMemberController extends Controller
{
    public function store(Request $request, Organization $organization)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'role_id' => ['required', 'integer'],
        ]);

        $role = $organization->roles()->findOrFail($data['role_id']);

        // Limite de usuários do plano/override: só bloqueia se for um novo membro.
        $email = mb_strtolower($data['email']);
        $alreadyMember = $organization->memberships()->whereHas('user', fn ($q) => $q->where('email', $email))->exists();
        if (! $alreadyMember && ! PlanResolver::withinLimit($organization, 'users', 1)) {
            $limit = PlanResolver::limit($organization, 'users');
            $usage = PlanResolver::usage($organization, 'users');

            return back()->withErrors(['email' => "Limite de usuários do plano atingido ({$usage}/{$limit}). Ajuste o plano ou os limites da organização."]);
        }

        $user = User::firstOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['name' => Str::headline(Str::before($data['email'], '@')), 'password' => Hash::make(Str::random(40))]
        );

        $membership = $organization->memberships()->firstOrCreate(
            ['user_id' => $user->id],
            ['role_id' => $role->id, 'status' => 'active', 'invited_by' => auth()->id(), 'joined_at' => now()]
        );

        if (! $membership->wasRecentlyCreated) {
            $membership->update(['role_id' => $role->id, 'status' => 'active']);
        }

        Audit::log('member.added', 'organization', $organization->id, 'membership', $membership->id, null, [
            'email' => $user->email, 'role' => $role->name,
        ]);

        return back()->with('status', 'Membro adicionado.');
    }

    public function update(Request $request, Organization $organization, Membership $membership)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');
        abort_unless($membership->organization_id === $organization->id, 404);

        $data = $request->validate([
            'role_id' => ['required', 'integer'],
            'status' => ['required', 'in:active,disabled'],
        ]);

        $role = $organization->roles()->findOrFail($data['role_id']);
        $before = ['role_id' => $membership->role_id, 'status' => $membership->status];
        $membership->update(['role_id' => $role->id, 'status' => $data['status']]);

        Audit::log('member.updated', 'organization', $organization->id, 'membership', $membership->id, $before, [
            'role_id' => $role->id, 'status' => $data['status'],
        ]);

        return back()->with('status', 'Membro atualizado.');
    }

    public function destroy(Organization $organization, Membership $membership)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');
        abort_unless($membership->organization_id === $organization->id, 404);

        $email = $membership->user?->email;
        $membershipId = $membership->id;
        $membership->delete();

        Audit::log('member.removed', 'organization', $organization->id, 'membership', $membershipId, ['email' => $email], null);

        return back()->with('status', 'Membro removido.');
    }
}
