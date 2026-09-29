<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Support\Facades\Http;

/** Publica posts nos canais sociais configurados (webhook de automação ou página do Facebook). */
class SocialPublisher
{
    public static function publish(Post $post): void
    {
        $post->load('targets.account');

        foreach ($post->targets as $target) {
            if ($target->status === 'published') {
                continue;
            }
            $account = $target->account;
            if ($account === null || ! $account->is_active) {
                $target->update(['status' => 'failed', 'error' => 'Conta social inativa ou removida.']);
                continue;
            }

            try {
                if ($account->network === 'webhook') {
                    self::publishWebhook($post, $account);
                } elseif ($account->network === 'facebook_page') {
                    self::publishFacebook($post, $account);
                } else {
                    throw new \RuntimeException('Rede "' . $account->network . '" exige um webhook de automação configurado.');
                }
                $target->update(['status' => 'published', 'published_at' => now(), 'error' => null]);
            } catch (\Throwable $exception) {
                $target->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 500)]);
            }
        }

        $post->refresh()->load('targets');
        $statuses = $post->targets->pluck('status')->unique();
        $status = $statuses->contains('pending') ? 'publishing' : ($statuses->contains('failed') ? 'failed' : 'published');
        $post->update(['status' => $status]);
    }

    private static function publishWebhook(Post $post, $account): void
    {
        $url = (string) ($account->config['webhook_url'] ?? '');
        if (! Webhooks::isSafeUrl($url)) {
            throw new \RuntimeException('Webhook da conta social inválido ou interno.');
        }

        $body = json_encode([
            'event' => 'post.published',
            'data' => ['title' => $post->title, 'body' => $post->body, 'media_url' => $post->media_url],
            'sent_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        $headers = ['Content-Type' => 'application/json'];
        $secret = (string) ($account->secret('secret') ?? '');
        if ($secret !== '') {
            $headers['X-QEAI-Signature'] = hash_hmac('sha256', $body, $secret);
        }

        $response = Http::timeout(10)->withHeaders($headers)->withBody($body, 'application/json')->post($url);
        if (! $response->ok()) {
            throw new \RuntimeException('Webhook respondeu ' . $response->status() . '.');
        }
    }

    private static function publishFacebook(Post $post, $account): void
    {
        $pageId = (string) ($account->config['page_id'] ?? '');
        $token = (string) ($account->secret('access_token') ?? '');
        if ($pageId === '' || $token === '') {
            throw new \RuntimeException('Conta do Facebook sem page_id ou access_token.');
        }

        $response = Http::timeout(15)->post('https://graph.facebook.com/v21.0/' . $pageId . '/feed', [
            'message' => $post->body,
            'access_token' => $token,
        ]);

        if (! $response->ok()) {
            throw new \RuntimeException('Facebook respondeu ' . $response->status() . ': ' . $response->body());
        }
    }
}
