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
            $table->string('email_status', 20)->default('pending')->after('email_verified_at');
            $table->string('ocr_status', 20)->default('pending')->after('fraud_score');
            $table->string('verification_status', 20)->default('pending')->after('ocr_status');
            $table->string('email_verify_token')->nullable()->after('email_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_status', 'ocr_status', 'verification_status', 'email_verify_token']);
        });
    }
};
