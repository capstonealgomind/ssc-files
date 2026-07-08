<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ua_management_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('idle_seconds')->default(60);
            $table->unsignedSmallInteger('countdown_seconds')->default(10);
            $table->timestamps();
        });

        DB::table('ua_management_settings')->insert([
            'is_enabled'        => true,
            'idle_seconds'      => 60,
            'countdown_seconds' => 10,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ua_management_settings');
    }
};
