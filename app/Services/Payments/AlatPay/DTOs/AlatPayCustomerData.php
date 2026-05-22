<?php

namespace App\Services\Payments\AlatPay\DTOs;

class AlatPayCustomerData
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $metadata,
    ) {
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'metadata' => $this->metadata,
        ];
    }
}
