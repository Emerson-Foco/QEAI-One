<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Crm\AdsController;
use App\Models\AdAccount;
use Illuminate\Http\Request;

class AdsImportController extends Controller
{
    /** Importa métricas de anúncios (POST /hooks/ads/{token}) — ideal para N8N/Zapier/Make. */
    public function store(Request $request, string $token)
    {
        $account = AdAccount::with('organization')->where('token', $token)->where('is_active', true)->first();
        if ($account === null) {
            return response()->json(['error' => 'Conta de anúncio inválida.'], 404);
        }

        $payload = $request->json()->all() ?: $request->all();
        if (empty($payload['date'])) {
            return response()->json(['error' => 'Informe a data (date).'], 422);
        }

        $metric = AdsController::upsertMetric($account->organization, $account, [
            'date' => $payload['date'],
            'impressions' => $payload['impressions'] ?? 0,
            'clicks' => $payload['clicks'] ?? 0,
            'spend_cents' => $payload['spend_cents'] ?? null,
            'spend_reais' => $payload['spend_reais'] ?? ($payload['spend'] ?? 0),
            'conversions' => $payload['conversions'] ?? 0,
        ]);

        return response()->json(['ok' => true, 'id' => $metric->id], 201);
    }
}
