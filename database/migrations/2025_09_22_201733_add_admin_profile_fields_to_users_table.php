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
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('telegram')->nullable()->after('phone');
            $table->date('dob')->nullable()->after('telegram');
            $table->text('address')->nullable()->after('dob');
            $table->string('avatar')->nullable()->after('address');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->string('two_factor_secret')->nullable()->after('last_login_ip');
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            $table->json('two_factor_recovery_codes')->nullable()->after('two_factor_enabled');
            $table->string('new_email')->nullable()->after('two_factor_recovery_codes');
            $table->string('email_verification_token')->nullable()->after('new_email');
            $table->timestamp('email_verification_expires')->nullable()->after('email_verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'phone',
                'telegram',
                'dob',
                'address',
                'avatar',
                'last_login_at',
                'last_login_ip',
                'two_factor_secret',
                'two_factor_enabled',
                'two_factor_recovery_codes',
                'new_email',
                'email_verification_token',
                'email_verification_expires'
            ]);
        });
    }
};
