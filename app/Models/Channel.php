<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $fillable = ['organization_id', 'type', 'name', 'token', 'config', 'secrets', 'is_active'];

    protected function casts(): array
    {
        return ['config' => 'array', 'secrets' => 'encrypted', 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
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
}
