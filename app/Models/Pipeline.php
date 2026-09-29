<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    protected $fillable = ['organization_id', 'name', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /** Garante um pipeline padrão com etapas para a organização. */
    public static function ensureDefaultFor(Organization $organization): Pipeline
    {
        $existing = $organization->pipelines()->where('is_default', true)->first();
        if ($existing !== null) {
            return $existing;
        }

        $pipeline = $organization->pipelines()->create(['name' => 'Pipeline padrão', 'is_default' => true]);
        foreach (['Novo', 'Em contato', 'Proposta'] as $position => $name) {
            $pipeline->stages()->create(['name' => $name, 'position' => $position]);
        }
        $pipeline->stages()->create(['name' => 'Ganho', 'position' => 3, 'is_won' => true]);
        $pipeline->stages()->create(['name' => 'Perdido', 'position' => 4, 'is_lost' => true]);

        return $pipeline;
    }
}
