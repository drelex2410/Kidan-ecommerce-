<?php

namespace App\Contracts;

interface ColorConverter
{
    public function convertHexToRgba(?string $color, $opacity = false): string;
}
