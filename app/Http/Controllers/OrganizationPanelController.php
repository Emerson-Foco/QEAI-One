<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Support\OrgAccess;
use App\Support\OrgPermissions;

class OrganizationPanelController extends Controller
{
    public function show(Organization $organization)
    {
        $user = auth()->user();
        $organization->load(['memberships.user', 'memberships.role', 'roles', 'plan']);

        $canMembers = OrgAccess::can($user, $organization, 'org.members');
        $canLogs = OrgAccess::can($user, $organization, 'org.logs');
        $canSettings = OrgAccess::can($user, $organization, 'org.settings');

        return view('member.organization', [
            'organization' => $organization,
            'membership' => OrgAccess::membership($user, $organization),
            'memberships' => $organization->memberships->sortBy(fn ($m) => $m->user?->name),
            'roles' => $organization->roles()->orderByDesc('is_system')->orderBy('name')->get(),
            'permissions' => OrgPermissions::catalog(),
            'canMembers' => $canMembers,
            'canLogs' => $canLogs,
            'canSettings' => $canSettings,
            'features' => \App\Support\PlanResolver::summary($organization),
            'invites' => $canMembers ? $organization->invites()->with('role')->latest('id')->limit(50)->get() : collect(),
            'audit' => $canLogs
                ? AuditLog::where('scope', 'organization')->where('organization_id', $organization->id)->latest('id')->limit(20)->get()
                : collect(),
        ]);
    }
}
