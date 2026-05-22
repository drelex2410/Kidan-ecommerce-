<?php

namespace App\Services\Payments\AlatPay\DTOs;

class AlatPayInitializationData
{
    public function __construct(
        public readonly string $businessId,
        public readonly string $businessName,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $orderId,
        public readonly string $description,
        public readonly string $channel,
        public readonly AlatPayCustomerData $customer,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $webhookUrl = null,
        public readonly array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'businessId' => $this->businessId,
            'businessName' => $this->businessName,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'orderId' => $this->orderId,
            'description' => $this->description,
            'channel' => $this->channel,
            'customer' => $this->customer->toArray(),
            'callbackUrl' => $this->callbackUrl,
            'redirectUrl' => $this->redirectUrl,
            'webhookUrl' => $this->webhookUrl,
            'metadata' => $this->metadata,
        ];
    }
}
