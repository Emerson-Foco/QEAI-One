<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Webhooks de saída (triggers). Ao ocorrer um evento, envia um POST assinado
 * (HMAC-SHA256) para os endpoints da organização — pronto para N8N/Zapier/Make.
 */
class Webhooks
{
    public static function dispatch(Organization $organization, string $event, array $data): void
    {
        foreach ($organization->webhookEndpoints()->where('is_active', true)->get() as $endpoint) {
            if (! $endpoint->listensTo($event)) {
                continue;
            }
            if (! self::isSafeUrl((string) $endpoint->url)) {
                Log::warning('[QEAI webhook] URL insegura ignorada: ' . $endpoint->url);
                continue;
            }

            $body = json_encode([
                'event' => $event,
                'data' => $data,
                'sent_at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

            $signature = hash_hmac('sha256', $body, (string) $endpoint->secret);

            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'X-QEAI-Event' => $event,
                        'X-QEAI-Signature' => $signature,
                        'X-QEAI-Delivery' => (string) Str::uuid(),
                        'Content-Type' => 'application/json',
                    ])
                    ->withBody($body, 'application/json')
                    ->post((string) $endpoint->url);

                $endpoint->forceFill(['last_status' => $response->status(), 'last_called_at' => now()])->save();
            } catch (\Throwable $exception) {
                Log::warning('[QEAI webhook] falha: ' . $exception->getMessage());
                $endpoint->forceFill(['last_status' => 0, 'last_called_at' => now()])->save();
            }
        }
    }

    /** Bloqueia destinos internos/privados (proteção básica contra SSRF). */
    public static function isSafeUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            return false;
        }
        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || $host === '127.0.0.1' || $host === '::1') {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return true;
    }
}
