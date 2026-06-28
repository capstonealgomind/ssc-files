<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrResult
{
    public function __construct(
        public readonly bool    $available,
        public readonly ?string $name      = null,
        public readonly ?string $studentId = null,
        public readonly ?string $course    = null,
        public readonly ?string $rawText   = null,
    ) {}
}

class OcrService
{
    /**
     * Extract text from a school ID image.
     * Tries OCR.Space cloud API first, then falls back to local Tesseract.
     */
    public function extract(string $imagePath): OcrResult
    {
        // Primary: OCR.Space cloud API (no local install required)
        $result = $this->extractViaOcrSpace($imagePath);
        if ($result->available) {
            return $result;
        }

        // Fallback: local Tesseract (if installed)
        return $this->extractViaTesseract($imagePath);
    }

    // ── OCR.Space API ─────────────────────────────────────────────────────

    private function extractViaOcrSpace(string $imagePath): OcrResult
    {
        $apiKey = config('services.ocrspace.key');

        if (!$apiKey || !file_exists($imagePath)) {
            return new OcrResult(available: false);
        }

        try {
            $fileContents = file_get_contents($imagePath);
            $mimeType     = mime_content_type($imagePath) ?: 'image/jpeg';
            $base64       = 'data:' . $mimeType . ';base64,' . base64_encode($fileContents);

            $response = Http::timeout(30)
                ->post('https://api.ocr.space/parse/image', [
                    'apikey'             => $apiKey,
                    'base64Image'        => $base64,
                    'language'           => 'eng',
                    'detectOrientation'  => 'true',
                    'scale'              => 'true',
                    'OCREngine'          => 2,
                    'isOverlayRequired'  => 'false',
                    'filetype'           => strtoupper(pathinfo($imagePath, PATHINFO_EXTENSION)) ?: 'JPG',
                ]);

            if (!$response->successful()) {
                Log::warning('OCR.Space HTTP error: ' . $response->status());
                return new OcrResult(available: false);
            }

            $data = $response->json();

            if ($data['IsErroredOnProcessing'] ?? true) {
                $msg = is_array($data['ErrorMessage'] ?? null)
                    ? implode(', ', $data['ErrorMessage'])
                    : ($data['ErrorMessage'] ?? 'unknown error');
                Log::warning('OCR.Space processing error: ' . $msg);
                return new OcrResult(available: false);
            }

            $text = trim($data['ParsedResults'][0]['ParsedText'] ?? '');

            if (empty($text)) {
                Log::info('OCR.Space returned empty text for ' . basename($imagePath));
                return new OcrResult(available: false);
            }

            Log::info('OCR.Space raw text: ' . $text);

            return new OcrResult(
                available: true,
                name:      $this->extractName($text),
                studentId: $this->extractStudentId($text),
                course:    $this->extractCourse($text),
                rawText:   $text,
            );
        } catch (\Throwable $e) {
            Log::warning('OCR.Space exception: ' . $e->getMessage());
            return new OcrResult(available: false);
        }
    }

    // ── Local Tesseract ───────────────────────────────────────────────────

    private function extractViaTesseract(string $imagePath): OcrResult
    {
        $binary = $this->findTesseract();

        if (!$binary) {
            return new OcrResult(available: false);
        }

        $outputBase = sys_get_temp_dir() . '/ocr_' . uniqid();
        $preprocessed = $this->preprocessImage($imagePath);
        $sourceFile   = $preprocessed ?? $imagePath;

        exec(
            escapeshellarg($binary) . ' ' . escapeshellarg($sourceFile) .
            ' ' . escapeshellarg($outputBase) . ' --oem 3 --psm 6 2>NUL',
            $output,
            $returnCode,
        );

        $textFile = $outputBase . '.txt';
        $text     = null;

        if ($returnCode === 0 && file_exists($textFile)) {
            $text = trim(file_get_contents($textFile));
            unlink($textFile);
        }

        if ($preprocessed && file_exists($preprocessed)) {
            unlink($preprocessed);
        }

        if (!$text) {
            return new OcrResult(available: false);
        }

        return new OcrResult(
            available: true,
            name:      $this->extractName($text),
            studentId: $this->extractStudentId($text),
            course:    $this->extractCourse($text),
            rawText:   $text,
        );
    }

    private function findTesseract(): ?string
    {
        $candidates = [
            'tesseract',                                         // in PATH
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',  // default Windows install
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
        ];

        foreach ($candidates as $path) {
            exec(escapeshellarg($path) . ' --version 2>&1', $out, $code);
            if ($code === 0) {
                return $path;
            }
        }

        return null;
    }

