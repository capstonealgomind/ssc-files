<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'account_expires_at')) {
                $table->timestamp('account_expires_at')->nullable()->after('registration_status');
            }
        });

        Schema::create('reactivation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('voter_id_number')->index();
            $table->string('full_name');
            $table->string('year_stopped');
            $table->text('reason');
            $table->string('reactivation_number')->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('duration_years_added')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactivation_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'account_expires_at')) {
                $table->dropColumn('account_expires_at');
            }
        });
    }
};
