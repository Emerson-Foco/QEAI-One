<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price_cents', 'currency', 'interval', 'is_active', 'is_public', 'sort',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_public' => 'boolean'];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function feature(string $key): ?PlanFeature
    {
        return $this->features->firstWhere('feature_key', $key);
    }

    public function allows(string $key): bool
    {
        return (bool) $this->feature($key)?->enabled;
    }

    public function limit(string $key): ?int
    {
        return $this->feature($key)?->limit_value;
    }

    public function priceFormatted(): string
    {
        return 'R$ ' . number_format($this->price_cents / 100, 2, ',', '.');
    }
}
