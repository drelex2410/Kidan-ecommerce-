<?php

namespace App\Services\Checkout;

use App\Models\Address;
use App\Models\CombinedOrder;
use App\Models\CouponUsage;
use App\Models\ManualPaymentMethod;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly ShippingService $shippingService
    ) {
    }

    public function place(User $user, array $payload, ?UploadedFile $receipt = null): array
    {
        $cartItems = $this->cartService->selectedItemsForUser($user, $payload['cart_item_ids']);

        if ($cartItems->isEmpty()) {
            throw new CheckoutException('Your cart is empty. Please select a product.');
        }

        foreach ($cartItems as $cartItem) {
            $this->assertCartItemIsOrderable($cartItem);
        }

        $typeOfDelivery = $payload['type_of_delivery'];
        $shippingAddress = null;
        $billingAddress = null;
        $shippingCostPerShop = 0.0;

        if ($typeOfDelivery === 'home_delivery') {
            $shippingAddress = $this->resolveAddress($user, $payload['shipping_address_id'], 'shipping');
            $billingAddress = $this->resolveAddress($user, $payload['billing_address_id'], 'billing');
            $shippingQuote = $this->shippingService->quote($user, $shippingAddress->id, $cartItems->groupBy(fn ($item) => $item->product->shop_id)->count());

            if (!$shippingQuote['success']) {
                throw new CheckoutException('Sorry, delivery is not available in this shipping address.');
            }

            $shippingCostPerShop = $payload['delivery_type'] === 'express'
                ? (float) $shippingQuote['express_delivery_cost']
                : (float) $shippingQuote['standard_delivery_cost'];
        }

        if ($typeOfDelivery === 'pickup' && empty($payload['pickup_point_id'])) {
            throw new CheckoutException('Please select a pick up point.');
        }

        $grouped = $cartItems->groupBy(fn ($item) => $item->product->shop_id);
        $couponMap = $this->couponService->resolveCheckoutCoupons($user, $payload['coupon_codes'] ?? [], $cartItems);

        return DB::transaction(function () use ($user, $payload, $receipt, $cartItems, $grouped, $couponMap, $shippingAddress, $billingAddress, $shippingCostPerShop, $typeOfDelivery) {
            $combinedOrder = new CombinedOrder();
            $combinedOrder->user_id = $user->id;
            $combinedOrder->code = now()->format('Ymd-His') . random_int(10, 99);
            $combinedOrder->shipping_address = $shippingAddress ? json_encode($shippingAddress->toArray()) : null;
            $combinedOrder->billing_address = $billingAddress ? json_encode($billingAddress->toArray()) : null;
            $combinedOrder->grand_total = 0;
            $combinedOrder->save();

            $grandTotal = 0.0;
            $packageNumber = 1;

            foreach ($grouped as $shopId => $shopItems) {
                $subtotal = 0.0;
                $taxTotal = 0.0;

                foreach ($shopItems as $item) {
                    $unitPrice = variation_discounted_price($item->product, $item->variation, false);
                    $unitTax = product_variation_tax($item->product, $item->variation);
                    $subtotal += $unitPrice * $item->quantity;
                    $taxTotal += $unitTax * $item->quantity;
                }

                $couponDiscount = (float) data_get($couponMap, $shopId . '.discount', 0.0);
                $shopShipping = $typeOfDelivery === 'pickup' ? 0.0 : $shippingCostPerShop;
                $shopGrandTotal = round(($subtotal + $taxTotal + $shopShipping) - $couponDiscount, 2);

                $order = Order::query()->create([
                    'user_id' => $user->id,
                    'shop_id' => $shopId,
                    'combined_order_id' => $combinedOrder->id,
                    'code' => $packageNumber,
                    'shipping_address' => $shippingAddress ? json_encode($shippingAddress->toArray()) : null,
                    'billing_address' => $billingAddress ? json_encode($billingAddress->toArray()) : null,
                    'shipping_cost' => $shopShipping,
                    'grand_total' => $shopGrandTotal,
                    'coupon_code' => data_get($couponMap, $shopId . '.coupon.code'),
                    'coupon_discount' => $couponDiscount,
                    'delivery_type' => $payload['delivery_type'] ?? null,
                    'payment_type' => $payload['payment_type'],
                    'type_of_delivery' => $typeOfDelivery,
                    'pickup_point_id' => $payload['pickup_point_id'] ?? null,
                    'payment_status' => $payload['payment_type'] === 'wallet' ? 'paid' : 'unpaid',
                    'delivery_status' => 'order_placed',
                ]);

                foreach ($shopItems as $item) {
                    $unitPrice = variation_discounted_price($item->product, $item->variation, false);
                    $unitTax = product_variation_tax($item->product, $item->variation);

                    OrderDetail::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variation_id' => $item->product_variation_id,
                        'price' => $unitPrice,
                        'tax' => $unitTax,
                        'total' => ($unitPrice + $unitTax) * $item->quantity,
                        'quantity' => $item->quantity,
                        'product_referral_code' => $item->product_referral_code,
                    ]);

                    $item->product->increment('num_of_sale', $item->quantity);

                    if ($item->variation->current_stock !== null) {
                        $item->variation->decrement('current_stock', $item->quantity);
                    }
                }

                if (isset($couponMap[$shopId])) {
                    DB::table('coupon_usages')->insert([
                        'user_id' => $user->id,
                        'coupon_id' => $couponMap[$shopId]['coupon']->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->persistManualPayment($order, $payload, $receipt);

                $packageNumber++;
                $grandTotal += $shopGrandTotal;
            }

            $combinedOrder->grand_total = round($grandTotal, 2);
            $combinedOrder->save();

            $this->finalizeWalletPayment($user, $combinedOrder, $payload['payment_type']);

            DB::table('carts')->whereIn('id', $cartItems->pluck('id'))->delete();

            return [
                'success' => true,
                'go_to_payment' => !$this->isDirectSettlementPayment($payload['payment_type']),
                'grand_total' => round($grandTotal, 2),
                'payment_method' => $payload['payment_type'],
                'message' => 'Your order has been placed successfully',
                'order_code' => $combinedOrder->code,
            ];
        });
    }

    private function assertCartItemIsOrderable($cartItem): void
    {
        if (!$cartItem->product || !$cartItem->variation || !$cartItem->product->published || !$cartItem->product->approved) {
            throw new CheckoutException('One or more products in your cart are unavailable.');
        }

        if (!$cartItem->variation->stock || ($cartItem->variation->current_stock !== null && $cartItem->quantity > $cartItem->variation->current_stock)) {
            throw new CheckoutException($cartItem->product->getTranslation('name') . ' is out of stock.');
        }

        if ($cartItem->quantity < max(1, (int) $cartItem->product->min_qty)) {
            throw new CheckoutException('Minimum quantity requirement is not met.');
        }

        if ((int) $cartItem->product->max_qty > 0 && $cartItem->quantity > (int) $cartItem->product->max_qty) {
            throw new CheckoutException('Max quantity reached');
        }
    }

    private function resolveAddress(User $user, int $addressId, string $type): Address
    {
        $address = Address::query()->find($addressId);

        if (!$address || (int) $address->user_id !== (int) $user->id) {
            throw new CheckoutException('Please select a valid ' . $type . ' address.', 403);
        }

        return $address;
    }

    private function isDirectSettlementPayment(string $paymentType): bool
    {
        return in_array($paymentType, ['cash_on_delivery', 'wallet'], true) || Str::contains($paymentType, 'offline_payment');
    }

    private function finalizeWalletPayment(User $user, CombinedOrder $combinedOrder, string $paymentType): void
    {
        if ($paymentType !== 'wallet') {
            return;
        }

        if ((float) $user->balance < (float) $combinedOrder->grand_total) {
            throw new CheckoutException("You don't have enough wallet balance. Please recharge.");
        }

        $user->decrement('balance', (float) $combinedOrder->grand_total);

        if (class_exists(Wallet::class) && Schema::hasTable('wallets')) {
            $wallet = new Wallet();
            $wallet->user_id = $user->id;
            $wallet->amount = $combinedOrder->grand_total;
            $wallet->type = 'Deducted';
            $wallet->details = 'Order Placed. Order Code ' . $combinedOrder->code;
            $wallet->save();
        }
    }

    private function persistManualPayment(Order $order, array $payload, ?UploadedFile $receipt): void
    {
        if (!Str::contains((string) $payload['payment_type'], 'offline_payment')) {
            return;
        }

        if (!Schema::hasColumn('orders', 'manual_payment') || !Schema::hasColumn('orders', 'manual_payment_data')) {
            return;
        }

        $segments = explode('-', $payload['payment_type']);
        $offlinePaymentId = (int) end($segments);
        $method = Schema::hasTable('manual_payment_methods')
            ? ManualPaymentMethod::query()->find($offlinePaymentId)
            : null;

        $manualPaymentData = [
            'transactionId' => $payload['transactionId'] ?? null,
            'payment_method' => $method?->heading,
            'receipt' => $receipt?->store('uploads/offline_payments'),
        ];

        $order->update([
            'manual_payment' => 1,
            'manual_payment_data' => json_encode($manualPaymentData),
        ]);
    }
}
