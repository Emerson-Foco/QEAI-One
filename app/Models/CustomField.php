<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomField extends Model
{
    protected $fillable = [
        'organization_id', 'entity', 'key', 'label', 'type', 'options',
        'required', 'show_in_list', 'is_system', 'position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'show_in_list' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function types(): array
    {
        return [
            'text' => 'Texto curto',
            'textarea' => 'Texto longo',
            'number' => 'Número',
            'date' => 'Data',
            'select' => 'Seleção',
            'boolean' => 'Sim/Não',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'url' => 'URL',
        ];
    }
}
