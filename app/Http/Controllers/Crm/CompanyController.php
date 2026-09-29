<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\OrgAccess;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.leads');
    }

    private function ensure(Organization $organization, Company $company): void
    {
        abort_unless($company->organization_id === $organization->id, 404);
    }

    public function index(Request $request, Organization $organization)
    {
        $this->authorize($organization);

        $query = Company::withCount('contacts')->where('organization_id', $organization->id);
        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('member.crm.companies', [
            'organization' => $organization,
            'companies' => $query->orderBy('name')->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $this->validated($request);

        $company = Company::create($data + ['organization_id' => $organization->id, 'created_by' => auth()->id()]);
        Audit::log('company.created', 'organization', $organization->id, 'company', $company->id, null, ['name' => $company->name]);

        return back()->with('status', 'Empresa criada.');
    }

    public function edit(Organization $organization, Company $company)
    {
        $this->authorize($organization);
        $this->ensure($organization, $company);

        return view('member.crm.company-edit', ['organization' => $organization, 'company' => $company]);
    }

    public function update(Request $request, Organization $organization, Company $company)
    {
        $this->authorize($organization);
        $this->ensure($organization, $company);
        $company->update($this->validated($request));

        Audit::log('company.updated', 'organization', $organization->id, 'company', $company->id, null, ['name' => $company->name]);

        return redirect()->route('member.org.companies.index', $organization)->with('status', 'Empresa atualizada.');
    }

    public function destroy(Organization $organization, Company $company)
    {
        $this->authorize($organization);
        $this->ensure($organization, $company);

        $name = $company->name;
        $companyId = $company->id;
        $company->delete();

        Audit::log('company.deleted', 'organization', $organization->id, 'company', $companyId, ['name' => $name], null);

        return back()->with('status', 'Empresa removida.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'document' => ['nullable', 'string', 'max:24'],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
