<?php

namespace App\Support;

/**
 * Money is handled as integer paise internally to avoid float drift,
 * and as 2-decimal strings at the edges (DB columns and JSON).
 */
final class Money
{
    public static function toPaise(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * Percentages such as "18.00" become basis points (1800).
     */
    public static function toBasisPoints(string|int|float $percent): int
    {
        return (int) round(((float) $percent) * 100);
    }

    public static function format(int $paise): string
    {
        $sign = $paise < 0 ? '-' : '';
        $paise = abs($paise);

        return sprintf('%s%d.%02d', $sign, intdiv($paise, 100), $paise % 100);
    }
}
