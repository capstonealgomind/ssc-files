<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registration_attempts')) {
            return;
        }

        Schema::create('registration_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('device_fingerprint', 64)->index();
            $table->string('ip_address', 45);
            $table->string('action', 50); // register, otp_failed, otp_success
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_attempts');
    }
};
