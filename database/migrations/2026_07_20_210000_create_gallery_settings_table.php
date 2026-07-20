<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_settings', function (Blueprint $table) {
            $table->id();
            $table->string('style', 32)->default('dome');
            $table->timestamps();
        });

        DB::table('gallery_settings')->insert([
            'style' => 'dome',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_settings');
    }
};
