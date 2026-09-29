<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'scope', 'organization_id', 'actor_type', 'actor_user_id', 'action',
        'entity_type', 'entity_id', 'before', 'after', 'ip', 'user_agent', 'hash_prev', 'hash_self',
    ];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }
}
