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
        public readonly ?array  $overlay   = null,
        public readonly ?string $yearLevel = null,
        public readonly ?string $section   = null,
        public readonly ?string $yearCourse = null,
        public readonly ?string $nameIssue = null,
    ) {}
}

class OcrService
{
    /**
     * Extract text from a school ID image.
     * Tries OCR.Space cloud API first, then falls back to local Tesseract.
     */
    public function extract(string $imagePath, ?string $documentType = null): OcrResult
    {
        $isEnrollmentForm = $documentType === 'enrollment_form';
        // Primary: OCR.Space cloud API (no local install required)
        $result = $this->extractViaOcrSpace($imagePath, 2, $isEnrollmentForm);
        $isEnrollmentForm = $isEnrollmentForm
            || $this->isEnrollmentFormText((string) $result->rawText)
            || $this->overlayHasCompleteNameCaptions($result->overlay ?? []);
        if ($isEnrollmentForm) {
            $strictName = $this->extractNameFromOverlay($result->overlay ?? [], true);
            $result = $this->withEnrollmentName($result, $strictName);
        }

        if ($result->available && (trim((string) $result->rawText) !== '' || $isEnrollmentForm)) {
            // Enrollment forms can be photographed sideways or at a steep
            // angle. Try a second engine and explicit 90/180-degree rotations
            // whenever the labeled full name is missing or looks like OCR noise.
            if ($isEnrollmentForm && ! $result->name) {
                $alternate = $this->extractViaOcrSpace($imagePath, 1, true);
                $alternate = $this->validateEnrollmentName($alternate);

                if ($alternate->name) {
                    return $this->mergeNameScan($result, $alternate);
                }

                // Phone photos often include the whole form, so the three
                // name fields are only a few pixels tall. Crop and enlarge
                // the student-information band before spending retries on
                // rotations; the field labels still bound which words count
                // as the student's name.
                $cropPath = $this->createEnrollmentNameCrop($imagePath, $alternate->overlay ?? $result->overlay);
                if ($cropPath) {
                    try {
                        $cropped = $this->validateEnrollmentName($this->extractViaOcrSpace($cropPath, 1, true));
                        if ($cropped->name) {
                            return $this->mergeNameScan($result, $cropped);
                        }
                    } finally {
                        @unlink($cropPath);
                    }
                }

                foreach ([90, 270, 180] as $angle) {
                    $rotatedPath = $this->createRotatedImage($imagePath, $angle);
                    if (! $rotatedPath) {
                        continue;
                    }

                    try {
                        $rotated = $this->validateEnrollmentName($this->extractViaOcrSpace($rotatedPath, 2, true));
                        if ($rotated->name) {
                            return $this->mergeNameScan($result, $rotated);
                        }

                        $rotatedCropPath = $this->createEnrollmentNameCrop($rotatedPath, $rotated->overlay);
                        if ($rotatedCropPath) {
                            try {
                                $rotatedCrop = $this->validateEnrollmentName($this->extractViaOcrSpace($rotatedCropPath, 1, true));
                                if ($rotatedCrop->name) {
                                    return $this->mergeNameScan($result, $rotatedCrop);
                                }
                            } finally {
                                @unlink($rotatedCropPath);
                            }
                        }
                    } finally {
                        @unlink($rotatedPath);
                    }
                }

            }

            if ($isEnrollmentForm && ! $result->name) {
                $result = $this->withEnrollmentName(
                    $result,
                    null,
                    $this->diagnoseEnrollmentPhoto($imagePath, $result->overlay),
                );
            }

            return $result;
        }

        // Fallback: local Tesseract (if installed)
        $fallback = $this->extractViaTesseract($imagePath);

        if ($fallback->available) {
            if ($isEnrollmentForm) {
                return $this->withEnrollmentName($fallback, null);
            }
            return $fallback;
        }

        // An empty OCR result means the service responded but could not read the image.
        // Keep that distinct from a real service/API failure for the registration UI.
        return $result->available ? $result : $fallback;
    }

    private function isEnrollmentFormText(string $text): bool
    {
        return (bool) preg_match('/\b(?:enrol{1,2}ment\s+form|year\s*(?:&|and)\s*course)\b/i', $text);
    }

    private function withEnrollmentName(OcrResult $result, ?string $name, ?string $nameIssue = null): OcrResult
    {
        return new OcrResult(
            available: $result->available,
            name: $name,
            studentId: $result->studentId,
            course: $result->course,
            rawText: $result->rawText,
            overlay: $result->overlay,
            yearLevel: $result->yearLevel,
            section: $result->section,
            yearCourse: $result->yearCourse,
            nameIssue: $nameIssue ?? $result->nameIssue,
        );
    }

