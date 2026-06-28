<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('registration_attempts', 'action')) {
                $table->string('action', 50)->default('register')->after('ip_address');
            }
            if (!Schema::hasColumn('registration_attempts', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('action')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('registration_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('action');
        });
    }
};
