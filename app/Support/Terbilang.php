<?php

namespace App\Support;

/**
 * Angka menjadi kata dalam bahasa Indonesia (untuk kalimat BAST, mis. "1 (Satu) unit").
 */
class Terbilang
{
    private const WORDS = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

    public static function make(int $number): string
    {
        if ($number < 0) {
            return 'Minus ' . self::make(-$number);
        }

        if ($number === 0) {
            return 'Nol';
        }

        return trim(preg_replace('/\s+/', ' ',self::say($number)));
    }

    private static function say(int $n): string
    {
        return match (true) {
            $n < 12 => self::WORDS[$n],
            $n < 20 => self::say($n - 10) . ' Belas',
            $n < 100 => self::say(intdiv($n, 10)) . ' Puluh ' . self::say($n % 10),
            $n < 200 => 'Seratus ' . self::say($n - 100),
            $n < 1000 => self::say(intdiv($n, 100)) . ' Ratus ' . self::say($n % 100),
            $n < 2000 => 'Seribu ' . self::say($n - 1000),
            $n < 1000000 => self::say(intdiv($n, 1000)) . ' Ribu ' . self::say($n % 1000),
            default => self::say(intdiv($n, 1000000)) . ' Juta ' . self::say($n % 1000000),
        };
    }
}
