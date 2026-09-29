<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function challenge(Request $request)
    {
        if (! $request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request)
    {
        $userId = $request->session()->get('2fa:user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);

        $key = '2fa|' . $userId . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Muitas tentativas. Aguarde alguns minutos.']);
        }

        $user = User::find($userId);
        if ($user === null || ! $user->two_factor_confirmed_at || ! $user->two_factor_secret) {
            $request->session()->forget(['2fa:user_id', '2fa:remember']);

            return redirect()->route('login');
        }

        if (! Totp::verify((string) $user->two_factor_secret, $data['code'])) {
            RateLimiter::hit($key, 900);
            Audit::log('auth.2fa_failed', 'platform', null, 'user', $user->id, null, ['email' => $user->email]);
            throw ValidationException::withMessages(['code' => 'Código inválido.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('2fa:remember', false);
        $request->session()->forget('2fa:user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        Audit::log('auth.login_2fa', 'platform', null, 'user', $user->id, null, ['email' => $user->email]);

        return redirect()->intended($user->is_root_admin ? route('panel.dashboard') : route('member.dashboard'));
    }
}
