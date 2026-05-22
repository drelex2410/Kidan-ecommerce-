<?php

namespace App\Services\Payments\AlatPay\DTOs;

class AlatPayWebhookData
{
    public function __construct(
        public readonly string $eventType,
        public readonly string $reference,
        public readonly ?string $providerReference,
        public readonly ?string $transactionId,
        public readonly string $status,
        public readonly array $payload,
    ) {
    }
}
