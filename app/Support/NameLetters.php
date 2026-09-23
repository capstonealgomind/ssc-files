<?php

namespace App\Support;

class NameLetters
{
    /**
     * Letters only, in order, so "Juan dela Cruz" and "JUAN-DELA CRUZ" match.
     */
    public static function key(?string $name): string
    {
        return preg_replace('/[^a-z]/', '', self::fold((string) $name)) ?? '';
    }

    /**
     * Name words in alphabetical order, so last-name-first and first-name-first match.
     *
     * @return list<string>
     */
    public static function words(?string $name): array
    {
        $value = self::fold((string) $name);
        $value = preg_replace('/[^a-z]+/', ' ', $value) ?? '';
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $parts = array_values(array_filter(
            $parts,
            fn (string $part) => strlen($part) >= 2,
        ));
        sort($parts, SORT_STRING);

        return $parts;
    }

    public static function samePerson(?string $left, ?string $right): bool
    {
        $leftKey = self::key($left);
        $rightKey = self::key($right);

        if ($leftKey !== '' && $leftKey === $rightKey) {
            return true;
        }

        $leftWords = self::words($left);
        $rightWords = self::words($right);
        $shorter = count($leftWords) <= count($rightWords) ? $leftWords : $rightWords;
        $longer = count($leftWords) <= count($rightWords) ? $rightWords : $leftWords;

        // Same words in any order, or a shorter name that only drops a middle name.
        return count($shorter) >= 2 && array_diff($shorter, $longer) === [];
    }

    /**
     * 1 or 2 letter changes between two names, including a different word order.
     * Exact matches are not included.
     */
    public static function nearDuplicateDistance(?string $left, ?string $right): ?int
    {
        if (self::samePerson($left, $right)) {
            return null;
        }

        $distances = [];
        $wordDistance = self::wordEditDistance(self::words($left), self::words($right));

        if ($wordDistance >= 1 && $wordDistance <= 2) {
            $distances[] = $wordDistance;
        }

        $leftKey = self::key($left);
        $rightKey = self::key($right);

        if (
            strlen($leftKey) >= 8
            && strlen($rightKey) >= 8
            && abs(strlen($leftKey) - strlen($rightKey)) <= 2
        ) {
            $keyDistance = levenshtein($leftKey, $rightKey);

            if ($keyDistance >= 1 && $keyDistance <= 2) {
                $distances[] = $keyDistance;
            }
        }

        return $distances === [] ? null : min($distances);
    }

    /**
     * @param  list<string>  $left
     * @param  list<string>  $right
     */
    private static function wordEditDistance(array $left, array $right): int
    {
        if (count($left) < 2 || count($left) !== count($right) || count($left) > 6) {
            return PHP_INT_MAX;
        }

        $best = PHP_INT_MAX;

        foreach (self::permutations($right) as $candidate) {
            $distance = 0;

            foreach ($left as $index => $word) {
                $distance += levenshtein($word, $candidate[$index]);

                if ($distance >= $best || $distance > 2) {
                    break;
                }
            }

            $best = min($best, $distance);
        }

        return $best;
    }

    /**
     * @param  list<string>  $items
     * @return list<list<string>>
     */
    private static function permutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $result = [];

        foreach ($items as $index => $item) {
            $rest = $items;
            array_splice($rest, $index, 1);

            foreach (self::permutations($rest) as $permutation) {
                array_unshift($permutation, $item);
                $result[] = $permutation;
            }
        }

        return $result;
    }

    private static function fold(string $name): string
    {
        $value = mb_strtolower(trim($name));

        return strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
            'ç' => 'c',
        ]);
    }
}
