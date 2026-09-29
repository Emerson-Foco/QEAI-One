<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Support\Audit;
use App\Support\PlatformMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot');
    }

    public function email(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $key = 'password-reset|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Muitas solicitações. Aguarde alguns minutos.']);
        }
        RateLimiter::hit($key, 900);

        $user = User::where('email', mb_strtolower($data['email']))->first();
        if ($user !== null) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            $link = route('password.reset', ['token' => $token, 'email' => $user->email]);
            try {
                PlatformMail::send(
                    $user->email,
                    'Redefinição de senha — ' . Setting::get('company_name', 'QEAI One'),
                    view('emails.password_reset', ['user' => $user, 'link' => $link])->render()
                );
            } catch (\Throwable $exception) {
                Log::warning('[QEAI password reset] ' . $exception->getMessage());
            }

            Audit::log('auth.password_reset_requested', 'platform', null, 'user', $user->id, null, ['email' => $user->email]);
        }

        return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir a senha.');
    }

    public function show(string $token, Request $request)
    {
        return view('auth.reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $data['email'])->first();
        if ($row === null || ! Hash::check($data['token'], (string) $row->token)
            || Carbon::parse($row->created_at)->addMinutes(60)->isPast()) {
            throw ValidationException::withMessages(['email' => 'Link inválido ou expirado. Solicite um novo.']);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->password = $data['password'];
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        Audit::log('auth.password_reset', 'platform', null, 'user', $user->id, null, ['email' => $user->email]);

        return redirect()->route('login')->with('status', 'Senha redefinida. Entre com a nova senha.');
    }
}
