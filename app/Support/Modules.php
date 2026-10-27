<?php

namespace App\Support;

class Modules
{
    public static function enabled(string $module): bool
    {
        return in_array($module, (array) config('modules.enabled', []), true);
    }

    public static function label(string $module): string
    {
        return (string) config('modules.catalog.'.$module, $module);
    }

    /** @return array<string, string> */
    public static function catalog(): array
    {
        return (array) config('modules.catalog', []);
    }

    /** @return array<int, string> */
    public static function enabledModules(): array
    {
        return (array) config('modules.enabled', []);
    }
}
