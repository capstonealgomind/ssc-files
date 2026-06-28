<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $hexToName = [
        '#6366f1' => 'Indigo',
        '#2563eb' => 'Blue',
        '#0891b2' => 'Cyan',
        '#059669' => 'Green',
        '#d97706' => 'Orange',
        '#dc2626' => 'Red',
        '#9333ea' => 'Purple',
        '#db2777' => 'Pink',
    ];

    public function up(): void
    {
        foreach ($this->hexToName as $hex => $name) {
            DB::table('departments')->where('color', $hex)->update(['color' => $name]);
        }

        DB::table('departments')
            ->where('color', 'like', '#%')
            ->update(['color' => 'Blue']);

        Schema::table('departments', function (Blueprint $table) {
            $table->string('color', 32)->default('Blue')->change();
        });
    }

    public function down(): void
    {
        foreach ($this->hexToName as $hex => $name) {
            DB::table('departments')->where('color', $name)->update(['color' => $hex]);
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->string('color', 7)->default('#6366f1')->change();
        });
    }
};
