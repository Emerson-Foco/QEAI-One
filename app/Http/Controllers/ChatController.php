<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Conversation;
use App\Support\LeadIntake;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    private function channel(string $token): Channel
    {
        return Channel::with('organization')
            ->where('token', $token)->where('type', 'web_chat')->where('is_active', true)
            ->firstOrFail();
    }

    public function show(Request $request, string $token)
    {
        $channel = $this->channel($token);
        $visitor = $this->visitorToken($request, $channel);
        $conversation = Conversation::where('channel_id', $channel->id)->where('visitor_token', $visitor)->latest('id')->first();

        return view('public.chat', [
            'channel' => $channel,
            'organization' => $channel->organization,
            'messages' => $conversation?->messages()->get() ?? collect(),
        ]);
    }

    public function send(Request $request, string $token)
    {
        $channel = $this->channel($token);
        $visitor = $this->visitorToken($request, $channel);

        $key = 'chat-send|'.$channel->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            throw ValidationException::withMessages(['body' => 'Muitas mensagens. Aguarde um instante.']);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:180'],
        ]);

        $conversation = $this->conversationFor($channel, $visitor, $data);
        $conversation->messages()->create(['direction' => 'in', 'body' => $data['body']]);
        $conversation->update(['last_message_at' => now(), 'status' => 'open']);
        Notify::chatMessage($channel->organization, $conversation, $data['body']);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('chat.show', $token)->with('chat_sent', true);
    }

    public function messages(Request $request, string $token)
    {
        $channel = $this->channel($token);
        $visitor = $this->visitorToken($request, $channel);

        $conversation = Conversation::where('channel_id', $channel->id)->where('visitor_token', $visitor)->latest('id')->first();
        if ($conversation === null) {
            return response()->json(['messages' => []]);
        }

        $after = (int) $request->query('after', 0);
        $messages = $conversation->messages()->where('id', '>', $after)->get()->map(fn ($m) => [
            'id' => $m->id,
            'direction' => $m->direction,
            'body' => $m->body,
            'at' => $m->created_at?->format('H:i'),
        ]);

        return response()->json(['messages' => $messages]);
    }

    private function visitorToken(Request $request, Channel $channel): string
    {
        $key = 'chat_visitor_'.$channel->id;
        if (! $request->session()->has($key)) {
            $request->session()->put($key, Str::random(40));
        }

        return (string) $request->session()->get($key);
    }

    private function conversationFor(Channel $channel, string $visitor, array $data): Conversation
    {
        $conversation = Conversation::where('channel_id', $channel->id)
            ->where('visitor_token', $visitor)
            ->whereIn('status', ['open', 'pending'])
            ->latest('id')->first();

        if ($conversation !== null) {
            return $conversation;
        }

        $contact = null;
        $email = trim((string) ($data['email'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        if ($email !== '' || $name !== '') {
            $contact = LeadIntake::create($channel->organization, [
                'name' => $name !== '' ? $name : 'Visitante',
                'email' => $email !== '' ? $email : null,
                'source' => 'Chat no site',
            ], 'chat', ['channel_id' => $channel->id]);
        }

        return Conversation::create([
            'organization_id' => $channel->organization_id,
            'channel_id' => $channel->id,
            'contact_id' => $contact?->id,
            'subject' => 'Chat no site',
            'status' => 'open',
            'visitor_token' => $visitor,
            'last_message_at' => now(),
        ]);
    }
}
