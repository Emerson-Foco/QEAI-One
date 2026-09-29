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