    /** Return a specific photo issue only when image/OCR geometry supports it. */
    private function diagnoseEnrollmentPhoto(string $imagePath, ?array $overlay): ?string
    {
        $info = @getimagesize($imagePath);
        if (! $info || ! $info[0] || ! $info[1]) {
            return null;
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        // BCC enrollment forms are landscape; a portrait upload is a strong
        // signal that the form is sideways in the frame.
        if ($height > $width * 1.15) {
            return 'sideways';
        }

        $bounds = $this->enrollmentNameCropBounds($overlay ?? [], $width, $height);
        if ($bounds) {
            [, , , $cropHeight, $skewAngle] = $bounds;
            if (abs($skewAngle) >= 5) {
                return 'tilted';
            }
            // A narrow name-information band relative to the whole photo means
            // the form is likely too far away for the name characters to OCR.
            if ($cropHeight / $height < 0.045) {
                return 'small';
            }
        }

        $anchorHeights = [];
        foreach ($overlay ?? [] as $line) {
            foreach (($line['Words'] ?? []) as $word) {
                $text = trim((string) ($word['WordText'] ?? ''), "()[]{}:;,. \t\n\r\0\x0B");
                if (preg_match('/^(?:Name|Last|First|Middle)$/iu', $text)
                    && is_numeric($word['Height'] ?? null)
                    && (float) $word['Height'] > 0) {
                    $anchorHeights[] = (float) $word['Height'];
                }
            }
        }
        if ($anchorHeights !== []) {
            sort($anchorHeights);
            $medianTextHeight = $anchorHeights[(int) floor(count($anchorHeights) / 2)];
            if ($medianTextHeight / $height < 0.012) {
                return 'small';
            }
        }

        return null;
    }

    private function validateEnrollmentName(OcrResult $result): OcrResult
    {
        if (! $result->name || ! preg_match('/\b(?:last|first|middle|student|number|semester|schoolyear|course|major)\b/i', $result->name)) {
            return $result;
        }

        return new OcrResult(
            available: $result->available,
            studentId: $result->studentId,
            course: $result->course,
            rawText: $result->rawText,
            overlay: $result->overlay,
            yearLevel: $result->yearLevel,
            section: $result->section,
            yearCourse: $result->yearCourse,
            nameIssue: $result->nameIssue,
        );
    }

    private function mergeNameScan(OcrResult $base, OcrResult $nameSource): OcrResult
    {
        return new OcrResult(
            available: $base->available || $nameSource->available,
            name: $nameSource->name,
            studentId: $base->studentId ?? $nameSource->studentId,
            course: $base->course ?? $nameSource->course,
            rawText: $base->rawText ?? $nameSource->rawText,
            overlay: $base->overlay ?? $nameSource->overlay,
            yearLevel: $base->yearLevel ?? $nameSource->yearLevel,
            section: $base->section ?? $nameSource->section,
            yearCourse: $base->yearCourse ?? $nameSource->yearCourse,
            nameIssue: $nameSource->nameIssue ?? $base->nameIssue,
        );
    }

    // ── OCR.Space API ─────────────────────────────────────────────────────

    private function extractViaOcrSpace(string $imagePath, int $engine = 2, bool $strictEnrollmentForm = false): OcrResult
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
                    'OCREngine'          => $engine,
                    'isOverlayRequired'  => 'true',
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

            $parsed = $data['ParsedResults'][0] ?? [];
            $text = trim($parsed['ParsedText'] ?? '');
            $overlay = $parsed['TextOverlay']['Lines'] ?? [];
            if ($text === '' && is_array($overlay)) {
                $text = trim(implode("\n", array_map(
                    fn ($line) => implode(' ', array_map(
                        fn ($word) => trim((string) ($word['WordText'] ?? '')),
                        is_array($line['Words'] ?? null) ? $line['Words'] : [],
                    )),
                    $overlay,
                )));
            }

            if (empty($text)) {
                Log::info('OCR.Space returned empty text for ' . basename($imagePath));
                return new OcrResult(available: true, rawText: '');
            }

            $academic = $this->extractYearCourse($text);
            $enrollmentName = $strictEnrollmentForm
                || $this->isEnrollmentFormText($text)
                || $this->overlayHasCompleteNameCaptions($overlay);
            $overlayName = $this->extractNameFromOverlay($overlay, $enrollmentName);
            $nameCaptionsVisible = $this->overlayHasCompleteNameCaptions($overlay);

            return new OcrResult(
                available: true,
                name:      $enrollmentName
                    ? $overlayName
                    : ($overlayName ?? ($nameCaptionsVisible ? null : $this->extractName($text))),
                studentId: $this->extractStudentId($text),
                course:    $academic['course'] ?? $this->extractCourse($text),
                rawText:   $text,
                overlay:   is_array($overlay) ? $overlay : null,
                yearLevel: $academic['year_level'],
                section:   $academic['section'],
                yearCourse: $academic['year_course'],
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

    /**
     * Read the words on the same horizontal band as the explicit student
     * Name: label. OCR.Space's word boxes let us join names across wide gaps
     * without pulling in the dean or registrar text lower on the page.
     */
    private function extractNameFromOverlay(array $lines, bool $requireNameCaptions = false): ?string
    {
        $words = [];

        foreach ($lines as $lineIndex => $line) {
            $lineWords = $line['Words'] ?? [];
            if (! is_array($lineWords)) {
                continue;
            }

            $lineText = implode(' ', array_map(
                fn ($word) => is_array($word) ? (string) ($word['WordText'] ?? '') : '',
                $lineWords,
            ));
            $isCaptionLine = (bool) preg_match('/\b(?:Last|First|Middle)\s*Name\b/i', $lineText);
            $isOtherFieldLine = (bool) preg_match('/\b(?:Year\s*(?:&|and)\s*Course|Major|Semester|School\s*year|Subject\s+Code)\b/i', $lineText);

            foreach ($lineWords as $index => $word) {
                if (! is_array($word) || ! is_numeric($word['Left'] ?? null) || ! is_numeric($word['Top'] ?? null)) {
                    continue;
                }

                $words[] = [
                    'text' => trim((string) ($word['WordText'] ?? '')),
                    'left' => (float) $word['Left'],
                    'top' => (float) $word['Top'],
                    'width' => max(0.0, (float) ($word['Width'] ?? 0)),
                    'height' => max(1.0, (float) ($word['Height'] ?? 1)),
                    'line' => $lineIndex,
                    'index' => $index,
                    'caption_line' => $isCaptionLine,
                    'other_field_line' => $isOtherFieldLine,
                ];
            }
        }

        if ($words === []) {
            return null;
        }

        $heights = array_column($words, 'height');
        sort($heights);
        $medianHeight = $heights[(int) floor(count($heights) / 2)] ?? 10.0;

        foreach ($words as $label) {
            if (! preg_match('/^Name\s*[:;.]?$/iu', $label['text']) || $label['caption_line']) {
                continue;
            }

            $linePriorWords = array_values(array_filter(
                $words,
                fn ($word) => $word['line'] === $label['line'] && $word['left'] < $label['left'],
            ));
            usort($linePriorWords, fn ($a, $b) => $a['left'] <=> $b['left']);
            $priorText = implode(' ', array_column($linePriorWords, 'text'));
            if (preg_match('/\b(?:Last|First|Middle)\s*$/i', $priorText)) {
                continue;
            }

            $labelCenter = $label['top'] + $label['height'] / 2;
            $captionAnchoredName = $this->extractNameByCaptionCoordinates($words, $label, $medianHeight);
            if ($captionAnchoredName) {
                return $captionAnchoredName;
            }
            if ($this->hasCompleteNameCaptions($words)) {
                // All three field captions are visible, but one or more value
                // columns could not be read. Do not fall back to nearby text.
                return null;
            }
            if ($requireNameCaptions) {
                return null;
            }

            $tolerance = max($label['height'], $medianHeight) * 1.05;
            $row = array_values(array_filter($words, function ($word) use ($label, $labelCenter, $tolerance) {
                $wordCenter = $word['top'] + $word['height'] / 2;
                $distance = abs($word['left'] - $label['left']);
                $tiltTolerance = max($tolerance, $distance * 0.035);

                return $word['left'] >= $label['left'] + $label['width'] * 0.75
                    // OCR.Space may split a long, tilted row into several
                    // visual lines. Keep words from the label's own OCR line,
                    // and widen the vertical band gradually with horizontal
                    // distance to account for perspective tilt.
                    && ($word['line'] === $label['line'] || abs($wordCenter - $labelCenter) <= $tiltTolerance)
                    && ! $word['caption_line']
                    && ! $word['other_field_line']
                    && ! preg_match('/^[\s:;,.]+$/u', $word['text'])
                    && ! preg_match('/\d/u', $word['text'])
                    && ! preg_match('/^Name\s*[:;.]?$/iu', $word['text']);
            }));

            usort($row, fn ($a, $b) => $a['left'] <=> $b['left']);
            $name = $this->cleanName($this->joinOverlayWords($row));

            if ($this->looksLikeEnrollmentName($name)) {
                return $name;
            }
        }

        return null;
    }

    private function hasCompleteNameCaptions(array $words): bool
    {
        $text = implode(' ', array_column($words, 'text'));

        return preg_match('/\bLast\s*Name\b/i', $text)
            && preg_match('/\bFirst\s*Name\b/i', $text)
            && preg_match('/\bMiddle\s*Name\b/i', $text);
    }

    private function overlayHasCompleteNameCaptions(array $lines): bool
    {
        $words = [];
        foreach ($lines as $line) {
            foreach (($line['Words'] ?? []) as $word) {
                if (is_array($word)) {
                    $words[] = ['text' => (string) ($word['WordText'] ?? '')];
                }
            }
        }

        return $this->hasCompleteNameCaptions($words);
    }

    /**
     * On BCC enrollment forms, the three captions sit directly below the
     * student's surname, first name, and middle name. Use those coordinates
     * to divide the row into columns so nearby fields like Student Number can
     * never leak into the student's name.
     */
    private function extractNameByCaptionCoordinates(array $words, array $label, float $medianHeight): ?string
    {
        $captions = [];
        $types = ['Last', 'First', 'Middle'];

        foreach ($words as $word) {
            $normalized = trim($word['text'], "()[]{}:;,. \t\n\r\0\x0B");
            foreach ($types as $type) {
                if (preg_match('/^'.preg_quote($type, '/').'\s*Name$/iu', $normalized)) {
                    $captions[$type] = [
                        'center' => $word['left'] + $word['width'] / 2,
                        'top' => $word['top'],
                        'height' => $word['height'],
                    ];
                    continue 2;
                }

                if (! preg_match('/^'.preg_quote($type, '/').'$/iu', $normalized)) {
                    continue;
                }

                $partners = array_values(array_filter($words, function ($candidate) use ($word) {
                    $candidateText = trim($candidate['text'], "()[]{}:;,. \t\n\r\0\x0B");
                    $sameBand = $candidate['line'] === $word['line']
                        || abs(($candidate['top'] + $candidate['height'] / 2) - ($word['top'] + $word['height'] / 2)) <= max($candidate['height'], $word['height']);

                    return preg_match('/^Name$/iu', $candidateText)
                        && $candidate['left'] > $word['left']
                        && $candidate['left'] - ($word['left'] + $word['width']) <= max(120, $word['height'] * 8)
                        && $sameBand;
                }));
                usort($partners, fn ($a, $b) => $a['left'] <=> $b['left']);
                if ($partners !== []) {
                    $partner = $partners[0];
                    $captions[$type] = [
                        'center' => ($word['left'] + $partner['left'] + $partner['width']) / 2,
                        'top' => min($word['top'], $partner['top']),
                        'height' => max($word['height'], $partner['height']),
                    ];
                }
            }
        }

        if (count($captions) !== 3) {
            return null;
        }

        $ordered = array_values($captions);
        usort($ordered, fn ($a, $b) => $a['center'] <=> $b['center']);
        $xValues = array_column($ordered, 'center');
        $yValues = array_map(fn ($caption) => $caption['top'] + $caption['height'] / 2, $ordered);
        $meanX = array_sum($xValues) / 3;
        $meanY = array_sum($yValues) / 3;
        $varianceX = 0.0;
        $covariance = 0.0;
        foreach ($ordered as $index => $caption) {
            $varianceX += ($caption['center'] - $meanX) ** 2;
            $covariance += ($caption['center'] - $meanX) * ($yValues[$index] - $meanY);
        }
        $slope = $varianceX > 0 ? $covariance / $varianceX : 0.0;
        $labelCenter = $label['top'] + $label['height'] / 2;
        $parts = [];

        foreach ($types as $type) {
            $caption = $captions[$type];
            $columnIndex = array_search($caption, $ordered, true);
            $leftBoundary = $columnIndex === 0
                ? $label['left'] + $label['width'] * 0.65
                : ($ordered[$columnIndex - 1]['center'] + $caption['center']) / 2;
            $rightBoundary = $columnIndex === 2
                ? INF
                : ($caption['center'] + $ordered[$columnIndex + 1]['center']) / 2;
            $valueWords = array_values(array_filter($words, function ($word) use ($leftBoundary, $rightBoundary, $label, $caption, $slope, $medianHeight) {
                $centerX = $word['left'] + $word['width'] / 2;
                $centerY = $word['top'] + $word['height'] / 2;
                $expectedY = $label['top'] + $label['height'] / 2 + $slope * ($centerX - $label['left']);
                $maxDelta = max($label['height'], $medianHeight, $word['height']) * 1.15;
                $isCaption = (bool) preg_match('/\b(?:Last|First|Middle)\s*Name\b/i', $word['text']);

                return $centerX >= $leftBoundary
                    && $centerX < $rightBoundary
                    && $centerY < $caption['top'] + $caption['height'] * 0.25
                    && abs($centerY - $expectedY) <= $maxDelta
                    && ! $word['caption_line']
                    && ! $word['other_field_line']
                    && ! $isCaption
                    && ! preg_match('/\d/u', $word['text'])
                    && ! preg_match('/^Name\s*[:;.]?$/iu', $word['text']);
            }));
            usort($valueWords, fn ($a, $b) => $a['left'] <=> $b['left']);
            $parts[$type] = $this->cleanName($this->joinOverlayWords($valueWords));

            if ($parts[$type] === '') {
                return null;
            }
        }

        $fullName = trim($parts['Last'].' '.$parts['First'].' '.$parts['Middle']);

        return $this->looksLikeEnrollmentName($fullName) ? $fullName : null;
    }

    private function joinOverlayWords(array $words): string
    {
        $result = '';

        foreach ($words as $word) {
            $text = trim((string) ($word['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            if (preg_match('/^[,.;:\x27\x{2019}\-]+$/u', $text)) {
                $result = rtrim($result).$text;
            } elseif (preg_match('/[,.;:\x27\x{2019}\-]$/u', $result)) {
                $result .= $text;
            } else {
                $result .= ($result === '' ? '' : ' ').$text;
            }
        }

        return $result;
    }

    private function extractName(string $text): ?string
    {
        // Prefer the value explicitly attached to a Name label on any
        // document, whether or not OCR recognized its title as an enrollment form.
        $fieldName = $this->extractEnrollmentFormName($text);
        if ($fieldName !== null) {
            return $fieldName;
        }

        $lines = $this->normalizeLines($text);

        // Preserve older vertical-ID layout where the student name is just
        // above a standalone Name label with no value after the label.
        for ($i = 1; $i < count($lines); $i++) {
            if (preg_match('/^Name\s*[:;.]?\s*$/i', $lines[$i])
                && $this->looksLikePersonName($lines[$i - 1])) {
                return $this->cleanName($lines[$i - 1]);
            }
        }

        // If the explicit student Name field exists but OCR only found one
        // component (for example, the surname), do not fall through to the
        // generic name guesses. extract() will retry with another OCR pass.
        if ($this->hasStudentNameLabel($text)) {
            return null;
        }

        // Enrollment forms have several staff signatures near the bottom. Only
        // accept a name next to the student's explicit "Name" field on these
        // forms; the generic ID heuristics can otherwise mistake a dean or
        // registrar signature for the student.
        if (preg_match('/\b(enrol{1,2}ment\s+form|student\s*\x27?s\s+copy|year\s*(?:&|and)\s*course|student\s+number)\b/i', $text)) {
            return null;
        }

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

    private function extractEnrollmentFormName(string $text): ?string
    {
        $lines = $this->normalizeLines($text);
        $nextField = '/\b(?:Year\s*(?:&|and)\s*Course|Major|Semester|School\s*year|Subject\s+Code|Instructor|Total\s+Units|Dean|College\s+Registrar)\b/i';

        // OCR.Space may put the heading, student fields, and captions on one
        // long line. Search the whole OCR result for the actual Name: label,
        // while skipping captions like Last Name and Middle Name.
        // The colon is optional because OCR often drops punctuation in forms.
        preg_match_all('/\bName\s*[:;.]?\s*/i', $text, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] ?? [] as [$label, $offset]) {
            $prefix = substr($text, max(0, $offset - 24), min(24, $offset));
            if (preg_match('/(?:Last|First|Middle)\s*$/i', $prefix)) {
                continue;
            }

            $name = substr($text, $offset + strlen($label));
            if (preg_match($nextField, $name, $fieldMatch, PREG_OFFSET_CAPTURE)) {
                $name = substr($name, 0, $fieldMatch[0][1]);
            }

            $name = $this->cleanName($name);

            // Reject a surname-only result so another OCR pass is attempted.
            if ($this->looksLikeEnrollmentName($name)) {
                return $name;
            }
        }

        foreach ($lines as $i => $line) {
            // Ignore captions such as "(First Name)" and "(Middle Name)";
            // only the actual field begins with the standalone Name label.
            if (! preg_match('/^Name\s*[:;.]?\s*(.*)$/i', $line, $match)) {
                continue;
            }

            $name = trim($match[1]);
            if (preg_match($nextField, $name, $fieldMatch, PREG_OFFSET_CAPTURE)) {
                $name = substr($name, 0, $fieldMatch[0][1]);
            }

            // The OCR may return the last, first, and middle names on separate
            // lines. Join the complete Name field and its captions, then stop
            // at the next enrollment field before reaching signatures.
            for ($j = $i + 1; $j < count($lines); $j++) {
                $next = $lines[$j];
                if (preg_match($nextField, $next, $fieldMatch, PREG_OFFSET_CAPTURE)) {
                    $prefix = trim(substr($next, 0, $fieldMatch[0][1]));
                    if ($prefix !== '') {
                        $name .= ' '.$prefix;
                    }
                    break;
                }
                $name .= ' '.$next;
            }

            $name = $this->cleanName($name);

            return $this->looksLikeEnrollmentName($name) ? $name : null;
        }

        return null;
    }

    private function createRotatedImage(string $imagePath, int $angle): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($imagePath);
        if (! $info) {
            return null;
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($imagePath),
            IMAGETYPE_PNG => @imagecreatefrompng($imagePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($imagePath),
            default => null,
        };
        if (! $source) {
            return null;
        }

        $background = imagecolorallocate($source, 255, 255, 255);
        $rotated = imagerotate($source, $angle, $background);
        imagedestroy($source);
        if (! $rotated) {
            return null;
        }

        $rotatedPath = sys_get_temp_dir().'/enrollment_rotated_'.uniqid().'.png';
        $saved = imagepng($rotated, $rotatedPath);
        imagedestroy($rotated);

        return $saved ? $rotatedPath : null;
    }

    private function createEnrollmentNameCrop(string $imagePath, ?array $overlay): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($imagePath);
        if (! $info) {
            return null;
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($imagePath),
            IMAGETYPE_PNG => @imagecreatefrompng($imagePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($imagePath),
            default => null,
        };

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $bounds = $this->enrollmentNameCropBounds($overlay ?? [], $width, $height);
        if (! $bounds) {
            imagedestroy($source);
            return null;
        }

        [$left, $top, $cropWidth, $cropHeight, $skewAngle] = $bounds;
        $crop = imagecrop($source, [
            'x' => $left,
            'y' => $top,
            'width' => $cropWidth,
            'height' => $cropHeight,
        ]);
        imagedestroy($source);

        if (! $crop) {
            return null;
        }

        if (abs($skewAngle) >= 0.35 && abs($skewAngle) <= 10) {
            $background = imagecolorallocate($crop, 255, 255, 255);
            $deskewed = imagerotate($crop, $skewAngle, $background);
            if ($deskewed) {
                imagedestroy($crop);
                $crop = $deskewed;
            }
        }

        imagefilter($crop, IMG_FILTER_GRAYSCALE);
        imagefilter($crop, IMG_FILTER_CONTRAST, -15);
        if (function_exists('imageconvolution')) {
            @imageconvolution($crop, [
                [0, -1, 0],
                [-1, 5, -1],
                [0, -1, 0],
            ], 1, 0);
        }

        // The name row is very small in full-page phone photos. Enlarge the
        // focused crop three times before the final OCR pass.
        $scaled = imagescale($crop, max(1, imagesx($crop) * 3), max(1, imagesy($crop) * 3), IMG_BICUBIC);
        if ($scaled) {
            imagedestroy($crop);
            $crop = $scaled;
        }

        $cropPath = sys_get_temp_dir().'/enrollment_name_'.uniqid().'.png';
        $saved = imagepng($crop, $cropPath);
        imagedestroy($crop);

        return $saved ? $cropPath : null;
    }

    /** @return array{int,int,int,int,float}|null */
    private function enrollmentNameCropBounds(array $overlay, int $imageWidth, int $imageHeight): ?array
    {
        $words = [];
        $label = null;
        $captionBounds = [];
        $studentNumberY = null;
        $yearCourseY = null;
        $skewAngle = 0.0;

        foreach ($overlay as $lineIndex => $line) {
            $lineWords = $line['Words'] ?? [];
            if (! is_array($lineWords)) {
                continue;
            }

            $lineText = implode(' ', array_map(
                fn ($word) => is_array($word) ? (string) ($word['WordText'] ?? '') : '',
                $lineWords,
            ));
            $lineTop = min(array_map(
                fn ($word) => is_array($word) && is_numeric($word['Top'] ?? null) ? (float) $word['Top'] : INF,
                $lineWords,
            ) ?: [INF]);
            $lineBottom = max(array_map(
                fn ($word) => is_array($word) && is_numeric($word['Top'] ?? null) ? (float) $word['Top'] + (float) ($word['Height'] ?? 0) : 0,
                $lineWords,
            ) ?: [0]);
            $lineCenter = ($lineTop + $lineBottom) / 2;

            if (preg_match('/\bStudent\s*(?:Number|No\.?|ID)\b/i', $lineText)) {
                $studentNumberY = $lineCenter;
            }
            if (preg_match('/\bYear\s*(?:&|and)\s*Course\b/i', $lineText)) {
                $yearCourseY = $lineCenter;
            }

            $isNameCaption = (bool) preg_match('/\b(?:Last|First|Middle)\s*Name\b/i', $lineText);
            $isOtherFieldLine = (bool) preg_match('/\b(?:Year\s*(?:&|and)\s*Course|Major|Semester|School\s*year|Subject\s+Code)\b/i', $lineText);
            foreach ($lineWords as $wordIndex => $word) {
                if (! is_array($word) || ! is_numeric($word['Left'] ?? null) || ! is_numeric($word['Top'] ?? null)) {
                    continue;
                }

                $entry = [
                    'text' => trim((string) ($word['WordText'] ?? '')),
                    'left' => (float) $word['Left'],
                    'top' => (float) $word['Top'],
                    'width' => max(0.0, (float) ($word['Width'] ?? 0)),
                    'height' => max(1.0, (float) ($word['Height'] ?? 1)),
                    'line' => $lineIndex,
                    'index' => $wordIndex,
                    'caption_line' => $isNameCaption,
                    'other_field_line' => $isOtherFieldLine,
                ];
                $words[] = $entry;

                if ($isNameCaption) {
                    $captionBounds[] = [
                        $entry['left'],
                        $entry['top'],
                        $entry['left'] + $entry['width'],
                        $entry['top'] + $entry['height'],
                    ];
                }

                if (! $label && ! $isNameCaption && preg_match('/^Name\s*[:;.]?$/iu', $entry['text'])) {
                    $label = $entry;
                }
            }
        }

        if ($label) {
            $scale = max(8.0, $label['height']);
            $left = $label['left'] - 1.2 * $scale;
            $top = $label['top'] - 1.8 * $scale;
            $right = $label['left'] + $label['width'] + 2.5 * $scale;
            $bottom = $label['top'] + 3.0 * $scale;
            $rowAnchors = [$label];

            foreach ($words as $word) {
                $center = $word['top'] + $word['height'] / 2;
                $labelCenter = $label['top'] + $label['height'] / 2;
                $tiltTolerance = max($scale, $word['height']) * 1.05;
                $tiltTolerance = max($tiltTolerance, abs($word['left'] - $label['left']) * 0.035);
                if ($word['left'] > $label['left'] + $label['width']
                    && ($word['line'] === $label['line'] || abs($center - $labelCenter) <= $tiltTolerance)
                    && ! $word['caption_line']
                    && ! $word['other_field_line']
                    && ! preg_match('/\d/u', $word['text'])
                    && ! preg_match('/\b(?:Last|First|Middle)\s*Name\b/i', $word['text'])) {
                    $rowAnchors[] = $word;
                    $left = min($left, $word['left'] - 0.5 * $scale);
                    $right = max($right, $word['left'] + $word['width'] + 0.7 * $scale);
                    $top = min($top, $word['top'] - 0.6 * $scale);
                    $bottom = max($bottom, $word['top'] + $word['height'] + 0.6 * $scale);
                }
            }

            $skewAngle = $this->estimateRowSkew($rowAnchors);

            foreach ($captionBounds as [$captionLeft, $captionTop, $captionRight, $captionBottom]) {
                if ($captionTop > $label['top']) {
                    $left = min($left, $captionLeft - 0.5 * $scale);
                    $right = max($right, $captionRight + 0.8 * $scale);
                    $bottom = max($bottom, $captionBottom + 0.8 * $scale);
                }
            }
        } elseif ($studentNumberY !== null && $yearCourseY !== null && $yearCourseY > $studentNumberY) {
            // If the Name: glyph itself was missed, use the OCR coordinates of
            // the neighboring student-number and year/course labels to bound
            // the student-information block instead of fixed image pixels.
            $top = $studentNumberY;
            $bottom = $yearCourseY;
            $left = 0;
            $right = $imageWidth;
        } else {
            return null;
        }

        $left = max(0, min($imageWidth - 1, (int) floor($left)));
        $top = max(0, min($imageHeight - 1, (int) floor($top)));
        $right = max($left + 1, min($imageWidth, (int) ceil($right)));
        $bottom = max($top + 1, min($imageHeight, (int) ceil($bottom)));

        return [$left, $top, $right - $left, $bottom - $top, $skewAngle];
    }

    private function estimateRowSkew(array $words): float
    {
        if (count($words) < 3) {
            return 0.0;
        }

        $points = array_map(fn ($word) => [
            (float) $word['left'] + (float) $word['width'] / 2,
            (float) $word['top'] + (float) $word['height'] / 2,
        ], $words);
        $meanX = array_sum(array_column($points, 0)) / count($points);
        $meanY = array_sum(array_column($points, 1)) / count($points);
        $covariance = 0.0;
        $varianceX = 0.0;

        foreach ($points as [$x, $y]) {
            $covariance += ($x - $meanX) * ($y - $meanY);
            $varianceX += ($x - $meanX) ** 2;
        }

        if ($varianceX <= 0) {
            return 0.0;
        }

        return rad2deg(atan($covariance / $varianceX));
    }

    private function looksLikeEnrollmentName(string $name): bool
    {
        preg_match_all('/[\p{L}]+(?:[.\x27-][\p{L}]+)*/u', $name, $matches);
        $words = count($matches[0] ?? []);

        return $words >= 2
            && $words <= 10
            && (bool) preg_match('/^[\p{L}][\p{L}\s\-,.\x27]{3,100}$/u', $name)
            && ! preg_match('/\b(?:COLLEGE|UNIVERSITY|REGISTRAR|ENROLLED|COURSE|SEMESTER)\b/i', $name);
    }

    private function hasStudentNameLabel(string $text): bool
    {
        preg_match_all('/\bName\b/i', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] ?? [] as [$label, $offset]) {
            $prefix = substr($text, max(0, $offset - 24), min(24, $offset));
            if (! preg_match('/(?:Last|First|Middle)\s*$/i', $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function cleanName(string $name): string
    {
        $name = preg_replace('/\b(?:Last|First|Middle)\s*Name\b/i', '', $name);
        // OCR sometimes merges the preceding "Student Number" label into the
        // surname (for example, "NumberSERDAN"). Never treat that label as a
        // name component on enrollment forms.
        $name = preg_replace('/(?:Student\s*)?Number(?=\p{L}|\b)/iu', ' ', $name);
        $name = str_replace(["\u{2019}", "\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}"], ["'", '-', '-', '-', '-', '-'], $name);

        return preg_replace('/\s+/', ' ', trim(preg_replace('/[^\p{L}\s\-,.\x27]/u', '', $name)));
    }

    /** @return array{course:?string,year_level:?string,section:?string,year_course:?string} */
    private function extractYearCourse(string $text): array
    {
        $empty = ['course' => null, 'year_level' => null, 'section' => null, 'year_course' => null];

        if (! preg_match('/\bYear\s*(?:&|and)\s*Course\s*[:;.]?\s*([^\r\n]+)/i', $text, $match)) {
            return $empty;
        }

        $value = trim($match[1]);
        $value = preg_split('/\b(?:Major|Semester|School\s*year|Subject\s+Code|Day|Time|Units|Instructor)\s*[:;]?/i', $value)[0] ?? $value;
        $value = trim($value, " \t\n\r\0\x0B:;,.|-");

        if ($value === '') {
            return $empty;
        }

        $result = [
            'course' => $this->extractCourse($text),
            'year_level' => null,
            'section' => null,
            'year_course' => $value,
        ];

        if (preg_match('/\b([1-9])(?:st|nd|rd|th)?(?:\s*year)?\s*[- ]?\s*([A-Z])?\b/i', $value, $academic)) {
            $year = (int) $academic[1];
            $result['year_level'] = $year.$this->ordinalSuffix($year).' Year';
            $result['section'] = isset($academic[2]) && $academic[2] !== '' ? strtoupper($academic[2]) : null;
        }

        return $result;
    }

    private function ordinalSuffix(int $number): string
    {
        return match ($number % 100) {
            11, 12, 13 => 'th',
            default => match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            },
        };
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

        // Enrollment forms label this field "Student Number" instead of "Student ID".
        if (preg_match('~Student\s+Number\s*[:#]?\s*(\d{2}[-./\s]+\d[-./\s]+\d{4})~i', $text, $m)) {
            return $this->normalizeStudentId($m[1]);
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

        // BCC enrollment forms use values such as "BSIS-4A" after "Year & Course".
        if (preg_match('~Year\s*(?:&|and)\s*Course\s*:?\s*([A-Z]{2,8})\s*-\s*\d{1,2}\s*[A-Z]?~i', $text, $m)) {
            return strtoupper($m[1]);
        }

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
