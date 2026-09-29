<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = ['organization_id', 'network', 'name', 'config', 'secrets', 'is_active'];

    protected function casts(): array
    {
        return ['config' => 'array', 'secrets' => 'encrypted', 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function secret(string $key): ?string
    {
        $secrets = $this->secrets ? json_decode($this->secrets, true) : [];
        return $secrets[$key] ?? null;
    }

    public function setSecrets(array $values): void
    {
        $this->secrets = json_encode($values, JSON_UNESCAPED_UNICODE);
    }

    public static function networks(): array
    {
        return [
            'webhook' => 'Webhook (automação)',
            'facebook_page' => 'Página do Facebook',
            'instagram' => 'Instagram (via webhook)',
            'linkedin' => 'LinkedIn (via webhook)',
            'tiktok' => 'TikTok (via webhook)',
        ];
    }
}
