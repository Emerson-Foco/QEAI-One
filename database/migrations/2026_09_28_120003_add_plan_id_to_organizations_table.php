<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('owner_user_id')->constrained('plans')->nullOnDelete();
        });

        // Organizações existentes passam a usar o plano Free.
        $freeId = DB::table('plans')->where('slug', 'free')->value('id');
        if ($freeId) {
            DB::table('organizations')->whereNull('plan_id')->update(['plan_id' => $freeId]);
        }
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });
    }
};
