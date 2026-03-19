<template>
  <div class="cart-content-page">
    <v-row>
      <!-- Cart Items Column -->
      <v-col cols="12" md="8">
        <v-expansion-panels v-model="panel" class="mb-4">
          <v-expansion-panel
            v-for="(shop, i) in getCartShops"
            :key="i"
            elevation="0"
          >
            <v-expansion-panel-title class="pa-6">
              <div class="d-flex align-center w-100">
                <v-checkbox
                  true-icon="las la-check"
                  hide-details
                  :model-value="shop.selected"
                  @update:modelValue="toggleCartShop({ shop_id: shop.id, status: $event })"
                  @click.stop
                />
                <img
                  :src="shop.logo"
                  :alt="shop.name"
                  class="shop-logo me-3"
                />
                <div class="flex-grow-1">
                  <div class="text-h6 font-weight-medium">{{ shop.name }}</div>
                  <div class="text-body-2 text-medium-emphasis">
                    {{ format_price(getShopCartPrice(shop.id)) }}
                  </div>
                </div>
              </div>
            </v-expansion-panel-title>

            <v-expansion-panel-text class="px-6 pb-6">
              <min-order-progress
                class="mb-4"
                :shop-id="shop.id"
                :cart-price="getShopCartPrice(shop.id)"
                :min-order="getShopMinOrder(shop.id)"
                v-if="getShopMinOrder(shop.id) > 0"
              />

              <v-list class="pa-0">
                <cart-items-page :cart-items="getShopProductsById(shop.id)" />
              </v-list>

              <coupon-form
                class="mt-4"
                :shop-id="shop.id"
              />
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
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

            <div v-if="getTotalCouponDiscount > 0" class="summary-row mb-3 text-success">
              <span class="text-body-2">Total Discount</span>
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
  data: () => ({
    panel: 0,
  }),
  computed: {
    ...mapGetters("cart", [
      "getCartCount",
      "getCartPrice",
      "getCartShops",
      "getShopMinOrder",
      "getShopCartPrice",
      "getShopProductsById",
      "getTotalCouponDiscount",
    ]),
  },
  methods: {
    ...mapActions("cart", ["toggleCartShop"]),
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
.shop-logo {
  width: 50px;
  height: 50px;
  object-fit: contain;
  border-radius: 4px;
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