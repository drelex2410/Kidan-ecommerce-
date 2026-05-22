<?php

namespace App\Services\Payments\AlatPay;

use RuntimeException;

class AlatPayGatewayException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly array $context = []
    ) {
        parent::__construct($message);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function context(): array
    {
        return $this->context;
    }
}
