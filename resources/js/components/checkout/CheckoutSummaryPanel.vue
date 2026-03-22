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
          <span>Shipping</span>
          <strong>{{ shippingLabel }}</strong>
        </div>
      </div>

      <div class="checkout-summary__total">
        <span>Estimated Total</span>
        <strong>{{ format_price(total, false) }}</strong>
      </div>

      <router-link
        :to="{ name: 'Cart' }"
        class="checkout-summary__link"
      >
        <span>View Shopping Cart</span>
        <i class="las la-angle-right"></i>
      </router-link>
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
export default {
  name: "CheckoutSummaryPanel",
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
    shippingLabel: {
      type: String,
      default: "Awaiting Selection",
    },
    total: {
      type: Number,
      default: 0,
    },
    order: {
      type: Object,
      default: () => ({}),
    },
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

.checkout-summary__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-decoration: none;
  color: #6a6258;
  font-size: 16px;
  padding: 8px 0 22px;
  border-bottom: 1px solid #ddd4c8;
}

.checkout-summary__link:hover {
  color: #17130f;
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
