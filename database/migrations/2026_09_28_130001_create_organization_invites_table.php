<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('email', 180);
            $table->string('token_hash', 64)->unique();
            $table->enum('status', ['pending', 'accepted', 'revoked', 'reported'])->default('pending');
            $table->boolean('consent_confirmed')->default(false);
            $table->foreignId('consent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consent_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->string('report_note', 255)->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invites');
    }
};
