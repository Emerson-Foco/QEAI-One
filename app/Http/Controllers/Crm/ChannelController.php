<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Organization;
use App\Support\Audit;
use App\Support\OrgAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.channels');
    }

    public function index(Organization $organization)
    {
        $this->authorize($organization);

        return view('member.channels', [
            'organization' => $organization,
            'channels' => $organization->channels()->latest('id')->get(),
        ]);
    }

    public function storeChat(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);

        $channel = $organization->channels()->create([
            'type' => 'web_chat',
            'name' => $data['name'],
            'token' => Str::random(40),
            'config' => [],
            'is_active' => true,
        ]);

        Audit::log('channel.created', 'organization', $organization->id, 'channel', $channel->id, null, ['type' => 'web_chat']);

        return back()->with('status', 'Canal de chat criado.')->with('new_chat_url', url('/chat/' . $channel->token));
    }

    public function storeEmail(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'encryption' => ['required', 'in:ssl,tls'],
            'username' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:100'],
            'from_email' => ['nullable', 'email', 'max:180'],
        ]);

        $channel = $organization->channels()->create([
            'type' => 'email',
            'name' => $data['name'],
            'config' => [
                'host' => $data['host'],
                'port' => (int) $data['port'],
                'encryption' => $data['encryption'],
                'username' => $data['username'],
                'from_name' => $data['from_name'] ?? 'Atendimento',
                'from_email' => $data['from_email'] ?? $data['username'],
            ],
            'is_active' => true,
        ]);
        $channel->setSecrets(['password' => $data['password']]);
        $channel->save();

        Audit::log('channel.created', 'organization', $organization->id, 'channel', $channel->id, null, ['type' => 'email']);

        return back()->with('status', 'Canal de e-mail criado.');
    }

    public function toggle(Organization $organization, Channel $channel)
    {
        $this->authorize($organization);
        abort_unless($channel->organization_id === $organization->id, 404);

        $channel->update(['is_active' => ! $channel->is_active]);
        Audit::log('channel.toggled', 'organization', $organization->id, 'channel', $channel->id, null, ['active' => $channel->is_active]);

        return back()->with('status', 'Canal ' . ($channel->is_active ? 'ativado' : 'desativado') . '.');
    }

    public function destroy(Organization $organization, Channel $channel)
    {
        $this->authorize($organization);
        abort_unless($channel->organization_id === $organization->id, 404);

        $channelId = $channel->id;
        $channel->delete();
        Audit::log('channel.deleted', 'organization', $organization->id, 'channel', $channelId);

        return back()->with('status', 'Canal removido.');
    }
}
