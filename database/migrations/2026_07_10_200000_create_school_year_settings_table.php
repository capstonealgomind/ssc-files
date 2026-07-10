<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_year_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->timestamps();
        });

        $now = now();
        $startYear = $now->month >= 6 ? $now->year : $now->year - 1;
        $endYear = $startYear + 1;

        DB::table('school_year_settings')->insert([
            'start_year' => $startYear,
            'end_year' => $endYear,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('school_year_settings');
    }
};
