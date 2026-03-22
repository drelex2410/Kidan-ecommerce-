<template>
  <v-container
    v-if="!is_empty_obj(order)"
    class="checkout-complete-view"
  >
    <div class="checkout-complete-layout">
      <div class="checkout-complete-layout__main">
        <div class="checkout-complete">
      <div class="checkout-complete__header">
        <h1>Checkout</h1>
      </div>

      <CheckoutStepper
        :current-step="3"
        :complete-all="true"
      />

      <div class="checkout-complete__intro">
        <span class="checkout-complete__intro-bar"></span>
        <p>
          <strong>Hello {{ customerFirstName }},</strong> Your order will be
          processed within 24 hours on regular working days. Once your package
          has been shipped, you'll receive an email notification with all the
          details you need.
        </p>
      </div>

      <div class="checkout-complete__section">
        <div class="checkout-complete__section-title">Order Details</div>
        <div class="checkout-complete__card">
          <h2>Thank you for your purchase</h2>

          <div class="checkout-complete__meta-grid">
            <div class="checkout-complete__meta">
              <span>Order ID</span>
              <strong>#{{ order.code }}</strong>
            </div>
            <div class="checkout-complete__meta">
              <span>Name</span>
              <strong>{{ customerName }}</strong>
            </div>
            <div class="checkout-complete__meta">
              <span>Date</span>
              <strong>{{ order.date }}</strong>
            </div>
            <div class="checkout-complete__meta">
              <span>Payment Method</span>
              <strong>{{ paymentMethodLabel }}</strong>
            </div>
          </div>

          <div class="checkout-complete__address">
            <span>Address</span>
            <strong>{{ fullAddress }}</strong>
          </div>

          <div class="checkout-complete__actions">
            <button
              type="button"
              class="checkout-complete__primary"
              @click="downloadReceipt"
            >
              Download Receipt
            </button>
            <button
              type="button"
              class="checkout-complete__secondary"
              @click="handleTrackOrder"
            >
              Track Order
            </button>
          </div>
        </div>
      </div>
        </div>
      </div>

      <aside class="checkout-complete-layout__summary">
      <CheckoutSummaryPanel
        mode="complete"
        :order="order"
      />
      </aside>
    </div>
  </v-container>
</template>

<script>
import CheckoutStepper from "../components/checkout/CheckoutStepper.vue";
import CheckoutSummaryPanel from "../components/checkout/CheckoutSummaryPanel.vue";
import { mapGetters } from "vuex";

export default {
  name: "OrderConfirmed",
  components: {
    CheckoutStepper,
    CheckoutSummaryPanel,
  },
  data: () => ({
    order: {},
  }),
  computed: {
    ...mapGetters("auth", ["isAuthenticated"]),
    shippingAddress() {
      return this.order?.shipping_address || {};
    },
    customerName() {
      return (
        this.order?.user?.name ||
        this.shippingAddress?.name ||
        "Customer"
      );
    },
    customerFirstName() {
      return this.customerName.split(" ")[0] || this.customerName;
    },
    paymentMethodLabel() {
      const paymentType = this.order?.orders?.[0]?.payment_type || "";

      return paymentType
        .replaceAll("_", " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
    },
    fullAddress() {
      const addressParts = [
        this.shippingAddress?.address,
        this.shippingAddress?.city,
        this.shippingAddress?.state,
        this.shippingAddress?.country,
      ].filter(Boolean);

      if (this.order?.orders?.[0]?.type_of_delivery === "pickup") {
        const pickupPoint = this.order?.orders?.[0]?.pickup_point;
        return [pickupPoint?.name, pickupPoint?.location, pickupPoint?.phone]
          .filter(Boolean)
          .join(", ");
      }

      return addressParts.join(", ");
    },
  },
  methods: {
    async getDetails() {
      const res = await this.call_api(
        "get",
        `checkout/order/${this.$route.query.orderCode}`
      );

      if (res.data.success) {
        this.order = res.data.data;
      } else {
        this.snack({
          message: res.data.message,
          color: "red",
        });
      }
    },
    downloadReceipt() {
      window.print();
    },
    async handleTrackOrder() {
      const orderCode = this.order?.code;

      if (!orderCode) {
        return;
      }

      if (this.isAuthenticated) {
        this.$router.push({
          name: "TrackOrder",
          query: { orderCode },
        });
        return;
      }

      try {
        await navigator.clipboard.writeText(orderCode);
        this.snack({
          message: "Order code copied. Share it with support to track your guest order.",
          color: "green",
        });
      } catch (error) {
        this.snack({
          message: `Order code: ${orderCode}`,
          color: "green",
        });
      }
    },
  },
  created() {
    this.getDetails();
  },
};
</script>

<style scoped>
.checkout-complete-view {
  max-width: 1460px;
  padding-top: 28px;
  padding-bottom: 56px;
}

.checkout-complete-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.65fr) minmax(320px, 0.75fr);
  gap: 52px;
  align-items: start;
}

