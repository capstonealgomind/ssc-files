<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('is_verified');
        });

        DB::table('users')
            ->where('is_verified', true)
            ->whereNull('verified_at')
            ->update(['verified_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verified_at');
        });
    }
};
