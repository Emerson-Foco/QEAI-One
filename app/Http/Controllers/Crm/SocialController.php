<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Support\Audit;
use App\Support\OrgAccess;
use App\Support\SocialPublisher;
use App\Support\Webhooks;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    private function authorize(Organization $organization): void
    {
        OrgAccess::authorizeData(auth()->user(), $organization, 'org.social');
    }

    public function index(Organization $organization)
    {
        $this->authorize($organization);

        return view('member.social', [
            'organization' => $organization,
            'accounts' => $organization->socialAccounts()->latest('id')->get(),
            'posts' => $organization->posts()->with('targets.account')->latest('id')->limit(50)->get(),
            'networks' => SocialAccount::networks(),
        ]);
    }

    public function storeAccount(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'network' => ['required', 'in:webhook,facebook_page,instagram,linkedin,tiktok'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
            'page_id' => ['nullable', 'string', 'max:120'],
            'access_token' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['network'] === 'webhook' && ! Webhooks::isSafeUrl((string) ($data['webhook_url'] ?? ''))) {
            return back()->withErrors(['webhook_url' => 'Informe uma URL de webhook válida e pública.']);
        }

        $account = $organization->socialAccounts()->create([
            'network' => $data['network'],
            'name' => $data['name'],
            'config' => array_filter([
                'webhook_url' => $data['webhook_url'] ?? null,
                'page_id' => $data['page_id'] ?? null,
            ]),
            'is_active' => true,
        ]);
        if (! empty($data['access_token'])) {
            $account->setSecrets(['access_token' => $data['access_token']]);
            $account->save();
        }

        Audit::log('social_account.created', 'organization', $organization->id, 'social_account', $account->id, null, ['network' => $account->network]);

        return back()->with('status', 'Conta social adicionada.');
    }

    public function toggleAccount(Organization $organization, SocialAccount $account)
    {
        $this->authorize($organization);
        abort_unless($account->organization_id === $organization->id, 404);

        $account->update(['is_active' => ! $account->is_active]);
        Audit::log('social_account.toggled', 'organization', $organization->id, 'social_account', $account->id, null, ['active' => $account->is_active]);

        return back()->with('status', 'Conta atualizada.');
    }

    public function destroyAccount(Organization $organization, SocialAccount $account)
    {
        $this->authorize($organization);
        abort_unless($account->organization_id === $organization->id, 404);

        $accountId = $account->id;
        $account->delete();
        Audit::log('social_account.deleted', 'organization', $organization->id, 'social_account', $accountId);

        return back()->with('status', 'Conta removida.');
    }

    public function storePost(Request $request, Organization $organization)
    {
        $this->authorize($organization);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'scheduled_at' => ['nullable', 'date'],
            'targets' => ['required', 'array'],
            'targets.*' => ['integer'],
        ]);

        $accountIds = $organization->socialAccounts()->whereIn('id', $data['targets'])->pluck('id');
        if ($accountIds->isEmpty()) {
            return back()->withErrors(['targets' => 'Selecione ao menos uma conta social.']);
        }

        $scheduledAt = ! empty($data['scheduled_at']) ? date('Y-m-d H:i:s', strtotime((string) $data['scheduled_at'])) : null;

        $post = $organization->posts()->create([
            'title' => $data['title'],
            'body' => $data['body'],
            'media_url' => $data['media_url'] ?? null,
            'scheduled_at' => $scheduledAt,
            'status' => $scheduledAt ? 'scheduled' : 'draft',
            'created_by' => auth()->id(),
        ]);
        foreach ($accountIds as $accountId) {
            $post->targets()->create(['social_account_id' => $accountId, 'status' => 'pending']);
        }

        Audit::log('post.created', 'organization', $organization->id, 'post', $post->id, null, ['scheduled' => (bool) $scheduledAt]);

        return back()->with('status', $scheduledAt ? 'Post agendado.' : 'Post salvo como rascunho.');
    }

    public function publishNow(Organization $organization, Post $post)
    {
        $this->authorize($organization);
        abort_unless($post->organization_id === $organization->id, 404);

        SocialPublisher::publish($post);
        Audit::log('post.publish_now', 'organization', $organization->id, 'post', $post->id);

        return back()->with('status', 'Publicação processada.');
    }

    public function destroyPost(Organization $organization, Post $post)
    {
        $this->authorize($organization);
        abort_unless($post->organization_id === $organization->id, 404);

        $postId = $post->id;
        $post->delete();
        Audit::log('post.deleted', 'organization', $organization->id, 'post', $postId);

        return back()->with('status', 'Post removido.');
    }
}
