<?php

namespace App\Support;

/**
 * S5 toman formatting (scope §8): integer toman, Persian digits.
 *
 * Amounts are stored as integer toman in the DB and rendered here with
 * Persian digits and the Persian thousand separator, followed by تومان.
 * Example: 99000 → «۹۹٬۰۰۰ تومان». No floats, no rial conversion.
 */
final class Toman
{
    /** @var array<string, string> */
    private const FA_DIGITS = [
        '0' => '۰',
        '1' => '۱',
        '2' => '۲',
        '3' => '۳',
        '4' => '۴',
        '5' => '۵',
        '6' => '۶',
        '7' => '۷',
        '8' => '۸',
        '9' => '۹',
        ',' => '٬',
    ];

    public static function format(int $toman): string
    {
        $grouped = number_format($toman);
        $fa = strtr($grouped, self::FA_DIGITS);

        return $fa.' تومان';
    }
}
