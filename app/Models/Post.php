<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = ['organization_id', 'title', 'body', 'media_url', 'scheduled_at', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(PostTarget::class);
    }
}
