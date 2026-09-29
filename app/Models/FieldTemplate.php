<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'fields', 'is_default', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['fields' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public static function defaultTemplate(): ?FieldTemplate
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }
}
