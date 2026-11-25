<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('location_range_settings');
    }

    public function down(): void
    {
        // The geofencing settings table is intentionally not restored.
    }
};
