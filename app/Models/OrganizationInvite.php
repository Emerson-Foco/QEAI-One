<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvite extends Model
{
    protected $fillable = [
        'organization_id', 'role_id', 'email', 'token_hash', 'status',
        'consent_confirmed', 'consent_by', 'consent_at', 'invited_by',
        'expires_at', 'accepted_at', 'reported_at', 'report_note',
    ];

    protected function casts(): array
    {
        return [
            'consent_confirmed' => 'boolean',
            'consent_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isValid(): bool
    {
        return $this->status === 'pending' && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
