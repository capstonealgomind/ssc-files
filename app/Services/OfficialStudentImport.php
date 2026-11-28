<?php

namespace App\Services;

use App\Models\OfficialStudentRecord as Record;
use Illuminate\Http\UploadedFile;
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
            [$headerRow, $columns, $headerError] = $this->findHeader($sheet);
            if ($headerError !== null) {
                $summary['error'] = $headerError;
                return [[], $summary];
            }

            $rows = [];
            $lastRow = $sheet->getHighestDataRow();
            for ($row = $headerRow + 1; $row <= $lastRow; $row++) {

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
                $values['student_id'] = Record::studentId($values['student_id']);
                $summary['processed']++;
                $error = match (true) {
                    $formula => 'Formula and error cells are unsupported.',
                    ! preg_match(Record::STUDENT_ID_PATTERN, $values['student_id']) => 'Invalid Student ID. Enter seven digits in the format 26-1-0231; spaces and separators are ignored.',
                    $requiredEmpty !== [] => 'Missing required values: '.implode(', ', array_intersect_key(Record::HEADERS, array_flip($requiredEmpty))).'.',
                    ! mb_check_encoding($values, 'UTF-8') => 'Invalid character encoding. Save CSV files as UTF-8.',
                    $values['email'] !== null && (! filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 255) => 'Invalid Email. Enter a valid email address or leave the cell blank.',
                    mb_strlen($values['full_name']) > 255 || max(array_map(fn (string $field) => mb_strlen($values[$field]), ['department', 'course', 'year_level', 'section', 'status'])) > 150 => 'A value exceeds the allowed length.',
                    default => null,
                };
                if ($error !== null) {
                    $summary['invalid']++;
                    $summary['issues'][] = ['row' => $row, 'message' => $error, 'values' => $values, 'category' => 'invalid'];
                    continue;
                }
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

    /**
     * Find the required columns by their labels instead of fixed positions.
     * This also permits a title or blank row above the header row.
     */
    private function findHeader($sheet): array
    {
        $headers = [
            'student_id' => ['student id', 'student number', 'student no', 'student id number', 'id number'],
            'full_name' => ['full name', 'student name', 'complete name', 'name'],
            'email' => ['email', 'email address', 'student email', 'student email address', 'e-mail', 'e-mail address', 'gmail'],
            'department' => ['department', 'department name', 'dept'],
            'course' => ['course', 'course/program', 'program', 'degree program'],
            'year_level' => ['year level', 'year'],
            'section' => ['section', 'class section', 'block'],
            'status' => ['enrollment/eligibility status', 'eligibility status', 'status'],
        ];
        $required = ['student_id', 'full_name', 'email', 'department', 'course', 'year_level', 'section'];
        $lastColumn = $sheet->getHighestDataColumn();
        $lastRow = min(max((int) $sheet->getHighestDataRow(), 1), 10);
        $bestMissing = $required;
        $bestAmbiguous = [];
        $bestScore = -1;

        for ($row = 1; $row <= $lastRow; $row++) {
            $values = $sheet->rangeToArray('A'.$row.':'.$lastColumn.$row, null, false, false)[0] ?? [];
            $normalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $values);
            $columns = [];
            $missing = [];
            $ambiguous = [];

            foreach ($required as $key) {
                $aliases = array_map(fn ($alias) => $this->normalizeHeader($alias), $headers[$key]);
                $matches = [];
                foreach ($normalized as $index => $header) {
                    if ($header !== '' && in_array($header, $aliases, true)) {
                        $matches[] = $index + 1;
                    }
                }

                if (count($matches) === 1) {
                    $columns[$key] = $matches[0];
                } elseif (count($matches) > 1) {
                    $ambiguous[] = Record::HEADERS[$key];
                } else {
                    $missing[] = Record::HEADERS[$key];
                }
            }

            $score = count($columns) + count($ambiguous);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMissing = $missing;
                $bestAmbiguous = $ambiguous;
            }

            if ($missing === [] && $ambiguous === []) {
                $statusAliases = array_map(fn ($alias) => $this->normalizeHeader($alias), $headers['status']);
                foreach ($normalized as $index => $header) {
                    if ($header !== '' && in_array($header, $statusAliases, true)) {
                        $columns['status'] = $index + 1;
                        break;
                    }
                }

                return [$row, $columns, null];
            }

        }

        if ($sheet->getHighestDataRow() < 2) {
            return [null, [], 'The spreadsheet contains no student rows.'];
        }

        $details = [];
        if ($bestMissing !== []) {
            $details[] = 'missing: '.implode(', ', $bestMissing);
        }
        if ($bestAmbiguous !== []) {
            $details[] = 'repeated: '.implode(', ', $bestAmbiguous);
        }

        return [null, [], 'Required columns not found in one header row ('.implode('; ', $details).').'];
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim(ltrim($value, "\xEF\xBB\xBF")));

        return preg_replace('/[^a-z0-9]+/u', '', $value) ?? '';
    }
}
