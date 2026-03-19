<?php

namespace App\Support\Colors;

use App\Contracts\ColorConverter as ColorConverterContract;

class ColorConverter implements ColorConverterContract
{
    public function convertHexToRgba(?string $color, $opacity = false): string
    {
        $default = 'rgb(230,46,4)';

        if (!$color) {
            return $default;
        }

        $normalized = ltrim(trim($color), '#');

        if (strlen($normalized) === 3) {
            $normalized = preg_replace('/(.)/', '$1$1', $normalized);
        }

        if (strlen($normalized) !== 6 || !ctype_xdigit($normalized)) {
            return $default;
        }

        $rgb = [
            hexdec(substr($normalized, 0, 2)),
            hexdec(substr($normalized, 2, 2)),
            hexdec(substr($normalized, 4, 2)),
        ];

        if ($opacity === false || $opacity === null || $opacity === '') {
            return 'rgb(' . implode(',', $rgb) . ')';
        }

        $alpha = max(0, min(1, (float) $opacity));

        return 'rgba(' . implode(',', $rgb) . ',' . $alpha . ')';
    }
}
