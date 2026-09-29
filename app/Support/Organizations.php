<?php

namespace App\Support;

use App\Models\FieldTemplate;
use App\Models\Organization;
use App\Models\Pipeline;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Organizations
{
    /** Cria uma organização com papéis, pipeline, campos (template) e plano padrão. */
    public static function provision(string $name, ?string $document = null, ?User $owner = null): Organization
    {
        return DB::transaction(function () use ($name, $document, $owner) {
            $organization = Organization::create([
                'name' => $name,
                'slug' => self::uniqueSlug($name),
                'document' => $document ?: null,
                'country' => 'BR',
                'status' => 'active',
                'owner_user_id' => $owner?->id,
                'plan_id' => Plan::where('slug', 'free')->value('id'),
            ]);

            foreach (OrgPermissions::defaults() as $role) {
                $organization->roles()->create($role);
            }

            Pipeline::ensureDefaultFor($organization);

            $template = FieldTemplate::defaultTemplate();
            if ($template !== null) {
                CustomFields::applyTemplate($organization, $template->fields ?? []);
            } else {
                CustomFields::ensureDefaults($organization, 'contact');
            }

            return $organization;
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'org';
        $slug = $base;
        $suffix = 2;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
