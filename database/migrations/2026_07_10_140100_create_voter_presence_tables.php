<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('device', 20)->default('desktop');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->index('last_seen_at');
            $table->index(['device', 'last_seen_at']);
        });

        Schema::create('voter_presence_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('recorded_at')->index();
            $table->unsignedInteger('online_total')->default(0);
            $table->unsignedInteger('online_mobile')->default(0);
            $table->unsignedInteger('online_desktop')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_presence_snapshots');
        Schema::dropIfExists('voter_presences');
    }
};
