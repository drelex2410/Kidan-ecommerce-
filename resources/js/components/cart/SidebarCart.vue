<template>
  <v-navigation-drawer v-model="cartDrawerOpen" location="right" width="420" fixed temporary class="cart-drawer">


    <cart-for-multi v-if="is_addon_activated('multi_vendor')" />
    <cart-for-single v-else />
  </v-navigation-drawer>


  <div v-if="cartDrawerOpen" class="fixed inset-0 bg-black bg-opacity-50 z-[-1]" @click="closeCartDrawer" />
</template>

<script>
import { mapGetters, mapMutations } from "vuex";
import CartForMulti from "./CartForMulti.vue";
import CartForSingle from "./CartForSingle.vue";

export default {
  components: {
    CartForMulti,
    CartForSingle,
  },
  computed: {
    ...mapGetters("cart", [
      "getCartCount",
      "getCartPrice",
      "getTotalCouponDiscount",
    ]),
    ...mapGetters("auth", ["cartDrawerOpen"]),
  },
  methods: {
    ...mapMutations("auth", ["updateCartDrawer"]),
    closeCartDrawer() {
      this.updateCartDrawer(false);
    },
  },
};
</script>

<style scoped>
.cart-drawer {
  z-index: 3000 !important;
}
</style>