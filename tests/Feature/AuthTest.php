<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Setting;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureInstalled::class);
        $this->configureSmtp();
        Mail::fake();
    }

    private function configureSmtp(): void
    {
        Setting::put('company_name', 'QEAI One');
        Setting::put('smtp_host', 'smtp.exemplo.com');
        Setting::put('smtp_port', '465');
        Setting::put('smtp_encryption', 'ssl');
        Setting::put('smtp_username', 'no-reply@qeai.com.br');
        Setting::put('smtp_password', 'segredo', true);
    }

    private function user(array $attributes = []): User
    {
        $user = new User(array_merge([
            'name' => 'Pessoa',
            'email' => 'pessoa@test.local',
            'password' => Hash::make('password-123'),
        ], $attributes));
        $user->save();

        return $user;
    }

    public function test_password_reset_request_is_generic_and_stores_token(): void
    {
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

        $this->post(route('password.email'), ['email' => 'inexistente@test.local'])
            ->assertSessionHas('status');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = $this->user();
        $raw = 'token-de-teste';
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($raw),
            'created_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'token' => $raw,
            'email' => $user->email,
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('nova-senha-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_reset']);
    }

    public function test_password_reset_rejects_invalid_token(): void
    {
        $user = $this->user();
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('correto'),
            'created_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'token' => 'errado',
            'email' => $user->email,
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password-123', $user->fresh()->password));
    }

    public function test_login_with_two_factor_requires_code(): void
    {
        $user = $this->user(['email' => 'root@test.local']);
        $user->is_root_admin = true;
        $user->two_factor_secret = Totp::generateSecret();
        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password-123'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $this->post(route('two-factor.verify'), ['code' => Totp::code($user->two_factor_secret)])
            ->assertRedirect(route('panel.dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_login_without_two_factor_logs_in_directly(): void
    {
        $user = $this->user(['email' => 'root2@test.local']);
        $user->is_root_admin = true;
        $user->save();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password-123'])
            ->assertRedirect(route('panel.dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_email_verification_link_verifies_user(): void
    {
        $user = $this->user(['email' => 'novo@test.local']);

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($link)->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.email_verified']);
    }

    public function test_email_verification_rejects_tampered_hash(): void
    {
        $user = $this->user(['email' => 'novo2@test.local']);

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1('outro@email.com'),
        ]);

        $this->get($link)->assertStatus(403);
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
