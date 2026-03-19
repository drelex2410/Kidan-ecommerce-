<?php

namespace App\Services;

use App\Contracts\ApplicationBootstrap;

class ApplicationBootstrapService implements ApplicationBootstrap
{
    public function initialize(): void
    {
        // Standalone application bootstrap intentionally does not perform
        // any remote activation or external verification checks.
    }
}
