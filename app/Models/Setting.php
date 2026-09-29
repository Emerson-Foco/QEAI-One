<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['scope', 'scope_id', 'name', 'value', 'encrypted'];

    protected $casts = ['encrypted' => 'boolean'];

    /** Igual a get(), mas nunca lança (útil em layouts antes do banco estar pronto). */
    public static function safe(string $name, ?string $default = null): ?string
    {
        try {
            return static::get($name, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function get(string $name, ?string $default = null, string $scope = 'platform', int $scopeId = 0): ?string
    {
        $setting = static::query()->where('scope', $scope)->where('scope_id', $scopeId)->where('name', $name)->first();
        if ($setting === null) {
            return $default;
        }
        if ($setting->encrypted && $setting->value !== null && $setting->value !== '') {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Throwable) {
                return $default;
            }
        }
        return $setting->value;
    }

    public static function put(string $name, ?string $value, bool $encrypted = false, string $scope = 'platform', int $scopeId = 0): void
    {
        $stored = ($encrypted && $value !== null && $value !== '') ? Crypt::encryptString($value) : $value;
        static::query()->updateOrCreate(
            ['scope' => $scope, 'scope_id' => $scopeId, 'name' => $name],
            ['value' => $stored, 'encrypted' => $encrypted]
        );
    }
}
