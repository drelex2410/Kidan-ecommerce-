<?php

namespace App\Services\Payments;

use App\Models\CombinedOrder;
use App\Models\ManualPaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentCallbackService
{
    public function markSuccess(string $gateway, mixed $details = null): RedirectResponse
    {
        $payment = $this->resolveSessionPayment();

        DB::transaction(function () use ($payment, $gateway, $details) {
            $payment->refresh();

            $this->recordTransaction($payment, $gateway, 'success', $details, 'success');

            if ($payment->status === 'paid') {
                return;
            }

            if (in_array($payment->payment_type, ['cart_payment', 'repayment'], true)) {
                $this->markOrderPaymentAsPaid($payment, $gateway, $details);
            } elseif ($payment->payment_type === 'wallet_payment') {
                $this->markWalletPaymentAsPaid($payment, $gateway, $details);
            }

            $payment->update([
                'status' => 'paid',
                'payment_method' => $gateway,
                'completed_at' => now(),
            ]);
        });

        $redirect = $this->buildRedirectUrl($payment, 'success', $gateway);
        $this->clearSession();

        return redirect($redirect);
    }

    public function markFailed(string $gateway, mixed $details = null, string $status = 'failed'): RedirectResponse
    {
        $payment = $this->resolveSessionPayment(false);

        if ($payment) {
            DB::transaction(function () use ($payment, $gateway, $details, $status) {
                $payment->refresh();
                $this->recordTransaction($payment, $gateway, $status, $details, $status);

                if ($payment->status !== 'paid') {
                    $payment->update([
                        'status' => $status,
                        'failed_at' => now(),
                    ]);
                }
            });
        }

        $redirect = $payment
            ? $this->buildRedirectUrl($payment, 'failed', $gateway)
            : url('/') . '?' . http_build_query(['payment' => 'failed', 'payment_method' => $gateway]);

        $this->clearSession();

        return redirect($redirect);
    }

    public function markOfflinePending(Payment $payment, ?string $transactionId = null, ?UploadedFile $receipt = null): array
    {
        DB::transaction(function () use ($payment, $transactionId, $receipt) {
            $this->recordTransaction(
                $payment,
                $payment->gateway,
                'offline_pending',
                ['transactionId' => $transactionId],
                'pending'
            );

            if ($payment->payment_type === 'wallet_payment') {
                $this->createPendingWalletRecharge($payment, $transactionId, $receipt);
            }

            if ($payment->payment_type === 'repayment') {
                $this->storePendingManualRepayment($payment, $transactionId, $receipt);
            }

            $payment->update([
                'status' => 'pending',
            ]);
        });

        return [
            'success' => true,
            'go_to_payment' => false,
            'payment_method' => $payment->gateway,
            'payment_type' => $payment->payment_type,
            'order_code' => $payment->order_code,
            'grand_total' => (float) $payment->amount,
            'message' => 'Your payment request has been submitted and is pending approval.',
        ];
    }

    private function resolveSessionPayment(bool $strict = true): ?Payment
    {
        $paymentId = session('payment_id');

        if (!$paymentId) {
            if ($strict) {
                throw new HttpException(422, 'Payment session has expired.');
            }

            return null;
        }

        $payment = Payment::query()->find($paymentId);

        if (!$payment && $strict) {
            throw new HttpException(404, 'Payment record not found.');
        }

        return $payment;
    }

    private function recordTransaction(Payment $payment, string $gateway, string $eventType, mixed $payload, string $status): PaymentTransaction
    {
        $normalizedPayload = $this->normalizePayload($payload);
        $reference = $this->extractReference($payload);
        $fingerprint = sha1($payment->id . '|' . $gateway . '|' . $eventType . '|' . ($reference ?: json_encode($normalizedPayload)));

        return PaymentTransaction::query()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'payment_id' => $payment->id,
                'gateway' => $gateway,
                'event_type' => $eventType,
                'reference' => $reference,
                'status' => $status,
                'payload' => $normalizedPayload,
                'processed_at' => now(),
            ]
        );
    }

    private function markOrderPaymentAsPaid(Payment $payment, string $gateway, mixed $details): void
    {
        $combinedOrder = $payment->combinedOrder ?: CombinedOrder::query()->with('orders')->where('code', $payment->order_code)->first();

        if (!$combinedOrder) {
            throw new HttpException(404, 'Order not found for payment.');
        }

        Order::query()
            ->where('combined_order_id', $combinedOrder->id)
            ->get()
            ->each(function (Order $order) use ($gateway, $details): void {
                if (function_exists('calculate_seller_commision')) {
                    try {
                        calculate_seller_commision($order);
                    } catch (\Throwable) {
                        // Commission history is legacy-sidecar behavior and should not block payment reconciliation.
                    }
                }

                $order->payment_status = 'paid';
                $order->payment_type = $gateway;
                $order->payment_details = json_encode($this->normalizePayload($details));
                $order->save();
            });
    }

    private function markWalletPaymentAsPaid(Payment $payment, string $gateway, mixed $details): void
    {
        $user = User::query()->findOrFail($payment->user_id);

        $user->increment('balance', (float) $payment->amount);

        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->amount = $payment->amount;
        $wallet->payment_method = $gateway;
        $wallet->payment_details = $this->extractReference($details);
        $wallet->details = 'Recharge';
        $wallet->approval = 1;
        $wallet->offline_payment = 0;
        $wallet->type = 'Added';
        $wallet->save();
    }

    private function createPendingWalletRecharge(Payment $payment, ?string $transactionId, ?UploadedFile $receipt): void
    {
        $segments = explode('-', $payment->gateway);
        $offlinePaymentId = (int) end($segments);
        $method = ManualPaymentMethod::query()->find($offlinePaymentId);

        $wallet = new Wallet();
        $wallet->user_id = $payment->user_id;
        $wallet->amount = $payment->amount;
        $wallet->payment_details = $transactionId;
        $wallet->approval = 0;
        $wallet->offline_payment = 1;
        $wallet->payment_method = $method?->heading;
        $wallet->details = $method?->heading ? 'Paid via ' . $method->heading . ' for recharge' : 'Offline recharge';
        $wallet->type = 'Pending';

        if ($receipt && Schema::hasColumn('wallets', 'reciept')) {
            $wallet->reciept = $receipt->store('uploads/offline_payments');
        }

        $wallet->save();
    }

    private function storePendingManualRepayment(Payment $payment, ?string $transactionId, ?UploadedFile $receipt): void
    {
        $combinedOrder = CombinedOrder::query()->with('orders')->where('code', $payment->order_code)->first();

        if (!$combinedOrder) {
            return;
        }

        $segments = explode('-', $payment->gateway);
        $offlinePaymentId = (int) end($segments);
        $method = ManualPaymentMethod::query()->find($offlinePaymentId);

        foreach ($combinedOrder->orders as $order) {
            if (!Schema::hasColumn('orders', 'manual_payment') || !Schema::hasColumn('orders', 'manual_payment_data')) {
                continue;
            }

            $order->update([
                'payment_type' => $payment->gateway,
                'manual_payment' => 1,
                'manual_payment_data' => json_encode([
                    'transactionId' => $transactionId,
                    'payment_method' => $method?->heading,
                    'receipt' => $receipt?->store('uploads/offline_payments'),
                ]),
            ]);
        }
    }

    private function buildRedirectUrl(Payment $payment, string $status, string $gateway): string
    {
        $query = [$payment->payment_type => $status, 'payment_method' => $gateway];

        if ($payment->order_code) {
            $query['order_code'] = $payment->order_code;
        }

        return $payment->redirect_to . '?' . http_build_query($query);
    }

    private function clearSession(): void
    {
        session()->forget([
            'payment_id',
            'redirect_to',
            'amount',
            'payment_method',
            'payment_type',
            'user_id',
            'order_code',
            'transactionId',
            'receipt',
            'receiptFile',
            'seller_package_id',
            'manualPaymentMethod',
            'card_number',
            'cvv',
            'expiration_month',
            'expiration_year',
        ]);
    }

    private function normalizePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_object($payload)) {
            return json_decode(json_encode($payload), true) ?: [];
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : ['value' => $payload];
        }

        return [];
    }

    private function extractReference(mixed $payload): ?string
    {
        $data = $this->normalizePayload($payload);

        return data_get($data, 'data.reference')
            ?? data_get($data, 'reference')
            ?? data_get($data, 'id')
            ?? data_get($data, 'InvoiceId')
            ?? data_get($data, 'invoiceId')
            ?? data_get($data, 'tx_ref')
            ?? null;
    }
}
