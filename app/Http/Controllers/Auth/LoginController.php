<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->intended($this->homeFor(Auth::user()));
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = mb_strtolower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Muitas tentativas. Tente novamente em ' . RateLimiter::availableIn($key) . ' segundos.',
            ]);
        }

        $user = User::where('email', mb_strtolower($data['email']))->first();
        if ($user === null || ! Hash::check($data['password'], (string) $user->password)) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages(['email' => 'E-mail ou senha incorretos.']);
        }

        RateLimiter::clear($key);

        if ($user->two_factor_confirmed_at) {
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        Audit::log('auth.login', 'platform', null, 'user', $user->id, null, ['email' => $user->email]);

        return redirect()->intended($this->homeFor($user));
    }

    private function homeFor(User $user): string
    {
        return $user->is_root_admin ? route('panel.dashboard') : route('member.dashboard');
    }

    public function destroy(Request $request)
    {
        Audit::log('auth.logout', 'platform', null, 'user', $request->user()?->id);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
