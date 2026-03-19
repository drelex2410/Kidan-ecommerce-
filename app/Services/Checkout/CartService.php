<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private readonly CartOwner $cartOwner
    ) {
    }

    public function fetch(?User $user, ?string $tempUserId): array
    {
        $items = $this->baseQuery($user, $tempUserId)->get();
        $items = $this->pruneBrokenLines($items);

        return [
            'items' => $items->values(),
            'shops' => $this->extractShops($items),
            'summary' => $this->buildSummary($items),
        ];
    }

    public function add(?User $user, ?string $tempUserId, int $variationId, int $qty): array
    {
        $variation = ProductVariation::query()
            ->with([
                'product.shop',
                'product.taxes',
                'combinations.attribute.attribute_translations',
                'combinations.attribute_value.attribute_value_translations',
            ])
            ->findOrFail($variationId);

        $product = $variation->product;

        if (!$product || !$product->published || !$product->approved) {
            throw new CheckoutException('The selected product is unavailable.');
        }

        if (!$variation->stock || ($variation->current_stock !== null && $variation->current_stock < 1)) {
            throw new CheckoutException('The selected product is out of stock.');
        }

        $existing = $this->cartOwner
            ->query($user, $tempUserId)
            ->where('product_variation_id', $variation->id)
            ->first();

        $nextQuantity = ($existing?->quantity ?? 0) + $qty;
        $this->assertPurchasableQuantity($product->min_qty ?? 1, $product->max_qty ?? 0, $variation->current_stock, $nextQuantity);

        if ($existing) {
            $existing->update(['quantity' => $nextQuantity]);
            $cart = $existing->fresh(['product.shop', 'product.taxes', 'variation.combinations.attribute.attribute_translations', 'variation.combinations.attribute_value.attribute_value_translations']);
        } else {
            $cart = Cart::query()->create([
                'user_id' => $user?->id,
                'temp_user_id' => $user ? null : $tempUserId,
                'product_id' => $product->id,
                'product_variation_id' => $variation->id,
                'quantity' => $qty,
            ])->load(['product.shop', 'product.taxes', 'variation.combinations.attribute.attribute_translations', 'variation.combinations.attribute_value.attribute_value_translations']);
        }

        return [
            'item' => $cart,
            'shop' => $product->shop,
            'summary' => $this->buildSummary($this->baseQuery($user, $tempUserId)->get()),
        ];
    }

    public function changeQuantity(?User $user, ?string $tempUserId, int $cartId, string $type): array
    {
        $cart = $this->baseQuery($user, $tempUserId)->whereKey($cartId)->first();

        if (!$cart) {
            throw new CheckoutException('Cart item not found.', 404);
        }

        $product = $cart->product;
        $variation = $cart->variation;

        if ($type === 'plus') {
            $nextQuantity = $cart->quantity + 1;
            $this->assertPurchasableQuantity($product->min_qty ?? 1, $product->max_qty ?? 0, $variation->current_stock, $nextQuantity);
            $cart->update(['quantity' => DB::raw('quantity + 1')]);
            $message = 'Cart updated';
        } elseif ($type === 'minus') {
            if ($cart->quantity <= max(1, (int) $product->min_qty)) {
                $cart->delete();
                $message = 'Cart deleted due to minimum quantity';
            } else {
                $cart->update(['quantity' => DB::raw('quantity - 1')]);
                $message = 'Cart updated';
            }
        } else {
            throw new CheckoutException('The quantity change type is invalid.');
        }

        return [
            'message' => $message,
            'summary' => $this->buildSummary($this->baseQuery($user, $tempUserId)->get()),
        ];
    }

    public function destroy(?User $user, ?string $tempUserId, int $cartId): array
    {
        $cart = $this->baseQuery($user, $tempUserId)->whereKey($cartId)->first();

        if (!$cart) {
            throw new CheckoutException('Cart item not found.', 404);
        }

        $cart->delete();

        return [
            'message' => 'Product has been successfully removed from your cart',
            'summary' => $this->buildSummary($this->baseQuery($user, $tempUserId)->get()),
        ];
    }

    public function mergeTempCart(User $user, string $tempUserId): array
    {
        $tempItems = $this->baseQuery(null, $tempUserId)->get();

        foreach ($tempItems as $tempItem) {
            $variation = $tempItem->variation;
            $product = $tempItem->product;

            if (!$variation || !$product || !$product->published || !$product->approved) {
                $tempItem->delete();
                continue;
            }

            $existing = Cart::query()
                ->where('user_id', $user->id)
                ->where('product_variation_id', $tempItem->product_variation_id)
                ->first();

            $nextQuantity = ($existing?->quantity ?? 0) + $tempItem->quantity;
            $this->assertPurchasableQuantity($product->min_qty ?? 1, $product->max_qty ?? 0, $variation->current_stock, $nextQuantity);

            if ($existing) {
                $existing->update(['quantity' => $nextQuantity]);
                $tempItem->delete();
                continue;
            }

            $tempItem->update([
                'user_id' => $user->id,
                'temp_user_id' => null,
            ]);
        }

        return $this->fetch($user, null);
    }

    public function selectedItemsForUser(User $user, array $cartItemIds): Collection
    {
        return $this->baseQuery($user, null)
            ->whereIn('id', $cartItemIds)
            ->get();
    }

    private function baseQuery(?User $user, ?string $tempUserId)
    {
        return $this->cartOwner
            ->query($user, $tempUserId)
            ->with([
                'product.shop',
                'product.taxes',
                'variation.combinations.attribute.attribute_translations',
                'variation.combinations.attribute_value.attribute_value_translations',
            ]);
    }

    private function pruneBrokenLines(Collection $items): Collection
    {
        return $items->filter(function (Cart $item): bool {
            if ($item->product && $item->variation) {
                return true;
            }

            $item->delete();

            return false;
        })->values();
    }

    private function extractShops(Collection $items): Collection
    {
        return $items
            ->map(fn (Cart $item) => $item->product?->shop)
            ->filter()
            ->unique('id')
            ->values();
    }

    private function buildSummary(Collection $items): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            if (!$item->product || !$item->variation) {
                continue;
            }

            $subtotal += variation_discounted_price($item->product, $item->variation, false) * $item->quantity;
            $taxTotal += product_variation_tax($item->product, $item->variation) * $item->quantity;
        }

        return [
            'item_count' => $items->count(),
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'discount_total' => 0.0,
            'shipping_total' => 0.0,
            'grand_total' => round($subtotal + $taxTotal, 2),
        ];
    }

    private function assertPurchasableQuantity(int $minQty, int $maxQty, ?int $currentStock, int $quantity): void
    {
        if ($quantity < max(1, $minQty)) {
            throw new CheckoutException('Minimum quantity requirement is not met.');
        }

        if ($maxQty > 0 && $quantity > $maxQty) {
            throw new CheckoutException('Max quantity reached');
        }

        if ($currentStock !== null && $quantity > $currentStock) {
            throw new CheckoutException('The selected product is out of stock.');
        }
    }
}
