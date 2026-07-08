<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ua_management_settings', function (Blueprint $table) {
            $table->boolean('sound_enabled')->default(true)->after('countdown_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('ua_management_settings', function (Blueprint $table) {
            $table->dropColumn('sound_enabled');
        });
    }
};
