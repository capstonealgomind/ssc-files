<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_year_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('school_year_settings', 'year_level_edit_starts_at')) {
                $table->timestamp('year_level_edit_starts_at')->nullable()->after('allow_year_level_edit');
            }
            if (! Schema::hasColumn('school_year_settings', 'year_level_edit_ends_at')) {
                $table->timestamp('year_level_edit_ends_at')->nullable()->after('year_level_edit_starts_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_disabled')) {
                $table->boolean('is_disabled')->default(false)->after('is_expired');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_year_settings', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('school_year_settings', 'year_level_edit_starts_at') ? 'year_level_edit_starts_at' : null,
                Schema::hasColumn('school_year_settings', 'year_level_edit_ends_at') ? 'year_level_edit_ends_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_disabled')) {
                $table->dropColumn('is_disabled');
            }
        });
    }
};
