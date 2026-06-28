<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_id_number')->nullable()->unique()->after('role');
            $table->string('course')->nullable()->after('student_id_number');
            $table->string('year_level')->nullable()->after('course');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['student_id_number', 'course', 'year_level']);
        });
    }
};
