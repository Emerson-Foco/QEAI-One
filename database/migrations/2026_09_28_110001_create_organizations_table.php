<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('document_type', 10)->default('cnpj');
            $table->string('document', 24)->nullable();
            $table->string('country', 2)->default('BR');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
