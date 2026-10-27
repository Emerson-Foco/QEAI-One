<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('lead_id', 40)->nullable()->after('id');
        });

        DB::table('contacts')->select('id')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('contacts')->where('id', $row->id)->update([
                    'lead_id' => 'LD-'.strtoupper((string) Str::ulid()),
                ]);
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->unique('lead_id', 'contacts_lead_id_unique');
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->string('field_id', 40)->nullable()->after('key');
            $table->boolean('is_active')->default(true)->after('is_system');
        });

        DB::table('custom_fields')->select('id')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('custom_fields')->where('id', $row->id)->update([
                    'field_id' => 'FLD-'.strtoupper((string) Str::ulid()),
                ]);
            }
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->unique('field_id', 'custom_fields_field_id_unique');
        });

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
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropUnique('custom_fields_field_id_unique');
            $table->dropColumn(['field_id', 'is_active']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique('contacts_lead_id_unique');
            $table->dropColumn('lead_id');
        });
    }
};
