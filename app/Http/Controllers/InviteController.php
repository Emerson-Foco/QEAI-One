<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Support\Audit;
use App\Support\OrgAccess;
use App\Support\PlatformMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    public function store(Request $request, Organization $organization)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'role_id' => ['required', 'integer'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'É obrigatório declarar que você tem a autorização e o consentimento desta pessoa para enviar o convite.',
        ]);

        $role = $organization->roles()->findOrFail($data['role_id']);

        // Limites por organização e por IP (antiabuso).
        $organizationKey = 'invite-org-' . $organization->id;
        $ipKey = 'invite-ip-' . $request->ip();
        if (RateLimiter::tooManyAttempts($organizationKey, 20)) {
            return back()->withErrors(['email' => 'Limite de convites desta organização atingido. Tente novamente mais tarde.']);
        }
        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            return back()->withErrors(['email' => 'Limite de convites atingido. Tente novamente mais tarde.']);
        }
        RateLimiter::hit($organizationKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        $rawToken = Str::random(64);
        $invite = $organization->invites()->create([
            'email' => mb_strtolower($data['email']),
            'role_id' => $role->id,
            'token_hash' => hash('sha256', $rawToken),
            'status' => 'pending',
            'consent_confirmed' => true,
            'consent_by' => auth()->id(),
            'consent_at' => now(),
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);

        Audit::log('invite.created', 'organization', $organization->id, 'invite', $invite->id, null, [
            'email' => $invite->email,
            'role' => $role->name,
            'consent_confirmed' => true,
            'consent_by' => auth()->id(),
        ]);

        $acceptUrl = route('invite.show', $rawToken);
        $reportUrl = route('invite.report.form', $rawToken);

        $sent = false;
        try {
            PlatformMail::send(
                $invite->email,
                'Convite para ' . $organization->name . ' no QEAI One',
                view('emails.invite', [
                    'organization' => $organization,
                    'role' => $role,
                    'acceptUrl' => $acceptUrl,
                    'reportUrl' => $reportUrl,
                ])->render()
            );
            $sent = true;
        } catch (\Throwable $exception) {
            Log::warning('[QEAI invite] ' . $exception->getMessage());
        }

        return back()
            ->with('status', $sent
                ? 'Convite enviado para ' . $invite->email . '.'
                : 'Convite criado, mas o e-mail não foi enviado (SMTP não configurado). Copie o link abaixo e envie manualmente.')
            ->with('invite_link', $sent ? null : $acceptUrl);
    }

    public function destroy(Organization $organization, OrganizationInvite $invite)
    {
        OrgAccess::authorize(auth()->user(), $organization, 'org.members');
        abort_unless($invite->organization_id === $organization->id, 404);

        if ($invite->status === 'pending') {
            $invite->update(['status' => 'revoked']);
            Audit::log('invite.revoked', 'organization', $organization->id, 'invite', $invite->id, null, ['email' => $invite->email]);
        }

        return back()->with('status', 'Convite revogado.');
    }
}
