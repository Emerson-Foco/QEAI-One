<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\OrganizationFeatureOverride;

/**
 * Resolve recursos e limites de uma organização por precedência:
 * override da organização  >  plano  >  padrão.
 */
class PlanResolver
{
    public static function override(Organization $organization, string $key): ?OrganizationFeatureOverride
    {
        return $organization->featureOverrides->firstWhere('feature_key', $key);
    }

    public static function enabled(Organization $organization, string $key): bool
    {
        $override = self::override($organization, $key);
        if ($override !== null && $override->enabled !== null) {
            return (bool) $override->enabled;
        }

        $feature = $organization->plan?->feature($key);

        return $feature !== null ? (bool) $feature->enabled : false;
    }

    public static function limit(Organization $organization, string $key): ?int
    {
        $override = self::override($organization, $key);
        if ($override !== null && $override->limit_value !== null) {
            return (int) $override->limit_value;
        }

        $feature = $organization->plan?->feature($key);

        return $feature?->limit_value;
    }

    /** Consumo atual do recurso (para comparar com o limite). */
    public static function usage(Organization $organization, string $key): int
    {
        return match ($key) {
            'users' => $organization->memberships()->where('status', 'active')->count(),
            default => 0,
        };
    }

    public static function withinLimit(Organization $organization, string $key, int $additional = 1): bool
    {
        $limit = self::limit($organization, $key);
        if ($limit === null) {
            return true;
        }

        return self::usage($organization, $key) + $additional <= $limit;
    }

    /** @return array<string, array{label:string,type:string,enabled:bool,limit:?int,usage:int,overrideEnabled:?bool,overrideLimit:?int}> */
    public static function summary(Organization $organization): array
    {
        $organization->loadMissing(['plan.features', 'featureOverrides']);
        $rows = [];

        foreach (PlanFeatures::catalog() as $key => $meta) {
            $override = self::override($organization, $key);
            $rows[$key] = [
                'label' => $meta['label'],
                'type' => $meta['type'],
                'enabled' => self::enabled($organization, $key),
                'limit' => self::limit($organization, $key),
                'usage' => self::usage($organization, $key),
                'overrideEnabled' => $override?->enabled,
                'overrideLimit' => $override?->limit_value,
            ];
        }

        return $rows;
    }
}
