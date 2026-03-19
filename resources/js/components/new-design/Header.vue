<template>
  <header 
    :class="[
      'header-sticky',
      { 'sticky-top': generalSettings.sticky_header == 1 },
      { 'header-scrolled': isScrolled }
    ]"
  >
    <!-- Top Bar Section -->
    <div v-if="!loading && data.top_bar" class="top-bar">
      <div class="top-bar-container">
        <div class="top-bar-content">
          <span v-if="data.top_bar.message" class="top-bar-message">
            {{ data.top_bar.message }}
          </span>
        </div>
      </div>
    </div>

    <!-- Logo Bar Section -->
    <div class="logo-bar">
      <div class="logo-bar-container">
        <div class="logo-section">
          <!-- Logo -->
          <router-link v-if="!loading" :to="{ name: 'Home' }" class="logo-link">
            <img :src="appLogo" :alt="appName" class="logo-img" />
          </router-link>
          <v-skeleton-loader v-else type="image" width="120" height="40"></v-skeleton-loader>
        </div>

        <!-- Search Bar -->
        <div :class="['search-section', { 'search-open': openSearch }]">
          <v-form 
            class="search-form"
            @submit.stop.prevent="search()"
          >
            <button 
              class="search-back-btn d-md-none" 
              @click.stop="toggleSearch(false)"
              type="button"
            >
              <i class="las la-arrow-left"></i>
            </button>
            
            <input
              type="text"
              class="search-input"
              :placeholder="$t('search_for_products_brands_and_more')"
              v-model="searchKeyword"
              @keyup="ajaxSearch"
              required
            />
            
            <button 
              class="search-submit-btn d-none d-md-flex"
              type="submit"
            >
              <i class="las la-search search-icon"></i>
              <span class="search-text">{{ $t("search") }}</span>
            </button>
          </v-form>

          <!-- Search Suggestions -->
          <div class="search-suggestions" v-if="showSuggestionContainer">
            <div class="search-loading" v-if="loadingSuggestion">
              <div class="loading-spinner"></div>
              <span class="loading-text">{{ $t('loading_suggestions') }}</span>
            </div>

            <div v-else>
              <div v-if="suggestionNotFound" class="no-results">
                <i class="las la-search no-results-icon"></i>
                <p class="no-results-text">{{ $t("sorry_nothing_found") }}</p>
                <p class="no-results-subtext">{{ $t("try_different_keywords") }}</p>
              </div>
              
              <div class="suggestions-content" v-else>
                <!-- Keywords -->
                <div v-if="keywords.length" class="suggestion-group">
                  <div class="suggestion-header">
                    <i class="las la-fire suggestion-icon"></i>
                    {{ $t('popular_suggestions') }}
                  </div>
                  <ul class="suggestion-list">
                    <li
                      v-for="(keyword, i) in keywords" 
                      :key="i"
                      class="suggestion-item"
                      @click="popularSuggesation(keyword)"
                    >
                      <i class="las la-search suggestion-item-icon"></i>
                      <span class="suggestion-item-text">{{ keyword }}</span>
                    </li>
                  </ul>
                </div>

                <!-- Products -->
                <div v-if="products.length" class="suggestion-group">
                  <div class="suggestion-header">
                    <i class="las la-shopping-bag suggestion-icon"></i>
                    {{ $t('products') }}
                  </div>
                  <ul class="suggestion-list">
                    <li 
                      v-for="(product, i) in products" 
                      :key="i"
                      class="suggestion-item product-item"
                    >
                      <div class="product-image-container">
                        <img
                          :src="product.thumbnail_image"
                          :alt="product.name"
                          @error="imageFallback($event)"
                          class="product-img"
                        >
                      </div>
                      <div class="product-info">
                        <router-link 
                          :to="{ name: 'ProductDetails', params: {slug: product.slug}}"
                          class="product-name"
                          @click="hideSearchContainer"
                        >
                          {{ product.name }}
                        </router-link>
                        <div class="product-price">
                          <del v-if="product.base_price > product.base_discounted_price" class="original-price">
                            {{ format_price(product.base_price) }}
                          </del>
                          <span class="discounted-price">
                            {{ format_price(product.base_discounted_price) }}
                          </span>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>

                <!-- Categories -->
                <div v-if="categories.length" class="suggestion-group">
                  <div class="suggestion-header">
                    <i class="las la-list suggestion-icon"></i>
                    {{ $t('category_suggestions') }}
                  </div>
                  <ul class="suggestion-list">
                    <li 
                      v-for="(category, i) in categories" 
                      :key="i"
                      class="suggestion-item"
                    >
                      <i class="las la-folder suggestion-item-icon"></i>
                      <router-link 
                        :to="{ name: 'Category', params: {categorySlug: category.slug}}" 
                        class="suggestion-link"
                        @click="hideSearchContainer"
                      >
                        {{ category.name }}
                      </router-link>
                    </li>
                  </ul>
                </div>

                <!-- Brands -->
                <div v-if="brands.length" class="suggestion-group">
                  <div class="suggestion-header">
                    <i class="las la-tags suggestion-icon"></i>
                    {{ $t('brands') }}
                  </div>
                  <ul class="suggestion-list">
                    <li 
                      v-for="(brand, i) in brands" 
                      :key="i"
                      class="suggestion-item"
                    >
                      <i class="las la-copyright suggestion-item-icon"></i>
                      <router-link 
                        :to="{ name: 'Brand', params: {brandId: brand.id }}" 
                        class="suggestion-link"
                        @click="hideSearchContainer"
                      >
                        {{ brand.name }}
                      </router-link>
                    </li>
                  </ul>
                </div>

                <!-- Shops -->
                <div v-if="shops.length" class="suggestion-group">
                  <div class="suggestion-header">
                    <i class="las la-store suggestion-icon"></i>
                    {{ $t('Shops') }}
                  </div>
                  <ul class="suggestion-list">
                    <li 
                      v-for="(shop, i) in shops" 
                      :key="i"
                      class="suggestion-item product-item"
                    >
                      <div class="shop-image-container">
                        <img
                          :src="shop.logo"
                          :alt="shop.name"
                          @error="imageFallback($event)"
                          class="shop-img"
                        >
                      </div>
                      <div class="product-info">
                        <router-link 
                          :to="{ name: 'ShopDetails', params: {slug: shop.slug}}"
                          class="product-name"
                          @click="hideSearchContainer"
                        >
                          {{ shop.name }}
                        </router-link>
                        <div class="shop-rating" v-if="shop.rating">
                          <i class="las la-star rating-star"></i>
                          <span class="rating-value">{{ shop.rating }}</span>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Search Toggle -->
        <button
          class="mobile-search-btn d-md-none"
          @click.stop="toggleSearch(true)"
        >
          <i class="las la-search"></i>
        </button>

        <!-- User Actions -->
        <div class="user-actions d-none d-md-flex">
          <!-- Not Authenticated -->
          <div v-if="!isAuthenticated" class="auth-links">
            <div class="auth-icon-container">
              <i class="las la-user user-icon"></i>
            </div>
            <div class="auth-text-container">
              <router-link
                :to="{ name: 'Login' }"
                class="auth-link"
              >
                {{ $t("login") }}
              </router-link>
              <span class="auth-divider">{{ $t("or") }}</span>
              <router-link
                :to="{ name: 'Registration' }"
                class="auth-link"
              >
                {{ $t("registration") }}
              </router-link>
            </div>
          </div>

          <!-- Authenticated -->
          <div v-else class="auth-links">
            <!-- Notifications -->
            <div v-if="currentUser.user_type == 'customer'" class="notification-container">
              <button class="notification-btn" @click="fetNotification">
                <i class="las la-bell"></i>
                <span class="notification-badge" v-if="notifications.length > 0">
                  {{ notifications.length }}
                </span>
              </button>
            </div>

            <div class="auth-icon-container">
              <i class="las la-user user-icon"></i>
            </div>
            <div class="auth-text-container">
              <router-link
                :to="{ name: currentUser.user_type == 'delivery_boy' ? 'DeliveryBoyDashboard': 'DashBoard' }"
                class="auth-link"
              >
                {{ $t("dashboard") }}
              </router-link>
              <span class="auth-divider">{{ $t('or') }}</span>
              <div
                class="auth-link logout-link"
                @click.stop="logout"
              >
                {{ $t("logout") }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="nav-menu d-none d-md-block" v-if="!loading">
      <div class="nav-menu-container">
        <ul class="nav-links">
          <!-- All Categories -->
          <li class="nav-item">
            <button class="nav-link categories-btn" @click="goToCategoryPage">
              <i class="las la-bars categories-icon"></i>
              {{ $t("all_categories") }}
            </button>
          </li>

          <!-- Menu Links from API -->
          <li 
            v-for="(link, label, i) in data.header_menu"
            :key="i"
            class="nav-item"
          >
            <dynamic-link
              :to="link"
              append-class="nav-link"
            >
              {{ label }}
            </dynamic-link>
          </li>
        </ul>
      </div>
    </nav>
  </header>
</template>

<script>
import { mapGetters, mapActions } from "vuex";

export default {
  name: 'Header',
  data: () => ({
    loading: true,
    data: {},
    isScrolled: false,
    openSearch: false,
    searchKeyword: "",
    loadingSuggestion: false,
    showSuggestionContainer: false,
    suggestionNotFound: false,
    keywords: [],
    categories: [],
    brands: [],
    products: [],
    shops: [],
    notifications: [],
  }),
  computed: {
    ...mapGetters("app", ["generalSettings", "appLogo", "appName"]),
    ...mapGetters("auth", ["isAuthenticated", "currentUser"]),
  },
  methods: {
    ...mapActions(["auth/logout"]),
    ...mapActions("cart", ["resetCart"]),
    ...mapActions("wishlist", ["resetWishlist"]),

    async getDetails() {
      const res = await this.call_api("get", `setting/header`);

      if (res.status === 200) {
        this.data = res.data;
        this.loading = false;
      }
    },

    handleScroll() {
      this.isScrolled = window.scrollY > 50;
    },

    toggleSearch(status) {
      this.openSearch = status;
    },

    goToCategoryPage() {
      this.$router.push({ name: "AllCategories" });
    },

    search() {
      this.showSuggestionContainer = false;
      this.$router
        .push({
          name: "Search",
          params:
            this.searchKeyword.length > 0
              ? { keyword: this.searchKeyword }
              : {},
          query: {
            page: 1,
          },
        })
        .catch(() => {});
    },

    hideSearchContainer() {
      this.showSuggestionContainer = false;
    },

    popularSuggesation(tag) {
      this.showSuggestionContainer = false;
      this.searchKeyword = tag;
      this.search();
    },

    async ajaxSearch(event) {
      this.loadingSuggestion = true;
      this.showSuggestionContainer = false;
      const searchKey = event.target.value;

      if (searchKey.length > 0) {
        this.showSuggestionContainer = true;
        const res = await this.call_api("get", `search.ajax/${searchKey}`);

        if (res.data.success) {
          this.suggestionNotFound = false;
          this.loadingSuggestion = false;
          this.keywords = res.data.keywords;
          this.categories = res.data.categories;
          this.brands = res.data.brands;
          this.products = res.data.products.data;
          this.shops = res.data.shops.data;
        } else {
          this.loadingSuggestion = false;
          this.suggestionNotFound = true;
        }
      }
    },

    async logout() {
      const res = await this.call_api("get", "auth/logout");
      this["auth/logout"]();
      this.resetCart();
      this.resetWishlist();
      this.$router.push({ name: "Home" }).catch(() => {});
    },

    async fetNotification() {
      const res = await this.call_api("get", `user/notification`);
      if (res.data.success) {
        this.notifications = res.data.notifications;
      }
    },

    onClick(event) {
      let trigger = document.getElementsByClassName(".search_content_box");
      if (trigger !== event.target) {
        this.showSuggestionContainer = false;
      }
    },
  },
  mounted() {
    window.addEventListener('scroll', this.handleScroll);
    document.addEventListener('click', this.onClick);
  },
  beforeUnmount() {
    window.removeEventListener('scroll', this.handleScroll);
    document.removeEventListener('click', this.onClick);
  },
  created() {
    this.getDetails();
  },
};
</script>

<style scoped>
/* ===================================
   BASE HEADER STYLES
   =================================== */
.header-sticky {
  position: sticky;
  top: 0;
  z-index: 1000;
  background-color: #2e1a1a;
  transition: all 0.3s ease;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.header-scrolled {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* ===================================
   TOP BAR
   =================================== */
.top-bar {
  background: linear-gradient(90deg, rgba(0,0,0,0.3), rgba(0,0,0,0.2), rgba(0,0,0,0.3));
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.75rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.top-bar-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.top-bar-content {
  text-align: center;
}

.top-bar-message {
  font-weight: 500;
  letter-spacing: 0.5px;
}

/* ===================================
   LOGO BAR
   =================================== */
.logo-bar {
  background-color: #3d2222;
  padding: 1rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.logo-bar-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
}

.logo-link {
  display: block;
  line-height: 0;
  transition: transform 0.2s;
}

.logo-link:hover {
  transform: scale(1.05);
}

.logo-img {
  height: 40px;
  width: auto;
  filter: brightness(0) invert(1);
}

/* ===================================
   SEARCH SECTION
   =================================== */
.search-section {
  flex: 1;
  position: relative;
  max-width: 600px;
  margin: 0 2rem;
}

.search-form {
  display: flex;
  align-items: center;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.search-form:focus-within {
  background-color: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
}

.search-back-btn {
  background: none;
  border: none;
  color: #fff;
  padding: 0.75rem;
  cursor: pointer;
  font-size: 1.25rem;
  transition: background-color 0.2s;
}

.search-back-btn:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.search-input {
  flex: 1;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  color: #fff;
  font-size: 0.875rem;
  outline: none;
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.search-submit-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
  color: #fff;
  border: none;
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
}

.search-submit-btn:hover {
  background: linear-gradient(135deg, rgba(255,255,255,0.3), rgba(255,255,255,0.2));
}

.search-icon {
  font-size: 1rem;
}

.mobile-search-btn {
  display: none;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: #fff;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.mobile-search-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

/* Search Suggestions */
.search-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: #fff;
  border-radius: 8px;
  margin-top: 0.5rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  max-height: 500px;
  overflow-y: auto;
  z-index: 100;
  border: 1px solid #eaeaea;
}

.search-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 1rem;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(139, 69, 19, 0.2);
  border-top-color: #8b4513;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-text {
  color: #666;
  font-size: 0.875rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.no-results-icon {
  font-size: 3rem;
  color: #ccc;
  margin-bottom: 1rem;
}

.no-results-text {
  color: #666;
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.no-results-subtext {
  color: #999;
  font-size: 0.875rem;
}

.suggestions-content {
  padding: 0.5rem 0;
}

.suggestion-group {
  margin-bottom: 1rem;
}

.suggestion-group:last-child {
  margin-bottom: 0;
}

.suggestion-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background-color: #f8f8f8;
  padding: 0.75rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #555;
  border-bottom: 1px solid #eaeaea;
}

.suggestion-icon {
  font-size: 0.875rem;
}

.suggestion-list {
  list-style: none;
  padding: 0.5rem 0;
  margin: 0;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 0.875rem;
  text-transform: capitalize;
  border-left: 3px solid transparent;
}

.suggestion-item:hover {
  background-color: #f8f8f8;
  border-left-color: #8b4513;
}

.suggestion-item-icon {
  color: #8b4513;
  font-size: 1rem;
  width: 16px;
  text-align: center;
}

.suggestion-item-text {
  color: #333;
}

.product-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.product-image-container,
.shop-image-container {
  flex-shrink: 0;
}

.product-img,
.shop-img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #eaeaea;
}

.shop-img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

.product-info {
  flex: 1;
  min-width: 0;
}

.product-name {
  display: block;
  color: #333;
  text-decoration: none;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.2s;
}

.product-name:hover {
  color: #8b4513;
}

.product-price {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.original-price {
  color: #999;
  text-decoration: line-through;
  font-size: 0.8rem;
}

.discounted-price {
  color: #d32f2f;
  font-weight: 600;
}

.shop-rating {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.25rem;
}

.rating-star {
  color: #ffc107;
  font-size: 0.75rem;
}

.rating-value {
  color: #666;
  font-size: 0.75rem;
}

.suggestion-link {
  color: #333;
  text-decoration: none;
  display: block;
  transition: color 0.2s;
  flex: 1;
}

.suggestion-link:hover {
  color: #8b4513;
}

/* ===================================
   USER ACTIONS
   =================================== */
.user-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
}

.auth-links {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
}

.auth-icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  transition: all 0.2s;
}

.auth-links:hover .auth-icon-container {
  background: rgba(255, 255, 255, 0.15);
  transform: scale(1.05);
}

.user-icon {
  font-size: 1.25rem;
  opacity: 0.9;
}

.auth-text-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.auth-link {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-weight: 500;
  transition: opacity 0.2s;
  white-space: nowrap;
}

.auth-link:hover {
  opacity: 0.8;
}

.logout-link {
  cursor: pointer;
}

.auth-divider {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.8rem;
}

.notification-container {
  position: relative;
}

.notification-btn {
  position: relative;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: rgba(255, 255, 255, 0.9);
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.notification-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: scale(1.05);
}

.notification-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  background: #e74c3c;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

/* ===================================
   NAVIGATION MENU
   =================================== */
.nav-menu {
  background-color: #2e1a1a;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.nav-menu-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.nav-links {
  display: flex;
  list-style: none;
  gap: 2.5rem;
  margin: 0;
  padding: 0.875rem 0;
}

.nav-item {
  position: relative;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 400;
  letter-spacing: 0.5px;
  text-transform: capitalize;
  transition: all 0.2s;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
  position: relative;
}

.nav-link:hover {
  color: #fff;
  opacity: 1;
}

.nav-link:hover::after {
  content: '';
  position: absolute;
  bottom: -0.875rem;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.8);
}

.categories-btn {
  font-weight: 600;
}

.categories-icon {
  font-size: 1rem;
}

/* ===================================
   MOBILE STYLES
   =================================== */
@media (max-width: 768px) {
  .logo-bar-container {
    padding: 0 1rem;
    gap: 1rem;
  }

  .mobile-search-btn {
    display: flex;
  }

  .search-section {
    position: fixed;
    width: 100%;
    left: 0;
    right: 0;
    top: -100%;
    background: #3d2222;
    padding: 1rem;
    z-index: 1001;
    transition: top 0.3s ease;
    margin: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .search-section.search-open {
    top: 0;
  }

  .search-form {
    border-radius: 12px;
  }

  .user-actions {
    display: none !important;
  }

  .search-suggestions {
    top: 70px;
    left: 1rem;
    right: 1rem;
    width: auto;
  }
}

@media (max-width: 480px) {
  .logo-bar-container {
    padding: 0 0.75rem;
  }
  
  .logo-img {
    height: 32px;
  }
}
</style>