<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_student_records', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable()->index();
            $table->string('removal_reason')->nullable();
            $table->unsignedBigInteger('last_import_batch_id')->nullable();
            $table->unsignedBigInteger('last_full_batch_id')->nullable();
            $table->timestamp('restored_at')->nullable();
        });
        Schema::create('official_student_sync_state', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('revision')->default(0);
            $table->unsignedBigInteger('latest_full_batch_id')->nullable();
        });
        DB::table('official_student_sync_state')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('official_student_sync_state');
        Schema::table('official_student_records', function (Blueprint $table) {
            $table->dropIndex(['removed_at']);
            $table->dropColumn(['removed_at', 'removal_reason', 'last_import_batch_id', 'last_full_batch_id', 'restored_at']);
        });
    }
};
