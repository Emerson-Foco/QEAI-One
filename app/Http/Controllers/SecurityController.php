<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Audit;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        return view('security.index', [
            'user' => auth()->user(),
            'pendingSecret' => $request->session()->get('two_factor_secret'),
            'pendingUri' => $request->session()->get('two_factor_uri'),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'A senha atual está incorreta.']);
        }

        $user->password = $data['password'];
        $user->save();
        $request->session()->regenerate();
        Audit::log('auth.password_changed', 'platform', null, 'user', $user->id);

        return back()->with('status', 'Senha atualizada.');
    }

    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();
        if (! $user->two_factor_secret) {
            $user->two_factor_secret = Totp::generateSecret();
            $user->save();
        }

        $uri = Totp::provisioningUri((string) $user->two_factor_secret, $user->email, (string) Setting::get('company_name', 'QEAI One'));
        Audit::log('auth.2fa_started', 'platform', null, 'user', $user->id);

        return redirect()->route('panel.security.index')
            ->with('two_factor_secret', $user->two_factor_secret)
            ->with('two_factor_uri', $uri);
    }

    public function confirmTwoFactor(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $user = $request->user();

        if (! $user->two_factor_secret || ! Totp::verify((string) $user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Código inválido. Confira o app autenticador.']);
        }

        $user->two_factor_confirmed_at = now();
        $user->save();
        Audit::log('auth.2fa_enabled', 'platform', null, 'user', $user->id);

        return redirect()->route('panel.security.index')->with('status', 'Verificação em duas etapas ativada.');
    }

    public function disableTwoFactor(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'string']]);
        $user = $request->user();

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Senha atual incorreta.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
        Audit::log('auth.2fa_disabled', 'platform', null, 'user', $user->id);

        return redirect()->route('panel.security.index')->with('status', 'Verificação em duas etapas desativada.');
    }
}
