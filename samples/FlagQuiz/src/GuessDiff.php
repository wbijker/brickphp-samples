<?php

namespace Samples\FlagQuiz;

/**
 * Letter-by-letter comparison of a wrong guess against the country's real
 * name, for the review list on the results screen.
 *
 * The alignment is the Levenshtein edit distance: the table is filled with the
 * cost of turning the first i letters of the answer into the first j letters of
 * the guess, then walked back from the corner to recover *which* edits the
 * cheapest route used. That backward walk is the whole point — the distance on
 * its own is a number, and what the player needs to see is where it came from.
 *
 * A substitution reads as both halves of the swap: the letter that should have
 * been there, then the one that was typed instead.
 */
final class GuessDiff
{
    /**
     * Align `$typed` against `$answer`.
     *
     * @return DiffPart[] runs covering both strings, in reading order
     */
    public static function compare(string $answer, string $typed): array
    {
        $a = mb_str_split($answer);
        $b = mb_str_split($typed);
        $m = count($a);
        $n = count($b);

        // cost[i][j] — edits turning the first i of $a into the first j of $b.
        // Row 0 / column 0 are the degenerate cases: delete everything, or
        // insert everything.
        $cost = [];
        for ($i = 0; $i <= $m; $i++) {
            $cost[$i] = array_fill(0, $n + 1, 0);
            $cost[$i][0] = $i;
        }
        for ($j = 0; $j <= $n; $j++) {
            $cost[0][$j] = $j;
        }
        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                $swap = self::sameLetter($a[$i - 1], $b[$j - 1]) ? 0 : 1;
                $cost[$i][$j] = min(
                    $cost[$i - 1][$j] + 1,       // letter of the answer went untyped
                    $cost[$i][$j - 1] + 1,       // letter typed that isn't in the answer
                    $cost[$i - 1][$j - 1] + $swap,
                );
            }
        }

        // Walk back from the corner. Parts come out in reverse, so a
        // substitution pushes its typed letter first to end up second.
        $parts = [];
        $i = $m;
        $j = $n;
        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && self::sameLetter($a[$i - 1], $b[$j - 1]) && $cost[$i][$j] === $cost[$i - 1][$j - 1]) {
                $parts[] = new DiffPart(DiffKind::Same, $b[$j - 1]);
                $i--;
                $j--;
            } elseif ($i > 0 && $j > 0 && $cost[$i][$j] === $cost[$i - 1][$j - 1] + 1) {
                $parts[] = new DiffPart(DiffKind::Added, $b[$j - 1]);
                $parts[] = new DiffPart(DiffKind::Missing, $a[$i - 1]);
                $i--;
                $j--;
            } elseif ($j > 0 && $cost[$i][$j] === $cost[$i][$j - 1] + 1) {
                $parts[] = new DiffPart(DiffKind::Added, $b[$j - 1]);
                $j--;
            } else {
                $parts[] = new DiffPart(DiffKind::Missing, $a[$i - 1]);
                $i--;
            }
        }

        return self::coalesce(array_reverse($parts));
    }

    /**
     * Case is not what the quiz marks people down for — `matches()` lowercases
     * before comparing — so the diff doesn't call it a mistake either.
     */
    private static function sameLetter(string $x, string $y): bool
    {
        return mb_strtolower($x) === mb_strtolower($y);
    }

    /**
     * Merge neighbouring single letters of the same kind into one run.
     *
     * @param DiffPart[] $parts
     * @return DiffPart[]
     */
    private static function coalesce(array $parts): array
    {
        $out = [];
        foreach ($parts as $part) {
            $last = $out === [] ? null : $out[count($out) - 1];
            if ($last !== null && $last->kind === $part->kind) {
                $out[count($out) - 1] = new DiffPart($part->kind, $last->text . $part->text);
                continue;
            }
            $out[] = $part;
        }
        return $out;
    }
}
