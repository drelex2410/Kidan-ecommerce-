<template>
  <v-app class="d-flex flex-column custom-background">
    <v-locale-provider :rtl="isRTL === 'rtl'">
      <!-- HEADER -->
      <Header v-if="!$route.meta.hideLayout" @toggle-theme="toggleTheme" :current-theme="currentTheme"
        :is-home-page="isHomePage" />

      <v-main class="aiz-main-wrap">
        <!-- Active homepage shell. Keep homepage composition here to avoid drifting into legacy routed shells. -->
        <div class="back custom-background" v-if="isHomePage && !$route.meta.hideLayout"
          style="position: relative !important">
          <HeroMainSlider />
          <HomePopularCategories />
          <HomeBannerSectionOne />
          <HomeProductSectionOne />
          <HomeShopSectionOne v-if="is_addon_activated('multi_vendor')" />
          <HomeShopBannerSectionOne v-if="is_addon_activated('multi_vendor')" />
          <!-- <HomeProductSectionTwo /> -->
          <HomeBannerSectiontwo />
          <HomeShopSectionTwo v-if="is_addon_activated('multi_vendor')" />
          <HomeShopBannerSectionTwo v-if="is_addon_activated('multi_vendor')" />
          <HomeShopSectionThree v-if="is_addon_activated('multi_vendor')" />
          <!-- <HomeProductSectionThree /> -->
          <!-- <HomeBannerSectionThree /> -->
          <HomeShopSectionFour v-if="is_addon_activated('multi_vendor')" />
          <HomeProductSectionFour />
          <HomeShopSectionFive v-if="is_addon_activated('multi_vendor')" />
          <HomeShopBannerSectionThree v-if="is_addon_activated('multi_vendor')" />
          <!-- <HomeProductSectionFive />
          <HomeProductSectionSix /> -->
          <HomeBannerSectionFour />
          <!-- Homepage stories slot: sits between testimonials and the featured editorial section below. -->
          <BlogSlider />
        </div>

        <!-- OTHER PAGES -->
        <div v-else class="custom-background">
          <router-view :key="['ShopDetails', 'ShopCoupons', 'ShopProducts'].includes($route.name)
            ? null
            : $route.path
            "></router-view>
        </div>
      </v-main>

      <!-- Featured editorial section rendered after the homepage stories row. -->
      <HomeAboutText v-if="isHomePage && !$route.meta.hideLayout" />

      <!-- FOOTER -->
      <Footer v-if="!$route.meta.hideLayout" :class="['mt-auto', { 'd-none': routerLoading }]" />

      <!-- CHAT -->
      <v-locale-provider :rtl="isRTL === 'rtl'">
        <BottomChat v-if="!$route.meta.hideLayout" />
      </v-locale-provider>

      <!-- CART SIDEBAR -->
      <SidebarCart v-if="!$route.meta.hideLayout" />

      <!-- ADD TO CART DIALOG -->
      <AddToCartDialog v-if="!$route.meta.hideLayout" />

      <!-- LOGIN POPUP (DISABLED ON LOGIN PAGE) -->
      <LoginDialog v-if="!isAuthenticated && !$route.meta.hideLayout" />

      <!-- MOBILE MENU -->
      <MobileMenu v-if="!$route.meta.hideLayout" class="d-lg-none user-side-nav" />

      <!-- SNACKBAR -->
      <SnackBar v-if="!$route.meta.hideLayout" />
    </v-locale-provider>
  </v-app>
</template>

<script>
import { mapActions, mapGetters, mapMutations } from "vuex";
import { useHead } from "@unhead/vue";

// Your App components
import Header from "./new-design-header2/Header.vue";
import HeroSection from "./new-design/HeroSection.vue";
import HeroMainSlider from "./new-design2/HeroMainSlider.vue";
import HeroSmallSliders from "./new-design/HeroSmallSliders.vue";
import Footer from "./new-design2/Footer.vue";
import LoginDialog from "./auth/LoginDialog.vue";
import SidebarCart from "./cart/SidebarCart.vue";
import BottomChat from "./new-design2/BottomChat.vue";
import MobileMenu from "./inc/MobileMenu.vue";
import SnackBar from "./inc/SnackBar.vue";
import AddToCartDialog from "./product/AddToCartDialog.vue";

