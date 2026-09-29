<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class PlatformMail
{
    public static function configured(): bool
    {
        return Setting::get('smtp_host')
            && Setting::get('smtp_username')
            && Setting::get('smtp_password');
    }

    public static function send(string $to, string $subject, string $html): void
    {
        if (! self::configured()) {
            throw new \RuntimeException('SMTP da plataforma não configurado.');
        }

        $from = (string) Setting::get('smtp_username');
        $fromName = (string) Setting::get('smtp_from_name', 'QEAI One');

        Config::set('mail.mailers.platform_smtp', [
            'transport' => 'smtp',
            'host' => Setting::get('smtp_host'),
            'port' => (int) Setting::get('smtp_port', '465'),
            'encryption' => Setting::get('smtp_encryption', 'ssl'),
            'username' => Setting::get('smtp_username'),
            'password' => Setting::get('smtp_password'),
            'timeout' => null,
            'local_domain' => null,
        ]);

        Mail::mailer('platform_smtp')->html($html, function ($message) use ($to, $subject, $from, $fromName) {
            $message->to($to)->subject($subject)->from($from, $fromName);
        });
    }
}
