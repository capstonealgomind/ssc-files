<?php

namespace App\Services;

class BccLogoMatcher
{
    private const SAMPLE_SIZE = 20;
    private const MIN_SIMILARITY = 0.32;

    /**
     * Finds a scaled BCC seal pattern in the ID photo. Coarse color classes
     * allow substantial blur, downscaling, and lighting differences.
     */
    public function matches(string $imagePath): bool
    {
        if (! extension_loaded('gd') || ! is_file($imagePath)) {
            return false;
        }

        $templatePath = public_path('images/bcc.png');
        if (! is_file($templatePath)) {
            return false;
        }

        $source = @imagecreatefromstring((string) file_get_contents($imagePath));
        $template = @imagecreatefrompng($templatePath);
        if (! $source || ! $template) {
            return false;
        }

        $source = $this->scaleDown($source, 420);
        $reference = imagecreatetruecolor(self::SAMPLE_SIZE, self::SAMPLE_SIZE);
        imagecopyresampled(
            $reference,
            $template,
            0,
            0,
            0,
            0,
            self::SAMPLE_SIZE,
            self::SAMPLE_SIZE,
            imagesx($template),
            imagesy($template),
        );

        $referencePattern = $this->samplePattern($reference, 0, 0, self::SAMPLE_SIZE);
        $width = imagesx($source);
        $height = imagesy($source);
        $shortSide = min($width, $height);

        foreach ([0.10, 0.13, 0.17, 0.21, 0.26, 0.32, 0.39, 0.47, 0.56, 0.66] as $fraction) {
            $size = (int) round($shortSide * $fraction);
            if ($size < self::SAMPLE_SIZE || $size > $shortSide) {
                continue;
            }

            $step = max(3, (int) floor($size / 14));
            for ($top = 0; $top <= $height - $size; $top += $step) {
                for ($left = 0; $left <= $width - $size; $left += $step) {
                    $candidate = $this->samplePattern($source, $left, $top, $size);
                    if ($this->similarity($referencePattern, $candidate) >= self::MIN_SIMILARITY) {
                        imagedestroy($source);
                        imagedestroy($template);
                        imagedestroy($reference);

                        return true;
                    }
                }
            }
        }

        imagedestroy($source);
        imagedestroy($template);
        imagedestroy($reference);

        return false;
    }

    private function scaleDown(\GdImage $image, int $maxSide): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));

        if ($scale === 1.0) {
            return $image;
        }

        $scaled = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
        imagedestroy($image);

        return $scaled;
    }

    /** @return list<int> */
    private function samplePattern(\GdImage $image, int $left, int $top, int $size): array
    {
        $pattern = [];
        $sampleSize = self::SAMPLE_SIZE;

        for ($y = 0; $y < $sampleSize; $y++) {
            for ($x = 0; $x < $sampleSize; $x++) {
                $px = $left + min($size - 1, (int) floor(($x + 0.5) * $size / $sampleSize));
                $py = $top + min($size - 1, (int) floor(($y + 0.5) * $size / $sampleSize));
                $color = imagecolorat($image, $px, $py);
                $pattern[] = $this->colorClass(($color >> 16) & 0xff, ($color >> 8) & 0xff, $color & 0xff);
            }
        }

        return $pattern;
    }

    private function colorClass(int $red, int $green, int $blue): int
    {
        if (min($red, $green, $blue) > 205 && max($red, $green, $blue) - min($red, $green, $blue) < 65) {
            return 1; // white
        }
        if (max($red, $green, $blue) < 70) {
            return 2; // black / dark outline
        }
        if ($blue > $red * 1.12 && $blue > $green * 1.06 && $blue > 70) {
            return 3; // blue field
        }
        if ($red > 125 && $green > 75 && $red > $green * 1.08 && $green > $blue * 1.12) {
            return 4; // gold crest
        }
        if ($red > 125 && $red > $green * 1.35 && $red > $blue * 1.35) {
            return 5; // red stars / banner
        }

        return 0; // neutral or anti-aliased edge
    }

    /** @param list<int> $reference
     *  @param list<int> $candidate
     */
    private function similarity(array $reference, array $candidate): float
    {
        $score = 0.0;
        $informative = 0;

        foreach ($reference as $index => $expected) {
            $actual = $candidate[$index];

            // Ignore the reference's white background; it must not make a blank
            // ID photo look like a seal.
            if ($expected === 0 || $expected === 1) {
                continue;
            }

            $informative++;
            if ($expected === $actual) {
                $score += 1.0;
            } elseif ($actual === 0) {
                $score += 0.3; // tolerate blur and anti-aliased edges
            } elseif (($expected === 2 && $actual === 3) || ($expected === 3 && $actual === 2)) {
                $score += 0.35;
            }
        }

        return $informative > 0 ? $score / $informative : 0.0;
    }
}
