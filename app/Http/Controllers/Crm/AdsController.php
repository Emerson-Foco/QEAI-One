<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdsController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.ads');
    }

    public function index(Request $request, Organization $organization)
    {
        $this->authorize($organization);

        $accounts = $organization->adAccounts()->latest('id')->get();
        $query = AdMetric::with('account')->where('organization_id', $organization->id);
        if ($from = $request->input('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('date', '<=', $to);
        }

        $summaryQuery = clone $query;
        $summary = [
            'impressions' => (int) $summaryQuery->sum('impressions'),
            'clicks' => (int) $summaryQuery->sum('clicks'),
            'spend_cents' => (int) $summaryQuery->sum('spend_cents'),
            'conversions' => (int) $summaryQuery->sum('conversions'),
        ];

        return view('member.ads', [
            'organization' => $organization,
            'accounts' => $accounts,
            'providers' => AdAccount::providers(),
            'metrics' => $query->orderByDesc('date')->limit(100)->get(),
            'summary' => $summary,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function storeAccount(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'provider' => ['required', 'in:meta,google,tiktok,other'],
            'external_account_id' => ['nullable', 'string', 'max:120'],
            'access_token' => ['nullable', 'string', 'max:2000'],
        ]);

        $account = $organization->adAccounts()->create([
            'provider' => $data['provider'],
            'name' => $data['name'],
            'external_account_id' => $data['external_account_id'] ?? null,
            'token' => Str::random(48),
            'is_active' => true,
        ]);
        if (! empty($data['access_token'])) {
            $account->secrets = json_encode(['access_token' => $data['access_token']]);
            $account->save();
        }

        Audit::log('ad_account.created', 'organization', $organization->id, 'ad_account', $account->id, null, ['provider' => $account->provider]);

        return back()->with('status', 'Conta de anúncio criada.')->with('new_ad_url', url('/hooks/ads/' . $account->token));
    }

    public function toggleAccount(Organization $organization, AdAccount $account)
    {
        $this->authorize($organization);
        abort_unless($account->organization_id === $organization->id, 404);

        $account->update(['is_active' => ! $account->is_active]);
        Audit::log('ad_account.toggled', 'organization', $organization->id, 'ad_account', $account->id, null, ['active' => $account->is_active]);

        return back()->with('status', 'Conta atualizada.');
    }

    public function destroyAccount(Organization $organization, AdAccount $account)
    {
        $this->authorize($organization);
        abort_unless($account->organization_id === $organization->id, 404);

        $accountId = $account->id;
        $account->delete();
        Audit::log('ad_account.deleted', 'organization', $organization->id, 'ad_account', $accountId);

        return back()->with('status', 'Conta removida.');
    }

    public function storeMetric(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'ad_account_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'spend_reais' => ['nullable', 'numeric', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
        ]);

        $account = $organization->adAccounts()->findOrFail($data['ad_account_id']);
        $this->upsertMetric($organization, $account, $data);

        Audit::log('ad_metric.saved', 'organization', $organization->id, 'ad_account', $account->id, null, ['date' => $data['date']]);

        return back()->with('status', 'Métricas registradas.');
    }

    public function destroyMetric(Organization $organization, AdMetric $metric)
    {
        $this->authorize($organization);
        abort_unless($metric->organization_id === $organization->id, 404);

        $metricId = $metric->id;
        $metric->delete();
        Audit::log('ad_metric.deleted', 'organization', $organization->id, 'ad_metric', $metricId);

        return back()->with('status', 'Registro removido.');
    }

    public static function upsertMetric(Organization $organization, AdAccount $account, array $data): AdMetric
    {
        return AdMetric::updateOrCreate(
            ['ad_account_id' => $account->id, 'date' => date('Y-m-d', strtotime((string) $data['date']))],
            [
                'organization_id' => $organization->id,
                'impressions' => (int) ($data['impressions'] ?? 0),
                'clicks' => (int) ($data['clicks'] ?? 0),
                'spend_cents' => isset($data['spend_cents']) ? (int) $data['spend_cents'] : (int) round(((float) ($data['spend_reais'] ?? 0)) * 100),
                'conversions' => (int) ($data['conversions'] ?? 0),
            ]
        );
    }
}
