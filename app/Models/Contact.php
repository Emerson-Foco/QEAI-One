<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Contact extends Model
{
    protected $fillable = [
        'organization_id', 'company_id', 'name', 'email', 'phone', 'whatsapp',
        'job_title', 'source', 'notes', 'custom', 'owner_user_id', 'created_by',
    ];

    protected function casts(): array
    {
        return ['custom' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            if (empty($contact->lead_id)) {
                $contact->lead_id = self::generateLeadId();
            }
        });
    }

    public static function generateLeadId(): string
    {
        do {
            $leadId = 'LD-'.strtoupper((string) Str::ulid());
        } while (static::where('lead_id', $leadId)->exists());

        return $leadId;
    }

    /** Nome de exibição: campo personalizado "nome" ou fallback para o ID permanente. */
    public function displayName(): string
    {
        $custom = $this->custom ?: [];
        $name = $custom['nome'] ?? null;
        if (is_scalar($name) && trim((string) $name) !== '') {
            return trim((string) $name);
        }

        if (trim((string) $this->name) !== '' && $this->name !== 'Lead') {
            return (string) $this->name;
        }

        return (string) $this->lead_id;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }
}
