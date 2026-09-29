<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationFeatureOverride extends Model
{
    protected $fillable = ['organization_id', 'feature_key', 'enabled', 'limit_value'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
