<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Organization;
use App\Models\Pipeline;
use App\Support\Audit;
use App\Support\OrgAccess;
use Illuminate\Http\Request;

class DealController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.leads');
    }

    private function ensure(Organization $organization, Deal $deal): void
    {
        abort_unless($deal->organization_id === $organization->id, 404);
    }

    public function store(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $pipeline = Pipeline::ensureDefaultFor($organization);
        $data = $this->validated($request);

        $stage = $pipeline->stages()->findOrFail($data['stage_id']);
        $deal = Deal::create([
            'organization_id' => $organization->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'contact_id' => $this->owned($organization, Contact::class, $data['contact_id'] ?? null),
            'company_id' => $this->owned($organization, Company::class, $data['company_id'] ?? null),
            'title' => $data['title'],
            'value_cents' => (int) round(((float) ($data['value_reais'] ?? 0)) * 100),
            'status' => $this->statusFor($stage),
            'owner_user_id' => auth()->id(),
            'expected_close_at' => $data['expected_close_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        Audit::log('deal.created', 'organization', $organization->id, 'deal', $deal->id, null, ['title' => $deal->title]);

        return back()->with('status', 'Negócio criado.');
    }

    public function move(Request $request, Organization $organization, Deal $deal)
    {
        $this->authorize($organization);
        $this->ensure($organization, $deal);

        $data = $request->validate(['stage_id' => ['required', 'integer']]);
        $stage = $deal->pipeline->stages()->findOrFail($data['stage_id']);
        $deal->update(['stage_id' => $stage->id, 'status' => $this->statusFor($stage)]);

        Audit::log('deal.moved', 'organization', $organization->id, 'deal', $deal->id, null, ['stage' => $stage->name]);

        return back()->with('status', 'Negócio movido para ' . $stage->name . '.');
    }

    public function edit(Organization $organization, Deal $deal)
    {
        $this->authorize($organization);
        $this->ensure($organization, $deal);

        return view('member.crm.deal-edit', [
            'organization' => $organization,
            'deal' => $deal,
            'stages' => $deal->pipeline->stages()->orderBy('position')->get(),
            'contacts' => Contact::where('organization_id', $organization->id)->orderBy('name')->get(),
            'companies' => Company::where('organization_id', $organization->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Organization $organization, Deal $deal)
    {
        $this->authorize($organization);
        $this->ensure($organization, $deal);
        $data = $this->validated($request);

        $stage = $deal->pipeline->stages()->findOrFail($data['stage_id']);
        $deal->update([
            'stage_id' => $stage->id,
            'status' => $this->statusFor($stage),
            'contact_id' => $this->owned($organization, Contact::class, $data['contact_id'] ?? null),
            'company_id' => $this->owned($organization, Company::class, $data['company_id'] ?? null),
            'title' => $data['title'],
            'value_cents' => (int) round(((float) ($data['value_reais'] ?? 0)) * 100),
            'expected_close_at' => $data['expected_close_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Audit::log('deal.updated', 'organization', $organization->id, 'deal', $deal->id, null, ['title' => $deal->title]);

        return redirect()->route('member.org.pipeline.index', $organization)->with('status', 'Negócio atualizado.');
    }

    public function destroy(Organization $organization, Deal $deal)
    {
        $this->authorize($organization);
        $this->ensure($organization, $deal);

        $title = $deal->title;
        $dealId = $deal->id;
        $deal->delete();

        Audit::log('deal.deleted', 'organization', $organization->id, 'deal', $dealId, ['title' => $title], null);

        return back()->with('status', 'Negócio removido.');
    }

    private function statusFor($stage): string
    {
        return $stage->is_won ? 'won' : ($stage->is_lost ? 'lost' : 'open');
    }

    private function owned(Organization $organization, string $class, $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $class::where('organization_id', $organization->id)->where('id', $id)->value('id');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'value_reais' => ['nullable', 'numeric', 'min:0'],
            'stage_id' => ['required', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'expected_close_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
