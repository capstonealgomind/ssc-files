<?php

namespace App\Services;

use App\Models\OfficialStudentRecord as Record;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OfficialStudentImport
{
    public function read(UploadedFile $file): array
    {
        $summary = ['processed' => 0, 'added' => 0, 'duplicates' => 0, 'conflicts' => 0, 'invalid' => 0, 'issues' => []];
        $extension = strtolower($file->getClientOriginalExtension());
        $format = ['xlsx' => 'Xlsx', 'xls' => 'Xls', 'csv' => 'Csv'][$extension];
        $path = $file->getPathname();
        $reader = IOFactory::createReader($format);
        if ($format === 'Csv') {
            $reader->setValueBinder(new StringValueBinder);
        }

        try {
            if (! $reader->canRead($path)) {
                throw new \RuntimeException('The file does not match its extension.');
            }

            if ($format === 'Xlsx') {
                $archive = new \ZipArchive;
                if ($archive->open($path) !== true) {
                    throw new \RuntimeException('The Excel archive could not be opened.');
                }
                try {
                    $bytes = 0;
                    for ($index = 0; $index < $archive->numFiles; $index++) {
                        $bytes += $archive->statIndex($index)['size'];
                        if ($bytes > 32 * 1024 * 1024) {
                            throw new \RuntimeException('The expanded Excel file exceeds 32 MB.');
                        }
                    }
                } finally {
                    $archive->close();
                }
            }

            $info = $reader->listWorksheetInfo($path)[0];
            if ($info['totalRows'] > 10001 || $info['totalColumns'] > 50) {
                throw new \RuntimeException('Use at most 10,000 data rows and 50 columns per upload.');
            }
            $reader->setLoadSheetsOnly($info['worksheetName']);
            $book = $reader->load($path);
        } catch (\Throwable $exception) {
            $summary['error'] = $exception instanceof \RuntimeException && $exception::class === \RuntimeException::class
                ? $exception->getMessage()
                : 'The spreadsheet could not be read. Upload a valid Excel or CSV file.';
            return [[], $summary];
        }

        try {
            $sheet = $book->getSheet(0);
            $headers = array_map(
                fn ($value) => Record::comparable(ltrim((string) $value, "\xEF\xBB\xBF")),
                $sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().'1', null, false, false)[0],
            );
            $columns = [];
            $missing = [];
            foreach (Record::HEADERS as $key => $label) {
                if (in_array($key, ['email', 'status'], true)) {
                    continue;
                }
                $matches = array_keys($headers, Record::comparable($label), true);
                if (count($matches) !== 1) {
                    $missing[] = $label;
                    continue;
                }
                $columns[$key] = $matches[0] + 1;
            }
            // Email is part of the official record used to verify registration,
            // so require exactly one supported email header during import.
            $emailMatches = [];
            foreach (['email', 'email address', 'student email', 'student email address', 'e-mail', 'e-mail address'] as $emailHeader) {
                $emailMatches = [...$emailMatches, ...array_keys($headers, $emailHeader, true)];
            }
            if (count($emailMatches) !== 1) {
                $missing[] = 'a single Email column';
            } else {
                $columns['email'] = $emailMatches[0] + 1;
            }
            $statusMatches = [];
            foreach (['enrollment/eligibility status', 'eligibility status', 'status'] as $statusHeader) {
                $statusMatches = [...$statusMatches, ...array_keys($headers, $statusHeader, true)];
            }
            if ($statusMatches !== []) {
                // Status is optional; if a workbook has repeated status columns,
                // use the first one instead of rejecting the whole import.
                $columns['status'] = $statusMatches[0] + 1;
            }
            if ($missing || $sheet->getHighestDataRow() < 2) {
                $summary['error'] = $missing
                    ? 'Missing or repeated columns: '.implode(', ', $missing).'.'
                    : 'The spreadsheet contains no student rows.';
                return [[], $summary];
            }

            $catalog = $this->catalog();
            $allowed = [];
            foreach ($catalog as $entry) {
                $values = (array) $entry;
                $allowed[$this->academicKey($values)] = $entry;
                if ($entry->acronym) {
                    $values['department'] = $entry->acronym;
                    $allowed[$this->academicKey($values)] = $entry;
                }
            }

            $rows = [];
            $lastRow = $sheet->getHighestDataRow();
            for ($row = 2; $row <= $lastRow; $row++) {

                $values = [];
                $formula = false;
                foreach ($columns as $key => $column) {
                    $cell = $sheet->getCell([$column, $row]);
                    $formula = $formula || in_array($cell->getDataType(), [DataType::TYPE_FORMULA, DataType::TYPE_ERROR], true);
                    $values[$key] = trim((string) ($formula ? '' : $cell->getFormattedValue()));
                }

                $empty = array_keys($values, '', true);
                if (count($empty) === count($values)) {
                    continue;
                }
                $requiredEmpty = array_values(array_filter($empty, fn (string $key) => $key !== 'status'));
                $values['email'] = isset($values['email']) && $values['email'] !== ''
                    ? mb_strtolower(trim($values['email']))
                    : null;
                $values['status'] = filled($values['status'] ?? null)
                    ? trim($values['status'])
                    : 'For Verification';
                $summary['processed']++;
                $error = match (true) {
                    $formula => 'Formula and error cells are unsupported.',
                    ! preg_match(Record::STUDENT_ID_PATTERN, $values['student_id']) || strlen($values['student_id']) > 50 => 'Invalid Student ID. Use letters, digits and optional hyphens, with at least one digit.',
                    $requiredEmpty !== [] => 'Missing required values: '.implode(', ', array_intersect_key(Record::HEADERS, array_flip($requiredEmpty))).'.',
                    ! mb_check_encoding($values, 'UTF-8') => 'Invalid character encoding. Save CSV files as UTF-8.',
                    $values['email'] !== null && (! filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 255) => 'Invalid Email. Enter a valid email address or leave the cell blank.',
                    mb_strlen($values['full_name']) > 255 || max(array_map(fn (string $field) => mb_strlen($values[$field]), ['department', 'course', 'year_level', 'section', 'status'])) > 150 => 'A value exceeds the allowed length.',
                    ! isset($allowed[$this->academicKey($values)]) => $this->academicError($values, $catalog),
                    default => null,
                };
                if ($error !== null) {
                    $summary['invalid']++;
                    $summary['issues'][] = ['row' => $row, 'message' => $error, 'values' => $values, 'category' => 'invalid'];
                    continue;
                }
                $configured = $allowed[$this->academicKey($values)];
                foreach (['department', 'course', 'year_level', 'section'] as $field) {
                    $values[$field] = $configured->$field;
                }
                $values['student_id'] = Record::studentId($values['student_id']);
                $rows[$row] = $values;
            }
        } finally {
            $book->disconnectWorksheets();
        }

        $unique = $conflicts = [];
        $summary['row_numbers'] = [];
        foreach ($rows as $row => $values) {
            $id = $values['student_id'];
            if (isset($unique[$id])) {
                $conflict = $unique[$id] !== $values;
                if ($conflict) {
                    $conflicts[$id] = true;
                }
                $summary['duplicates']++;
                $summary['issues'][] = [
                    'row' => $row, 'category' => 'duplicates', 'values' => $values,
                    'message' => $conflict ? 'Conflicting duplicate Student ID. Review required.' : 'Repeated Student ID.',
                    'first_row' => $summary['row_numbers'][$id], 'first_values' => $unique[$id],
                ];
                continue;
            }
            $unique[$id] = $values;
            $summary['row_numbers'][$id] = $row;
        }
        foreach ($conflicts as $id => $_) {
            $summary['invalid']++;
            $summary['issues'][] = [
                'row' => $summary['row_numbers'][$id], 'category' => 'invalid', 'values' => $unique[$id],
                'message' => 'Student ID has conflicting entries. Review the duplicate rows.',
            ];
            unset($unique[$id]);
        }

        return [$unique, $summary];
    }

    public function catalog()
    {
        return DB::table('sections')
                ->join('courses', 'courses.id', '=', 'sections.course_id')
                ->join('departments', 'departments.id', '=', 'courses.department_id')
                ->join('year_levels', 'year_levels.id', '=', 'sections.year_level_id')
                ->whereColumn('year_levels.sort_order', '<=', 'courses.duration_years')
                ->get(['departments.name as department', 'departments.acronym as acronym', 'courses.name as course', 'year_levels.name as year_level', 'sections.letter as section']);
    }

    public function academicError(array $values, $catalog): ?string
    {
        $choices = $catalog;
        foreach (['department', 'course', 'year_level', 'section'] as $field) {
            $input = Record::comparable($values[$field]);
            if ($field === 'section') {
                $input = preg_replace('/^section (?=[a-z]$)/', '', $input);
            }

            $matches = $choices->filter(fn ($entry) => Record::comparable($entry->$field) === $input
                || ($field === 'department' && Record::comparable($entry->acronym ?? '') === $input));
            if ($matches->isEmpty()) {
                $label = Record::HEADERS[$field];
                return $label.' "'.$values[$field].'" does not match Settings for this row. Valid choices: '
                    .$choices->pluck($field)->unique()->sort()->implode(', ').'.';
            }
            $choices = $matches;
        }

        return null;
    }

    private function academicKey(array $values): string
    {
        $section = preg_replace('/^section (?=[a-z]$)/', '', Record::comparable($values['section']));

        return json_encode([
            Record::comparable($values['department']), Record::comparable($values['course']),
            Record::comparable($values['year_level']), $section,
        ], JSON_THROW_ON_ERROR);
    }
}
