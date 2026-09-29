<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->json('fields')->nullable();
            $table->string('success_message', 255)->default('Recebemos seu contato. Obrigado!');
            $table->string('redirect_url', 255)->nullable();
            $table->string('consent_text', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('prefix', 16);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('url', 255);
            $table->string('secret', 64);
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('last_status')->nullable();
            $table->timestamp('last_called_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('forms');
    }
};
