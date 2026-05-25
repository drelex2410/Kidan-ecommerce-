<?php

namespace App\Support\Uploads;

use RuntimeException;

class UploadValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        protected array $errors = [],
        protected int $status = 422,
    ) {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function status(): int
    {
        return $this->status;
    }
}
