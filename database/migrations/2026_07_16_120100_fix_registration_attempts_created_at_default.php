<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registration_attempts')) {
            return;
        }

        // Prefer the related user's registration time when created_at was never stored.
        $nullAttempts = DB::table('registration_attempts')
            ->whereNull('created_at')
            ->get(['id', 'user_id']);

        foreach ($nullAttempts as $attempt) {
            $fallback = null;

            if ($attempt->user_id) {
                $fallback = DB::table('users')
                    ->where('id', $attempt->user_id)
                    ->value('created_at');
            }

            DB::table('registration_attempts')
                ->where('id', $attempt->id)
                ->update(['created_at' => $fallback ?? now()]);
        }

        // Hostinger/MariaDB often lacks DEFAULT CURRENT_TIMESTAMP; enforce it.
        DB::statement('ALTER TABLE registration_attempts MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        // Keep the production-safe default in place.
    }
};
