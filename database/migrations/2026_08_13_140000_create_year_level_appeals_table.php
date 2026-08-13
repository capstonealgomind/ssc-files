<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'year_level_update_override')) {
                $table->boolean('year_level_update_override')->default(false)->after('is_disabled');
            }
        });

        Schema::create('year_level_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('school_year_start')->index();
            $table->text('reason');
            $table->string('status', 30)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'school_year_start', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('year_level_appeals');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'year_level_update_override')) {
                $table->dropColumn('year_level_update_override');
            }
        });
    }
};
