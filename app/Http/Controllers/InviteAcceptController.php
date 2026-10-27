<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvite;
use App\Models\User;
use App\Support\Audit;
use App\Support\PlanResolver;
use App\Support\PlatformAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class InviteAcceptController extends Controller
{
    private function find(string $token): ?OrganizationInvite
    {
        return OrganizationInvite::with(['organization', 'role'])
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    public function show(string $token)
    {
        $invite = $this->find($token);

        if (! $invite || ! $invite->isValid()) {
            return view('invites.invalid', ['invite' => $invite]);
        }

        $existing = User::where('email', $invite->email)->first();

        // Não existe conta: pessoa nova define nome e senha.
        if ($existing === null) {
            return view('invites.accept', ['invite' => $invite, 'token' => $token]);
        }

        // Já existe conta: precisa estar logado com o e-mail convidado.
        if (! Auth::check() || Auth::id() !== $existing->id) {
            session(['url.intended' => route('invite.show', $token)]);

            return view('invites.login', ['invite' => $invite, 'token' => $token, 'logoutMismatch' => Auth::check()]);
        }

        $alreadyMember = $invite->organization->memberships()->where('user_id', $existing->id)->exists();

        return view('invites.confirm', ['invite' => $invite, 'token' => $token, 'alreadyMember' => $alreadyMember]);
    }

    public function accept(Request $request, string $token)
    {
        $invite = $this->find($token);

        if (! $invite || ! $invite->isValid()) {
            return view('invites.invalid', ['invite' => $invite]);
        }

        $existing = User::where('email', $invite->email)->first();
        $alreadyMember = false;

        if ($existing !== null) {
            // Impede aceitar convite em nome de outra pessoa.
            if (! Auth::check() || Auth::id() !== $existing->id) {
                session(['url.intended' => route('invite.show', $token)]);

                return redirect()->route('login')->with('status', 'Entre com a conta deste e-mail para aceitar o convite.');
            }
            $user = $existing;
            $alreadyMember = $invite->organization->memberships()->where('user_id', $user->id)->exists();
        }

        // Limite de usuários do plano/override (não se aplica a quem já é membro).
        if (! $alreadyMember && ! PlanResolver::withinLimit($invite->organization, 'users', 1)) {
            return view('invites.limit', ['invite' => $invite]);
        }

        if ($existing === null) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'password' => ['required', 'string', 'min:10', 'max:72', 'confirmed'],
                'terms' => ['accepted'],
            ], [
                'terms.accepted' => 'É necessário aceitar os Termos de Uso e a Política de Privacidade.',
            ]);
            $user = User::create([
                'name' => $data['name'],
                'email' => $invite->email,
                'password' => $data['password'],
            ]);
            // Recebimento e aceite do convite por e-mail comprovam a posse do endereço.
            $user->forceFill([
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'terms_version' => (string) config('legal.terms_version'),
            ])->save();
        }

        $membership = $invite->organization->memberships()->firstOrCreate(
            ['user_id' => $user->id],
            ['role_id' => $invite->role_id, 'status' => 'active', 'invited_by' => $invite->invited_by, 'joined_at' => now()]
        );
        // Se já era membro, apenas garante o grupo convidado.
        if (! $membership->wasRecentlyCreated) {
            $membership->update(['role_id' => $invite->role_id, 'status' => 'active']);
        }

        $invite->update(['status' => 'accepted', 'accepted_at' => now()]);
        Audit::log('invite.accepted', 'organization', $invite->organization_id, 'invite', $invite->id, null, [
            'email' => $invite->email,
            'user_id' => $user->id,
            'already_member' => ! $membership->wasRecentlyCreated,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('member.dashboard')->with('status', 'Convite aceito. Bem-vindo(a) ao Q One!');
    }

    public function reportForm(string $token)
    {
        return view('invites.report', ['invite' => $this->find($token), 'token' => $token]);
    }

    public function report(Request $request, string $token)
    {
        $key = 'invite-report|'.$token.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return view('invites.reported');
        }
        RateLimiter::hit($key, 3600);

        $invite = $this->find($token);

        if ($invite) {
            $note = (string) $request->input('note', '');
            $invite->update([
                'status' => 'reported',
                'reported_at' => now(),
                'report_note' => mb_substr($note, 0, 255),
            ]);

            Audit::log('invite.reported', 'platform', $invite->organization_id, 'invite', $invite->id, null, [
                'email' => $invite->email,
                'organization' => $invite->organization?->name,
                'note' => $note,
            ]);

            try {
                PlatformAlert::inviteReported($invite);
            } catch (\Throwable $exception) {
                Log::warning('[QEAI alert] '.$exception->getMessage());
            }
        }

        return view('invites.reported');
    }
}
