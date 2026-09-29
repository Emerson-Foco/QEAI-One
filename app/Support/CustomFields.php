<?php

namespace App\Support;

use App\Models\CustomField;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomFields
{
    /** Cria os campos padrão de contatos, se ainda não existirem (defaults / template base). */
    public static function ensureDefaults(Organization $organization, string $entity = 'contact'): void
    {
        if ($organization->customFields()->where('entity', $entity)->exists()) {
            return;
        }

        if ($entity === 'contact') {
            $organization->customFields()->createMany([
                ['entity' => 'contact', 'key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Novo', 'Em contato', 'Qualificado', 'Perdido'], 'show_in_list' => true, 'is_system' => true, 'position' => 1],
                ['entity' => 'contact', 'key' => 'temperatura', 'label' => 'Temperatura', 'type' => 'select', 'options' => ['Frio', 'Morno', 'Quente'], 'show_in_list' => true, 'is_system' => true, 'position' => 2],
            ]);
        }
    }

    /** @return Collection<int, CustomField> */
    public static function forEntity(Organization $organization, string $entity = 'contact'): Collection
    {
        self::ensureDefaults($organization, $entity);

        return $organization->customFields()
            ->where('entity', $entity)
            ->orderBy('position')->orderBy('id')
            ->get();
    }

    public static function slug(string $label): string
    {
        return Str::slug($label) ?: 'campo';
    }

    /**
     * Aplica uma definição de campos (template) a uma organização.
     * Não remove campos existentes; só cria os que faltam.
     *
     * @param array<int, array{label:string,type:string,options:?array,required?:bool,show_in_list?:bool}> $definitions
     */
    public static function applyTemplate(Organization $organization, array $definitions, string $entity = 'contact'): int
    {
        $allowedTypes = array_keys(\App\Models\CustomField::types());
        $created = 0;
        $position = (int) $organization->customFields()->where('entity', $entity)->max('position');

        foreach ($definitions as $definition) {
            $label = trim((string) ($definition['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $type = in_array($definition['type'] ?? 'text', $allowedTypes, true) ? $definition['type'] : 'text';
            $base = self::slug($label);
            $key = $base;
            $suffix = 2;
            while ($organization->customFields()->where('entity', $entity)->where('key', $key)->exists()) {
                $key = $base . '-' . $suffix++;
            }

            $organization->customFields()->create([
                'entity' => $entity,
                'key' => $key,
                'label' => mb_substr($label, 0, 120),
                'type' => $type,
                'options' => $type === 'select' ? array_values($definition['options'] ?? []) : null,
                'required' => (bool) ($definition['required'] ?? false),
                'show_in_list' => (bool) ($definition['show_in_list'] ?? false),
                'is_system' => false,
                'position' => ++$position,
            ]);
            $created++;
        }

        return $created;
    }

    /** @param Collection<int, CustomField> $fields */
    public static function rules(Collection $fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $rule = [$field->required ? 'required' : 'nullable'];
            $rule[] = match ($field->type) {
                'number' => 'numeric',
                'date' => 'date',
                'email' => 'email',
                'url' => 'url',
                'boolean' => 'boolean',
                default => 'string',
            };
            if ($field->type === 'select' && ! empty($field->options)) {
                $rule[] = Rule::in($field->options);
            }
            $rules['custom.' . $field->key] = $rule;
        }

        return $rules;
    }

    /** @param Collection<int, CustomField> $fields */
    public static function collect(Request $request, Collection $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $key = $field->key;
            if ($field->type === 'boolean') {
                $values[$key] = $request->boolean('custom.' . $key);
                continue;
            }
            $value = $request->input('custom.' . $key);
            $values[$key] = is_string($value) ? trim($value) : $value;
        }

        return $values;
    }
}
