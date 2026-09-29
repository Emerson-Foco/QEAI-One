<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\Tag;
use Illuminate\Support\Str;

/**
 * Ponto único de entrada de leads (formulário público, API, manual).
 * Cria o contato, aplica tags e campos personalizados, audita e dispara webhooks.
 */
class LeadIntake
{
    public static function create(Organization $organization, array $data, string $origin = 'manual', array $meta = []): Contact
    {
        $fields = CustomFields::forEntity($organization, 'contact');

        $custom = [];
        foreach ((array) ($data['custom'] ?? []) as $key => $value) {
            $field = $fields->firstWhere('key', $key);
            if ($field === null) {
                continue;
            }
            if (is_string($value)) {
                $value = trim($value);
            }
            if ($field->type === 'select' && $value !== null && $value !== '' && ! in_array($value, $field->options ?? [], true)) {
                continue;
            }
            $custom[$key] = $value;
        }

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'name' => mb_substr(trim((string) ($data['name'] ?? '')) ?: 'Lead', 0, 180),
            'email' => self::text($data['email'] ?? null, 180),
            'phone' => self::text($data['phone'] ?? null, 40),
            'whatsapp' => self::text($data['whatsapp'] ?? null, 40),
            'job_title' => self::text($data['job_title'] ?? null, 120),
            'source' => mb_substr((string) ($data['source'] ?? $origin), 0, 60),
            'notes' => self::text($data['notes'] ?? null, 5000),
            'custom' => $custom ?: null,
            'created_by' => auth()->id(),
        ]);

        $tags = $data['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }
        $ids = [];
        foreach (array_slice((array) $tags, 0, 15) as $name) {
            if ($name === '') {
                continue;
            }
            $tag = Tag::firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => Str::slug((string) $name) ?: 'tag'],
                ['name' => mb_substr((string) $name, 0, 60)]
            );
            $ids[] = $tag->id;
        }
        if ($ids) {
            $contact->tags()->sync($ids);
        }

        Audit::log('lead.created', 'organization', $organization->id, 'contact', $contact->id, null, ['origin' => $origin] + $meta);
        Webhooks::dispatch($organization, 'lead.created', self::payload($contact));
        Notify::leadCreated($organization, $contact);

        return $contact;
    }

    public static function payload(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'whatsapp' => $contact->whatsapp,
            'job_title' => $contact->job_title,
            'source' => $contact->source,
            'custom' => $contact->custom ?: new \stdClass(),
            'created_at' => $contact->created_at?->toIso8601String(),
        ];
    }

    private static function text($value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr(trim((string) $value), 0, $max);
    }
}
