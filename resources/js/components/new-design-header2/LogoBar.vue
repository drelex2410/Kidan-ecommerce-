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
          <!-- Search Icon - Always visible -->
          <router-link :to="{ name: 'Search' }" class="icon-btn search-trigger mobile-visible">
            <i class="las la-search"></i>
          </router-link>

          <!-- Paper Plane - Hidden on mobile -->
          <button class="icon-btn d-none d-sm-flex">
            <i class="las la-paper-plane"></i>
          </button>


          <router-link :to="{ name: 'Wishlist' }" class="icon-btn d-none d-sm-flex">
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
      :categories="categories" 
      :is-scrolled="isScrolled || showCategories"
      @mouseenter="showCategories = true"
      @mouseleave="startHideTimer" />

    <Sidebar :show-sidebar="showSidebar" :loading-categories="loadingCategories" :categories="categories"
      @toggle-sidebar="toggleSidebar" :data="data" />
    <div class="account-overlay" v-if="showAccountMenu" @click="toggleAccountMenu"></div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import Sidebar from "./Sidebar.vue";
import CategoryBar from "./CategoryBar.vue";

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
    lastScrollPosition: 0,
    scrollTimeout: null,
    hideCategoryTimeout: null, // NEW: Timer for hiding categories
  }),
  computed: {
    ...mapGetters("app", ["appLogo", "appName"]),
    ...mapGetters("auth", ["isAuthenticated", "currentUser"]),
    ...mapGetters("cart", ["getCartCount", "getCartPrice", "getTotalCouponDiscount"]),
    isHeaderActive() {
      return this.isHovered || this.isScrolled;
    },
    cartDrawerOpen: {
      get() {
        return this.$store.state.auth.cartDrawerOpen || false;
      },
      set(val) {
        this.$store.commit("auth/updateCartDrawer", val);
      },
    },
  },
  watch: {
    showSidebar(val) {
      document.body.classList.toggle("overflow-hidden", val);
    },
    showAccountMenu(val) {
      document.body.classList.toggle("overflow-hidden", val);
    },
    $route() {
      this.closeCartDrawer();
    },
    isScrolled(newVal) {
      // Always show categories when scrolled
      if (newVal) {
        this.showCategories = true;
        this.clearHideTimer();
      }
    },
  },
  beforeUnmount() {
    document.body.classList.remove("overflow-hidden");
    window.removeEventListener("scroll", this.handleScroll);
    clearTimeout(this.scrollTimeout);
    this.clearHideTimer();
  },
  mounted() {
    this.fetchCategories();
    window.addEventListener("scroll", this.handleScroll);
  },
  methods: {
    ...mapActions(["auth/logout"]),
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
      clearTimeout(this.scrollTimeout);
      this.scrollTimeout = setTimeout(() => { }, 150);
    },
    async logout() {
      await this.call_api("get", "auth/logout");
      this["auth/logout"]();
      this.resetCart();
      this.resetWishlist();
      this.toggleAccountMenu();
      this.$router.push({ name: "Home" }).catch(() => { });
    },
    handleHeaderMouseEnter() {
      this.isHovered = true;
      this.clearHideTimer();
      this.showCategories = true;
    },
    handleHeaderMouseLeave() {
      this.isHovered = false;
      this.startHideTimer();
    },
    toggleSidebar() {
      this.showSidebar = !this.showSidebar;
      if (this.showSidebar) {
        this.showAccountMenu = false;
        this.showCategories = false; // Hide categories when sidebar opens
      }
    },
    toggleAccountMenu() {
      this.showAccountMenu = !this.showAccountMenu;
      if (this.showAccountMenu) {
        this.showSidebar = false;
        this.showCategories = false; // Hide categories when account menu opens
      }
    },
    // NEW: Timer methods for smooth hover effects
    startHideTimer() {
      // Only hide categories if not scrolled
      if (!this.isScrolled) {
        this.hideCategoryTimeout = setTimeout(() => {
          this.showCategories = false;
        }, 300);
      }
    },
    clearHideTimer() {
      if (this.hideCategoryTimeout) {
        clearTimeout(this.hideCategoryTimeout);
        this.hideCategoryTimeout = null;
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
}

.logobar.header-active,
.logobar.scrolled {
  --header-fg: #1a1a1a;
  --header-logo-filter: brightness(0) saturate(100%);
}

.logobar:not(.home-page) {
  position: sticky !important;
  top: 0 !important;
}

.logobar.home-page {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
}

.logobar.scrolled {
  background: #FFFBF3 !important;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
  border-bottom-color: rgba(0, 0, 0, 0.08);
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
  .right-section>*:not(.search-trigger) {
    display: none !important;
  }

  .right-section>.search-trigger.mobile-visible {
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
  .right-section>.icon-btn:not(.search-trigger):nth-child(2),
  .right-section>.icon-btn:not(.search-trigger):nth-child(3) {
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
