<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Notificações in-app (sino) e por e-mail. Envia para os membros que têm a
 * permissão relevante na organização.
 */
class Notify
{
    /** @return array<int, int> ids de usuários com a permissão na organização */
    public static function usersWithPermission(Organization $organization, string $permission): array
    {
        return Membership::with('role')
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->get()
            ->filter(function (Membership $membership) use ($permission) {
                $permissions = $membership->role?->permissions ?? [];
                return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
            })
            ->pluck('user_id')->unique()->values()->all();
    }

    /** @param array<int, int> $userIds */
    public static function send(array $userIds, string $type, string $title, string $body = '', ?string $url = null, ?Organization $organization = null, bool $email = true): void
    {
        foreach (array_unique($userIds) as $userId) {
            Notification::create([
                'organization_id' => $organization?->id,
                'user_id' => $userId,
                'type' => $type,
                'title' => mb_substr($title, 0, 180),
                'body' => mb_substr($body, 0, 500),
                'url' => $url,
            ]);

            if ($email) {
                self::email($userId, $title, $body, $url);
            }
        }
    }

    public static function leadCreated(Organization $organization, Contact $contact): void
    {
        $users = self::usersWithPermission($organization, 'org.leads');
        self::send(
            $users,
            'lead.created',
            'Novo lead: ' . $contact->name,
            trim(($contact->email ? $contact->email . ' · ' : '') . ($contact->source ?: '')),
            route('member.org.contacts.index', $organization),
            $organization
        );
    }

    public static function chatMessage(Organization $organization, Conversation $conversation, string $body): void
    {
        $users = self::usersWithPermission($organization, 'org.conversations');
        self::send(
            $users,
            'conversation.message',
            'Nova mensagem de ' . ($conversation->contact?->name ?? 'Visitante'),
            $body,
            route('member.org.inbox.show', [$organization, $conversation]),
            $organization
        );
    }

    public static function taskDue(Task $task): void
    {
        $organization = $task->organization;
        $users = $task->assigned_to ? [$task->assigned_to] : self::usersWithPermission($organization, 'org.leads');
        self::send($users, 'task.due', 'Tarefa vencendo: ' . $task->title, 'Vencimento: ' . ($task->due_at?->format('d/m/Y H:i') ?? ''), route('member.org.tasks.index', $organization), $organization);
    }

    private static function email(int $userId, string $title, string $body, ?string $url): void
    {
        if (! PlatformMail::configured()) {
            return;
        }
        $user = User::find($userId);
        if ($user === null) {
            return;
        }

        try {
            $html = view('emails.notification', ['user' => $user, 'title' => $title, 'body' => $body, 'url' => $url])->render();
            PlatformMail::send($user->email, $title, $html);
        } catch (\Throwable $exception) {
            Log::warning('[QEAI notify] ' . $exception->getMessage());
        }
    }
}
