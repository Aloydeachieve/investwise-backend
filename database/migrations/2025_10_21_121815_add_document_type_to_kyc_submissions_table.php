<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add if it doesn't already exist
        if (!Schema::hasColumn('kyc_submissions', 'document_type')) {
            Schema::table('kyc_submissions', function (Blueprint $table) {
                $table->string('document_type', 50)->default('passport')->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kyc_submissions', 'document_type')) {
            Schema::table('kyc_submissions', function (Blueprint $table) {
                $table->dropColumn('document_type');
            });
        }
    }
};
