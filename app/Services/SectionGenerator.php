<?php

namespace App\Services;

use App\Models\Course;
use App\Models\YearLevel;
use Illuminate\Support\Facades\DB;

class SectionGenerator
{
    public function generate(): array
    {
        return DB::transaction(function () {
            $courses = Course::whereHas('department')->orderBy('id')->lockForUpdate()->get(['id', 'duration_years']);
            $years = YearLevel::orderBy('id')->lockForUpdate()->get(['id', 'sort_order']);
            $created = $total = 0;
            $now = now();

            foreach ($courses as $course) {
                foreach ($years as $year) {
                    if ($year->sort_order > $course->duration_years) {
                        continue;
                    }

                    $rows = array_map(fn ($letter) => [
                        'course_id' => $course->id,
                        'year_level_id' => $year->id,
                        'letter' => $letter,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], range('A', 'Z'));

                    $total += count($rows);
                    $created += DB::table('sections')->insertOrIgnore($rows);
                }
            }

            return ['created' => $created, 'skipped' => $total - $created];
        }, 5);
    }
}
