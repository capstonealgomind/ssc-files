<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_student_records', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50)->unique();
            $table->string('full_name');
            foreach (['department', 'course', 'year_level', 'section', 'status'] as $field) {
                $table->string($field, 150)->index();
            }
            $table->timestamps();
        });

        Schema::create('official_student_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrator_id');
            $table->string('filename');
            $table->json('result');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_student_imports');
        Schema::dropIfExists('official_student_records');
    }
};
