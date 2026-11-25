<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unlisted_registrations', function (Blueprint $table) {
            $table->string('reason')->nullable();
        });

        DB::table('unlisted_registrations')
            ->whereNull('reason')
            ->update(['reason' => 'Student ID not found on official list']);
    }

    public function down(): void
    {
        Schema::table('unlisted_registrations', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
