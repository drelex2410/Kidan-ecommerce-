<template>
  <aside class="checkout-summary">
    <template v-if="mode === 'checkout'">
      <h3 class="checkout-summary__title">Order Summary</h3>
      <div class="checkout-summary__rows">
        <div class="checkout-summary__row">
          <span>Subtotal ({{ itemCount }} Item{{ itemCount === 1 ? "" : "s" }})</span>
          <strong>{{ format_price(subtotal, false) }}</strong>
        </div>
        <div class="checkout-summary__row">
          <span>Discount</span>
          <strong>{{ discount > 0 ? format_price(discount, false) : "-" }}</strong>
        </div>
        <div class="checkout-summary__row">
          <span>Kidan Points</span>
          <strong>{{ points > 0 ? points : "-" }}</strong>
        </div>
        <div class="checkout-summary__row">
          <span>VAT</span>
          <strong>{{ tax > 0 ? format_price(tax, false) : "-" }}</strong>
        </div>
        <div class="checkout-summary__row">
          <span>Shipping</span>
          <strong>{{ shippingLabel }}</strong>
        </div>
      </div>

      <div class="checkout-summary__coupon">
        <CouponForm :for-checkout="true" />
      </div>

      <div class="checkout-summary__total">
        <span>Estimated Total</span>
        <strong>{{ format_price(total, false) }}</strong>
      </div>

      <button
        type="button"
        class="checkout-summary__link"
        @click="cartOpen = !cartOpen"
      >
        <span>View Shopping Cart</span>
        <i :class="cartOpen ? 'las la-angle-up' : 'las la-angle-down'"></i>
      </button>

      <div
        v-if="cartOpen"
        class="checkout-summary__cart-dropdown"
      >
        <div
          v-if="cartItems.length"
          class="checkout-summary__cart-items"
        >
          <div
            v-for="item in cartItems"
            :key="item.cart_id || item.variation_id"
            class="checkout-summary__cart-item"
          >
            <label class="checkout-summary__cart-check">
              <input
                type="checkbox"
                :checked="item.selected"
                @change="toggleCartItem({ cart_id: item.cart_id, status: $event.target.checked })"
              />
              <span></span>
            </label>
            <img
              :src="item.thumbnail"
              :alt="item.name"
              class="checkout-summary__cart-thumb"
            />
            <div class="checkout-summary__cart-details">
              <div class="checkout-summary__cart-name">{{ item.name }}</div>
              <div class="checkout-summary__cart-meta">
                Qty: {{ item.qty }}
              </div>
            </div>
            <strong class="checkout-summary__cart-price">
              {{ format_price(item.dicounted_price * item.qty, false) }}
            </strong>
          </div>
        </div>
        <p
          v-else
          class="checkout-summary__cart-empty"
        >
          Your cart items will appear here.
        </p>
      </div>
    </template>

    <template v-else>
      <h3 class="checkout-summary__title">Order Summary</h3>
      <div
        v-if="primaryProduct"
        class="checkout-summary__product"
      >
        <img
          :src="primaryProduct.thumbnail"
          :alt="primaryProduct.name"
          class="checkout-summary__product-image"
        />
        <div class="checkout-summary__product-content">
          <div class="checkout-summary__product-name">{{ primaryProduct.name }}</div>
          <div class="checkout-summary__product-meta">
            <span>{{ variationLabel }}</span>
            <span>Quantity: {{ primaryProduct.quantity }}</span>
          </div>
        </div>
      </div>
      <div class="checkout-summary__total checkout-summary__total--complete">
        <span>Total Paid</span>
        <strong>{{ format_price(order?.grand_total || 0, false) }}</strong>
      </div>
    </template>
  </aside>
</template>

<script>
import CouponForm from "../cart/CouponForm.vue";
import { mapActions } from "vuex";

