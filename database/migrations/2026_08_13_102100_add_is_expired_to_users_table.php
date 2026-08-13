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
            if (! Schema::hasColumn('users', 'is_expired')) {
                $table->boolean('is_expired')->default(false)->after('account_expires_at');
            }
        });

        DB::table('users')
            ->where('role', 'voter')
            ->where(function ($query) {
                $query->where('registration_status', 'expired')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('account_expires_at')
                            ->where('account_expires_at', '<=', now());
                    });
            })
            ->update(['is_expired' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_expired')) {
                $table->dropColumn('is_expired');
            }
        });
    }
};
