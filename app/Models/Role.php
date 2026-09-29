<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    protected $fillable = ['organization_id', 'name', 'slug', 'permissions', 'is_system'];

    protected function casts(): array
    {
        return ['permissions' => 'array', 'is_system' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function grants(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
