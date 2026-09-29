<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('source', 30)->default('generic');
            $table->string('token', 64)->unique();
            $table->text('secret')->nullable();
            $table->string('meta_verify_token', 64)->nullable();
            $table->text('meta_page_access_token')->nullable();
            $table->text('meta_app_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_endpoints');
    }
};
