<template>
  <div class="cart-content-page">
    <v-row>
      <!-- Cart Items Column -->
      <v-col cols="12" md="8">
        <v-card elevation="0" class="mb-4">
          <v-card-text class="pa-6">
            <min-order-progress
              class="mb-4"
              :cart-price="getCartPrice"
              :min-order="getShopMinOrder()"
              v-if="getShopMinOrder() > 0"
            />

            <v-list class="pa-0">
              <cart-items-page :cart-items="getCartProducts" />
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Order Summary Column -->
      <v-col cols="12" md="4">
        <v-card elevation="0" class="sticky-summary">
          <v-card-text class="pa-6">
            <h3 class="text-h6 mb-4">Order Summary</h3>
            
            <div class="summary-row mb-3">
              <span class="text-body-2">Subtotal ({{ getCartCount }} items)</span>
              <span class="font-weight-medium">{{ format_price(getCartPrice) }}</span>
            </div>

            <coupon-form class="mb-4" />

            <div v-if="getTotalCouponDiscount > 0" class="summary-row mb-3 text-success">
              <span class="text-body-2">Discount</span>
              <span class="font-weight-medium">-{{ format_price(getTotalCouponDiscount) }}</span>
            </div>

            <v-divider class="my-4" />

            <div class="summary-row mb-4">
              <span class="text-h6 font-weight-bold">Total</span>
              <span class="text-h6 font-weight-bold text-primary">
                {{ format_price(getCartPrice - getTotalCouponDiscount) }}
              </span>
            </div>

            <v-btn
              color="primary"
              size="x-large"
              elevation="0"
              block
              @click="checkout"
            >
              PROCEED TO CHECKOUT
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import CartItemsPage from "./CartItemsPage.vue";
import CouponForm from "./CouponForm.vue";
import MinOrderProgress from "./MinOrderProgress.vue";

export default {
  components: { CartItemsPage, CouponForm, MinOrderProgress },
  computed: {
    ...mapGetters("cart", [
      "getCartCount",
      "getCartPrice",
      "getShopMinOrder",
      "getCartProducts",
      "getTotalCouponDiscount",
    ]),
  },
  methods: {
    ...mapActions("cart", ["fetchCartProducts"]),
    checkout() {
      if (this.getCartPrice > 0) {
        this.$router.push({ name: "Checkout" });
      } else {
        this.snack({
          message: this.$i18n.t("please_select_a_cart_product"),
          color: "red",
        });
      }
    },
  },
};
</script>

<style scoped>
.cart-content-page {
  background: transparent;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sticky-summary {
  position: sticky;
  top: 100px;
}
</style>