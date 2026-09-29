<?php

namespace App\Http\Controllers;

use App\Models\InboundEndpoint;
use App\Support\LeadIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class InboundReceiveController extends Controller
{
    /** Entrada genérica (Zapier/Make/N8N/site): POST /hooks/{token}. */
    public function generic(Request $request, string $token)
    {
        $endpoint = InboundEndpoint::with('organization')
            ->where('token', $token)->where('source', 'generic')->where('is_active', true)->first();

        if ($endpoint === null) {
            return response()->json(['error' => 'Endpoint inválido.'], 404);
        }

        if (! RateLimiter::tooManyAttempts('hook-' . $endpoint->id, 120)) {
            RateLimiter::hit('hook-' . $endpoint->id, 60);
        } else {
            return response()->json(['error' => 'Limite excedido.'], 429);
        }

        $raw = $request->getContent();
        if ($endpoint->secret) {
            $signature = (string) $request->header('X-QEAI-Signature');
            if (! hash_equals(hash_hmac('sha256', (string) $raw, (string) $endpoint->secret), $signature)) {
                return response()->json(['error' => 'Assinatura inválida.'], 401);
            }
        }

        $payload = $request->json()->all() ?: $request->all();
        if (empty($payload['name']) && empty($payload['email']) && empty($payload['phone']) && empty($payload['whatsapp'])) {
            return response()->json(['error' => 'Informe ao menos nome, e-mail ou telefone.'], 422);
        }

        $contact = LeadIntake::create($endpoint->organization, $payload, 'webhook: ' . $endpoint->name, [
            'inbound_endpoint_id' => $endpoint->id,
            'ip' => $request->ip(),
        ]);
        $endpoint->forceFill(['last_received_at' => now()])->save();

        return response()->json(['ok' => true, 'id' => $contact->id], 201);
    }

    /** Verificação de inscrição do Meta (hub.challenge). */
    public function metaVerify(Request $request, string $token)
    {
        $endpoint = InboundEndpoint::where('token', $token)->where('source', 'meta_lead_ads')->where('is_active', true)->first();

        if ($endpoint === null
            || $request->query('hub_mode') !== 'subscribe' && $request->query('hub.mode') !== 'subscribe'
            || ($request->query('hub_verify_token') ?? $request->query('hub.verify_token')) !== $endpoint->meta_verify_token) {
            return response('Forbidden', 403);
        }

        return response((string) ($request->query('hub_challenge') ?? $request->query('hub.challenge')), 200)
            ->header('Content-Type', 'text/plain');
    }

    /** Recebimento de leads do Meta Lead Ads. */
    public function metaReceive(Request $request, string $token)
    {
        $endpoint = InboundEndpoint::with('organization')
            ->where('token', $token)->where('source', 'meta_lead_ads')->where('is_active', true)->first();

        if ($endpoint === null) {
            return response('Not found', 404);
        }

        $raw = $request->getContent();
        if ($endpoint->meta_app_secret) {
            $signature = (string) $request->header('X-Hub-Signature-256');
            $expected = 'sha256=' . hash_hmac('sha256', (string) $raw, (string) $endpoint->meta_app_secret);
            if (! hash_equals($expected, $signature)) {
                return response('Invalid signature', 401);
            }
        }

        $created = 0;
        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $leadgenId = $change['value']['leadgen_id'] ?? null;
                if (! $leadgenId) {
                    continue;
                }
                try {
                    $payload = $this->fetchMetaLead((string) $leadgenId, $endpoint);
                    if ($payload !== null) {
                        LeadIntake::create($endpoint->organization, $payload, 'meta_lead_ads: ' . $endpoint->name, [
                            'inbound_endpoint_id' => $endpoint->id, 'leadgen_id' => $leadgenId,
                        ]);
                        $created++;
                    }
                } catch (\Throwable $exception) {
                    Log::warning('[QEAI meta lead] ' . $exception->getMessage());
                }
            }
        }

        if ($created > 0) {
            $endpoint->forceFill(['last_received_at' => now()])->save();
        }

        return response('EVENT_RECEIVED', 200);
    }

    /** Busca os dados do lead no Graph API e mapeia para o nosso formato. */
    private function fetchMetaLead(string $leadgenId, InboundEndpoint $endpoint): ?array
    {
        $response = Http::timeout(10)->get('https://graph.facebook.com/v21.0/' . $leadgenId, [
            'access_token' => (string) $endpoint->meta_page_access_token,
            'fields' => 'field_data,created_time',
        ]);

        if (! $response->ok()) {
            Log::warning('[QEAI meta lead] Graph respondeu ' . $response->status());
            return null;
        }

        $builtin = ['name' => 'name', 'full_name' => 'name', 'first_name' => 'name', 'email' => 'email', 'phone_number' => 'phone', 'phone' => 'phone'];
        $payload = ['source' => 'Meta Lead Ads'];
        $notes = [];

        foreach ((array) $response->json('field_data', []) as $item) {
            $key = $item['name'] ?? '';
            $value = $item['values'][0] ?? null;
            if ($value === null) {
                continue;
            }
            if (isset($builtin[$key])) {
                $payload[$builtin[$key]] = $value;
            } else {
                $payload['custom'][$key] = $value;
                $notes[] = $key . ': ' . $value;
            }
        }

        if (! empty($notes)) {
            $payload['notes'] = implode("\n", $notes);
        }

        return $payload;
    }
}
