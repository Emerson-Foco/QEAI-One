<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\ChannelMail;
use App\Support\OrgAccess;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.conversations');
    }

    private function ensure(Organization $organization, Conversation $conversation): void
    {
        abort_unless($conversation->organization_id === $organization->id, 404);
    }

    public function index(Request $request, Organization $organization)
    {
        $this->authorize($organization);

        $status = (string) $request->input('status', '');
        $query = Conversation::with(['contact', 'channel', 'assignee'])->where('organization_id', $organization->id);
        if (in_array($status, ['open', 'pending', 'closed'], true)) {
            $query->where('status', $status);
        }

        return view('member.inbox.index', [
            'organization' => $organization,
            'conversations' => $query->orderByDesc('last_message_at')->orderByDesc('id')->paginate(30)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Organization $organization, Conversation $conversation)
    {
        $this->authorize($organization);
        $this->ensure($organization, $conversation);
        $conversation->load(['messages.author', 'contact', 'channel', 'assignee']);

        return view('member.inbox.show', [
            'organization' => $organization,
            'conversation' => $conversation,
            'members' => $organization->memberships()->with('user')->get()->pluck('user')->filter(),
        ]);
    }

    public function reply(Request $request, Organization $organization, Conversation $conversation)
    {
        $this->authorize($organization);
        $this->ensure($organization, $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        $meta = null;
        $channel = $conversation->channel;
        try {
            if ($channel->type === 'email' && $conversation->contact?->email) {
                ChannelMail::send($channel, $conversation->contact->email, 'Re: ' . ($conversation->subject ?: 'Atendimento'), $data['body']);
            } elseif ($channel->type === 'whatsapp') {
                $to = $conversation->visitor_token ?: $conversation->contact?->whatsapp;
                if ($to) {
                    \App\Support\WhatsApp::send($channel, (string) $to, $data['body']);
                }
            }
        } catch (\Throwable $exception) {
            $meta = ['error' => $exception->getMessage()];
        }

        $conversation->messages()->create([
            'direction' => 'out',
            'body' => $data['body'],
            'author_user_id' => auth()->id(),
            'meta' => $meta,
        ]);
        $conversation->update(['last_message_at' => now()]);
        Audit::log('conversation.replied', 'organization', $organization->id, 'conversation', $conversation->id);

        if ($meta) {
            return back()->with('status', 'Mensagem registrada, mas falhou o envio por e-mail: ' . $meta['error']);
        }

        return back()->with('status', 'Resposta enviada.');
    }

    public function status(Request $request, Organization $organization, Conversation $conversation)
    {
        $this->authorize($organization);
        $this->ensure($organization, $conversation);
        $data = $request->validate(['status' => ['required', 'in:open,pending,closed']]);

        $conversation->update(['status' => $data['status']]);
        Audit::log('conversation.status', 'organization', $organization->id, 'conversation', $conversation->id, null, ['status' => $data['status']]);

        return back()->with('status', 'Status atualizado.');
    }

    public function assign(Request $request, Organization $organization, Conversation $conversation)
    {
        $this->authorize($organization);
        $this->ensure($organization, $conversation);
        $data = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);

        $conversation->update(['assigned_to' => $data['assigned_to'] ?: null]);
        Audit::log('conversation.assigned', 'organization', $organization->id, 'conversation', $conversation->id, null, ['assigned_to' => $conversation->assigned_to]);

        return back()->with('status', 'Atribuição atualizada.');
    }
}
