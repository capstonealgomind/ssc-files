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

    // IDs are compared after formatting characters are removed. This pattern is
    // for the canonical value, not the student's original punctuation/spacing.
    public const STUDENT_ID_PATTERN = '/\A[0-9]{7}\z/';

    public static function studentId(string $value): string
    {
        // Keep the exact letter/digit sequence while ignoring separators such
        // as spaces, hyphens, periods, and Unicode dash variants.
        return preg_replace('/[^A-Z0-9]/u', '', mb_strtoupper(trim($value))) ?? '';
    }

    public static function formattedStudentId(?string $value): string
    {
        $original = trim((string) $value);
        $key = self::studentId($original);

        return preg_match('/\A[0-9]{7}\z/', $key)
            ? substr($key, 0, 2).'-'.substr($key, 2, 1).'-'.substr($key, 3, 4)
            : $original;
    }

    public static function comparable(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
    }
}
