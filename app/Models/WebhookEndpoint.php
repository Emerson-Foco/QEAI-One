<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEndpoint extends Model
{
    protected $fillable = ['organization_id', 'url', 'secret', 'events', 'is_active', 'last_status', 'last_called_at'];

    protected function casts(): array
    {
        return ['events' => 'array', 'is_active' => 'boolean', 'last_called_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }
}
