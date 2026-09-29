<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    protected $fillable = [
        'organization_id', 'pipeline_id', 'stage_id', 'contact_id', 'company_id', 'title',
        'value_cents', 'currency', 'status', 'owner_user_id', 'expected_close_at', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['expected_close_at' => 'date'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function valueFormatted(): string
    {
        return 'R$ ' . number_format($this->value_cents / 100, 2, ',', '.');
    }
}
