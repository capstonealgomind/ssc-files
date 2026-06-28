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

    /** @return list<string> */
    private function normalizeLines(string $text): array
    {
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, fn ($l) => $l !== '');

        return array_values($lines);
    }

    private function isInstitutionLine(string $line): bool
    {
        return (bool) preg_match(
            '/\b(COLLEGE|COMMUNITY|UNIVERSITY|INSTITUTE|CAMARINES|BAAO|STUDENT\s+ID\s+CARD|EMERGENCY|CONTACT|SIGNATURE|ADDRESS|SAN\s+JUAN)\b/i',
            $line,
        );
    }

    private function isAddressOrPhoneLine(string $line): bool
    {
        return (bool) preg_match(
            '/\b(\+?\d{10,12}|CONTACT\s+NO|PHONE|MOBILE|HEIGHTS|STREET|BARANGAY|BRGY|BLDG|AVE\.?|SUBD|VILLAGE)\b/i',
            $line,
        );
    }

    private function isEmergencyContext(array $lines, int $index): bool
    {
        for ($j = $index; $j >= max(0, $index - 10); $j--) {
            if (preg_match('/\b(in\s+case\s+of\s+)?emergency|please\s+contact\b/i', $lines[$j])) {
                return true;
            }
            if (preg_match('/^(Name|Course|Student\s*No\.?)\s*\.?\s*$/i', $lines[$j])) {
                return false;
            }
        }

        return false;
    }

    private function looksLikeMajor(string $line): bool
    {
        $line = trim($line);
        if (strlen($line) < 3 || strlen($line) > 40) {
            return false;
        }
        if ($this->isInstitutionLine($line) || $this->isAddressOrPhoneLine($line)) {
            return false;
        }
        if (preg_match('/^(Bachelor|Master|Associate|BACHELOR|Name|Course|Student)/i', $line)) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z][A-Za-z\s\-]{2,38}$/', $line);
    }

    private function isLabelLine(string $line): bool
    {
        return (bool) preg_match(
            '/^(Name|Course|Student\s*No\.?|Program|Degree|Strand|Signature|Address|Contact)\s*\.?\s*$/i',
            trim($line),
        );
    }

    private function looksLikePersonName(string $line): bool
    {
        if ($this->isInstitutionLine($line) || $this->isAddressOrPhoneLine($line)) {
            return false;
        }

        if (preg_match('/^[A-Z][A-Z\s\-,.Ñ]{1,40},\s*[A-Z][A-Z\s\-,.Ñ]{2,60}$/i', $line)) {
            return true;
        }

        return (bool) (
            preg_match('/^[A-Z][A-Z\s\-,.Ñ]{5,60}$/', $line)
            && str_word_count($line) >= 2
            && str_word_count($line) <= 6
            && !preg_match('/\b(BACHELOR|MASTER|SCIENCE|EDUCATION|INFORMATION|SYSTEMS|COLLEGE)\b/i', $line)
        );
    }

    private function normalizeStudentId(string $id): string
    {
        $id = trim($id);

        if (preg_match('/(\d{2})[\s.\/\\\-]+(\d)[\s.\/\\\-]+(\d{4})/', $id, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return $id;
    }

    private function extractName(string $text): ?string
    {
        $lines = $this->normalizeLines($text);

        // Pattern 1: "Name:" label on the same line
        foreach ($lines as $i => $line) {
            if (preg_match('/(?:Student\s+)?(?:Full\s+)?Name\s*[:：]\s*(.+)/i', $line, $m)) {
                $name = trim($m[1]);
                if (strlen($name) < 3 && isset($lines[$i + 1])) {
                    $name = trim($lines[$i + 1]);
                }
                if (strlen($name) > 3 && !$this->isInstitutionLine($name)) {
                    return $this->cleanName($name);
                }
            }
        }

        // Pattern 2: BCC vertical ID — name appears on the line above a standalone "Name" label
        for ($i = 1; $i < count($lines); $i++) {
            if (preg_match('/^Name\s*\.?\s*$/i', $lines[$i])) {
                $name = trim($lines[$i - 1]);
                if (strlen($name) > 3 && !$this->isInstitutionLine($name)) {
                    return $this->cleanName($name);
                }
            }
        }

        // Pattern 3: "Name:" label alone on its own line, name on next line
        for ($i = 0; $i < count($lines) - 1; $i++) {
            if (preg_match('/^(?:Student\s+)?(?:Full\s+)?Name\s*[:：]?\s*$/i', $lines[$i])) {
                $name = trim($lines[$i + 1]);
                if (strlen($name) > 3
                    && !$this->isInstitutionLine($name)
                    && !$this->isLabelLine($name)
                    && $this->looksLikePersonName($name)) {
                    return $this->cleanName($name);
                }
            }
        }

        // Pattern 4: BCC horizontal ID — "LAST, FIRST MIDDLE" comma format
        foreach ($lines as $line) {
            if (preg_match('/^[A-Z][A-Z\s\-,.Ñ]{1,40},\s*[A-Z][A-Z\s\-,.Ñ]{2,60}$/i', $line)
                && !$this->isInstitutionLine($line)) {
                return $this->cleanName($line);
            }
        }

        // Pattern 5: All-caps lines that look like a Philippine ID name
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if ($this->isEmergencyContext($lines, $i) || $this->isAddressOrPhoneLine($line)) {
                continue;
            }
            if (!preg_match('/^[A-Z][A-Z\s\-,.Ñ]{5,60}$/', $line)) {
                continue;
            }
            $words = str_word_count($line);
            if ($words < 2 || $words > 6) {
                continue;
            }
            if (preg_match('/\d|ZONE|BARANGAY|BRGY|STREET|AVE|BLDG/i', $line) || $this->isInstitutionLine($line)) {
                continue;
            }
            if (preg_match('/\b(BACHELOR|MASTER|SCIENCE|EDUCATION|INFORMATION|SYSTEMS|CONTACT)\b/i', $line)) {
                continue;
            }
            $fullName = $line;
            if (isset($lines[$i + 1])) {
                $next = $lines[$i + 1];
                if (preg_match('/^[A-Z][A-Z\s\-,.Ñ]{2,40}$/', $next)
                    && !preg_match('/\d|ZONE|BRGY|STREET/i', $next)
                    && !$this->isInstitutionLine($next)
                    && !$this->isEmergencyContext($lines, $i + 1)
                    && !preg_match('/^(Name|Course|Student\s*No\.?)\s*\.?\s*$/i', $next)) {
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
        $lines = $this->normalizeLines($text);

        // Pattern 1: BCC vertical ID — number on the line above "Student No." label
        for ($i = 1; $i < count($lines); $i++) {
            if (preg_match('/^Student\s*No\.?\s*$/i', $lines[$i])) {
                $id = $this->normalizeStudentId(trim($lines[$i - 1]));
                if (preg_match('/^\d{2}-\d-\d{4}$/', $id)) {
                    return $id;
                }
            }
        }

        // Pattern 2: BCC format "YY-1-XXXX" anywhere (e.g. 23-1-0516, 24-1-0462)
        if (preg_match('/\b(\d{2}[\s.\/\\\-]+\d[\s.\/\\\-]+\d{4})\b/', $text, $m)) {
            $id = $this->normalizeStudentId($m[1]);
            if (preg_match('/^\d{2}-\d-\d{4}$/', $id)) {
                return $id;
            }
        }

        // Pattern 3: "Student No:", "ID No:", etc. labels followed by digits
        if (preg_match('/(?:Student\s*(?:ID|No\.?)|ID\s*No\.?|S\.?N\.?)\s*[:：#]?\s*(\d[\d\s.\/\\\-]{3,14})/i', $text, $m)) {
            $id = $this->normalizeStudentId($m[1]);
            if (preg_match('/^\d{2}-\d-\d{4}$/', $id)) {
                return $id;
            }
        }

        // Pattern 4: "YYYY-XXXXXX" format
        if (preg_match('/\b(\d{4}-\d{3,7})\b/', $text, $m)) {
            return trim($m[1]);
        }

        // Pattern 5: standalone 6–10 digit number (not a phone number)
        if (preg_match('/(?<!\d)(\d{6,10})(?!\d)/', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractCourse(string $text): ?string
    {
        $lines = $this->normalizeLines($text);

        // Pattern 1: "Course:", "Program:", "Degree:" label on the same line
        if (preg_match('/(?:Course|Program|Degree|Strand)\s*[:：]\s*([^\n\r]+)/i', $text, $m)) {
            return $this->cleanCourse($m[1]);
        }

        // Pattern 2: BCC vertical ID — course on the line above a standalone "Course" label
        for ($i = 1; $i < count($lines); $i++) {
            if (preg_match('/^Course\s*\.?\s*$/i', $lines[$i])) {
                $course = trim($lines[$i - 1]);
                if (strlen($course) > 5
                    && !$this->isInstitutionLine($course)
                    && !$this->looksLikePersonName($course)) {
                    return $this->cleanCourse($course);
                }
            }
        }

        // Pattern 3: BCC horizontal ID — degree line, optionally followed by major/specialization
        for ($i = 0; $i < count($lines); $i++) {
            if (!preg_match('/^(Bachelor|Master|Associate)\s+of\s+/i', $lines[$i])) {
                continue;
            }
            if ($this->isInstitutionLine($lines[$i]) || $this->isAddressOrPhoneLine($lines[$i])) {
                continue;
            }

            $course = $this->cleanCourse($lines[$i]);
            if (isset($lines[$i + 1]) && $this->looksLikeMajor($lines[$i + 1])) {
                $course .= ' - ' . $this->cleanCourse($lines[$i + 1]);
            }

            return $course;
        }

        // Pattern 4: spelled-out degree titles anywhere in text
        if (preg_match(
            '/\b((?:Bachelor|Master|Associate)\s+of\s+(?:Science|Arts|Technology|Engineering|Education|Secondary\s+Education|Elementary\s+Education|Information\s+Systems)(?:\s+in\s+[^\n\r,]{3,60})?)\b/i',
            $text,
            $m,
        )) {
            return $this->cleanCourse($m[1]);
        }

        // Pattern 5: all-caps BCC vertical layout (e.g. "BACHELOR OF SCIENCE IN INFORMATION SYSTEMS")
        if (preg_match('/\b(BACHELOR\s+OF\s+(?:SCIENCE|ARTS|EDUCATION)\s+IN\s+[A-Z\s]{3,50})\b/', $text, $m)) {
            return $this->cleanCourse(trim($m[1]));
        }

        if (preg_match('/\b(BACHELOR\s+OF\s+(?:SECONDARY|ELEMENTARY)\s+EDUCATION)\b/', $text, $m)) {
            return $this->cleanCourse(trim($m[1]));
        }

        // Pattern 6: Philippine BS/AB/other degree abbreviations
        if (preg_match('/\b(BS[A-Z]{2,8}|BSIT|BSCS|BSECE|BSEE|BSME|BSCE|BSBA|BSED|BSHRM|BSPSYCH|BSSW|AB[A-Z]{2,6}|B\.?Ed|BPA|BLIS)\b/', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function cleanCourse(string $course): string
    {
        return preg_replace('/\s+/', ' ', trim($course));
    }
}
