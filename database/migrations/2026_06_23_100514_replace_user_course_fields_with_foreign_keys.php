<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('student_id_number')->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('year_level_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['course', 'year_level']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('course')->nullable()->after('student_id_number');
            $table->string('year_level')->nullable()->after('course');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('year_level_id');
        });
    }
};
