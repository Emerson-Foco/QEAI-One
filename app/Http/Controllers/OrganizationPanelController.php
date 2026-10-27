<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\OrgAccess;
use App\Support\OrgPermissions;
use App\Support\PlanResolver;

class OrganizationPanelController extends Controller
{
    public function show(Organization $organization)
    {
        $user = auth()->user();
        $organization->load(['memberships.user', 'memberships.role', 'roles', 'plan']);

        $canMembers = OrgAccess::can($user, $organization, 'org.members');
        $canLogs = OrgAccess::can($user, $organization, 'org.logs');
        $canSettings = OrgAccess::can($user, $organization, 'org.settings');
        $isRootWithoutMembership = $user->is_root_admin && OrgAccess::membership($user, $organization) === null;

        $auditQuery = AuditLog::query()
            ->where('scope', 'organization')
            ->where('organization_id', $organization->id);
        if ($isRootWithoutMembership) {
            $auditQuery = Audit::onlyAdministrative($auditQuery);
        }

        $onboarding = [];
        if (OrgAccess::canData($user, $organization, 'org.leads')) {
            $onboarding[] = ['label' => 'Cadastrar o primeiro lead', 'done' => $organization->contacts()->exists(), 'url' => route('member.org.contacts.create', $organization)];
        }
        if (OrgAccess::canData($user, $organization, 'org.crm_fields')) {
            $onboarding[] = ['label' => 'Configurar os campos do CRM', 'done' => $organization->customFields()->where('entity', 'contact')->where('is_system', false)->exists(), 'url' => route('member.org.fields.index', $organization)];
        }
        if ($canMembers) {
            $onboarding[] = ['label' => 'Convidar a equipe', 'done' => $organization->memberships()->where('status', 'active')->count() > 1, 'url' => route('member.org.show', $organization)];
        }

        return view('member.organization', [
            'organization' => $organization,
            'membership' => OrgAccess::membership($user, $organization),
            'memberships' => $organization->memberships->sortBy(fn ($m) => $m->user?->name),
            'roles' => $organization->roles()->orderByDesc('is_system')->orderBy('name')->get(),
            'permissions' => OrgPermissions::catalog(),
            'canMembers' => $canMembers,
            'canLogs' => $canLogs,
            'canSettings' => $canSettings,
            'features' => PlanResolver::summary($organization),
            'onboarding' => $onboarding,
            'onboardingPending' => collect($onboarding)->contains(fn ($s) => ! $s['done']),
            'invites' => $canMembers ? $organization->invites()->with('role')->latest('id')->limit(50)->get() : collect(),
            'audit' => $canLogs
                ? $auditQuery->latest('id')->limit(20)->get()
                : collect(),
        ]);
    }
}
