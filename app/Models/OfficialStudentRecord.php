<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialStudentRecord extends Model
{
    public const HEADERS = [
        'student_id' => 'Student ID',
        'full_name' => 'Full Name',
        'email' => 'Email',
        'department' => 'Department',
        'course' => 'Course',
        'year_level' => 'Year Level',
        'section' => 'Section',
        'status' => 'Enrollment/Eligibility Status',
    ];

    public const STUDENT_ID_PATTERN = '/\A(?=[A-Z0-9-]*[0-9])[A-Z0-9]+(?:-[A-Z0-9]+)*\z/i';

    public static function studentId(string $value): string
    {
        return strtoupper(trim($value));
    }

    public static function comparable(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
    }
}
