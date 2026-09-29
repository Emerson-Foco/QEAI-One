<?php

namespace App\Support;

use App\Models\AuditLog;

class Audit
{
    /**
     * Registra uma ação de forma encadeada (hash_prev + hash_self) para permitir
     * verificação de integridade da trilha de auditoria.
     */
    public static function log(
        string $action,
        string $scope = 'platform',
        ?int $organizationId = null,
        string $entityType = '',
        ?int $entityId = null,
        ?array $before = null,
        ?array $after = null,
        string $actorType = 'user',
        ?int $actorUserId = null,
    ): AuditLog {
        $previous = AuditLog::query()
            ->where('scope', $scope)
            ->where('organization_id', $organizationId)
            ->latest('id')
            ->value('hash_self');
        $hashPrev = $previous ?: str_repeat('0', 64);

        $payload = json_encode([
            'action' => $action,
            'scope' => $scope,
            'organization_id' => $organizationId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before' => $before,
            'after' => $after,
            'at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return AuditLog::create([
            'scope' => $scope,
            'organization_id' => $organizationId,
            'actor_type' => $actorType,
            'actor_user_id' => $actorUserId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before' => $before,
            'after' => $after,
            'ip' => (string) request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 300),
            'hash_prev' => $hashPrev,
            'hash_self' => hash('sha256', $hashPrev . '|' . $payload),
        ]);
    }
}
