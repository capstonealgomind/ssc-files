<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $index = 'registration_attempts_device_fingerprint_ip_address_unique';

        if (Schema::hasIndex('registration_attempts', $index, 'unique')) {
            Schema::table('registration_attempts', function (Blueprint $table) use ($index) {
                $table->dropUnique($index);
            });
        }
    }

    public function down(): void
    {
        // Repeated attempts are intentional audit records.
    }
};
