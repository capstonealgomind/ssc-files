<?php

namespace Database\Seeders;

use App\Services\SectionGenerator;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(SectionGenerator $generator): void
    {
        $result = $generator->generate();
        $this->command?->info("Created {$result['created']} sections. Skipped {$result['skipped']} existing combinations.");
    }
}
