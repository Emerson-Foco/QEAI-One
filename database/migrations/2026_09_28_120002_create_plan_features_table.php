<?php

use App\Support\PlanFeatures;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('limit_value')->nullable();
            $table->timestamps();
            $table->unique(['plan_id', 'feature_key']);
        });

        // Plano Free inicial.
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Plano gratuito para começar.',
            'price_cents' => 0,
            'currency' => 'BRL',
            'interval' => 'month',
            'is_active' => true,
            'is_public' => true,
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (PlanFeatures::freeDefaults() as $key => $config) {
            DB::table('plan_features')->insert([
                'plan_id' => $planId,
                'feature_key' => $key,
                'enabled' => $config['enabled'],
                'limit_value' => $config['limit'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
