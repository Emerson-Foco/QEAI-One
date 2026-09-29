<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Support\Audit;
use App\Support\PlatformMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->email), $hash)) {
            abort(403);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
            Audit::log('auth.email_verified', 'platform', null, 'user', $user->id, null, ['email' => $user->email]);
        }

        $target = $user->is_root_admin ? 'panel.dashboard' : 'member.dashboard';

        return redirect()->route(auth()->check() ? $target : 'login')->with('status', 'E-mail verificado com sucesso.');
    }

    public function send(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('login');
        }
        if ($user->email_verified_at !== null) {
            return back()->with('status', 'Seu e-mail já está verificado.');
        }

        $key = 'verify-email|' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Aguarde alguns minutos para reenviar.']);
        }
        RateLimiter::hit($key, 600);

        $link = URL::temporarySignedRoute('verification.verify', now()->addHours(24), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        try {
            PlatformMail::send(
                $user->email,
                'Confirme seu e-mail — ' . Setting::get('company_name', 'QEAI One'),
                view('emails.verify_email', ['user' => $user, 'link' => $link])->render()
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['email' => 'Não foi possível enviar o e-mail: ' . $exception->getMessage()]);
        }

        Audit::log('auth.verification_sent', 'platform', null, 'user', $user->id);

        return back()->with('status', 'Enviamos um link de verificação para ' . $user->email . '.');
    }
}
