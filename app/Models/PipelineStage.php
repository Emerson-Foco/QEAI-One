<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    protected $fillable = ['pipeline_id', 'name', 'position', 'is_won', 'is_lost'];

    protected function casts(): array
    {
        return ['is_won' => 'boolean', 'is_lost' => 'boolean'];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }
}
