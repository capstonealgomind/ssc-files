<?php

namespace App\Services;

use App\Models\OfficialStudentRecord as Record;
use App\Models\User;

class OfficialStudentVerification
{
    public function rejectionReason(User $student, string $section): ?string
    {
        $record = Record::findByStudentId((string) $student->student_id_number);

        if (! $record) {
            // If the name identifies an official record, report the entered ID as
            // the mismatch instead of claiming the student is absent from the list.
            $matchingName = Record::query()
                ->whereNull('removed_at')
                ->get(['student_id', 'full_name'])
                ->contains(fn (Record $candidate) => FraudDetectionService::nameSpellingMatches($candidate->full_name, $student->name));

            if ($matchingName) {
                return 'official_record_conflict_student_id';
            }

            return 'official_record_missing';
        }

        if ($record->removed_at !== null) {
            return 'official_record_removed';
        }

        if (! FraudDetectionService::nameSpellingMatches($record->full_name, $student->name)) {
            $nameBelongsToAnotherRecord = Record::query()
                ->whereNull('removed_at')
                ->where('student_id', '!=', $record->student_id)
                ->get(['student_id', 'full_name'])
                ->contains(fn (Record $candidate) => FraudDetectionService::nameSpellingMatches($candidate->full_name, $student->name));

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
                ? ($value !== null && FraudDetectionService::nameSpellingMatches($record->full_name, $value))
                : ($value !== null && $this->programNamesMatch($record->$field, $value));

            if (! $matches) {
                return 'official_record_conflict_'.$field;
            }
        }

        $academicConflicts = [];
        $officialYearLevel = $this->yearLevelKey((string) ($record->year_level ?? ''));
        $enteredYearLevel = $this->yearLevelKey($student->yearLevel?->name ?? '');
        if ($enteredYearLevel === '' || $officialYearLevel !== $enteredYearLevel) {
            $academicConflicts[] = 'year_level';
        }

        $officialSection = $this->sectionKey((string) ($record->section ?? ''));
        $enteredSection = $this->sectionKey($section);
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
            $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

            return Record::comparable($value);
        };

        return $normalize($official) === $normalize($entered);
    }

    private function yearLevelKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
        $value = Record::comparable($value);

        $ordinal = [
            '1' => '1', '1st' => '1', 'first' => '1',
            '2' => '2', '2nd' => '2', 'second' => '2',
            '3' => '3', '3rd' => '3', 'third' => '3',
            '4' => '4', '4th' => '4', 'fourth' => '4',
        ];
        $parts = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (isset($parts[0]) && isset($ordinal[$parts[0]]) && in_array($parts[1] ?? '', ['year', 'yr', 'years'], true)) {
            return $ordinal[$parts[0]];
        }

        return $value;
    }

    private function sectionKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/^\s*section\s+/i', '', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? $value;

        return $value;
    }
}
