<template>
  <div class="search-page">
    <div class="search-page-content">
      <router-link to="/" class="back-btn">
        <i class="las la-arrow-left"></i> Back to Home
      </router-link>
      
      <div class="search-wrapper">
        <h1 class="search-title">Find Fashion on Kidan</h1>
        
        <v-form class="search-form" @submit.stop.prevent="search">
          <div class="search-form-inner">
            <input
              type="text"
              class="search-input"
              :placeholder="$t('search_for_products_brands_and_more')"
              v-model="searchQuery"
              @keyup="handleKeyup"
              required
              ref="searchInput"
            />
            <button class="search-btn" type="submit">
              Search
            </button>
          </div>
        </v-form>

        <div class="popular-suggestions" v-if="!searchQuery && keywords && keywords.length">
          <h3 class="popular-title">Popular Searches</h3>
          <div class="popular-tags">
            <button
              v-for="(keyword, index) in keywords"
              :key="index"
              class="popular-tag"
              @click="popularSuggesation(keyword)"
            >
              {{ keyword }}
            </button>
          </div>
        </div>

        <div class="search-suggestions" v-if="showSuggestionContainer && searchQuery">
          <div class="search-loading" v-if="loadingSuggestion">
            <div class="spinner"></div>
            <p class="loading-text">Searching...</p>
          </div>

          <div class="no-results" v-else-if="suggestionNotFound">
            <i class="las la-search no-results-icon"></i>
            <p class="no-results-text">No results found for "{{ searchQuery }}"</p>
            <p class="no-results-subtext">Try different keywords or browse our categories</p>
          </div>

          <div v-else>
            <div v-if="searchKeywords && searchKeywords.length" class="suggestion-section">
              <div class="suggestion-header">
                <i class="las la-search suggestion-icon"></i>
                <span>Keywords</span>
              </div>
              <ul class="suggestion-list">
                <li
                  v-for="(keyword, index) in searchKeywords"
                  :key="index"
                  class="suggestion-item"
                  @click="popularSuggesation(keyword)"
                >
                  <i class="las la-search suggestion-item-icon"></i>
                  <span class="suggestion-item-text">{{ keyword }}</span>
                </li>
              </ul>
            </div>

            <div v-if="searchCategories && searchCategories.length" class="suggestion-section">
              <div class="suggestion-header">
                <i class="las la-list suggestion-icon"></i>
                <span>Categories</span>
              </div>
              <ul class="suggestion-list">
                <li
                  v-for="category in searchCategories"
                  :key="category.id"
                  class="suggestion-item"
                  @click="selectCategory(category)"
                >
                  <i class="las la-folder suggestion-item-icon"></i>
                  <router-link
                    :to="{ name: 'Category', params: { categorySlug: category.slug }}"
                    class="suggestion-link"
                  >
                    {{ category.name }}
                  </router-link>
                </li>
              </ul>
            </div>

            <div v-if="products && products.length" class="suggestion-section">
              <div class="suggestion-header">
                <i class="las la-shopping-bag suggestion-icon"></i>
                <span>Products</span>
              </div>
              <ul class="suggestion-list">
                <li
                  v-for="product in products"
                  :key="product.id"
                  class="suggestion-item product-item"
                >
                  <div class="product-image-container">
                    <img
                      :src="product.thumbnail_image"
                      :alt="product.name"
                      class="product-img"
                      @error="imageFallback"
                    />
                  </div>
                  <div class="product-info">
                    <router-link
                      :to="{ name: 'ProductDetails', params: { slug: product.slug }}"
                      class="product-name"
                    >
                      {{ product.name }}
                    </router-link>
                    <div class="product-price">
                      <span v-if="product.base_price > product.base_discounted_price" class="original-price">
                        {{ format_price(product.base_price) }}
                      </span>
                      <span class="discounted-price">{{ format_price(product.base_discounted_price) }}</span>
                    </div>
                  </div>
                </li>
              </ul>
            </div>

            <div v-if="brands && brands.length" class="suggestion-section">
              <div class="suggestion-header">
                <i class="las la-tag suggestion-icon"></i>
                <span>Brands</span>
              </div>
              <ul class="suggestion-list">
                <li
                  v-for="brand in brands"
                  :key="brand.id"
                  class="suggestion-item"
                >
                  <i class="las la-tag suggestion-item-icon"></i>
                  <router-link
                    :to="{ name: 'Brand', params: { brandId: brand.id } }"
                    class="suggestion-link"
                  >
                    {{ brand.name }}
                  </router-link>
                </li>
              </ul>
            </div>

            <div v-if="shops && shops.length" class="suggestion-section">
              <div class="suggestion-header">
                <i class="las la-store suggestion-icon"></i>
                <span>Shops</span>
              </div>
              <ul class="suggestion-list">
                <li
                  v-for="shop in shops"
                  :key="shop.id"
                  class="suggestion-item product-item"
                >
                  <div class="shop-image-container">
                    <img
                      :src="shop.logo"
                      :alt="shop.name"
                      class="shop-img"
                      @error="imageFallback"
                    />
                  </div>
                  <router-link
                    :to="{ name: 'ShopDetails', params: { slug: shop.slug }}"
                    class="suggestion-link"
                  >
                    {{ shop.name }}
                  </router-link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: 'SearchPage',
  data() {
    return {
      searchQuery: '',
      loadingSuggestion: false,
      showSuggestionContainer: false,
      suggestionNotFound: false,
      keywords: [],
      searchKeywords: [],
      searchCategories: [],
      brands: [],
      products: [],
      shops: [],
      debounceTimer: null,
      allCategories: [],
      loadingCategories: false
    };
  },
  computed: {
    ...mapGetters(['isAuthenticated']),
  },
  mounted() {
    if (this.$route.params.keyword) {
      this.searchQuery = this.$route.params.keyword;
      this.search();
    }
    this.$nextTick(() => {
      this.$refs.searchInput?.focus();
    });
    this.loadCategories();
  },
  methods: {
    applySearchPayload(payload = {}) {
      this.searchKeywords = payload.keywords || [];
      this.searchCategories = payload.categories || [];
      this.products = payload.products?.data || payload.products || [];
      this.brands = payload.brands || [];
      this.shops = payload.shops?.data || payload.shops || [];

      const hasResults =
        this.searchKeywords.length ||
        this.searchCategories.length ||
        this.products.length ||
        this.brands.length ||
        this.shops.length;

      this.suggestionNotFound = !hasResults;
    },
    async search() {
      const keyword = this.searchQuery.trim();
      if (!keyword) return;
      this.loadingSuggestion = true;
      this.showSuggestionContainer = true;
      this.suggestionNotFound = false;
      try {
        const response = await this.call_api("get", `search.ajax/${encodeURIComponent(keyword)}`);
        if (response.data?.success) {
          this.applySearchPayload(response.data);
        } else {
          this.applySearchPayload({});
        }
      } catch (error) {
        console.error('Search error:', error);
        this.suggestionNotFound = true;
      } finally {
        this.loadingSuggestion = false;
      }
      if (this.$route.params.keyword !== keyword) {
        this.$router.push({ name: 'Search', params: { keyword } });
      }
    },
    handleKeyup() {
      if (this.debounceTimer) clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.searchQuery.trim() ? this.ajaxSearch() : this.showSuggestionContainer = false;
      }, 300);
    },
    async ajaxSearch() {
      const keyword = this.searchQuery.trim();
      if (!keyword) {
        this.showSuggestionContainer = false;
        this.applySearchPayload({});
        return;
      }
      this.loadingSuggestion = true;
      this.showSuggestionContainer = true;
      try {
        const response = await this.call_api("get", `search.ajax/${encodeURIComponent(keyword)}`);
        if (response.data?.success) {
          this.applySearchPayload(response.data);
        } else {
          this.applySearchPayload({});
        }
      } catch (error) {
        console.error('Ajax search error:', error);
        this.suggestionNotFound = true;
      } finally {
        this.loadingSuggestion = false;
      }
    },
    async loadCategories() {
      try {
        this.loadingCategories = true;
        const res = await this.call_api("get", "all-categories");
        if (res.data.success) {
          this.allCategories = res.data.data;
          if (this.allCategories.length) {
            this.keywords = this.allCategories.slice(0, 7).map(cat => cat.name);
          }
          if (this.searchQuery.trim()) this.filterCategoriesByQuery();
        }
      } catch (error) {
        console.error("Error loading categories:", error);
      } finally {
        this.loadingCategories = false;
      }
    },
    filterCategoriesByQuery() {
      const query = this.searchQuery.toLowerCase().trim();
      if (!query) {
        this.searchCategories = [];
        return;
      }
      this.searchCategories = this.allCategories
        .filter(c => c.name.toLowerCase().includes(query) ||
          (c.description && c.description.toLowerCase().includes(query)))
        .slice(0, 5);
    },
    selectCategory(category) {
      this.$router.push({ name: 'Category', params: { categorySlug: category.slug } });
      this.showSuggestionContainer = false;
    },
    popularSuggesation(keyword) {
      this.searchQuery = keyword;
      this.search();
    },
    imageFallback(e) {
      e.target.src = "/images/placeholder.png";
    }
  },
  watch: {
    '$route.params.keyword'(newVal) {
      if (newVal && newVal !== this.searchQuery) {
        this.searchQuery = newVal;
        this.search();
      }
    },
    searchQuery() {
      this.searchQuery.trim() ? this.filterCategoriesByQuery() : this.searchCategories = [];
    }
  },
  beforeUnmount() {
    if (this.debounceTimer) clearTimeout(this.debounceTimer);
  }
};
</script>

