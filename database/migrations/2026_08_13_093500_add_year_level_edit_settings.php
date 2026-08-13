<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_year_settings', function (Blueprint $table) {
            $table->boolean('allow_year_level_edit')->default(false)->after('end_year');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('year_level_updated_school_year_start')
                ->nullable()
                ->after('year_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('school_year_settings', function (Blueprint $table) {
            $table->dropColumn('allow_year_level_edit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('year_level_updated_school_year_start');
        });
    }
};