.checkout-complete-layout__main,
.checkout-complete-layout__summary {
  min-width: 0;
}

.checkout-complete {
  width: 100%;
}

.checkout-complete__header {
  padding-bottom: 18px;
  border-bottom: 1px solid #ddd4c8;
}

.checkout-complete__header h1 {
  margin: 0;
  font-size: clamp(38px, 4.2vw, 54px);
  line-height: 1;
  color: #16120e;
  font-weight: 600;
  letter-spacing: -0.03em;
}

.checkout-complete__intro {
  display: grid;
  grid-template-columns: 6px minmax(0, 1fr);
  gap: 18px;
  align-items: start;
  margin-bottom: 52px;
}

.checkout-complete__intro-bar {
  width: 3px;
  min-height: 64px;
  background: #971e14;
  margin-top: 4px;
}

.checkout-complete__intro p {
  margin: 0;
  font-size: 18px;
  line-height: 1.8;
  color: #2e2822;
  max-width: 980px;
}

.checkout-complete__section {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 34px;
  align-items: start;
}

.checkout-complete__section-title {
  font-size: 22px;
  line-height: 1.2;
  font-weight: 700;
  color: #17130f;
  padding-top: 14px;
}

.checkout-complete__card {
  background: #fbfaf7;
  border: 1px solid #ece3d7;
  padding: 38px 40px 44px;
}

.checkout-complete__card h2 {
  margin: 0 0 28px;
  font-size: 28px;
  line-height: 1.2;
  color: #16120e;
  font-weight: 700;
  padding-bottom: 26px;
  border-bottom: 1px solid #ddd4c8;
}

.checkout-complete__meta-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 20px;
  padding-bottom: 28px;
  border-bottom: 1px solid #ddd4c8;
}

.checkout-complete__meta,
.checkout-complete__address {
  display: grid;
  gap: 12px;
}

.checkout-complete__meta span,
.checkout-complete__address span {
  color: #6d6459;
  font-size: 16px;
  line-height: 1.3;
}

.checkout-complete__meta strong,
.checkout-complete__address strong {
  font-size: 18px;
  line-height: 1.55;
  color: #17130f;
  font-weight: 700;
}

.checkout-complete__address {
  padding: 28px 0 0;
}

.checkout-complete__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding-top: 34px;
}

.checkout-complete__primary,
.checkout-complete__secondary {
  min-height: 54px;
  padding: 0 28px;
  border: 1px solid transparent;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.01em;
  transition: all 0.25s ease;
}

.checkout-complete__primary {
  background: #971e14;
  color: #fff8f2;
}

.checkout-complete__primary:hover {
  background: #7f180f;
}

.checkout-complete__secondary {
  background: transparent;
  color: #17130f;
  border-color: #d0c5b9;
}

.checkout-complete__secondary:hover {
  border-color: #17130f;
}

@media (max-width: 1180px) {
  .checkout-complete-view {
    padding-top: 18px;
    padding-bottom: 36px;
  }

  .checkout-complete-layout {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .checkout-complete__section {
    grid-template-columns: 1fr;
    gap: 22px;
  }

  .checkout-complete__section-title {
    padding-top: 0;
  }

  .checkout-complete__meta-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .checkout-complete__intro {
    grid-template-columns: 1fr;
    gap: 12px;
    margin-bottom: 34px;
  }

  .checkout-complete__intro-bar {
    min-height: 3px;
    width: 68px;
    margin-top: 0;
  }

  .checkout-complete__card {
    padding: 26px 22px 30px;
  }

  .checkout-complete__card h2 {
    font-size: 24px;
  }

  .checkout-complete__meta-grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .checkout-complete__actions {
    flex-direction: column;
  }

  .checkout-complete__primary,
  .checkout-complete__secondary {
    width: 100%;
  }
}
</style>
