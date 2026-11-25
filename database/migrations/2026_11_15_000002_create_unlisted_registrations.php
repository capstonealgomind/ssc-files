<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unlisted_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50);
            $table->string('name');
            $table->string('email');
            $table->timestamp('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unlisted_registrations');
    }
};
