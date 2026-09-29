<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->default('platform');
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->string('name');
            $table->text('value')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->timestamps();
            $table->unique(['scope', 'scope_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
