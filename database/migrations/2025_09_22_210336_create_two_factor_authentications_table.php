<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('two_factor_authentications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('secret_key');
            $table->json('recovery_codes')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('backup_phone')->nullable();
            $table->enum('status', ['active', 'disabled', 'suspended'])->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->string('device_name')->nullable();
            $table->string('device_type')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_authentications');
    }
};
