<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Role;
use App\Support\Audit;
use App\Support\OrgAccess;
use App\Support\OrgPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationRoleController extends Controller
{
    public function store(Request $request, Organization $organization)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = $this->sanitize($data['permissions'] ?? []);

        $role = $organization->roles()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($organization, $data['name']),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        Audit::log('role.created', 'organization', $organization->id, 'role', $role->id, null, ['name' => $role->name, 'permissions' => $permissions]);

        return back()->with('status', 'Grupo criado.');
    }

    public function update(Request $request, Organization $organization, Role $role)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');
        abort_unless($role->organization_id === $organization->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = $this->sanitize($data['permissions'] ?? []);
        $before = ['name' => $role->name, 'permissions' => $role->permissions];
        $role->update(['name' => $data['name'], 'permissions' => $permissions]);

        Audit::log('role.updated', 'organization', $organization->id, 'role', $role->id, $before, ['name' => $role->name, 'permissions' => $permissions]);

        return back()->with('status', 'Grupo atualizado.');
    }

    public function destroy(Organization $organization, Role $role)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');
        abort_unless($role->organization_id === $organization->id, 404);
        if ($role->is_system) {
            return back()->withErrors(['role' => 'Grupos padrão não podem ser excluídos.']);
        }
        if ($organization->memberships()->where('role_id', $role->id)->exists()) {
            return back()->withErrors(['role' => 'Há membros neste grupo. Mova-os antes de excluir.']);
        }

        $roleId = $role->id;
        $name = $role->name;
        $role->delete();

        Audit::log('role.deleted', 'organization', $organization->id, 'role', $roleId, ['name' => $name], null);

        return back()->with('status', 'Grupo excluído.');
    }

    /** @param array<int, string> $permissions */
    private function sanitize(array $permissions): array
    {
        $allowed = array_keys(OrgPermissions::catalog());
        $permissions = array_values(array_intersect($permissions, $allowed));
        return $permissions === $allowed ? ['*'] : $permissions;
    }

    private function uniqueSlug(Organization $organization, string $name): string
    {
        $base = Str::slug($name) ?: 'grupo';
        $slug = $base;
        $suffix = 2;
        while ($organization->roles()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }
}
