<?php

namespace App\Services\Payments\AlatPay\DTOs;

class AlatPayStatusData
{
    public function __construct(
        public readonly string $reference,
        public readonly string $status,
        public readonly ?string $providerReference,
        public readonly ?string $transactionId,
        public readonly ?string $message,
        public readonly array $payload,
    ) {
    }
}
