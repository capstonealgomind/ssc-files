<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });

        $defaultPositions = [
            ['name' => 'President', 'sort_order' => 1],
            ['name' => 'Vice President', 'sort_order' => 2],
            ['name' => 'Secretary', 'sort_order' => 3],
            ['name' => 'Treasurer', 'sort_order' => 4],
            ['name' => 'Auditor', 'sort_order' => 5],
            ['name' => 'Public Relations Officer', 'sort_order' => 6],
            ['name' => 'Representative', 'sort_order' => 7],
        ];

        foreach ($defaultPositions as $position) {
            DB::table('positions')->updateOrInsert(
                ['name' => $position['name']],
                [
                    'sort_order' => $position['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $positionIdsByName = DB::table('positions')->pluck('id', 'name');

        foreach (DB::table('candidates')->select('id', 'position')->get() as $candidate) {
            if (!$candidate->position) {
                continue;
            }

            $positionId = $positionIdsByName[$candidate->position] ?? null;

            if (!$positionId) {
                $positionId = DB::table('positions')->insertGetId([
                    'name'       => $candidate->position,
                    'sort_order' => 99,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $positionIdsByName[$candidate->position] = $positionId;
            }

            DB::table('candidates')->where('id', $candidate->id)->update([
                'position_id' => $positionId,
            ]);
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('position')->nullable()->after('name');
        });

        $positions = DB::table('positions')->pluck('name', 'id');

        foreach (DB::table('candidates')->select('id', 'position_id')->get() as $candidate) {
            DB::table('candidates')->where('id', $candidate->id)->update([
                'position' => $positions[$candidate->position_id] ?? null,
            ]);
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
        });
    }
};
