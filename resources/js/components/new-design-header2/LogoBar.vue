<template>
  <div class="logobar" :class="{ scrolled: isScrolled, 'home-page': isHomePage, 'header-active': isHeaderActive }"
       @mouseenter="handleHeaderMouseEnter"
       @mouseleave="handleHeaderMouseLeave">
    <div class="top-bar">
      <div class="top-bar-container d-flex align-center justify-space-between">
        <div class="left-section d-flex align-center">
          <button class="menu-btn" @click="toggleSidebar">
            <i class="las la-bars"></i>
            <span class="menu-text">Menu</span>
          </button>

          <router-link :to="{ name: 'TrackOrder' }">
            <span class="track-order-link d-none d-md-inline">Track My Order</span>
          </router-link>
        </div>

        <div class="logo-section">
          <router-link :to="{ name: 'Home' }" class="logo-link d-block lh-0">
            <img :src="appLogo" :alt="appName" class="logo-img" height="40" />
          </router-link>
        </div>

        <div class="right-section d-flex align-center">
          <div class="header-utilities">
            <div
              v-if="allLanguages.length > 1"
              ref="languageMenu"
              class="header-utility"
            >
              <button
                class="icon-btn utility-trigger"
                type="button"
                aria-label="Choose language"
                @click.stop="toggleLanguageMenu"
              >
                <i class="las la-globe"></i>
              </button>

              <div v-if="showLanguageMenu" class="utility-dropdown">
                <div class="utility-dropdown__title">Language</div>
                <button
                  v-for="language in allLanguages"
                  :key="language.code"
                  type="button"
                  class="utility-option"
                  :class="{ 'is-active': currentLanguageCode === language.code }"
                  @click.stop="switchLanguage(language.code)"
                >
                  <span>{{ language.name }}</span>
                  <small>{{ language.code.toUpperCase() }}</small>
                </button>
              </div>
            </div>

            <div
              v-if="allCurrencies.length > 0"
              ref="currencyMenu"
              class="header-utility"
            >
              <button
                class="icon-btn utility-trigger"
                type="button"
                aria-label="Choose currency"
                @click.stop="toggleCurrencyMenu"
              >
                <i class="las la-wallet"></i>
              </button>

              <div v-if="showCurrencyMenu" class="utility-dropdown utility-dropdown--wide">
                <div class="utility-dropdown__title">Currency</div>
                <button
                  v-for="currency in allCurrencies"
                  :key="currency.code"
                  type="button"
                  class="utility-option"
                  :class="{ 'is-active': selectedCurrencyCode === currency.code }"
                  @click.stop="switchCurrency(currency.code)"
                >
                  <span>{{ currency.code }}</span>
                  <small>{{ currency.symbol }} · {{ currency.name }}</small>
                </button>
              </div>
            </div>
          </div>

          <!-- Search Icon - Always visible -->
          <router-link :to="{ name: 'Search' }" class="icon-btn search-trigger mobile-visible">
            <i class="las la-search"></i>
          </router-link>

          <!-- Paper Plane - Hidden on mobile -->
          <router-link :to="{ name: 'AllBrands' }" class="icon-btn d-none d-sm-flex brand-link" aria-label="Browse all brands">
            <i class="las la-paper-plane"></i>
          </router-link>


          <router-link :to="{ name: 'Wishlist' }" class="icon-btn d-none d-sm-flex wishlist-link">
            <i class="las la-heart"></i>
          </router-link>

          <!-- Cart - Hidden on mobile (will be in sidebar) -->
          <div class="position-relative d-none d-sm-flex">
            <router-link :to="{ name: 'Cart' }" class="icon-btn position-relative">
              <i class="las la-shopping-cart"></i>
              <span v-if="getCartCount > 0"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size: 0.65rem; top: -8px; right: -8px">
                {{ getCartCount }}
              </span>
            </router-link>
          </div>

          <!-- Account - Hidden on mobile (will be in sidebar) -->
          <div class="account-container d-none d-sm-flex">
            <button class="icon-btn" @click="toggleAccountMenu">
              <i class="las la-user"></i>
            </button>
            <div class="account-dropdown" v-if="showAccountMenu">
              <div v-if="!isAuthenticated" class="account-dropdown-content">
                <router-link :to="{ name: 'Login' }" class="dropdown-link" @click="toggleAccountMenu">
                  <i class="las la-sign-in-alt"></i>
                  <span>{{ $t("login") }}</span>
                </router-link>
                <router-link :to="{ name: 'Registration' }" class="dropdown-link" @click="toggleAccountMenu">
                  <i class="las la-user-plus"></i>
                  <span>{{ $t("registration") }}</span>
                </router-link>
              </div>
              <div v-else class="account-dropdown-content">
                <router-link :to="{
                  name:
                    currentUser.user_type === 'delivery_boy'
                      ? 'DeliveryBoyDashboard'
                      : 'DashBoard',
                }" class="dropdown-link" @click="toggleAccountMenu">
                  <i class="las la-tachometer-alt"></i>
                  <span>{{ $t("dashboard") }}</span>
                </router-link>
                <div class="dropdown-link" @click="logout">
                  <i class="las la-sign-out-alt"></i>
                  <span>{{ $t("logout") }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <CategoryBar
      v-if="canUseDesktopHoverMenu"
      :categories="categories"
      :menu-items="data?.header_menu || {}"
      :visible="showCategories"
      @menu-state-change="handleCategoryMenuState"
      @request-close="closeDesktopMenu"
    />

    <Sidebar :show-sidebar="showSidebar" :loading-categories="loadingCategories" :categories="categories"
      @toggle-sidebar="toggleSidebar" :data="data" />
    <div class="account-overlay" v-if="showAccountMenu" @click="toggleAccountMenu"></div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import Sidebar from "./Sidebar.vue";
import CategoryBar from "./CategoryBar.vue";
import { loadLanguageAsync } from "../../plugins/i18n";

export default {
  components: {
    Sidebar,
    CategoryBar,
  },
  props: {
    loading: { type: Boolean, required: true, default: true },
    data: { type: Object, default: {} },
    isHomePage: { type: Boolean, default: false },
  },
  data: () => ({
    showSidebar: false,
    loadingCategories: true,
    categories: [],
    showAccountMenu: false,
    isScrolled: false,
    isHovered: false,
    showCategories: false, // NEW: Controls category bar visibility
    isCategoryMenuOpen: false,
    lastScrollPosition: 0,
    scrollTimeout: null,
    selectedCurrencyCode: "",
    showLanguageMenu: false,
    showCurrencyMenu: false,
    hideCategoryTimeout: null, // NEW: Timer for hiding categories
    canUseDesktopHoverMenu: false,
  }),
  computed: {
    ...mapGetters("app", [
      "appLogo",
      "appName",
      "allLanguages",
      "allCurrencies",
      "appLanguage",
      "userLanguageObj",
      "generalSettings",
    ]),
    ...mapGetters("auth", ["isAuthenticated", "currentUser"]),
    ...mapGetters("cart", ["getCartCount", "getCartPrice", "getTotalCouponDiscount"]),
    hasStaticNonHomeHeader() {
      return !this.isHomePage;
    },
    isHeaderActive() {
      return this.hasStaticNonHomeHeader || this.isHovered || this.isScrolled || this.showCategories || this.isCategoryMenuOpen;
    },
    cartDrawerOpen: {
      get() {
        return this.$store.state.auth.cartDrawerOpen || false;
      },
      set(val) {
        this.$store.commit("auth/updateCartDrawer", val);
      },
    },
    currentLanguageCode() {
      return this.userLanguageObj?.code || this.appLanguage || "en";
    },
  },
  watch: {
    showSidebar(val) {
      document.body.classList.toggle("overflow-hidden", val);
      if (val) {
        this.closeDesktopMenu({ preserveHover: true });
      }
    },
    showAccountMenu(val) {
      document.body.classList.toggle("overflow-hidden", val);
      if (val) {
        this.closeDesktopMenu({ preserveHover: true });
      }
    },
    $route() {
      this.closeCartDrawer();
      this.closeUtilityMenus();
      this.closeDesktopMenu();
    },
  },
  beforeUnmount() {
    document.body.classList.remove("overflow-hidden");
    window.removeEventListener("scroll", this.handleScroll);
    window.removeEventListener("resize", this.updateHoverCapability);
    document.removeEventListener("click", this.handleDocumentClick);
    clearTimeout(this.scrollTimeout);
    this.clearHideTimer();
  },
  mounted() {
    this.fetchCategories();
    this.updateHoverCapability();
    window.addEventListener("scroll", this.handleScroll);
    window.addEventListener("resize", this.updateHoverCapability);
    document.addEventListener("click", this.handleDocumentClick);
    this.selectedCurrencyCode =
      this.generalSettings?.currency?.selected_currency_code ||
      this.allCurrencies?.[0]?.code ||
      "";
  },
  methods: {
    ...mapActions(["auth/logout"]),
    ...mapActions("app", ["setLanguage", "setRTL"]),
    ...mapActions("cart", ["resetCart"]),
    ...mapActions("wishlist", ["resetWishlist"]),
    toggleCartDrawer() {
      this.$store.commit("auth/updateCartDrawer", !this.cartDrawerOpen);
    },
    closeCartDrawer() {
      if (this.cartDrawerOpen) {
        this.$store.commit("auth/updateCartDrawer", false);
      }
    },
    handleScroll() {
      const current = window.scrollY;
      this.isScrolled = current > 50;
      this.closeDesktopMenu({ preserveHover: true });
      clearTimeout(this.scrollTimeout);
      this.scrollTimeout = setTimeout(() => { }, 150);
    },
    updateHoverCapability() {
      if (typeof window === "undefined" || typeof window.matchMedia !== "function") {
        this.canUseDesktopHoverMenu = false;
        this.closeDesktopMenu();
        return;
      }

      const canHover = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
      this.canUseDesktopHoverMenu = canHover && window.innerWidth > 1024;

      if (!this.canUseDesktopHoverMenu) {
        this.closeDesktopMenu();
      }
    },
    async logout() {
      await this.call_api("get", "auth/logout");
      this["auth/logout"]();
      this.resetCart();
      this.resetWishlist();
      this.toggleAccountMenu();
      this.$router.push({ name: "Home" }).catch(() => { });
    },
    async switchLanguage(locale) {
      if (!locale || this.currentLanguageCode === locale) {
        return;
      }

      this.closeUtilityMenus();
      this.setLanguage(locale);

      const selectedLanguage = this.allLanguages.find(
        (language) => language.code === locale
      );
      const isRtl = Number(selectedLanguage?.rtl) === 1 ? "rtl" : "";
      this.setRTL(isRtl);

      await loadLanguageAsync(locale);
      window.location.reload();
    },
    async switchCurrency(currencyCode) {
      if (!currencyCode || this.generalSettings?.currency?.selected_currency_code === currencyCode) {
        return;
      }

      try {
        this.closeUtilityMenus();
        await window.axios.post("/currency/change", {
          currency_code: currencyCode,
        });
        this.selectedCurrencyCode = currencyCode;
        window.location.reload();
      } catch (error) {
        this.snack({
          message: error?.response?.data?.message || "Unable to change currency right now.",
          color: "red",
        });
      }
    },
    toggleLanguageMenu() {
      this.closeDesktopMenu({ preserveHover: true });
      this.showLanguageMenu = !this.showLanguageMenu;
      if (this.showLanguageMenu) {
        this.showCurrencyMenu = false;
      }
    },
    toggleCurrencyMenu() {
      this.closeDesktopMenu({ preserveHover: true });
      this.showCurrencyMenu = !this.showCurrencyMenu;
      if (this.showCurrencyMenu) {
        this.showLanguageMenu = false;
      }
    },
    closeUtilityMenus() {
      this.showLanguageMenu = false;
      this.showCurrencyMenu = false;
    },
    handleDocumentClick(event) {
      const languageMenu = this.$refs.languageMenu;
      const currencyMenu = this.$refs.currencyMenu;

      if (
        (languageMenu && languageMenu.contains(event.target)) ||
        (currencyMenu && currencyMenu.contains(event.target))
      ) {
        return;
      }

      this.closeUtilityMenus();
    },
    handleHeaderMouseEnter() {
      if (!this.canUseDesktopHoverMenu || this.showSidebar) {
        return;
      }

      this.isHovered = true;
      this.clearHideTimer();
      this.showCategories = true;
    },
    handleHeaderMouseLeave() {
      if (!this.canUseDesktopHoverMenu) {
        return;
      }

      this.isHovered = false;
      this.startHideTimer();
    },
    toggleSidebar() {
      this.showSidebar = !this.showSidebar;
      if (this.showSidebar) {
        this.showAccountMenu = false;
        this.closeDesktopMenu({ preserveHover: true });
      }
    },
    toggleAccountMenu() {
      this.showAccountMenu = !this.showAccountMenu;
      if (this.showAccountMenu) {
        this.showSidebar = false;
        this.closeDesktopMenu({ preserveHover: true });
      }
    },
    handleCategoryMenuState(isOpen) {
      this.isCategoryMenuOpen = isOpen;

      if (isOpen) {
        this.clearHideTimer();
      }
    },
    startHideTimer() {
      if (!this.canUseDesktopHoverMenu) {
        return;
      }

      this.clearHideTimer();
      this.hideCategoryTimeout = setTimeout(() => {
        this.closeDesktopMenu();
      }, 180);
    },
    clearHideTimer() {
      if (this.hideCategoryTimeout) {
        clearTimeout(this.hideCategoryTimeout);
        this.hideCategoryTimeout = null;
      }
    },
    closeDesktopMenu(options = {}) {
      const { preserveHover = false } = options;

      this.clearHideTimer();
      this.showCategories = false;
      this.isCategoryMenuOpen = false;

      if (!preserveHover) {
        this.isHovered = false;
      }
    },
    async fetchCategories() {
      try {
        this.loadingCategories = true;
        const res = await this.call_api("get", "all-categories");
        if (res.data.success) {
          this.categories = res.data.data;
        }
      } catch (error) {
        console.error("Error loading categories:", error);
      } finally {
        this.loadingCategories = false;
      }
    },
    imageFallback(event) {
      event.target.src = "/path/to/fallback/image.jpg";
    },
  },
};
</script>

<style scoped>
.logobar {
  width: 100vw !important;
  margin-left: calc(-50vw + 50%) !important;
  left: 0 !important;
  right: 0 !important;
  --header-fg: #fff;
  --header-transition: 240ms ease;
  --header-logo-filter: brightness(0) saturate(100%) invert(1);
  background-color: transparent;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  z-index: 1000;
  box-sizing: border-box;
  color: var(--header-fg);
  overflow: visible;
}

.logobar.header-active,
.logobar.scrolled {
  --header-fg: #1a1a1a;
  --header-logo-filter: brightness(0) saturate(100%);
}

.logobar:not(.home-page) {
  position: sticky !important;
  top: 0 !important;
  background: #FFFBF3 !important;
  --header-fg: #1a1a1a;
  --header-logo-filter: brightness(0) saturate(100%);
  box-shadow: none;
  border-bottom-color: rgba(0, 0, 0, 0.08);
}

.logobar.home-page {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
}

.logobar.home-page.header-active {
  background: #FFFBF3 !important;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
  border-bottom-color: rgba(0, 0, 0, 0.08);
}

.logobar.scrolled {
  background: #FFFBF3 !important;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
  border-bottom-color: rgba(0, 0, 0, 0.08);
}

.logobar:not(.home-page).scrolled,
.logobar:not(.home-page).header-active {
  background: #FFFBF3 !important;
  box-shadow: none;
}

.top-bar {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.top-bar-container {
  max-width: none !important;
  width: 100%;
  margin: 0 auto;
  padding: 1rem 3rem;
  min-height: 80px;
  box-sizing: border-box;
}

.left-section,
.right-section {
  gap: 1.5rem;
}

.header-utilities {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-utility {
  position: relative;
}

.utility-trigger {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.22);
  background: rgba(255, 255, 255, 0.08);
  color: var(--header-fg);
  font-size: 1.1rem;
}

.logobar.header-active .utility-trigger,
.logobar.scrolled .utility-trigger,
.logobar:not(.home-page) .utility-trigger {
  border-color: rgba(0, 0, 0, 0.12);
  background: rgba(255, 251, 243, 0.92);
  color: #1a1a1a;
}

.utility-dropdown {
  position: absolute;
  top: calc(100% + 0.7rem);
  right: 0;
  min-width: 180px;
  padding: 0.85rem;
  background: #FFFBF3;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 14px 32px rgba(0, 0, 0, 0.12);
  z-index: 1001;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.utility-dropdown--wide {
  min-width: 220px;
}

.utility-dropdown__title {
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #7c7c7c;
  margin-bottom: 0.1rem;
}

.utility-option {
  width: 100%;
  border: none;
  background: transparent;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  padding: 0.6rem 0.65rem;
  color: #1a1a1a;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.utility-option span {
  font-size: 0.92rem;
  font-weight: 600;
}

.utility-option small {
  font-size: 0.72rem;
  color: #6d6d6d;
}

.utility-option:hover,
.utility-option.is-active {
  background: rgba(128, 0, 0, 0.08);
  color: #800000;
}

.utility-option:hover small,
.utility-option.is-active small {
  color: #800000;
}

.menu-btn {
  background: none;
  border: none;
  color: var(--header-fg);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0;
  font-size: 1.125rem;
  transition: color var(--header-transition), opacity 0.3s ease;
}

.menu-btn i {
  font-size: 1.25rem;
}

.menu-text {
  font-size: 0.875rem;
  letter-spacing: 0.5px;
}

.track-order-link {
  font-size: 0.875rem;
  color: var(--header-fg);
  cursor: pointer;
  transition: color var(--header-transition), opacity 0.3s ease;
}

.track-order-link:hover {
  opacity: 0.7;
}

.logo-section {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}

.logo-img {
  height: 40px;
  filter: var(--header-logo-filter);
  transition: height 0.3s ease, filter var(--header-transition);
}

.icon-btn {
  background: none;
  border: none;
  color: var(--header-fg);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  font-size: 1.5rem;
  transition: color var(--header-transition), opacity 0.3s ease;
  text-decoration: none;
}

.icon-btn:hover {
  opacity: 0.7;
}

.account-container {
  position: relative;
}

.account-dropdown {
  position: absolute;
  top: calc(100% + 1rem);
  right: 0;
  background: #FFFBF3;
  border-radius: 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  min-width: 200px;
  opacity: 0;
  transform: translateY(-10px);
  animation: fadeInDown 0.3s ease forwards;
  z-index: 1000;
}

@keyframes fadeInDown {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.5rem;
  color: #333;
  text-decoration: none;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  cursor: pointer;
  border-left: 3px solid transparent;
}

.dropdown-link:hover {
  background: #f8f8f8;
  border-left-color: #1a1a1a;
}

.account-overlay {
  position: fixed;
  inset: 0;
  background: transparent;
  z-index: 999;
}

.text-reset {
  color: inherit !important;
  text-decoration: none !important;
}

.lh-0 {
  line-height: 0;
}

.fw-700 {
  /* font-weight: 700; */
}

.opacity-40 {
  opacity: 0.4;
}

.opacity-60 {
  opacity: 0.6;
}

.fs-14 {
  font-size: 0.875rem;
}

.overflow-hidden {
  overflow: hidden !important;
}

/* Mobile responsiveness */
@media (max-width: 767px) {
  .mobile-visible {
    display: flex !important;
  }

  .mobile-hidden {
    display: none !important;
  }

  .right-section {
    gap: 1rem;
  }

  /* Hide all icons except search on mobile */
  .right-section>*:not(.search-trigger):not(.header-utilities) {
    display: none !important;
  }

  .right-section>.search-trigger.mobile-visible {
    display: flex !important;
  }

  .right-section>.header-utilities {
    display: flex !important;
  }
}

/* Tablet responsiveness */
@media (min-width: 768px) and (max-width: 991px) {

  /* Show cart and account on tablet */
  .right-section>.d-none.d-sm-flex {
    display: flex !important;
  }

  /* Hide paper plane and heart on tablet if needed */
  .brand-link,
  .wishlist-link {
    display: none !important;
  }
}

@media (max-width: 959px) {
  .top-bar-container {
    padding: 1rem 1.5rem !important;
  }

  .left-section,
  .right-section {
    gap: 1rem;
  }

  .menu-text,
  .track-order-link {
    display: none;
  }
}

@media (max-width: 768px) {
  .top-bar-container {
    padding: 0.875rem 1rem !important;
    min-height: 70px;
  }

  .utility-dropdown {
    right: -0.25rem;
  }

  .logo-img {
    height: 32px !important;
  }
}

@media (max-width: 480px) {
  .top-bar-container {
    padding: 0.75rem 0.875rem !important;
    min-height: 65px;
  }

  .logo-img {
    height: 28px !important;
  }

  .icon-btn {
    font-size: 1.125rem;
  }
}

/* Ensure search icon is always visible on mobile */
.search-trigger {
  display: flex !important;
}
</style>
