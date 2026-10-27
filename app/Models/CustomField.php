<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomField extends Model
{
    protected $fillable = [
        'organization_id', 'entity', 'field_id', 'key', 'label', 'type', 'options',
        'required', 'show_in_list', 'is_system', 'is_active', 'position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'show_in_list' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CustomField $field) {
            if (empty($field->field_id)) {
                $field->field_id = self::generateFieldId();
            }
        });
    }

    public static function generateFieldId(): string
    {
        do {
            $fieldId = 'FLD-'.strtoupper((string) Str::ulid());
        } while (static::where('field_id', $fieldId)->exists());

        return $fieldId;
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
