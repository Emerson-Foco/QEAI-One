<?php

namespace App\Support;

use App\Models\OrganizationInvite;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PlatformAlert
{
    /** Avisa os ADMs (e o e-mail de contato) quando um convite é reportado como não reconhecido. */
    public static function inviteReported(OrganizationInvite $invite): void
    {
        $recipients = User::where('is_root_admin', true)->pluck('email')->all();
        if ($contact = Setting::get('contact_email')) {
            $recipients[] = $contact;
        }
        $recipients = array_values(array_unique(array_filter($recipients)));

        if (! PlatformMail::configured() || $recipients === []) {
            return;
        }

        $html = view('emails.alert_report', [
            'invite' => $invite,
            'organization' => $invite->organization?->name,
        ])->render();

        foreach ($recipients as $to) {
            try {
                PlatformMail::send($to, 'Alerta: convite reportado como não reconhecido', $html);
            } catch (\Throwable $exception) {
                Log::warning('[QEAI alert] ' . $exception->getMessage());
            }
        }
    }
}
