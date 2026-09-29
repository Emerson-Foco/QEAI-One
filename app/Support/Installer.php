<?php

namespace App\Support;

class Installer
{
    public static function markerPath(): string
    {
        return storage_path('app/installed.json');
    }

    public static function installed(): bool
    {
        return is_file(self::markerPath());
    }

    public static function markInstalled(): void
    {
        if (! is_dir(dirname(self::markerPath()))) {
            @mkdir(dirname(self::markerPath()), 0775, true);
        }
        file_put_contents(self::markerPath(), json_encode(['at' => date(DATE_ATOM)], JSON_PRETTY_PRINT));
    }

    /** @return array<int, array{label:string, ok:bool, detail:string}> */
    public static function requirements(): array
    {
        $extensions = ['mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'curl'];
        $checks = [
            ['label' => 'PHP 8.2 ou superior', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'detail' => PHP_VERSION],
        ];
        foreach ($extensions as $extension) {
            $checks[] = ['label' => 'Extensão ' . $extension, 'ok' => extension_loaded($extension), 'detail' => extension_loaded($extension) ? 'ok' : 'ausente'];
        }
        $checks[] = ['label' => 'storage/ gravável', 'ok' => is_writable(storage_path()), 'detail' => storage_path()];
        $checks[] = ['label' => 'bootstrap/cache/ gravável', 'ok' => is_writable(base_path('bootstrap/cache')), 'detail' => base_path('bootstrap/cache')];

        return $checks;
    }

    public static function requirementsOk(): bool
    {
        foreach (self::requirements() as $check) {
            if (! $check['ok']) {
                return false;
            }
        }
        return true;
    }
}
