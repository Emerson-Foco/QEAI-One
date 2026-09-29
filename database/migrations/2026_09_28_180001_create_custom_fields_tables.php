<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->json('custom')->nullable()->after('notes');
        });

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('entity', 20)->default('contact');
            $table->string('key', 60);
            $table->string('label', 120);
            $table->string('type', 20)->default('text');
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('show_in_list')->default(false);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'entity', 'key'], 'org_custom_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('custom');
        });
    }
};
