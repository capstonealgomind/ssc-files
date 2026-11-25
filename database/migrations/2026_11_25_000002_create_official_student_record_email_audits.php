<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_student_record_email_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('official_student_records')->cascadeOnDelete();
            $table->string('old_email')->nullable();
            $table->string('new_email');
            $table->foreignId('administrator_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['record_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_student_record_email_audits');
    }
};
