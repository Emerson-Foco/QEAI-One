<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Form extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'slug', 'fields', 'success_message',
        'redirect_url', 'consent_text', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['fields' => 'array', 'is_active' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Campos disponíveis em formulários (built-in de contato). */
    public static function builtinFields(): array
    {
        return [
            'name' => 'Nome',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'whatsapp' => 'WhatsApp',
            'job_title' => 'Cargo',
            'notes' => 'Mensagem',
        ];
    }
}
