<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface AddonStatusManager
{
    public function all(): Collection;

    public function frontendPayload(): Collection;

    public function isActivated(string $identifier): bool;

    public function clearCache(): void;
}
