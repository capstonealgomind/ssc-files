<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unlisted_registrations', function (Blueprint $table) {
            foreach (['department', 'course', 'year_level', 'section'] as $field) {
                $table->string($field)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('unlisted_registrations', function (Blueprint $table) {
            $table->dropColumn(['department', 'course', 'year_level', 'section']);
        });
    }
};
