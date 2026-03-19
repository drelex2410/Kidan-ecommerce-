<?php

namespace App\Support\Addons;

use App\Models\Addon;
use App\Contracts\AddonStatusManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AddonStatusService implements AddonStatusManager
{
    private const CACHE_KEY = 'addons.registry';

    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, 86400, function () {
            return Addon::query()
                ->select('id', 'name', 'unique_identifier', 'version', 'activated', 'purchase_code', 'image')
                ->get();
        });
    }

    public function frontendPayload(): Collection
    {
        return $this->all()->map(function (Addon $addon) {
            return [
                'unique_identifier' => $addon->unique_identifier,
                'version' => $addon->version,
                'activated' => (int) $addon->activated,
            ];
        })->values();
    }

    public function isActivated(string $identifier): bool
    {
        if ($identifier === '') {
            return false;
        }

        return $this->all()
            ->where('unique_identifier', $identifier)
            ->contains(function (Addon $addon) {
                return (int) $addon->activated === 1;
            });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('web_addons');
    }
}
