<?php

namespace App\Support;

class ClubPointMath
{
    public const SCALE = 6;

    public static function scale(): int
    {
        return self::SCALE;
    }

    public static function normalize(int|float|string|null $value, int $scale = self::SCALE): string
    {
        $numeric = self::sanitize($value);

        if (extension_loaded('bcmath')) {
            return bcadd($numeric, '0', $scale);
        }

        return number_format((float) $numeric, $scale, '.', '');
    }

    public static function add(int|float|string|null $left, int|float|string|null $right, int $scale = self::SCALE): string
    {
        if (extension_loaded('bcmath')) {
            return bcadd(self::sanitize($left), self::sanitize($right), $scale);
        }

        return number_format((float) $left + (float) $right, $scale, '.', '');
    }

    public static function subtract(int|float|string|null $left, int|float|string|null $right, int $scale = self::SCALE): string
    {
        if (extension_loaded('bcmath')) {
            return bcsub(self::sanitize($left), self::sanitize($right), $scale);
        }

        return number_format((float) $left - (float) $right, $scale, '.', '');
    }

    public static function multiply(int|float|string|null $left, int|float|string|null $right, int $scale = self::SCALE): string
    {
        if (extension_loaded('bcmath')) {
            return bcmul(self::sanitize($left), self::sanitize($right), $scale);
        }

        return number_format((float) $left * (float) $right, $scale, '.', '');
    }

    public static function divide(int|float|string|null $left, int|float|string|null $right, int $scale = self::SCALE): string
    {
        $divisor = self::sanitize($right);

        if (self::compare($divisor, '0', $scale) === 0) {
            return self::normalize('0', $scale);
        }

        if (extension_loaded('bcmath')) {
            return bcdiv(self::sanitize($left), $divisor, $scale);
        }

        return number_format((float) $left / (float) $divisor, $scale, '.', '');
    }

    public static function compare(int|float|string|null $left, int|float|string|null $right, int $scale = self::SCALE): int
    {
        if (extension_loaded('bcmath')) {
            return bccomp(self::sanitize($left), self::sanitize($right), $scale);
        }

        return ((float) $left <=> (float) $right);
    }

    public static function clampToZero(int|float|string|null $value, int $scale = self::SCALE): string
    {
        return self::compare($value, '0', $scale) < 0
            ? self::normalize('0', $scale)
            : self::normalize($value, $scale);
    }

    public static function toFloat(int|float|string|null $value, int $scale = self::SCALE): float
    {
        return (float) self::normalize($value, $scale);
    }

    private static function sanitize(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return trim((string) $value);
    }
}
