<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('voter_id_number', 30)->nullable()->unique()->after('id');
            $table->string('id_image_path')->nullable()->after('year_level_id');
            $table->string('ocr_name')->nullable()->after('id_image_path');
            $table->string('ocr_student_id')->nullable()->after('ocr_name');
            $table->string('ocr_course')->nullable()->after('ocr_student_id');
            $table->integer('fraud_score')->default(0)->after('ocr_course');
            $table->boolean('is_verified')->default(false)->after('fraud_score');
            $table->string('registration_status', 30)->default('active')->after('is_verified');
            $table->string('otp_code', 64)->nullable()->after('registration_status');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->tinyInteger('otp_attempts')->default(0)->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'voter_id_number',
                'id_image_path',
                'ocr_name',
                'ocr_student_id',
                'ocr_course',
                'fraud_score',
                'is_verified',
                'registration_status',
                'otp_code',
                'otp_expires_at',
                'otp_attempts',
            ]);
        });
    }
};
