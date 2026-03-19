<template>
  <div>
    <v-list-item
      v-for="(cart_item, i) in cartItems"
      :key="cart_item.cart_id"
      class="px-0 py-4"
      :class="[
        { 'text-reset': cart_item.outOfStock },
        { 'border-top': i != 0 }
      ]"
    >
      <div class="w-100">
        <div class="position-relative">
          <!-- Out of Stock Badge -->
          <v-chip
            v-if="cart_item.outOfStock"
            class="absolute-top-left white-text z-1"
            color="red"
            size="small"
            label
          >
            {{ $t('out_of_stock') }}
          </v-chip>

          <div :class="['d-flex align-start', { 'opacity-50': cart_item.outOfStock }]">
            <!-- Checkbox -->
            <v-checkbox
              true-icon="las la-check"
              hide-details
              class="mt-0 pt-0 flex-shrink-0"
              :model-value="cart_item.selected"
              :disabled="cart_item.outOfStock"
              @update:modelValue="toggleCartItem({ cart_id: cart_item.cart_id, status: $event })"
            />

            <!-- Product Image -->
            <div class="flex-shrink-0 lh-0 ms-2">
              <img
                :src="cart_item.thumbnail"
                :alt="cart_item.name"
                class="img-fluid cart-item-image"
                @error="imageFallback($event)"
              />
            </div>

            <!-- Product Details -->
            <div class="flex-grow-1 minw-0 ms-4">
              <!-- Product Name -->
              <div class="text-subtitle-1 font-weight-medium mb-1 product-name">
                {{ cart_item.name }}
              </div>

              <!-- Product Variations -->
              <div v-if="cart_item.combinations.length > 0" class="mb-3">
                <span
                  v-for="(combination, j) in cart_item.combinations"
                  :key="j"
                  class="variation-chip me-2"
                >
                  <span class="text-medium-emphasis">{{ combination.attribute }}:</span>
                  <span class="font-weight-medium ms-1">{{ combination.value }}</span>
                </span>
              </div>

              <!-- Quantity Controls -->
              <div
                :class="[
                  'd-flex align-center',
                  { 'pointer-disabled': cart_item.outOfStock }
                ]"
              >
                <v-btn
                  color="primary"
                  size="small"
                  class="cart-qty-btn"
                  elevation="0"
                  icon
                  variant="outlined"
                  @click="updateQuantity({ type: 'minus', cart_id: cart_item.cart_id })"
                >
                  <i class="las la-minus" />
                </v-btn>

                <span class="mx-4 text-body-1 font-weight-medium">
                  {{ cart_item.qty }}
                </span>

                <v-btn
                  color="primary"
                  size="small"
                  class="cart-qty-btn"
                  elevation="0"
                  icon
                  variant="outlined"
                  @click="updateQuantity({ type: 'plus', cart_id: cart_item.cart_id })"
                >
                  <i class="las la-plus" />
                </v-btn>
              </div>
            </div>

            <!-- Price Section -->
            <div class="flex-shrink-0 text-end price-section ms-4">
              <del
                v-if="cart_item.regular_price > cart_item.dicounted_price"
                class="text-body-2 text-medium-emphasis d-block mb-1"
              >
                {{ format_price(cart_item.regular_price * cart_item.qty) }}
              </del>
              <div class="text-h6 font-weight-bold text-primary">
                {{ format_price(cart_item.dicounted_price * cart_item.qty) }}
              </div>
              
              <!-- Discount Badge -->
              <v-chip
                v-if="cart_item.regular_price > cart_item.dicounted_price"
                color="success"
                size="x-small"
                class="mt-1"
                label
              >
                {{ calculateDiscount(cart_item.regular_price, cart_item.dicounted_price) }}% OFF
              </v-chip>
            </div>

            <!-- Remove Button -->
            <div class="ms-4 flex-shrink-0">
              <v-btn
                icon
                size="small"
                variant="text"
                color="error"
                @click="removeFromCart(cart_item.cart_id)"
              >
                <i class="las la-trash fs-20" />
              </v-btn>
            </div>
          </div>
        </div>
      </div>
    </v-list-item>
  </div>
</template>

<script>
import { mapActions } from "vuex";

export default {
  props: {
    cartItems: {
      type: Array,
      required: true,
      default: () => []
    }
  },
  methods: {
    ...mapActions("cart", [
      "updateQuantity",
      "toggleCartItem",
      "removeFromCart"
    ]),
    calculateDiscount(regular, discounted) {
      return Math.round(((regular - discounted) / regular) * 100);
    }
  }
};
</script>

<style scoped>
.cart-item-image {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.product-name {
  line-height: 1.4;
  max-width: 100%;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.variation-chip {
  display: inline-block;
  padding: 4px 12px;
  background-color: #f5f5f5;
  border-radius: 16px;
  font-size: 0.813rem;
  margin-bottom: 4px;
}

.cart-qty-btn {
  width: 32px;
  height: 32px;
  min-width: 32px;
  border-radius: 4px;
}

.cart-qty-btn i {
  font-size: 16px;
}

.price-section {
  min-width: 120px;
}

.pointer-disabled {
  pointer-events: none;
  opacity: 0.6;
}

.absolute-top-left {
  position: absolute;
  top: 8px;
  left: 8px;
}

.z-1 {
  z-index: 1;
}

.white-text {
  color: white !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .cart-item-image {
    width: 80px;
    height: 80px;
  }

  .price-section {
    min-width: 90px;
  }

  .product-name {
    font-size: 0.875rem;
  }

  .variation-chip {
    font-size: 0.75rem;
    padding: 2px 8px;
  }
}

@media (max-width: 600px) {
  .d-flex.align-start {
    flex-wrap: wrap;
  }

  .price-section {
    width: 100%;
    text-align: left !important;
    margin-top: 12px;
    margin-left: 48px !important;
  }
}
</style>