<style scoped>
.search-page {
  min-height: 100vh;
  background: #FFFBF3;
  padding: 2rem 1.5rem;
}
.search-page-content {
  max-width: 900px;
  margin: 0 auto;
}
.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #333;
  text-decoration: none;
  font-size: 1rem;
  margin-bottom: 2rem;
  transition: all 0.3s ease;
  padding: 0.5rem 1rem;
  border-radius: 4px;
}
.back-btn:hover {
  color: #d32f2f;
  background: #f8f8f8;
}
.search-wrapper {
  margin-top: 1rem;
  animation: fadeInUp 0.4s ease;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.search-title {
  text-align: center;
  font-size: 2rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 2rem;
  letter-spacing: -0.5px;
}
.search-form-inner {
  display: flex;
  align-items: center;
  background: #FFFBF3;
  border: 2px solid #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  transition: all 0.3s ease;
  max-width: 700px;
  margin: 0 auto;
}
.search-form-inner:focus-within {
  border-color: #333;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.search-input {
  flex: 1;
  background: transparent;
  border: none;
  color: #1a1a1a;
  font-size: 1rem;
  outline: none;
  padding: 1rem 1.25rem;
  min-width: 0;
}
.search-input::placeholder {
  color: rgba(0,0,0,0.4);
}
.search-btn {
  background: #d32f2f;
  color: #fff;
  border: none;
  padding: 1rem 2.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
  text-transform: capitalize;
}
.search-btn:hover {
  background: #b71c1c;
}
.search-btn:active {
  transform: scale(0.98);
}
.popular-suggestions {
  margin-top: 3rem;
  text-align: center;
}
.popular-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #666;
  margin-bottom: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.popular-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
  max-width: 700px;
  margin: 0 auto;
}
.popular-tag {
  background: #f5f5f5;
  border: 1px solid #e0e0e0;
  border-radius: 20px;
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
  color: #555;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.popular-tag:hover {
  background: #333;
  color: #fff;
  border-color: #333;
  transform: translateY(-2px);
}
.search-suggestions {
  background: #fff;
  border-radius: 8px;
  margin-top: 2rem;
  max-height: 60vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  border: 1px solid #e0e0e0;
}
.search-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  gap: 1rem;
}
.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #d32f2f;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.loading-text {
  color: #666;
  font-size: 0.9375rem;
}
.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3rem;
  text-align: center;
}
.no-results-icon {
  font-size: 4rem;
  color: #ccc;
  margin-bottom: 1rem;
}
.no-results-text {
  color: #333;
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.no-results-subtext {
  color: #666;
  font-size: 0.9375rem;
}
.suggestion-section {
  border-bottom: 1px solid #f0f0f0;
}
.suggestion-section:last-child {
  border-bottom: none;
}
.suggestion-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f8f8f8;
  padding: 0.875rem 1.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #555;
  letter-spacing: 1px;
  position: sticky;
  top: 0;
  z-index: 1;
}
.suggestion-icon {
  font-size: 1rem;
  color: #333;
}
.suggestion-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.suggestion-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}
.suggestion-item:hover {
  background: #f8f8f8;
  border-left-color: #d32f2f;
}
.suggestion-item-icon {
  color: #666;
  font-size: 1rem;
  width: 20px;
  text-align: center;
  flex-shrink: 0;
}
.suggestion-item-text {
  flex: 1;
  color: #333;
  font-size: 0.9375rem;
}
.product-item {
  align-items: center;
}
.product-image-container,
.shop-image-container {
  flex-shrink: 0;
}
.product-img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e0e0e0;
}
.shop-img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 50%;
  border: 1px solid #e0e0e0;
}
.product-info {
  flex: 1;
  min-width: 0;
}
.product-name {
  display: block;
  color: #333;
  text-decoration: none;
  font-size: 0.9375rem;
  margin-bottom: 0.5rem;
  transition: color 0.2s ease;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.product-name:hover {
  color: #d32f2f;
}
.product-price {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.original-price {
  color: #999;
  text-decoration: line-through;
  font-size: 0.875rem;
}
.discounted-price {
  color: #d32f2f;
  font-weight: 700;
  font-size: 1rem;
}
.suggestion-link {
  color: #333;
  text-decoration: none;
  display: block;
  transition: color 0.2s ease;
  flex: 1;
  font-size: 0.9375rem;
}
.suggestion-link:hover {
  color: #d32f2f;
}
.search-suggestions::-webkit-scrollbar {
  width: 6px;
}
.search-suggestions::-webkit-scrollbar-track {
  background: #f5f5f5;
}
.search-suggestions::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 3px;
}
.search-suggestions::-webkit-scrollbar-thumb:hover {
  background: #999;
}

@media (max-width: 768px) {
  .search-page { padding: 1.5rem 1rem; }
  .back-btn { font-size: 0.9375rem; margin-bottom: 1.5rem; }
  .search-wrapper { margin-top: 0.5rem; }
  .search-title { font-size: 1.5rem; margin-bottom: 1.5rem; }
  .search-form-inner { flex-direction: column; }
  .search-input {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 0.9375rem;
    border-bottom: 1px solid #e0e0e0;
  }
  .search-btn { width: 100%; padding: 0.875rem 1.5rem; }
  .popular-suggestions { margin-top: 2rem; }
  .popular-tags { gap: 0.5rem; }
  .popular-tag { padding: 0.4rem 1rem; font-size: 0.8125rem; }
  .search-suggestions { max-height: 50vh; margin-top: 1.5rem; }
  }
  .suggestion-header { padding: 0.75rem 1rem; font-size: 0.6875rem; }
  .suggestion-item { padding: 0.875rem 1rem; gap: 0.75rem; }
  .product-img { width: 50px; height: 50px; }
  .shop-img { width: 40px; height: 40px; }
  .product-name,
  .suggestion-link,
  .suggestion-item-text { font-size: 0.875rem; }
  .discounted-price { font-size: 0.9375rem; }
  .original-price { font-size: 0.8125rem; }

@media (max-width: 480px) {
  .search-page { padding: 1rem 0.75rem; }
  .search-title { font-size: 1.25rem; }
  .search-input { font-size: 0.875rem; padding: 0.75rem 1rem; }
  .search-btn { font-size: 0.9375rem; padding: 0.75rem 1.25rem; }
  .popular-tag { font-size: 0.75rem; padding: 0.35rem 0.875rem; }
  .product-img { width: 45px; height: 45px; }
  .no-results { padding: 2rem 1rem; }
  .no-results-icon { font-size: 3rem; }
  .no-results-text { font-size: 1rem; }
  .no-results-subtext { font-size: 0.875rem; }
}
</style>
