<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'fraud_review_checks')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('fraud_review_checks')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'fraud_review_checks')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fraud_review_checks');
            });
        }
    }
};
