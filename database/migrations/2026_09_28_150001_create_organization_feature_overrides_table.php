<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->boolean('enabled')->nullable();
            $table->unsignedInteger('limit_value')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'feature_key'], 'org_feature_override_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_feature_overrides');
    }
};
