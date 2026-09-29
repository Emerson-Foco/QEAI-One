<?php

namespace App\Support;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;

/**
 * Autorização por organização. Um usuário pode pertencer a várias organizações,
 * com grupos de acesso diferentes em cada uma. As permissões são sempre
 * avaliadas em relação à organização ativa (membership daquela organização).
 */
class OrgAccess
{
    public static function membership(User $user, Organization $organization): ?Membership
    {
        return Membership::with('role')
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->first();
    }

    public static function can(User $user, Organization $organization, string $permission): bool
    {
        // O ADM Root da plataforma tem acesso administrativo a qualquer organização.
        if ($user->is_root_admin) {
            return true;
        }

        $membership = self::membership($user, $organization);
        if ($membership === null) {
            return false;
        }

        $permissions = $membership->role?->permissions ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public static function authorize(User $user, Organization $organization, string $permission): void
    {
        if (! self::can($user, $organization, $permission)) {
            abort(403);
        }
    }

    public static function isMember(User $user, Organization $organization): bool
    {
        return $user->is_root_admin || self::membership($user, $organization) !== null;
    }
}
