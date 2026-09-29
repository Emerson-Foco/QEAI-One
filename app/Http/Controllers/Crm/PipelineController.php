<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Organization;
use App\Models\Pipeline;
use App\Support\OrgAccess;

class PipelineController extends Controller
{
    public function index(Organization $organization)
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.leads');

        $pipeline = Pipeline::ensureDefaultFor($organization);
        $deals = Deal::with(['contact', 'company'])
            ->where('organization_id', $organization->id)
            ->where('pipeline_id', $pipeline->id)
            ->latest('id')
            ->get()
            ->groupBy('stage_id');

        return view('member.crm.pipeline', [
            'organization' => $organization,
            'pipeline' => $pipeline,
            'stages' => $pipeline->stages()->orderBy('position')->get(),
            'deals' => $deals,
            'contacts' => Contact::where('organization_id', $organization->id)->orderBy('name')->get(),
            'companies' => Company::where('organization_id', $organization->id)->orderBy('name')->get(),
        ]);
    }
}
