<?php

namespace App\Support\Uploads;

class UploadInspectionResult
{
    public function __construct(
        public readonly string $type,
        public readonly string $extension,
        public readonly string $displayName,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?string $hash = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }
}
