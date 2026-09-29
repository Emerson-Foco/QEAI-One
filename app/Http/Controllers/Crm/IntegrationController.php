<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\InboundEndpoint;
use App\Models\Organization;
use App\Models\WebhookEndpoint;
use App\Support\Audit;
use App\Support\OrgAccess;
use App\Support\Webhooks;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    private const EVENTS = ['lead.created' => 'Lead criado'];

    public function storeInbound(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);

        $endpoint = $organization->inboundEndpoints()->create([
            'name' => $data['name'],
            'source' => 'generic',
            'token' => Str::random(48),
            'is_active' => true,
        ]);

        Audit::log('inbound.created', 'organization', $organization->id, 'inbound_endpoint', $endpoint->id, null, ['source' => 'generic']);

        return back()->with('status', 'Endpoint de entrada criado.')->with('new_inbound_url', url('/hooks/' . $endpoint->token));
    }

    public function storeMeta(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'meta_verify_token' => ['nullable', 'string', 'max:64'],
            'meta_page_access_token' => ['required', 'string', 'max:1000'],
            'meta_app_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $endpoint = $organization->inboundEndpoints()->create([
            'name' => $data['name'],
            'source' => 'meta_lead_ads',
            'token' => Str::random(48),
            'meta_verify_token' => $data['meta_verify_token'] ?: Str::random(24),
            'meta_page_access_token' => $data['meta_page_access_token'],
            'meta_app_secret' => $data['meta_app_secret'] ?? null,
            'is_active' => true,
        ]);

        Audit::log('inbound.created', 'organization', $organization->id, 'inbound_endpoint', $endpoint->id, null, ['source' => 'meta_lead_ads']);

        return back()->with('status', 'Endpoint do Meta criado.')->with('new_meta_url', url('/hooks/meta/' . $endpoint->token));
    }

    public function toggleInbound(Organization $organization, InboundEndpoint $inbound)
    {
        $this->authorize($organization);
        abort_unless($inbound->organization_id === $organization->id, 404);

        $inbound->update(['is_active' => ! $inbound->is_active]);
        Audit::log('inbound.toggled', 'organization', $organization->id, 'inbound_endpoint', $inbound->id, null, ['active' => $inbound->is_active]);

        return back()->with('status', 'Endpoint ' . ($inbound->is_active ? 'ativado' : 'desativado') . '.');
    }

    public function destroyInbound(Organization $organization, InboundEndpoint $inbound)
    {
        $this->authorize($organization);
        abort_unless($inbound->organization_id === $organization->id, 404);

        $inboundId = $inbound->id;
        $inbound->delete();
        Audit::log('inbound.deleted', 'organization', $organization->id, 'inbound_endpoint', $inboundId);

        return back()->with('status', 'Endpoint removido.');
    }

    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.api_keys');
    }

    public function index(Organization $organization)
    {
        $this->authorize($organization);

        return view('member.integrations', [
            'organization' => $organization,
            'keys' => $organization->apiKeys()->latest('id')->get(),
            'webhooks' => $organization->webhookEndpoints()->latest('id')->get(),
            'inbounds' => $organization->inboundEndpoints()->latest('id')->get(),
            'events' => self::EVENTS,
        ]);
    }

    public function storeKey(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);

        $token = 'qo_' . Str::random(40);
        $key = $organization->apiKeys()->create([
            'name' => $data['name'],
            'prefix' => substr($token, 0, 10),
            'token_hash' => hash('sha256', $token),
        ]);

        Audit::log('api_key.created', 'organization', $organization->id, 'api_key', $key->id, null, ['name' => $key->name]);

        return back()->with('status', 'Chave criada. Copie agora — não será exibida novamente.')->with('new_api_key', $token);
    }

    public function revokeKey(Organization $organization, ApiKey $key)
    {
        $this->authorize($organization);
        abort_unless($key->organization_id === $organization->id, 404);

        $key->update(['revoked_at' => now()]);
        Audit::log('api_key.revoked', 'organization', $organization->id, 'api_key', $key->id);

        return back()->with('status', 'Chave revogada.');
    }

    public function storeWebhook(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'url' => ['required', 'url', 'max:255'],
            'events' => ['array'],
            'events.*' => ['string'],
        ]);

        if (! Webhooks::isSafeUrl($data['url'])) {
            return back()->withErrors(['url' => 'URL inválida ou interna (não permitida por segurança).']);
        }

        $events = array_values(array_intersect($data['events'] ?? [], array_keys(self::EVENTS))) ?: ['lead.created'];

        $webhook = $organization->webhookEndpoints()->create([
            'url' => $data['url'],
            'secret' => Str::random(48),
            'events' => $events,
            'is_active' => true,
        ]);

        Audit::log('webhook.created', 'organization', $organization->id, 'webhook', $webhook->id, null, ['url' => $webhook->url]);

        return back()->with('status', 'Webhook criado.');
    }

    public function toggleWebhook(Organization $organization, WebhookEndpoint $webhook)
    {
        $this->authorize($organization);
        abort_unless($webhook->organization_id === $organization->id, 404);

        $webhook->update(['is_active' => ! $webhook->is_active]);
        Audit::log('webhook.toggled', 'organization', $organization->id, 'webhook', $webhook->id, null, ['active' => $webhook->is_active]);

        return back()->with('status', 'Webhook ' . ($webhook->is_active ? 'ativado' : 'desativado') . '.');
    }

    public function destroyWebhook(Organization $organization, WebhookEndpoint $webhook)
    {
        $this->authorize($organization);
        abort_unless($webhook->organization_id === $organization->id, 404);

        $webhookId = $webhook->id;
        $webhook->delete();
        Audit::log('webhook.deleted', 'organization', $organization->id, 'webhook', $webhookId);

        return back()->with('status', 'Webhook removido.');
    }
}
