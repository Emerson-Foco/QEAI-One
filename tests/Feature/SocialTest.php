<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Organization;
use App\Models\Post;
use App\Models\User;
use App\Support\CustomFields;
use App\Support\OrgPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SocialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
    }

    private function user(string $email): User
    {
        return User::create(['name' => Str::headline(Str::before($email, '@')), 'email' => $email, 'password' => 'senha-original-123']);
    }

    private function organization(string $name): Organization
    {
        $organization = Organization::create(['name' => $name, 'slug' => Str::slug($name), 'status' => 'active']);
        foreach (OrgPermissions::defaults() as $role) {
            $organization->roles()->create($role);
        }
        CustomFields::ensureDefaults($organization, 'contact');

        return $organization;
    }

    private function addMember(Organization $organization, User $user, string $roleSlug): void
    {
        $role = $organization->roles()->where('slug', $roleSlug)->firstOrFail();
        $organization->memberships()->create(['user_id' => $user->id, 'role_id' => $role->id, 'status' => 'active', 'joined_at' => now()]);
    }

    public function test_scheduled_post_is_published_via_webhook(): void
    {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->post(route('member.org.social.accounts.store', $organization), [
            'name' => 'N8N', 'network' => 'webhook', 'webhook_url' => 'https://example.com/publish',
        ])->assertRedirect();
        $account = $organization->socialAccounts()->firstOrFail();

        $this->actingAs($admin)->post(route('member.org.social.posts.store', $organization), [
            'title' => 'Post de teste', 'body' => 'Conteúdo', 'targets' => [$account->id],
            'scheduled_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $post = Post::firstOrFail();
        $this->assertSame('scheduled', $post->status);

        Artisan::call('posts:publish-due');

        $this->assertSame('published', $post->fresh()->status);
        $this->assertSame('published', $post->targets()->firstOrFail()->status);
        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/publish');
    }

    public function test_facebook_page_publish_uses_graph_api(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '123_456'], 200)]);
        $admin = $this->user('admin@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $admin, 'admin');

        $this->actingAs($admin)->post(route('member.org.social.accounts.store', $organization), [
            'name' => 'Página', 'network' => 'facebook_page', 'page_id' => '999', 'access_token' => 'tok',
        ])->assertRedirect();
        $account = $organization->socialAccounts()->firstOrFail();

        $this->actingAs($admin)->post(route('member.org.social.posts.store', $organization), [
            'title' => 'Post FB', 'body' => 'Olá Facebook', 'targets' => [$account->id],
        ])->assertRedirect();

        $post = Post::firstOrFail();
        $this->actingAs($admin)->post(route('member.org.social.posts.publish', [$organization, $post]))->assertRedirect();

        $this->assertSame('published', $post->fresh()->status);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com/v21.0/999/feed'));
    }

    public function test_agent_without_social_permission_is_forbidden(): void
    {
        $agent = $this->user('agente@empresa.com');
        $organization = $this->organization('Acme');
        $this->addMember($organization, $agent, 'agent');

        $this->actingAs($agent)->get(route('member.org.social.index', $organization))->assertForbidden();
    }
}
