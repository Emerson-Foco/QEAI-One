<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Support\Audit;
use App\Support\Notify;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function verify(Request $request, string $token)
    {
        $channel = Channel::where('token', $token)->where('type', 'whatsapp')->where('is_active', true)->first();
        if ($channel === null) {
            return response('Forbidden', 403);
        }

        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $verifyToken = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode !== 'subscribe' || $verifyToken !== $channel->secret('verify_token')) {
            return response('Forbidden', 403);
        }

        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, string $token)
    {
        $channel = Channel::with('organization')->where('token', $token)->where('type', 'whatsapp')->where('is_active', true)->first();
        if ($channel === null) {
            return response('Not found', 404);
        }

        $appSecret = (string) ($channel->secret('app_secret') ?? '');
        if ($appSecret !== '') {
            $signature = (string) $request->header('X-Hub-Signature-256');
            $expected = 'sha256=' . hash_hmac('sha256', (string) $request->getContent(), $appSecret);
            if (! hash_equals($expected, $signature)) {
                return response('Invalid signature', 401);
            }
        }

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $name = $value['contacts'][0]['profile']['name'] ?? 'WhatsApp';
                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $from = (string) ($message['from'] ?? '');
                    $body = $message['text']['body'] ?? ('[' . ($message['type'] ?? 'mensagem') . ']');
                    if ($from === '') {
                        continue;
                    }
                    $this->handleIncoming($channel, $from, $name, $body);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function handleIncoming(Channel $channel, string $from, string $name, string $body): void
    {
        $organization = $channel->organization;

        $contact = Contact::firstOrCreate(
            ['organization_id' => $organization->id, 'whatsapp' => $from],
            ['name' => mb_substr($name, 0, 180), 'source' => 'WhatsApp']
        );

        $conversation = Conversation::where('channel_id', $channel->id)
            ->where('contact_id', $contact->id)
            ->whereIn('status', ['open', 'pending'])
            ->latest('id')->first();

        if ($conversation === null) {
            $conversation = Conversation::create([
                'organization_id' => $organization->id,
                'channel_id' => $channel->id,
                'contact_id' => $contact->id,
                'subject' => 'WhatsApp',
                'status' => 'open',
                'visitor_token' => $from,
                'last_message_at' => now(),
            ]);
            Audit::log('conversation.created', 'organization', $organization->id, 'conversation', $conversation->id, null, ['channel' => 'whatsapp']);
        }

        $conversation->messages()->create(['direction' => 'in', 'body' => $body]);
        $conversation->update(['last_message_at' => now(), 'status' => 'open']);
        Notify::chatMessage($organization, $conversation, $body);
    }
}
