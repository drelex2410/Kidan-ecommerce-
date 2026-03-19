<?php

namespace App\Services\Content;

class ContentMedia
{
    public function asset(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return api_asset($value) ?: null;
    }

    public function gallery(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => $this->asset($value))
            ->filter()
            ->values()
            ->all();
    }
}
