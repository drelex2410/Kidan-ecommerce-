<?php

namespace App\Services\Payments\AlatPay;

use App\Models\AlatPayWebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AlatPayWebhookVerifier
{
    public function __construct(private readonly AlatPayConfig $config)
    {
    }

    public function verify(Request $request): array
    {
        $rawPayload = (string) $request->getContent();
        $payload = $request->json()->all();
        $signature = $this->firstHeader($request, [
            'X-ALATPAY-SIGNATURE',
            'X-AlatPay-Signature',
            'X-Alatpay-Signature',
            'X-Signature',
        ]);
        $timestampHeader = $this->firstHeader($request, [
            'X-ALATPAY-TIMESTAMP',
            'X-AlatPay-Timestamp',
            'X-Timestamp',
        ]);

        if ($timestampHeader && !$this->isTimestampFresh($timestampHeader)) {
            throw new HttpException(408, 'ALATPay webhook timestamp is too old.');
        }

        $secret = $this->config->webhookSecret();
        if ($secret) {
            if (!$signature) {
                throw new HttpException(401, 'ALATPay webhook signature is missing.');
            }

            $payloadSignatures = $this->candidateSignatures($secret, $rawPayload, $timestampHeader);
            $matched = collect($payloadSignatures)->contains(
                fn (string $candidate): bool => hash_equals($candidate, $signature)
            );

            if (!$matched) {
                throw new HttpException(401, 'ALATPay webhook signature is invalid.');
            }
        } elseif ($this->config->environment() === 'production') {
            throw new HttpException(503, 'ALATPay webhook secret must be configured in production.');
        }

        return [
            'correlation_id' => $this->firstHeader($request, ['X-Correlation-Id']) ?: (string) Str::uuid(),
            'signature' => $signature,
            'timestamp_header' => $timestampHeader,
            'headers' => collect($request->headers->all())->map(
                static fn (array $value) => count($value) === 1 ? $value[0] : $value
            )->all(),
            'raw_payload' => $rawPayload,
            'payload' => $payload,
            'fingerprint' => sha1(($signature ?: 'no-signature') . '|' . $rawPayload),
        ];
    }

    public function isDuplicate(string $fingerprint): bool
    {
        return AlatPayWebhookLog::query()->where('fingerprint', $fingerprint)->exists();
    }

    private function candidateSignatures(string $secret, string $rawPayload, ?string $timestampHeader): array
    {
        $baseCandidates = [
            hash_hmac('sha256', $rawPayload, $secret),
            base64_encode(hash_hmac('sha256', $rawPayload, $secret, true)),
        ];

        if ($timestampHeader) {
            $baseCandidates[] = hash_hmac('sha256', $timestampHeader . '.' . $rawPayload, $secret);
            $baseCandidates[] = hash_hmac('sha256', $timestampHeader . ':' . $rawPayload, $secret);
            $baseCandidates[] = base64_encode(hash_hmac('sha256', $timestampHeader . '.' . $rawPayload, $secret, true));
            $baseCandidates[] = base64_encode(hash_hmac('sha256', $timestampHeader . ':' . $rawPayload, $secret, true));
        }

        return array_values(array_unique(array_map('trim', $baseCandidates)));
    }

    private function isTimestampFresh(string $timestampHeader): bool
    {
        $timestamp = strtotime($timestampHeader);
        if ($timestamp === false) {
            return false;
        }

        $diff = abs(now()->timestamp - $timestamp);

        return $diff <= (int) config('alatpay.signature_tolerance_seconds', 600);
    }

    private function firstHeader(Request $request, array $names): ?string
    {
        foreach ($names as $name) {
            $value = $request->header($name);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
