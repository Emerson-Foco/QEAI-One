<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('provider', 20)->default('meta'); // meta | google | tiktok | other
            $table->string('name', 120);
            $table->string('external_account_id', 120)->nullable();
            $table->string('token', 64)->unique();
            $table->text('secrets')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ad_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('ad_account_id')->constrained('ad_accounts')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('spend_cents')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);
            $table->timestamps();
            $table->unique(['ad_account_id', 'date'], 'ad_metric_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_metrics');
        Schema::dropIfExists('ad_accounts');
    }
};
