<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Services\ElectionReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ElectionReportService $reports,
    ) {}

    public function index(Request $request): Response
    {
        $options = $this->reports->electionOptions();
        $selectedId = (int) ($request->query('election_id') ?: 0);
        $selected = $selectedId > 0
            ? Election::query()->find($selectedId)
            : null;

        if (! $selected && $options !== []) {
            $selected = Election::query()->find((int) $options[0]['value']);
        }

        return Inertia::render('Reports', [
            'electionOptions' => $options,
            'selectedElectionId' => $selected?->id,
            'preview' => $selected ? $this->reports->preview($selected) : null,
            'reportTypes' => $this->reports->reportCatalog(),
            'filterOptions' => $this->reports->filterOptions(),
        ]);
    }

    public function exportPdf(Request $request, Election $election, string $type)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $filters = $this->extractFilters($request);

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('reports.export.pdf', array_filter([
                'election' => $election->id,
                'type' => $type,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                ...$filters,
            ], fn ($value) => $value !== null && $value !== '')));
        }

        $report = $this->reports->build($election, $type, $dateFrom, $dateTo, $filters);
        $view = $this->pdfViewForType($type);
        $orientation = $type === 'students_who_voted' ? 'landscape' : 'portrait';

        $pdf = Pdf::loadView($view, [
            'report' => $report,
            'bcc_logo' => $this->imageDataUri('bcc.png'),
            'ssc_logo' => $this->imageDataUri('ssc.png'),
            'app_name' => config('app.name', 'SSCEVS'),
        ])->setPaper('a4', $orientation);

        $filename = $this->filename($election, $type, 'pdf');
        $content = $pdf->output();

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($content),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function exportExcel(Request $request, Election $election, string $type): StreamedResponse
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $filters = $this->extractFilters($request);
        $report = $this->reports->build($election, $type, $dateFrom, $dateTo, $filters);
        $sheets = $this->reports->excelSheets($report);
        $filename = $this->filename($election, $type, 'xlsx');

        return response()->streamDownload(function () use ($report, $sheets) {
            $spreadsheet = $this->buildSpreadsheet($report, $sheets);
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    public function exportAvailability(Request $request, Election $election, string $type)
    {
        $filters = $this->extractFilters($request);
        $hasData = $this->reports->hasExportData(
            $election,
            $type,
            $request->query('date_from'),
            $request->query('date_to'),
            $filters,
        );

        return response()->json([
            'has_data' => $hasData,
        ]);
    }

    /**
     * @return array{department_id?: string, year_level_id?: string, course_id?: string}
     */
    private function extractFilters(Request $request): array
    {
        return array_filter([
            'department_id' => $request->query('department_id'),
            'year_level_id' => $request->query('year_level_id'),
            'course_id' => $request->query('course_id'),
        ], fn ($value) => filled($value));
    }

    private function pdfViewForType(string $type): string
    {
        return match ($type) {
            'election_results' => 'reports.election-results',
            'vote_tally' => 'reports.vote-tally',
            'turnout' => 'reports.turnout',
            'partylist_performance' => 'reports.partylist-performance',
            'non_voters' => 'reports.non-voters',
            'students_who_voted' => 'reports.students-who-voted',
            'ballot_receipts' => 'reports.ballot-receipts',
            'voter_registration' => 'reports.voter-registration',
            'candidate_roster' => 'reports.candidate-roster',
            default => abort(404),
        };
    }

    private function filename(Election $election, string $type, string $extension): string
    {
        $slug = str($election->title)->slug('_')->limit(40, '')->toString() ?: 'election';

        return "sscevs_{$type}_{$slug}.".$extension;
    }

    /**
     * @param  list<array{name: string, headers: list<mixed>, rows: list<mixed>}>  $sheets
     */
    private function buildSpreadsheet(array $report, array $sheets): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $index => $sheetData) {
            $worksheet = new Worksheet($spreadsheet, $this->sanitizeSheetName($sheetData['name'] ?? 'Sheet'));
            $spreadsheet->addSheet($worksheet, $index);

            $headers = array_values($sheetData['headers'] ?? []);
            $rows = array_values($sheetData['rows'] ?? []);
            $columnCount = max(count($headers), 3);
            $lastColumn = $this->columnLetter($columnCount);

            $worksheet->getRowDimension(1)->setRowHeight(58);
            $this->addLogo($worksheet, 'bcc.png', 'A1');
            $this->addLogo($worksheet, 'ssc.png', $lastColumn.'1');

            $worksheet->mergeCells("B1:".$this->columnLetter(max($columnCount - 1, 2)).'1');
            $worksheet->setCellValue('B1', "Baao Community College\n".(config('app.name', 'SSCEVS'))."\n".$report['label']);
            $worksheet->getStyle('B1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            $row = 3;
            $worksheet->setCellValue("A{$row}", $report['election']['title']);
            $worksheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $worksheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);

            $row++;
            $worksheet->setCellValue("A{$row}", 'Generated: '.$report['generated_at']);
            $worksheet->mergeCells("A{$row}:{$lastColumn}{$row}");

            $filterLine = $this->excelFilterSummary($report);
            if ($filterLine !== null) {
                $row++;
                $worksheet->setCellValue("A{$row}", $filterLine);
                $worksheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            }

            $row += 2;
            $headerRow = $row;
            foreach ($headers as $colIndex => $header) {
                $coordinate = Coordinate::stringFromColumnIndex($colIndex + 1).$headerRow;
                $worksheet->setCellValue($coordinate, (string) $header);
            }
            $worksheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8EEF7'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            foreach ($rows as $dataRow) {
                $row++;
                $cells = array_values(is_array($dataRow) ? $dataRow : [$dataRow]);
                foreach ($cells as $colIndex => $cell) {
                    $coordinate = Coordinate::stringFromColumnIndex($colIndex + 1).$row;
                    $worksheet->setCellValue($coordinate, $cell);
                }
            }

            if ($rows !== []) {
                $worksheet->getStyle('A'.($headerRow + 1).":{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);
            }

            foreach ($headers as $colIndex => $header) {
                $maxLen = mb_strlen((string) $header);
                foreach ($rows as $dataRow) {
                    $cells = array_values(is_array($dataRow) ? $dataRow : [$dataRow]);
                    $maxLen = max($maxLen, mb_strlen((string) ($cells[$colIndex] ?? '')));
                }
                $width = max(12, min(45, $maxLen + 2));
                $worksheet->getColumnDimensionByColumn($colIndex + 1)->setWidth($width);
            }

            // Keep space for logos in first/last columns.
            $worksheet->getColumnDimension('A')->setWidth(max(14, (float) $worksheet->getColumnDimension('A')->getWidth()));
            $worksheet->getColumnDimension($lastColumn)->setWidth(max(14, (float) $worksheet->getColumnDimension($lastColumn)->getWidth()));
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function addLogo(Worksheet $worksheet, string $filename, string $coordinate): void
    {
        $path = public_path('images/'.$filename);
        if (! is_file($path)) {
            return;
        }

        $drawing = new Drawing;
        $drawing->setName(pathinfo($filename, PATHINFO_FILENAME));
        $drawing->setDescription($filename);
        $drawing->setPath($path);
        $drawing->setHeight(52);
        $drawing->setCoordinates($coordinate);
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(4);
        $drawing->setWorksheet($worksheet);
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $letter = chr(65 + $modulo).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter ?: 'A';
    }

    private function excelFilterSummary(array $report): ?string
    {
        $parts = [];

        if (! empty($report['date_from']) || ! empty($report['date_to'])) {
            $parts[] = 'Date: '.($report['date_from'] ?? '—').' to '.($report['date_to'] ?? '—');
        }

        $filters = $report['filters'] ?? [];
        if (! empty($filters['department'])) {
            $parts[] = 'Department: '.$filters['department'];
        }
        if (! empty($filters['year_level'])) {
            $parts[] = 'Year Level: '.$filters['year_level'];
        }
        if (! empty($filters['course'])) {
            $parts[] = 'Course/Section: '.$filters['course'];
        }

        if ($parts === []) {
            return null;
        }

        return 'Filters: '.implode(' · ', $parts);
    }

    private function sanitizeSheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $name) ?: 'Sheet';

        return mb_substr($clean, 0, 31);
    }

    private function imageDataUri(string $filename): ?string
    {
        $path = public_path('images/'.$filename);

        if (! is_file($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
