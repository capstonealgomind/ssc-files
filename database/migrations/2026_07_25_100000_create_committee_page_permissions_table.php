<?php

use App\Models\User;
use App\Support\CommitteePageCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_page_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('page_key', 50);
            $table->timestamps();

            $table->unique(['user_id', 'page_key']);
        });

        $now = now();
        $rows = [];

        User::query()
            ->where('role', 'committee')
            ->pluck('id')
            ->each(function (int $userId) use (&$rows, $now) {
                foreach (CommitteePageCatalog::DEFAULT_PAGES as $pageKey) {
                    $rows[] = [
                        'user_id' => $userId,
                        'page_key' => $pageKey,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            });

        if ($rows !== []) {
            DB::table('committee_page_permissions')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_page_permissions');
    }
};
