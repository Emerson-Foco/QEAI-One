<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\Organizations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('member.dashboard');
        }

        return view('auth.register');
    }

    public function store(Request $request)
    {
        $key = 'register|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Muitas tentativas. Aguarde alguns minutos.']);
        }
        RateLimiter::hit($key, 900);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:24'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'É necessário aceitar os Termos de Uso e a Política de Privacidade.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => $data['password'],
        ]);

        $organization = Organizations::provision($data['company_name'], $data['document'] ?? null, $user);

        $ownerRole = $organization->roles()->where('slug', 'owner')->firstOrFail();
        $organization->memberships()->create([
            'user_id' => $user->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Audit::log('organization.created', 'platform', null, 'organization', $organization->id, null, ['self_signup' => true, 'owner_user_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('member.dashboard')->with('status', 'Bem-vindo(a)! Sua organização foi criada.');
    }
}
