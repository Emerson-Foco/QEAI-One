<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdAccount extends Model
{
    protected $fillable = ['organization_id', 'provider', 'name', 'external_account_id', 'token', 'secrets', 'is_active'];

    protected function casts(): array
    {
        return ['secrets' => 'encrypted', 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }

    public static function providers(): array
    {
        return ['meta' => 'Meta Ads', 'google' => 'Google Ads', 'tiktok' => 'TikTok Ads', 'other' => 'Outro'];
    }
}
