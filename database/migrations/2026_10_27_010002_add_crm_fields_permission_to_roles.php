<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->whereIn('slug', ['admin', 'manager'])->orderBy('id')->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $permissions = json_decode((string) $role->permissions, true) ?: [];
                if (! in_array('org.crm_fields', $permissions, true) && ! in_array('*', $permissions, true)) {
                    $permissions[] = 'org.crm_fields';
                    DB::table('roles')->where('id', $role->id)->update([
                        'permissions' => json_encode(array_values($permissions)),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Permissão aditiva: não removemos automaticamente para não quebrar acessos existentes.
    }
};