export default {
  name: "CheckoutSummaryPanel",
  components: {
    CouponForm,
  },
  props: {
    mode: {
      type: String,
      default: "checkout",
    },
    itemCount: {
      type: Number,
      default: 0,
    },
    subtotal: {
      type: Number,
      default: 0,
    },
    discount: {
      type: Number,
      default: 0,
    },
    points: {
      type: Number,
      default: 0,
    },
    tax: {
      type: Number,
      default: 0,
    },
    shippingLabel: {
      type: String,
      default: "Awaiting Selection",
    },
    total: {
      type: Number,
      default: 0,
    },
    cartItems: {
      type: Array,
      default: () => [],
    },
    order: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      cartOpen: false,
    };
  },
  methods: {
    ...mapActions("cart", ["toggleCartItem"]),
  },
  computed: {
    primaryProduct() {
      return this.order?.orders?.[0]?.products?.data?.[0] || null;
    },
    variationLabel() {
      if (!this.primaryProduct?.combinations?.length) {
        return "";
      }

      return this.primaryProduct.combinations
        .map((item) => `${item.attribute}: ${item.value}`)
        .join(" • ");
    },
  },
};
</script>

<style scoped>
.checkout-summary {
  border-left: 1px solid #ddd4c8;
  padding-left: 36px;
  min-height: 100%;
}

.checkout-summary__title {
  margin: 0 0 26px;
  font-size: 24px;
  line-height: 1.15;
  font-weight: 700;
  color: #17130f;
}

.checkout-summary__rows {
  display: grid;
  gap: 18px;
  padding-bottom: 22px;
  border-bottom: 1px solid #ddd4c8;
}

.checkout-summary__row,
.checkout-summary__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  font-size: 16px;
  color: #1c1813;
}

.checkout-summary__row strong,
.checkout-summary__total strong {
  font-weight: 600;
  color: #17130f;
}

.checkout-summary__total {
  padding: 22px 0 16px;
  font-size: 17px;
  font-weight: 700;
}

.checkout-summary__coupon {
  padding-top: 22px;
}

.checkout-summary__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  background: transparent;
  border: none;
  text-align: left;
  color: #6a6258;
  font-size: 16px;
  padding: 8px 0 22px;
  border-bottom: 1px solid #ddd4c8;
  cursor: pointer;
}

.checkout-summary__link:hover {
  color: #17130f;
}

.checkout-summary__cart-dropdown {
  border-bottom: 1px solid #ddd4c8;
  padding: 16px 0 18px;
}

.checkout-summary__cart-items {
  display: grid;
  gap: 14px;
}

.checkout-summary__cart-item {
  display: grid;
  grid-template-columns: auto 62px minmax(0, 1fr) auto;
  gap: 14px;
  align-items: center;
}

.checkout-summary__cart-check {
  position: relative;
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.checkout-summary__cart-check input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.checkout-summary__cart-check span {
  width: 18px;
  height: 18px;
  border: 1px solid #b9aa97;
  border-radius: 3px;
  background: #fffdf8;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.checkout-summary__cart-check input:checked + span {
  background: #8f0f0f;
  border-color: #8f0f0f;
}

.checkout-summary__cart-check input:checked + span::after {
  content: "";
  width: 5px;
  height: 9px;
  border: solid #ffffff;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
  margin-top: -1px;
}

.checkout-summary__cart-thumb {
  width: 62px;
  height: 72px;
  object-fit: cover;
  border-radius: 4px;
  background: #efe6da;
}

.checkout-summary__cart-details {
  min-width: 0;
}

.checkout-summary__cart-name {
  font-size: 14px;
  line-height: 1.35;
  color: #17130f;
  font-weight: 600;
}

.checkout-summary__cart-meta {
  margin-top: 4px;
  font-size: 13px;
  color: #6f655b;
}

.checkout-summary__cart-price {
  font-size: 14px;
  color: #17130f;
  font-weight: 600;
}

.checkout-summary__cart-empty {
  margin: 0;
  font-size: 14px;
  color: #6f655b;
}

.checkout-summary__product {
  display: grid;
  grid-template-columns: 150px minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}

.checkout-summary__product-image {
  width: 150px;
  height: 170px;
  object-fit: cover;
  border-radius: 6px;
  background: #efe6da;
}

.checkout-summary__product-content {
  min-width: 0;
}

.checkout-summary__product-name {
  font-size: 18px;
  font-weight: 700;
  color: #17130f;
  line-height: 1.25;
  margin-bottom: 8px;
}

.checkout-summary__product-meta {
  display: grid;
  gap: 6px;
  color: #6f655b;
  font-size: 15px;
}

.checkout-summary__total--complete {
  border-top: 1px solid #ddd4c8;
  margin-top: 24px;
}

@media (max-width: 960px) {
  .checkout-summary {
    border-left: none;
    border-top: 1px solid #ddd4c8;
    padding-left: 0;
    padding-top: 32px;
  }
}
</style>
