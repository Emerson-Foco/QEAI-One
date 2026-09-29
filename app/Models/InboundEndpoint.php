<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundEndpoint extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'source', 'token', 'secret',
        'meta_verify_token', 'meta_page_access_token', 'meta_app_secret',
        'is_active', 'last_received_at',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'meta_page_access_token' => 'encrypted',
            'meta_app_secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_received_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
