<?php

namespace App\Services;

use App\Models\OfficialStudentRecord as Record;
use App\Models\User;

class OfficialStudentVerification
{
    public function rejectionReason(User $student, string $section): ?string
    {
        $record = Record::where('student_id', $student->student_id_number)->first();
        if (! $record) {
            // If the name identifies an official record, report the entered ID as
            // the mismatch instead of claiming the student is absent from the list.
            $matchingName = Record::query()
                ->whereNull('removed_at')
                ->get(['student_id', 'full_name'])
                ->contains(fn (Record $candidate) => FraudDetectionService::namesMatch($candidate->full_name, $student->name));

            if ($matchingName) {
                return 'official_record_conflict_student_id';
            }

            return 'official_record_missing';
        }

        if ($record->removed_at !== null) {
            return 'official_record_removed';
        }

        if (! FraudDetectionService::namesMatch($record->full_name, $student->name)) {
            $nameBelongsToAnotherRecord = Record::query()
                ->whereNull('removed_at')
                ->where('student_id', '!=', $record->student_id)
                ->get(['student_id', 'full_name'])
                ->contains(fn (Record $candidate) => FraudDetectionService::namesMatch($candidate->full_name, $student->name));

            if ($nameBelongsToAnotherRecord) {
                return 'official_record_conflict_student_id';
            }
        }

        if (! filled($record->email)) {
            return 'official_record_email_missing';
        }

        if (mb_strtolower(trim($student->email)) !== mb_strtolower(trim($record->email))) {
            return 'official_record_conflict_email';
        }

        $manual = [
            'full_name' => $student->name,
            'department' => $student->department?->name,
            'course' => $student->course?->name,
        ];

        foreach ($manual as $field => $value) {
            $matches = $field === 'full_name'
                ? ($value !== null && FraudDetectionService::namesMatch($record->full_name, $value))
                : ($value !== null && $this->programNamesMatch($record->$field, $value));

            if (! $matches) {
                return 'official_record_conflict_'.$field;
            }
        }

        if ($student->ocr_name && ! FraudDetectionService::namesMatch($student->ocr_name, $record->full_name)) {
            return 'official_record_ocr_conflict_name';
        }
        if ($student->ocr_student_id && ! FraudDetectionService::studentIdMatches($student->ocr_student_id, $record->student_id)) {
            return 'official_record_ocr_conflict_student_id';
        }
        if ($student->ocr_course && ! FraudDetectionService::courseMatches($student->ocr_course, $record->course)) {
            return 'official_record_ocr_conflict_course';
        }

        $academicConflicts = [];
        $officialYearLevel = Record::comparable((string) ($record->year_level ?? ''));
        $enteredYearLevel = Record::comparable($student->yearLevel?->name ?? '');
        if ($enteredYearLevel === '' || $officialYearLevel !== $enteredYearLevel) {
            $academicConflicts[] = 'year_level';
        }

        $officialSection = preg_replace('/^section (?=[a-z]$)/', '', Record::comparable((string) ($record->section ?? '')));
        $enteredSection = preg_replace('/^section (?=[a-z]$)/', '', Record::comparable($section));
        if ($enteredSection === '' || $officialSection !== $enteredSection) {
            $academicConflicts[] = 'section';
        }
        if ($academicConflicts !== []) {
            return 'official_record_conflict_'.implode('_and_', $academicConflicts);
        }

        return null;
    }

    private function programNamesMatch(?string $official, ?string $entered): bool
    {
        if (! filled($official) || ! filled($entered)) {
            return false;
        }

        // Official-list spreadsheets often append an abbreviation, such as
        // "Computer Information Technology (CIT)", while the form uses the
        // same department's full name without the abbreviation.
        $normalize = static function (string $value): string {
            $value = preg_replace('/\s*\([A-Z0-9&.\/-]{2,12}\)\s*$/i', '', trim($value)) ?? trim($value);

            return Record::comparable($value);
        };

        return $normalize($official) === $normalize($entered);
    }
}
