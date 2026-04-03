<?php

namespace App\Support\Payments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

trait HandlesPaystackInitialization
{
    private function assertPaystackConfigured(): void
    {
        $publicKey = trim((string) config('paystack.publicKey'));
        $secretKey = trim((string) config('paystack.secretKey'));

        if ($publicKey === '' || $secretKey === '') {
            throw new HttpException(503, 'Paystack is not configured for this environment.');
        }
    }

    private function paystackInitializationException(Throwable $exception, Request $request, array $context = []): HttpException
    {
        $details = $this->extractPaystackExceptionDetails($exception);

        Log::error('Paystack payment initialization failed.', array_filter(array_merge([
            'payment_type' => $request->input('payment_type', session('payment_type')),
            'payment_method' => $request->input('payment_method', session('payment_method')),
            'order_code' => $request->input('order_code', session('order_code')),
            'user_id' => $request->input('user_id', session('user_id')),
            'email_present' => filled($request->input('email')),
            'paystack_mode' => $this->paystackMode(),
            'paystack_url' => config('paystack.paymentUrl'),
            'provider_http_status' => $details['provider_http_status'],
            'provider_message' => $details['provider_message'],
            'provider_response_excerpt' => $details['provider_response_excerpt'],
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
        ], $context), static fn ($value) => $value !== null));

        return new HttpException(
            503,
            $this->userFacingPaystackMessage($details['provider_message']),
            $exception
        );
    }

    private function extractPaystackExceptionDetails(Throwable $exception): array
    {
        $providerHttpStatus = null;
        $providerMessage = null;
        $providerResponseExcerpt = null;

        if (method_exists($exception, 'getResponse') && $exception->getResponse()) {
            $response = $exception->getResponse();
            $providerHttpStatus = $response->getStatusCode();
            $body = (string) $response->getBody();
            $providerResponseExcerpt = Str::limit($body, 1000);

            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $providerMessage = isset($decoded['message']) && is_string($decoded['message'])
                    ? trim($decoded['message'])
                    : null;
            }
        }

        return [
            'provider_http_status' => $providerHttpStatus,
            'provider_message' => $providerMessage,
            'provider_response_excerpt' => $providerResponseExcerpt,
        ];
    }

    private function userFacingPaystackMessage(?string $providerMessage): string
    {
        $normalized = Str::lower((string) $providerMessage);

        if (Str::contains($normalized, 'merchant may be inactive') || Str::contains($normalized, 'merchant account is inactive')) {
            return 'Merchant account is inactive. Please contact support.';
        }

        if (Str::contains($normalized, 'invalid key') || Str::contains($normalized, 'unauthorized') || Str::contains($normalized, 'authorization')) {
            return 'Payment provider credentials are invalid. Please contact support.';
        }

        if (Str::contains($normalized, ['temporarily unavailable', 'service unavailable', 'timeout'])) {
            return 'Payment provider is temporarily unavailable. Please try again.';
        }

        return 'Payment provider is unavailable at the moment. Please try again or use another payment method.';
    }

    private function paystackMode(): string
    {
        $secretKey = (string) config('paystack.secretKey');

        return match (true) {
            Str::startsWith($secretKey, 'sk_test_') => 'test',
            Str::startsWith($secretKey, 'sk_live_') => 'live',
            default => 'unknown',
        };
    }
}
