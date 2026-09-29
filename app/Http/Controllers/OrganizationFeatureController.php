<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\Audit;
use App\Support\PlanFeatures;
use App\Support\PlanResolver;
use Illuminate\Http\Request;

class OrganizationFeatureController extends Controller
{
    public function update(Request $request, Organization $organization)
    {
        $enabledInput = (array) $request->input('enabled', []);
        $limitsInput = (array) $request->input('limits', []);
        $before = PlanResolver::summary($organization);

        foreach (PlanFeatures::catalog() as $key => $meta) {
            $enabledChoice = $enabledInput[$key] ?? 'inherit';
            $overrideEnabled = $enabledChoice === 'inherit' ? null : ($enabledChoice === '1');

            $limitInput = $limitsInput[$key] ?? '';
            $overrideLimit = ($limitInput === '' || $limitInput === null) ? null : max(0, (int) $limitInput);

            if ($overrideEnabled === null && $overrideLimit === null) {
                $organization->featureOverrides()->where('feature_key', $key)->delete();
                continue;
            }

            $organization->featureOverrides()->updateOrCreate(
                ['feature_key' => $key],
                ['enabled' => $overrideEnabled, 'limit_value' => $overrideLimit]
            );
        }

        $after = PlanResolver::summary($organization);
        Audit::log('organization.features_updated', 'platform', null, 'organization', $organization->id,
            ['limits' => array_map(fn ($r) => $r['limit'], $before)],
            ['limits' => array_map(fn ($r) => $r['limit'], $after)]
        );

        return back()->with('status', 'Recursos e limites da organização atualizados.');
    }
}
