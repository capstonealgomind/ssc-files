<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Services\ElectionReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
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
        ]);
    }

    public function exportPdf(Request $request, Election $election, string $type)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('reports.export.pdf', array_filter([
                'election' => $election->id,
                'type' => $type,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ])));
        }

        $report = $this->reports->build($election, $type, $dateFrom, $dateTo);
        $view = $this->pdfViewForType($type);

        $pdf = Pdf::loadView($view, [
            'report' => $report,
            'bcc_logo' => $this->imageDataUri('bcc.png'),
            'ssc_logo' => $this->imageDataUri('ssc.png'),
            'app_name' => config('app.name', 'SSCEVS'),
        ])->setPaper('a4', 'portrait');

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
        $report = $this->reports->build($election, $type, $dateFrom, $dateTo);
        $sheets = $this->reports->excelSheets($report);
        $filename = $this->filename($election, $type, 'xls');

        return response()->streamDownload(function () use ($report, $sheets) {
            echo $this->buildSpreadsheetXml($report, $sheets);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    private function pdfViewForType(string $type): string
    {
        return match ($type) {
            'election_results' => 'reports.election-results',
            'vote_tally' => 'reports.vote-tally',
            'turnout' => 'reports.turnout',
            'partylist_performance' => 'reports.partylist-performance',
            'non_voters' => 'reports.non-voters',
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

    private function buildSpreadsheetXml(array $report, array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            .'xmlns:o="urn:schemas-microsoft-com:office:office" '
            .'xmlns:x="urn:schemas-microsoft-com:office:excel" '
            .'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" '
            .'xmlns:html="http://www.w3.org/TR/REC-html40">'."\n";
        $xml .= '<Styles>'
            .'<Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#E8EEF7" ss:Pattern="Solid"/></Style>'
            .'<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/></Style>'
            .'</Styles>'."\n";

        foreach ($sheets as $sheet) {
            $name = $this->sanitizeSheetName($sheet['name'] ?? 'Sheet');
            $xml .= '<Worksheet ss:Name="'.$this->xml($name).'"><Table>'."\n";

            $xml .= '<Row>'
                .'<Cell ss:StyleID="Title"><Data ss:Type="String">'.$this->xml($report['label']).'</Data></Cell>'
                .'</Row>'."\n";
            $xml .= '<Row>'
                .'<Cell><Data ss:Type="String">'.$this->xml($report['election']['title']).'</Data></Cell>'
                .'</Row>'."\n";
            $xml .= '<Row>'
                .'<Cell><Data ss:Type="String">Generated: '.$this->xml($report['generated_at']).'</Data></Cell>'
                .'</Row>'."\n";
            $xml .= '<Row></Row>'."\n";

            $xml .= '<Row>';
            foreach ($sheet['headers'] as $header) {
                $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.$this->xml((string) $header).'</Data></Cell>';
            }
            $xml .= '</Row>'."\n";

            foreach ($sheet['rows'] as $row) {
                $xml .= '<Row>';
                foreach ($row as $cell) {
                    if (is_int($cell) || is_float($cell)) {
                        $xml .= '<Cell><Data ss:Type="Number">'.$cell.'</Data></Cell>';
                    } else {
                        $xml .= '<Cell><Data ss:Type="String">'.$this->xml((string) $cell).'</Data></Cell>';
                    }
                }
                $xml .= '</Row>'."\n";
            }

            $xml .= '</Table></Worksheet>'."\n";
        }

        $xml .= '</Workbook>';

        return $xml;
    }

    private function sanitizeSheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $name) ?: 'Sheet';

        return mb_substr($clean, 0, 31);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
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
