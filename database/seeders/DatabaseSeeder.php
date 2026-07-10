<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Course;
use App\Models\Department;
use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@sscevs.admin.com'],
            [
                'name'     => 'System Administrator',
                'password' => Hash::make('Admin@1234'),
                'role'     => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'committee@sscevs.committee.com'],
            [
                'name'     => 'Election Committee',
                'password' => Hash::make('Committee@1234'),
                'role'     => 'committee',
            ]
        );

        $yearLevels = [
            ['name' => '1st Year', 'sort_order' => 1],
            ['name' => '2nd Year', 'sort_order' => 2],
            ['name' => '3rd Year', 'sort_order' => 3],
            ['name' => '4th Year', 'sort_order' => 4],
        ];

        foreach ($yearLevels as $yearLevel) {
            YearLevel::firstOrCreate(
                ['name' => $yearLevel['name']],
                ['sort_order' => $yearLevel['sort_order']],
            );
        }

        $positions = [
            ['name' => 'President', 'sort_order' => 1],
            ['name' => 'Vice President', 'sort_order' => 2],
            ['name' => 'Secretary', 'sort_order' => 3],
            ['name' => 'Treasurer', 'sort_order' => 4],
            ['name' => 'Auditor', 'sort_order' => 5],
            ['name' => 'Public Relations Officer', 'sort_order' => 6],
            ['name' => 'Representative', 'sort_order' => 7],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['name' => $position['name']],
                ['sort_order' => $position['sort_order']],
            );
        }

        $department = Department::firstOrCreate(
            ['name' => 'College of Computer Studies'],
            ['acronym' => 'CCS', 'color' => 'Blue'],
        );

        $course = Course::firstOrCreate(
            [
                'department_id' => $department->id,
                'name'          => 'Bachelor of Science in Information Technology',
            ],
            [
                'duration_years' => 4,
            ],
        );

        $secondYear = YearLevel::where('name', '2nd Year')->first();
        $thirdYear = YearLevel::where('name', '3rd Year')->first();

        User::updateOrCreate(
            ['email' => 'juan@example.com'],
            [
                'name'              => 'Juan dela Cruz',
                'student_id_number' => '2024-00001',
                'department_id'     => $department->id,
                'course_id'         => $course->id,
                'year_level_id'     => $thirdYear?->id,
                'password'          => Hash::make('Voter@1234'),
                'role'              => 'voter',
            ]
        );

        // Expired voter for reactivation testing (BSIT 4 yrs, left at 2nd year; duration already ended).
        User::updateOrCreate(
            ['email' => 'expired@example.com'],
            [
                'name'                 => 'Pedro Expired',
                'student_id_number'    => '2020-00999',
                'voter_id_number'      => 'VOTER-EXPIRED-001',
                'department_id'        => $department->id,
                'course_id'            => $course->id,
                'year_level_id'        => $secondYear?->id,
                'password'             => Hash::make('Voter@1234'),
                'role'                 => 'voter',
                'is_verified'          => true,
                'email_verified_at'    => now()->subYears(2),
                'email_status'         => 'verified',
                'ocr_status'           => 'completed',
                'verification_status'  => 'approved',
                'registration_status'  => User::STATUS_EXPIRED,
                'account_expires_at'   => now()->subMonths(6),
            ]
        );

        $admin = User::where('email', 'admin@sscevs.admin.com')->first();

        Election::firstOrCreate(
            ['title' => '2026 Student Council General Election'],
            [
                'description'      => 'Annual election for student council officers and representatives.',
                'event_starts_at'  => now()->addDays(7)->setTime(8, 0),
                'event_ends_at'    => now()->addDays(14)->setTime(17, 0),
                'voting_starts_at' => now()->addDays(10)->setTime(8, 0),
                'voting_ends_at'   => now()->addDays(12)->setTime(17, 0),
                'status'           => Election::STATUS_SCHEDULED,
                'created_by'       => $admin?->id,
            ]
        );

        $election = Election::where('title', '2026 Student Council General Election')->first();

        $sampleCandidates = [
            [
                'name'       => 'Maria Santos',
                'position'   => 'President',
                'platform'   => 'Transparency, inclusivity, and student-led initiatives.',
            ],
            [
                'name'       => 'John Reyes',
                'position'   => 'President',
                'platform'   => 'Strong student representation and campus innovation.',
            ],
            [
                'name'       => 'Ana Garcia',
                'position'   => 'Vice President',
                'platform'   => 'Collaboration across departments and year levels.',
            ],
        ];

        foreach ($sampleCandidates as $sample) {
            $position = Position::where('name', $sample['position'])->first();

            Candidate::firstOrCreate(
                [
                    'election_id' => $election?->id,
                    'name'        => $sample['name'],
                    'position_id' => $position?->id,
                ],
                [
                    'department_id' => $department->id,
                    'platform'      => $sample['platform'],
                ]
            );
        }
    }
}
