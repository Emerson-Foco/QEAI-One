<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Audit;

class TermsAcceptanceController extends Controller
{
    public function show()
    {
        return view('auth.terms');
    }

    public function store()
    {
        $user = auth()->user();
        $user->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => (string) config('legal.terms_version'),
        ])->save();

        Audit::log('auth.terms_accepted', 'platform', null, 'user', $user->id, null, [
            'terms_version' => (string) config('legal.terms_version'),
        ]);

        return redirect()->intended($user->is_root_admin ? route('panel.dashboard') : route('member.dashboard'))
            ->with('status', 'Termos aceitos. Bem-vindo(a) ao Q One.');
    }
}
