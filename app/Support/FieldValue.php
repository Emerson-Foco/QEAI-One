<?php

namespace App\Support;

use App\Models\CustomField;
use Illuminate\Support\Carbon;

class FieldValue
{
    public static function display(CustomField $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '—';
        }

        try {
            return match ($field->type) {
                'date' => Carbon::parse((string) $value)->format('d/m/Y'),
                'datetime' => Carbon::parse((string) $value)->format('d/m/Y H:i'),
                'time' => self::time((string) $value),
                'boolean' => $value ? 'Sim' : 'Não',
                default => (string) $value,
            };
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public static function input(CustomField $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return match ($field->type) {
                'date' => Carbon::parse((string) $value)->format('Y-m-d'),
                'datetime' => Carbon::parse((string) $value)->format('Y-m-d\TH:i'),
                'time' => self::time((string) $value),
                default => (string) $value,
            };
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public static function isDateLike(string $type): bool
    {
        return in_array($type, ['date', 'datetime'], true);
    }

    public static function safeUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $url = trim($value);

        return preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    private static function time(string $value): string
    {
        if (preg_match('/^\d{2}:\d{2}/', $value) === 1) {
            return substr($value, 0, 5);
        }

        return Carbon::parse($value)->format('H:i');
    }
}
