<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\OrgPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::query()
            ->withCount('memberships')
            ->orderBy('name')
            ->get();

        return view('organizations.index', ['organizations' => $organizations]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:24'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $organization = DB::transaction(function () use ($data) {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'document' => $data['document'] ?? null,
                'country' => strtoupper($data['country'] ?? 'BR'),
                'status' => 'active',
                'owner_user_id' => auth()->id(),
                'plan_id' => \App\Models\Plan::where('slug', 'free')->value('id'),
            ]);

            foreach (OrgPermissions::defaults() as $role) {
                $organization->roles()->create([
                    'name' => $role['name'],
                    'slug' => $role['slug'],
                    'permissions' => $role['permissions'],
                    'is_system' => $role['is_system'],
                ]);
            }

            \App\Models\Pipeline::ensureDefaultFor($organization);

            $template = \App\Models\FieldTemplate::defaultTemplate();
            if ($template !== null) {
                \App\Support\CustomFields::applyTemplate($organization, $template->fields ?? []);
            } else {
                \App\Support\CustomFields::ensureDefaults($organization, 'contact');
            }

            return $organization;
        });

        Audit::log('organization.created', 'platform', null, 'organization', $organization->id, null, ['name' => $organization->name]);

        return redirect()->route('panel.organizations.show', $organization)->with('status', 'Organização criada.');
    }

    public function show(Organization $organization)
    {
        $organization->load(['memberships.user', 'memberships.role', 'roles', 'plan.features']);
        $roles = $organization->roles()->orderByDesc('is_system')->orderBy('name')->get();

        return view('organizations.show', [
            'organization' => $organization,
            'memberships' => $organization->memberships->sortBy(fn ($m) => $m->user?->name),
            'roles' => $roles,
            'permissions' => OrgPermissions::catalog(),
            'plans' => \App\Models\Plan::orderBy('sort')->orderBy('price_cents')->get(),
            'planCatalog' => \App\Support\PlanFeatures::catalog(),
            'templates' => \App\Models\FieldTemplate::orderBy('name')->get(),
            'features' => \App\Support\PlanResolver::summary($organization),
            'invites' => $organization->invites()->with('role')->latest('id')->limit(50)->get(),
            'audit' => \App\Models\AuditLog::query()
                ->where('scope', 'organization')
                ->where('organization_id', $organization->id)
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function logs(Request $request, Organization $organization)
    {
        \App\Support\OrgAccess::authorize(auth()->user(), $organization, 'org.logs');

        $query = AuditLog::query()->where('scope', 'organization')->where('organization_id', $organization->id);

        if ($action = $request->input('action')) {
            $query->where('action', 'like', '%' . $action . '%');
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($request->input('export') === 'csv') {
            return $this->csvLogs($query, 'logs-' . $organization->slug . '.csv');
        }

        return view('logs.organization', [
            'organization' => $organization,
            'logs' => $query->latest('id')->paginate(50)->withQueryString(),
            'filters' => $request->only(['action', 'from', 'to']),
        ]);
    }

    private function csvLogs($query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'actor_type', 'actor_user_id', 'action', 'entity_type', 'entity_id', 'ip', 'created_at', 'hash_self']);
            foreach ($query->latest('id')->cursor() as $log) {
                fputcsv($out, [
                    $log->id, $log->actor_type, $log->actor_user_id, $log->action, $log->entity_type,
                    $log->entity_id, $log->ip, optional($log->created_at)->toDateTimeString(), $log->hash_self,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function updatePlan(Request $request, Organization $organization)
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id']]);

        $before = ['plan_id' => $organization->plan_id];
        $organization->update(['plan_id' => $data['plan_id']]);

        Audit::log('organization.plan_changed', 'platform', null, 'organization', $organization->id, $before, ['plan_id' => $data['plan_id']]);

        return back()->with('status', 'Plano da organização atualizado.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'org';
        $slug = $base;
        $suffix = 2;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }
}
