<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\LocationRangeSetting;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    LocationRangeSetting::current()->update([
        'is_enabled' => false,
    ]);
});

test('a differently formatted name is unavailable when the letters match', function () {
    User::factory()->create([
        'name' => 'JUAN DELA CRUZ',
    ]);

    $this->getJson('/register/check-name?name='.urlencode('juan  dela-cruz'))
        ->assertOk()
        ->assertJson([
            'available' => false,
        ]);
});

test('a name with different letters stays available', function () {
    User::factory()->create([
        'name' => 'Juan dela Cruz',
    ]);

    $this->getJson('/register/check-name?name='.urlencode('Maria Clara'))
        ->assertOk()
        ->assertJson([
            'available' => true,
        ]);
});

test('registration is rejected when the full name letters already exist', function () {
    User::factory()->create([
        'name' => 'Juan dela Cruz',
        'email' => 'existing@example.com',
        'student_id_number' => '2020-00001',
    ]);

    $department = Department::query()->create(['name' => 'Computing']);
    $course = Course::query()->create([
        'department_id' => $department->id,
        'name' => 'BSIT',
        'duration_years' => 4,
    ]);
    $yearLevel = YearLevel::query()->create([
        'name' => '1st Year',
        'sort_order' => 1,
    ]);

    $this->post('/register', [
        'name' => 'juan-dela cruz',
        'email' => 'new.student@example.com',
        'student_id_number' => '2024-00001',
        'department_id' => $department->id,
        'course_id' => $course->id,
        'year_level_id' => $yearLevel->id,
        'password' => 'Secret1!',
        'password_confirmation' => 'Secret1!',
    ])->assertSessionHasErrors('name');
});
