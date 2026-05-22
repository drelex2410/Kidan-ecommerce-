<?php

namespace App\Services\Payments\AlatPay;

use App\Models\CombinedOrder;
use App\Models\Payment;
use App\Services\Payments\AlatPay\DTOs\AlatPayCustomerData;
use App\Services\Payments\AlatPay\DTOs\AlatPayInitializationData;
use App\Services\Payments\AlatPay\DTOs\AlatPayStatusData;
use App\Services\Payments\AlatPay\DTOs\AlatPayWebhookData;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AlatPayTransformer
{
    public function toInitializationData(Payment $payment, ?CombinedOrder $order, AlatPayConfig $config): AlatPayInitializationData
    {
        $reference = $this->merchantReference($payment);
        $customer = $this->customer($payment, $order);

        return new AlatPayInitializationData(
            businessId: (string) $config->merchantId(),
            businessName: config('app.name', 'Kidan'),
            amount: (float) $payment->amount,
            currency: (string) ($payment->currency ?: 'NGN'),
            orderId: $reference,
            description: sprintf('Kidan %s payment', str_replace('_', ' ', $payment->payment_type)),
            channel: 'ecommerce',
            customer: $customer,
            callbackUrl: $config->callbackUrl(),
            redirectUrl: $payment->redirect_to,
            webhookUrl: $config->callbackUrl(),
            metadata: [
                'order_id' => $payment->combined_order_id,
                'order_code' => $payment->order_code,
                'escrow_id' => Arr::get($payment->meta ?? [], 'escrow_id'),
                'user_id' => $payment->user_id,
                'tenant_id' => Arr::get($payment->meta ?? [], 'tenant_id'),
                'payment_channel' => Arr::get($payment->meta ?? [], 'payment_channel', 'bank_transfer'),
                'currency' => (string) ($payment->currency ?: 'NGN'),
                'amount' => (float) $payment->amount,
                'session_reference' => $this->sessionReference($payment),
            ],
        );
    }

    public function merchantReference(Payment $payment): string
    {
        return 'KIDAN-ALAT-' . $payment->id;
    }

    public function sessionReference(Payment $payment): string
    {
        $existing = Arr::get($payment->meta ?? [], 'session_reference');
        if (is_string($existing) && trim($existing) !== '') {
            return trim($existing);
        }

        return (string) Str::uuid();
    }

    public function customer(Payment $payment, ?CombinedOrder $order): AlatPayCustomerData
    {
        $user = $payment->user;
        $shipping = $this->decodeAddress($order?->shipping_address);
        $billing = $this->decodeAddress($order?->billing_address);

        $email = $this->firstFilled([
            $shipping['email'] ?? null,
            $billing['email'] ?? null,
            $user?->email,
            'payments@kidanstore.com',
        ]);

        $name = $this->firstFilled([
            $shipping['name'] ?? null,
            $billing['name'] ?? null,
            $user?->name,
            'Kidan Customer',
        ]);

        [$firstName, $lastName] = $this->splitName($name);

        return new AlatPayCustomerData(
            email: $email,
            phone: $this->firstFilled([$shipping['phone'] ?? null, $billing['phone'] ?? null, $user?->phone]),
            firstName: $firstName,
            lastName: $lastName,
            metadata: json_encode(array_filter([
                'payment_id' => $payment->id,
                'payment_type' => $payment->payment_type,
                'user_id' => $payment->user_id,
                'order_code' => $payment->order_code,
            ])),
        );
    }

    public function toWebhookData(array $payload): AlatPayWebhookData
    {
        $data = Arr::get($payload, 'data', $payload);
        $transactionId = Arr::get($data, 'transactionId')
            ?? Arr::get($data, 'virtualAccount.transactionId')
            ?? Arr::get($data, 'nipTransaction.transactionId');
        $providerReference = Arr::get($data, 'sessionId')
            ?? Arr::get($data, 'nipTransaction.paymentreference');

        return new AlatPayWebhookData(
            eventType: $this->eventTypeFromPayload($payload),
            reference: (string) ($data['orderId'] ?? $providerReference ?? $transactionId ?? ''),
            providerReference: is_string($providerReference) ? $providerReference : null,
            transactionId: is_string($transactionId) ? $transactionId : null,
            status: $this->normalizeStatus(
                Arr::get($data, 'status')
                ?? Arr::get($data, 'nipTransaction.transactionStatus')
                ?? Arr::get($data, 'virtualAccount.status')
            ),
            payload: $payload,
        );
    }

    public function toStatusData(array $payload, string $reference): AlatPayStatusData
    {
        $data = Arr::get($payload, 'data', $payload);
        $transactionId = Arr::get($data, 'transactionId')
            ?? Arr::get($data, 'virtualAccount.transactionId')
            ?? Arr::get($data, 'nipTransaction.transactionId');
        $providerReference = Arr::get($data, 'sessionId')
            ?? Arr::get($data, 'nipTransaction.paymentreference');

        return new AlatPayStatusData(
            reference: $reference,
            status: $this->normalizeStatus(
                Arr::get($data, 'status')
                ?? Arr::get($data, 'nipTransaction.transactionStatus')
                ?? Arr::get($data, 'virtualAccount.status')
            ),
            providerReference: is_string($providerReference) ? $providerReference : null,
            transactionId: is_string($transactionId) ? $transactionId : null,
            message: is_string($payload['message'] ?? null) ? $payload['message'] : null,
            payload: $payload,
        );
    }

    public function normalizeStatus(?string $providerStatus): string
    {
        $value = strtolower(trim((string) $providerStatus));

        return match (true) {
            $value === '',
            str_contains($value, 'open'),
            str_contains($value, 'pending'),
            str_contains($value, 'initiated'),
            str_contains($value, 'await') => 'pending',
            str_contains($value, 'process'),
            str_contains($value, 'under') => 'processing',
            str_contains($value, 'success'),
            str_contains($value, 'complete'),
            str_contains($value, 'settled'),
            $value === '00' => 'successful',
            str_contains($value, 'cancel') => 'cancelled',
            str_contains($value, 'reverse'),
            str_contains($value, 'refund') => 'reversed',
            str_contains($value, 'fail'),
            str_contains($value, 'declin'),
            str_contains($value, 'error') => 'failed',
            default => 'processing',
        };
    }

    private function eventTypeFromPayload(array $payload): string
    {
        if (is_string($payload['event'] ?? null) && $payload['event'] !== '') {
            return $payload['event'];
        }

        $status = $this->normalizeStatus(
            Arr::get($payload, 'data.status')
            ?? Arr::get($payload, 'data.nipTransaction.transactionStatus')
            ?? Arr::get($payload, 'data.virtualAccount.status')
        );

        return match ($status) {
            'successful' => 'payment.success',
            'failed' => 'payment.failed',
            'cancelled' => 'payment.cancelled',
            'reversed' => 'refund.completed',
            default => 'payment.updated',
        };
    }

    private function firstFilled(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function splitName(string $name): array
    {
        $segments = preg_split('/\s+/', trim($name)) ?: [];
        $first = $segments[0] ?? 'Customer';
        $last = count($segments) > 1 ? implode(' ', array_slice($segments, 1)) : 'Customer';

        return [$first, $last];
    }

    private function decodeAddress(mixed $address): array
    {
        if (!is_string($address) || trim($address) === '') {
            return [];
        }

        $decoded = json_decode($address, true);

        return is_array($decoded) ? $decoded : [];
    }
}
