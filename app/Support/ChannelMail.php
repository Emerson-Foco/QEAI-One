<?php

namespace App\Support;

use App\Models\Channel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/** Envia e-mail usando o SMTP configurado no próprio canal da organização. */
class ChannelMail
{
    public static function send(Channel $channel, string $to, string $subject, string $body): void
    {
        $config = $channel->config ?? [];
        $password = (string) ($channel->secret('password') ?? '');

        if (empty($config['host']) || empty($config['username']) || $password === '') {
            throw new \RuntimeException('Canal de e-mail sem SMTP configurado.');
        }

        Config::set('mail.mailers.channel', [
            'transport' => 'smtp',
            'host' => $config['host'],
            'port' => (int) ($config['port'] ?? 587),
            'encryption' => $config['encryption'] ?? 'tls',
            'username' => $config['username'],
            'password' => $password,
            'timeout' => null,
            'local_domain' => null,
        ]);

        $from = $config['from_email'] ?? $config['username'];
        $fromName = $config['from_name'] ?? 'Atendimento';

        Mail::mailer('channel')->html(nl2br(e($body)), function ($message) use ($to, $subject, $from, $fromName) {
            $message->to($to)->subject($subject)->from($from, $fromName);
        });
    }
}
