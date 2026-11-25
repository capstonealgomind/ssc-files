<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('official_student_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('official_student_imports');
            $table->unsignedInteger('row_number')->nullable();
            $table->string('category', 20);
            $table->json('details');
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->index(['batch_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_student_import_rows');
    }
};