    private function preprocessImage(string $imagePath): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($imagePath);
        if (!$info) {
            return null;
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($imagePath),
            IMAGETYPE_PNG  => @imagecreatefrompng($imagePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($imagePath),
            default        => null,
        };

        if (!$src) {
            return null;
        }

        imagefilter($src, IMG_FILTER_GRAYSCALE);
        imagefilter($src, IMG_FILTER_CONTRAST, -15);

        $outPath = sys_get_temp_dir() . '/ocr_pre_' . uniqid() . '.png';
        imagepng($src, $outPath);
        imagedestroy($src);

        return $outPath;
    }

    // ── Text extraction helpers ───────────────────────────────────────────

    private function extractName(string $text): ?string
    {
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, fn ($l) => $l !== '');
        $lines = array_values($lines);

        // Pattern 1: "Name:" label on the same line
        foreach ($lines as $i => $line) {
            if (preg_match('/(?:Student\s+)?(?:Full\s+)?Name\s*[:：]\s*(.+)/i', $line, $m)) {
                $name = trim($m[1]);
                // If the name after the label is empty, check the next line
                if (strlen($name) < 3 && isset($lines[$i + 1])) {
                    $name = trim($lines[$i + 1]);
                }
                if (strlen($name) > 3) {
                    return $this->cleanName($name);
                }
            }
        }

        // Pattern 2: "Name:" label alone on its own line, name on next line
        for ($i = 0; $i < count($lines) - 1; $i++) {
            if (preg_match('/^(?:Student\s+)?(?:Full\s+)?Name\s*[:：]?\s*$/i', $lines[$i])) {
                $name = trim($lines[$i + 1]);
                if (strlen($name) > 3) {
                    return $this->cleanName($name);
                }
            }
        }

        // Pattern 3: All-caps lines that look like a Philippine ID name
        // Philippine IDs often put first+middle on one line and last name on the next (or vice-versa)
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (!preg_match('/^[A-Z][A-Z\s\-,.Ñ]{5,60}$/', $line)) {
                continue;
            }
            $words = str_word_count($line);
            if ($words < 1 || $words > 6) {
                continue;
            }
            // Skip lines that are clearly dates or addresses
            if (preg_match('/\d|ZONE|BARANGAY|BRGY|STREET|AVE|BLDG/i', $line)) {
                continue;
            }
            // If the next line is also all-caps words, it may be the last name — combine them
            $fullName = $line;
            if (isset($lines[$i + 1])) {
                $next = $lines[$i + 1];
                if (preg_match('/^[A-Z][A-Z\s\-,.Ñ]{2,40}$/', $next) && !preg_match('/\d|ZONE|BRGY|STREET/i', $next)) {
                    $nextWords = str_word_count($next);
                    if ($nextWords >= 1 && $nextWords <= 3) {
                        $fullName = $line . ' ' . $next;
                    }
                }
            }
            return $this->cleanName($fullName);
        }

        return null;
    }

    private function cleanName(string $name): string
    {
        return preg_replace('/\s+/', ' ', trim(preg_replace('/[^\p{L}\s\-,.]/u', '', $name)));
    }

    private function extractStudentId(string $text): ?string
    {
        // Pattern 1: "YYYY-XXXXXX" format (most common Philippine student IDs)
        if (preg_match('/\b(\d{4}[-]\d{3,7})\b/', $text, $m)) {
            return trim($m[1]);
        }

        // Pattern 2: "IN:", "ID No:", "Student ID:", "No.:" labels followed by digits
        if (preg_match('/(?:Student\s*ID|ID\s*No|Student\s*No|IN|S\.?N|No\.?)\s*[:：#\.]\s*(\d[\d\-]{3,12})/i', $text, $m)) {
            return trim($m[1]);
        }

        // Pattern 3: standalone 6–10 digit number that looks like an ID (not a date)
        if (preg_match('/(?<!\d)(\d{6,10})(?!\d)/', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractCourse(string $text): ?string
    {
        // Pattern 1: "Course:", "Program:", "Degree:" label
        if (preg_match('/(?:Course|Program|Degree|Strand)\s*[:：]\s*([^\n\r]+)/i', $text, $m)) {
            return trim($m[1]);
        }

        // Pattern 2: Philippine BS/AB/other degree abbreviations
        if (preg_match('/\b(BS[A-Z]{2,8}|BSIT|BSCS|BSECE|BSEE|BSME|BSCE|BSBA|BSED|BSHRM|BSPSYCH|BSSW|AB[A-Z]{2,6}|B\.?Ed|BPA|BLIS)\b/', $text, $m)) {
            return trim($m[1]);
        }

        // Pattern 3: longer spelled-out course names
        if (preg_match('/Bachelor\s+of\s+(?:Science|Arts|Technology|Engineering|Education)\s+in\s+([^\n\r,]{5,60})/i', $text, $m)) {
            return 'BS ' . trim($m[1]);
        }

        return null;
    }
}
