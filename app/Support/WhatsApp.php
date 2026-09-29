<?php

namespace App\Support;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;

/** Envio de mensagens via WhatsApp Cloud API (Graph API) — credenciais do cliente. */
class WhatsApp
{
    public static function send(Channel $channel, string $to, string $body): void
    {
        $config = $channel->config ?? [];
        $phoneNumberId = (string) ($config['phone_number_id'] ?? '');
        $accessToken = (string) ($channel->secret('access_token') ?? '');

        if ($phoneNumberId === '' || $accessToken === '') {
            throw new \RuntimeException('Canal WhatsApp sem Phone Number ID ou Access Token configurado.');
        }

        $response = Http::timeout(15)
            ->withToken($accessToken)
            ->post('https://graph.facebook.com/v21.0/' . $phoneNumberId . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => preg_replace('/\D+/', '', $to) ?: $to,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        if (! $response->ok()) {
            throw new \RuntimeException('WhatsApp respondeu ' . $response->status() . ': ' . $response->body());
        }
    }
}
