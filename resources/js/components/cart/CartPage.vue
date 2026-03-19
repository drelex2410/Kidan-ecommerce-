<template>
  <div class="cart-page-wrapper">
    <v-container class="py-8">
      <h1 class="text-h4 font-weight-medium mb-8">Cart</h1>
      
      <!-- Empty Cart State -->
      <div v-if="getCartCount === 0" class="empty-cart-state">
        <div class="text-center py-16">
          <div class="cart-icon-wrapper mb-6">
            <i class="las la-shopping-cart" style="font-size: 120px; opacity: 0.3;"></i>
          </div>
          <h2 class="text-h5 mb-4 font-weight-regular">
            Add items to your Shopping Cart
          </h2>
          <v-btn
            color="primary"
            size="large"
            elevation="0"
            class="px-8"
            @click="shopTodaysDeal"
          >
            SHOP TODAY'S DEAL
          </v-btn>
        </div>
      </div>

      <!-- Cart with Items -->
      <cart-for-multi-page v-else-if="is_addon_activated('multi_vendor')" />
      <cart-for-single-page v-else />
    </v-container>
  </div>
</template>

<script>
import { mapGetters } from "vuex";
import CartForMultiPage from "./CartForMultiPage.vue";
import CartForSinglePage from "./CartForSinglePage.vue";

export default {
  components: {
    CartForMultiPage,
    CartForSinglePage,
  },
  computed: {
    ...mapGetters("cart", ["getCartCount"]),
  },
  methods: {
    shopTodaysDeal() {
      this.$router.push({ name: "Home" }); 
    },
  },
};
</script>

<style scoped>
.cart-page-wrapper {
  min-height: calc(100vh - 200px);
  background-color: #fafafa;
}

.empty-cart-state {
  background: white;
  border-radius: 8px;
  padding: 60px 20px;
}

.cart-icon-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>