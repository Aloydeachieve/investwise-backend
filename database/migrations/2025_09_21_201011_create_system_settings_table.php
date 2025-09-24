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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'min_deposit_amount', 'max_withdrawal_amount', 'referral_bonus_rate'
            $table->text('value')->nullable(); // JSON or string value
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false); // Whether this setting can be accessed by non-admin users
            $table->timestamps();

            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
