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
            self::setCustomValue($custom, $fields, (string) $key, $value);
        }

        foreach ((array) ($data['fields'] ?? []) as $key => $value) {
            self::setCustomValue($custom, $fields, (string) $key, $value);
        }

        $legacyMap = [
            'name' => 'nome',
            'email' => 'email',
            'phone' => 'telefone',
            'whatsapp' => 'whatsapp',
            'source' => 'origem',
            'notes' => 'observacoes',
            'job_title' => 'cargo',
        ];
        foreach ($legacyMap as $input => $key) {
            if (array_key_exists($input, $data) && ! array_key_exists($key, $custom)) {
                $value = $data[$input];
                if ($value !== null && $value !== '') {
                    $custom[$key] = is_string($value) ? trim($value) : $value;
                }
            }
        }

        if (! array_key_exists('origem', $custom) && $fields->contains('key', 'origem')) {
            $custom['origem'] = $origin;
        }

        $contact = Contact::create([
            'organization_id' => $organization->id,
            'name' => mb_substr(trim((string) (($custom['nome'] ?? null) ?: ($data['name'] ?? null) ?: 'Lead')), 0, 180),
            'email' => self::text($custom['email'] ?? ($data['email'] ?? null), 180),
            'phone' => self::text($custom['telefone'] ?? ($data['phone'] ?? null), 40),
            'whatsapp' => self::text($custom['whatsapp'] ?? ($data['whatsapp'] ?? null), 40),
            'job_title' => self::text($custom['cargo'] ?? ($data['job_title'] ?? null), 120),
            'source' => self::text($custom['origem'] ?? ($data['source'] ?? $origin), 60),
            'notes' => self::text($custom['observacoes'] ?? ($data['notes'] ?? null), 5000),
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

    private static function setCustomValue(array &$custom, $fields, string $key, mixed $value): void
    {
        $field = $fields->first(fn ($candidate) => $candidate->key === $key || $candidate->field_id === $key);
        if ($field === null) {
            return;
        }

        if (is_string($value)) {
            $value = trim($value);
        }
        if ($field->type === 'select' && $value !== null && $value !== '' && ! in_array($value, $field->options ?? [], true)) {
            return;
        }

        $custom[$field->key] = $value;
    }

    public static function payload(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'lead_id' => $contact->lead_id,
            'organization_id' => $contact->organization_id,
            'name' => $contact->displayName(),
            'email' => $contact->email,
            'phone' => $contact->phone,
            'whatsapp' => $contact->whatsapp,
            'job_title' => $contact->job_title,
            'source' => $contact->source,
            'custom' => $contact->custom ?: new \stdClass,
            'fields' => self::fieldsPayload($contact),
            'created_at' => $contact->created_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{field_id:string,key:string,label:string,type:string,value:mixed}> */
    private static function fieldsPayload(Contact $contact): array
    {
        $custom = $contact->custom ?: [];

        return CustomFields::forEntity($contact->organization, 'contact')
            ->map(fn ($field) => [
                'field_id' => $field->field_id,
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->type,
                'value' => $custom[$field->key] ?? null,
            ])
            ->values()
            ->all();
    }

    private static function text($value, int $max): ?string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        return mb_substr(trim((string) $value), 0, $max);
    }
}