import HomePopularCategories from "./new-design2/HomePopularCategories.vue";
import HomeProductSectionOne from "./new-design2/HomeProductSectionOne.vue";
import HomeProductSectionTwo from "./new-design2/HomeProductSectionTwo.vue";
import HomeProductSectionThree from "./new-design2/HomeProductSectionThree.vue";
import HomeProductSectionFour from "./new-design2/HomeProductSectionFour.vue";
import HomeProductSectionFive from "./new-design2/HomeProductSectionFive.vue";
import HomeProductSectionSix from "./new-design2/HomeProductSectionSix.vue";
import HomeBannerSectionOne from "./new-design2/HomeBannerSectionOne.vue";
import HomeBannerSectiontwo from "./new-design2/HomeBannerSectionTwo.vue";
import HomeBannerSectionThree from "./new-design2/HomeBannerSectionThree.vue";
import HomeBannerSectionFour from "./new-design2/HomeBannerSectionFour.vue";
import HomeShopSectionOne from "../components/home/HomeShopSectionOne.vue";
import HomeShopSectionTwo from "../components/home/HomeShopSectionTwo.vue";
import HomeShopSectionThree from "../components/home/HomeShopSectionThree.vue";
import HomeShopSectionFour from "../components/home/HomeShopSectionFour.vue";
import HomeShopSectionFive from "../components/home/HomeShopSectionFive.vue";
import HomeShopBannerSectionOne from "../components/home/HomeShopBannerSectionOne.vue";
import HomeShopBannerSectionTwo from "../components/home/HomeShopBannerSectionTwo.vue";
import HomeShopBannerSectionThree from "../components/home/HomeShopBannerSectionThree.vue";
import HomeAboutText from "../components/home/HomeAboutText.vue";
import HomeAllCategories from "../components/home/HomeAllCategories.vue";
import BlogSlider from "./new-design2/BlogSlider.vue"

export default {
  name: "App",
  components: {
    Header,
    HeroSection,
    HeroMainSlider,
    HeroSmallSliders,
    Footer,
    LoginDialog,
    SidebarCart,
    BottomChat,
    MobileMenu,
    SnackBar,
    AddToCartDialog,
    BlogSlider,
    HomePopularCategories,
    HomeProductSectionOne,
    HomeProductSectionTwo,
    HomeProductSectionThree,
    HomeProductSectionFour,
    HomeProductSectionFive,
    HomeProductSectionSix,
    HomeBannerSectionOne,
    HomeBannerSectiontwo,
    HomeBannerSectionThree,
    HomeBannerSectionFour,
    HomeShopSectionOne,
    HomeShopSectionTwo,
    HomeShopSectionThree,
    HomeShopSectionFour,
    HomeShopSectionFive,
    HomeShopBannerSectionOne,
    HomeShopBannerSectionTwo,
    HomeShopBannerSectionThree,
    HomeAboutText,
    HomeAllCategories,
  },
  data: () => ({
    currentTheme: "light",
    isRTL: " ",
    metaTitle: "",
    metaDescription: "",
  }),

  computed: {
    ...mapGetters("auth", ["isAuthenticated"]),
    ...mapGetters("cart", ["getTempUserId"]),
    ...mapGetters("app", [
      "appMetaTitle",
      "appMetaDescription",
      "userLanguageObj",
      "routerLoading",
    ]),
    isHomePage() {
      return this.$route.name === "Home";
    },
  },

  watch: {
    metaTitle(newTitle) {
      this.updateHead(newTitle, this.metaDescription);
    },
    metaDescription(newDescription) {
      this.updateHead(this.metaTitle, newDescription);
    },
    $route() {
      // Force update when route changes to ensure header behavior updates
      this.$forceUpdate();
    },
  },

  methods: {
    ...mapActions("auth", ["getUser", "checkSocialLoginStatus"]),
    ...mapActions("cart", ["fetchCartProducts"]),
    ...mapMutations("auth", ["setSociaLoginStatus"]),

    toggleTheme() {
      this.currentTheme = this.currentTheme === "light" ? "dark" : "light";
      document.documentElement.setAttribute("data-theme", this.currentTheme);
    },

    changeRTL() {
      const isRtl = Number(this.userLanguageObj?.rtl) === 1;
      this.isRTL = isRtl ? "rtl" : " ";
    },

    async getTempCartData() {
      if (this.isAuthenticated && this.getTempUserId) {
        const res = await this.call_api("post", "temp-id-cart-update", {
          temp_user_id: this.getTempUserId,
        });
        this.fetchCartProducts();
      }
    },

    updateHead(title, description) {
      useHead({
        title: title,
        meta: [{ name: "description", content: description }],
      });
    },

    is_addon_activated(addon) {
      const addons = this.$store.getters["app/addons"] || [];
      const currentAddon = addons.find(
        (item) => item.unique_identifier === addon
      );

      return Boolean(currentAddon && Number(currentAddon.activated) === 1);
    },
  },

  async created() {
    this.currentTheme = "light";
    document.documentElement.setAttribute("data-theme", this.currentTheme);

    this.changeRTL();
    await this.getUser();

    setTimeout(() => {
      this.checkSocialLoginStatus();
      this.getTempCartData();
    }, 200);

    // Meta initialization
    this.metaTitle = this.appMetaTitle;
    this.metaDescription = this.appMetaDescription;
  },
};
</script>

<style scoped>
.custom-background {
  background-color: #FFFBF3 !important;
}

.absolute-full {
  background: #FFFBF3;
  z-index: 10000;
}

</style>
