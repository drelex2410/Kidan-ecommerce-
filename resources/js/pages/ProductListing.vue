<template>
  <div>
    <v-container class="pt-md-7 px-0 px-md-3 pb-0">
      <banner
        :loading="false"
        :banner="$store.getters['app/banners'].listing_page"
      />
    </v-container>
    <v-container class="pt-0">
      <v-row no-gutters align="start">
        <v-col
          cols="12"
          lg="auto"
          class="filter-col"
          :class="{ 'filter-open': filterDrawerOpen }"
        >
          <div
            class="filter-drawer"
            :class="{ open: filterDrawerOpen }"
          >
            <div class="filter-mobile-header d-flex d-lg-none align-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 18 18"
              >
                <path
                  d="M20,5H18.829a3,3,0,0,0-5.659,0H4A1,1,0,0,0,4,7h9.171a3,3,0,0,0,5.659,0H20a1,1,0,0,0,0-2ZM16,7a1,1,0,1,0-1-1A1,1,0,0,0,16,7ZM3,12a1,1,0,0,1,1-1H5.171a3,3,0,0,1,5.659,0H20a1,1,0,0,1,0,2H10.829a3,3,0,0,1-5.659,0H4A1,1,0,0,1,3,12Zm5,1a1,1,0,1,0-1-1A1,1,0,0,0,8,13ZM4,17a1,1,0,0,0,0,2h9.171a3,3,0,0,0,5.659,0H20a1,1,0,0,0,0-2H18.829a3,3,0,0,0-5.659,0Zm13,1a1,1,0,1,1-1-1A1,1,0,0,1,17,18Z"
                  transform="translate(-3 -3)"
                  fill="#2a2e34"
                  fill-rule="evenodd"
                />
              </svg>
              <span class="ms-4 fw-600 fs-14">{{ $t('filters') }}</span>
              <v-btn
                icon
                class="ms-auto"
                @click.stop="toggleFilterDrawer(false)"
              >
                <i class="la la-close fs-20"></i>
              </v-btn>
            </div>
            <div class="filter-content overflow-y-auto">
              <div class="filter-header d-none d-lg-flex">
                <h3 class="filter-title">Filter</h3>
                <button class="clear-filter-btn" @click="clearAllFilters">Clear Filter</button>
              </div>
              <div class="filter-section">
                <h4 class="filter-section-title">{{ $t('price') }}</h4>
                <button class="unselect-btn" @click="clearPrice">Unselect All</button>
                <div class="price-range-inputs">
                  <v-text-field
                    variant="outlined"
                    type="number"
                    density="compact"
                    v-model="queryParam.minPrice"
                    :placeholder="'Under NGN 20,000'"
                    hide-details
                    class="price-input"
                  ></v-text-field>
                  <v-text-field
                    variant="outlined"
                    type="number"
                    density="compact"
                    v-model="queryParam.maxPrice"
                    :placeholder="'NGN 15,000 - 50,000'"
                    hide-details
                    class="price-input mt-2"
                    @change="filterByPriceRange"
                  ></v-text-field>
                </div>
              </div>
              <div class="filter-section">
                <h4 class="filter-section-title">{{ $t('categories') }}</h4>
                <button class="unselect-btn" @click="clearCategories">Unselect All</button>
                <div class="filter-options">
                  <template v-if="is_empty_obj(currentCategory)">
                    <label 
                      v-for="(category, i) in rootCategories" 
                      :key="i" 
                      class="filter-option-item"
                    >
                      <input 
                        type="radio" 
                        name="category" 
                        class="filter-radio"
                        @change="$router.push({ name: 'Category', params: { categorySlug: category.slug } })"
                      >
                      <span class="filter-option-text">{{ category.name }}</span>
                    </label>
                  </template>
                  <template v-else>
                    <div class="filter-option-item clickable" @click="$router.push({ name: 'Shop' })">
                      <span class="filter-option-text">{{ $t('all_categories') }}</span>
                    </div>
                    <div 
                      v-if="!is_empty_obj(parentCategory)"
                      class="filter-option-item clickable" 
                      @click="$router.push({ name: 'Category', params: { categorySlug: parentCategory.slug } })"
                    >
                      <span class="filter-option-text">{{ parentCategory.name }}</span>
                    </div>
                    <label class="filter-option-item active">
                      <input type="radio" name="category" class="filter-radio" checked>
                      <span class="filter-option-text">{{ currentCategory.name }}</span>
                    </label>
                    <label 
                      v-for="(category, i) in childCategories" 
                      :key="i" 
                      class="filter-option-item child-category"
                    >
                      <input 
                        type="radio" 
                        name="category" 
                        class="filter-radio"
                        @change="$router.push({ name: 'Category', params: { categorySlug: category.slug } })"
                      >
                      <span class="filter-option-text">{{ category.name }}</span>
                    </label>
                  </template>
                </div>
              </div>
              <div class="filter-section" v-if="!isBrandRoute && allBrands.length > 0">
                <h4 class="filter-section-title">{{ $t('brands') }}</h4>
                <button class="unselect-btn" @click="clearBrands">Unselect All</button>
                <div class="filter-options">
                  <ShowMore v-if="allBrands.length > 5">
                    <label 
                      v-for="(brand, i) in allBrands"
                      :key="i"
                      class="filter-option-item"
                    >
                      <input 
                        type="checkbox" 
                        class="filter-checkbox"
                        :checked="queryParam.brandIds.includes(brand.id)"
                        @change="brandChange(brand.id)"
                      >
                      <span class="filter-option-text">{{ brand.name }}</span>
                    </label>
                  </ShowMore>
                  <template v-else>
                    <label 
                      v-for="(brand, i) in allBrands"
                      :key="i"
                      class="filter-option-item"
                    >
                      <input 
                        type="checkbox" 
                        class="filter-checkbox"
                        :checked="queryParam.brandIds.includes(brand.id)"
                        @change="brandChange(brand.id)"
                      >
                      <span class="filter-option-text">{{ brand.name }}</span>
                    </label>
                  </template>
                </div>
              </div>
              <div
                class="filter-section"
                v-for="(attribute, i) in attributes"
                :key="i"
              >
                <h4 class="filter-section-title">{{ attribute.name }}</h4>
                <button class="unselect-btn" @click="clearAttribute(attribute)">Unselect All</button>
                <div class="filter-options">
                  <ShowMore v-if="attribute.values.data.length > 5">
                    <label 
                      v-for="(value, j) in attribute.values.data"
                      :key="j"
                      class="filter-option-item"
                    >
                      <input 
                        type="checkbox" 
                        class="filter-checkbox"
                        :checked="queryParam.attributeValues.includes(value.id)"
                        @change="attributeValueChange(value.id)"
                      >
                      <span class="filter-option-text">{{ value.name }}</span>
                    </label>
                  </ShowMore>
                  <template v-else>
                    <label 
                      v-for="(value, j) in attribute.values.data"
                      :key="j"
                      class="filter-option-item"
                    >
                      <input 
                        type="checkbox" 
                        class="filter-checkbox"
                        :checked="queryParam.attributeValues.includes(value.id)"
                        @change="attributeValueChange(value.id)"
                      >
                      <span class="filter-option-text">{{ value.name }}</span>
                    </label>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </v-col>
        <v-col>
          <div class="product-content">
            <div class="content-header">
              <div class="header-left">
                <button 
                  class="mobile-filter-btn d-lg-none"
                  @click.stop="toggleFilterDrawer(true)"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    viewBox="0 0 18 18"
                  >
                    <path
                      d="M20,5H18.829a3,3,0,0,0-5.659,0H4A1,1,0,0,0,4,7h9.171a3,3,0,0,0,5.659,0H20a1,1,0,0,0,0-2ZM16,7a1,1,0,1,0-1-1A1,1,0,0,0,16,7ZM3,12a1,1,0,0,1,1-1H5.171a3,3,0,0,1,5.659,0H20a1,1,0,0,1,0,2H10.829a3,3,0,0,1-5.659,0H4A1,1,0,0,1,3,12Zm5,1a1,1,0,1,0-1-1A1,1,0,0,0,8,13ZM4,17a1,1,0,0,0,0,2h9.171a3,3,0,0,0,5.659,0H20a1,1,0,0,0,0-2H18.829a3,3,0,0,0-5.659,0Zm13,1a1,1,0,1,1-1-1A1,1,0,0,1,17,18Z"
                      transform="translate(-3 -3)"
                      fill="#2a2e34"
                    />
                  </svg>
                </button>
                <div>
                  <h1 class="page-title" v-if="queryParam.keyword">
                    {{ $t('search_results_for') }} "{{ queryParam.keyword }}"
                  </h1>
                  <h1 class="page-title" v-else-if="!is_empty_obj(currentCategory)">
                    {{ currentCategory.name }}
                  </h1>
                  <h1 class="page-title" v-else>{{ $t('all_products') }}</h1>
                  <p class="page-subtitle">
                    {{ totalProducts }} {{ $t('items') }}
                  </p>
                </div>
              </div>
              <div class="header-right">
                <span class="sort-label">SORT BY</span>
                <v-select
                  v-model="sortingDefault"
                  :items="sortingOptions"
                  item-title="name"
                  item-value="value"
                  density="compact"
                  variant="outlined"
                  hide-details
                  class="sort-select"
                  @update:modelValue="sortUpdate"
                />
              </div>
            </div>
            <div class="products-section">
              <v-row
                v-if="products.length > 0"
                class="product-grid"
              >
                <v-col 
                  v-for="(product, i) in products" 
                  :key="i"
                  cols="6"
                  sm="6"
                  md="4"
                  lg="3"
                >
                  <product-box :product-details="product" :is-loading="loading" />
                </v-col>
              </v-row>
              <div v-else class="no-products-found">
                {{ $t('no_product_found') }}
              </div>
            </div>
            <div v-if="totalPages > 1" class="pagination-section">
              <v-pagination
                v-model="queryParam.page"
                :length="totalPages"
                prev-icon="las la-angle-left"
                next-icon="las la-angle-right"
                :total-visible="7"
                elevation="0"
                @update:modelValue="pageSwitch"
              />
            </div>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>
<script>
import ShowMore from "./../components/inc/ShowMore.vue";
import { useHead } from '@unhead/vue';
export default {
  components: { ShowMore },
  data: () => ({
    metaTitle: '',
    metaDescription: '',
    loading: true,
    filterDrawerOpen: false,
    totalProducts: 0,
    totalPages: 1,
    isBrandRoute: false,
    queryParam: {
      page: 1,
      categorySlug: null,
      brandIds: [],
      attributeValues: [],
      keyword: null,
      sortBy: "popular",
      minPrice: null,
      maxPrice: null,
    },
    attributes: [],
    allBrands: [],
    rootCategories: [],
    parentCategory: {},
    currentCategory: {},
    childCategories: [],
    products: [],
    sortingDefault: { name: "Most Popular", value: "popular" },
  }),
  computed: {
    sortingOptions() {
      return [
        { name: this.$i18n.t("most_popular"), value: "popular" },
        { name: this.$i18n.t("latest_first"), value: "latest" },
        { name: this.$i18n.t("oldest_first"), value: "oldest" },
        { name: this.$i18n.t("higher_price_first"), value: "highest_price" },
        { name: this.$i18n.t("lower_price_first"), value: "lowest_price" },
      ];
    },
  },
  watch: {
    metaTitle(newTitle) { this.updateHead(newTitle, this.metaDescription); },
    metaDescription(newDesc) { this.updateHead(this.metaTitle, newDesc); },
    filterDrawerOpen(open) {
      document.body.classList.toggle('overflow-hidden', open);
    },
  },
  beforeUnmount() {
    document.body.classList.remove('overflow-hidden');
  },
  methods: {
    updateHead(title, description) {
      useHead({
        title,
        meta: [{ name: 'description', content: description }]
      });
    },
    toggleFilterDrawer(status) {
      this.filterDrawerOpen = status;
    },
    clearAllFilters() {
      this.queryParam.brandIds = [];
      this.queryParam.attributeValues = [];
      this.queryParam.minPrice = null;
      this.queryParam.maxPrice = null;
      this.$router.push({ query: {} }).catch(() => {});
      this.getList();
    },
    clearPrice() {
      this.queryParam.minPrice = null;
      this.queryParam.maxPrice = null;
      this.getList();
    },
    clearCategories() {
      this.$router.push({ name: 'Shop' }).catch(() => {});
    },
    clearBrands() {
      this.queryParam.brandIds = [];
      this.$router.push({ query: { ...this.$route.query, brandIds: [] } }).catch(() => {});
      this.getList();
    },
    clearAttribute(attribute) {
      attribute.values.data.forEach(value => {
        const idx = this.queryParam.attributeValues.indexOf(value.id);
        if (idx > -1) {
          this.queryParam.attributeValues.splice(idx, 1);
        }
      });
      this.$router.push({ query: { ...this.$route.query, attributeValues: this.queryParam.attributeValues } }).catch(() => {});
      this.getList();
    },
    pageSwitch(page) {
      this.queryParam.page = page;
      this.$router.push({ query: { ...this.$route.query, page } }).catch(() => {});
      this.getList({ page });
    },
    sortUpdate(sort) {
      this.queryParam.sortBy = sort;
      this.$router.push({ query: { ...this.$route.query, sortBy: sort } }).catch(() => {});
      this.getList({ sortBy: sort });
      this.sortingDefault = this.sortingOptions.find(s => s.value === sort);
    },
    brandChange(id) {
      const idx = this.queryParam.brandIds.indexOf(id);
      idx > -1 ? this.queryParam.brandIds.splice(idx, 1) : this.queryParam.brandIds.push(id);
      this.$router.push({ query: { ...this.$route.query, brandIds: this.queryParam.brandIds } }).catch(() => {});
      this.getList();
    },
    attributeValueChange(id) {
      const idx = this.queryParam.attributeValues.indexOf(id);
      idx > -1 ? this.queryParam.attributeValues.splice(idx, 1) : this.queryParam.attributeValues.push(id);
      this.$router.push({ query: { ...this.$route.query, attributeValues: this.queryParam.attributeValues } }).catch(() => {});
      this.getList();
    },
    filterByPriceRange() {
      this.$router.push({
        query: { ...this.$route.query, minPrice: this.queryParam.minPrice, maxPrice: this.queryParam.maxPrice }
      }).catch(() => {});
      this.getList();
    },
    async getList(obj = {}) {
      this.loading = true;
      const params = { ...this.queryParam, ...obj };
      let url = "product/search?";
      url += `&page=${params.page}`;
      url += params.categorySlug ? `&category_slug=${params.categorySlug}` : "";
      url += params.brandIds.length ? `&brand_ids=${params.brandIds}` : "";
      url += params.attributeValues.length ? `&attribute_values=${params.attributeValues}` : "";
      url += params.keyword ? `&keyword=${params.keyword}` : "";
      url += params.sortBy ? `&sort_by=${params.sortBy}` : "";
      url += params.minPrice ? `&min_price=${params.minPrice}` : "";
      url += params.maxPrice ? `&max_price=${params.maxPrice}` : "";
      const res = await this.call_api("get", url);
      if (res.data.success) {
        this.loading = false;
        this.metaTitle = res.data.metaTitle;
        this.metaDescription = res.data.metaDescription || '';
        this.products = res.data.products.data;
        this.attributes = res.data.attributes.data;
        this.allBrands = res.data.allBrands.data;
        this.rootCategories = res.data.rootCategories.data;
        this.parentCategory = res.data.parentCategory || {};
        this.currentCategory = res.data.currentCategory || {};
        this.childCategories = res.data.childCategories?.data || [];
        this.totalPages = res.data.totalPage;
        this.totalProducts = res.data.total;
        this.queryParam.page = res.data.currentPage;
      }
    },
  },
  created() {
    this.isBrandRoute = !!this.$route.params.brandId;
    this.queryParam.categorySlug = this.$route.params.categorySlug || null;
    this.queryParam.keyword = this.$route.params.keyword || null;
    this.queryParam.brandIds = this.$route.params.brandId ? [this.$route.params.brandId] : [];
    this.queryParam.page = Number(this.$route.query.page) || 1;
    this.queryParam.sortBy = this.$route.query.sortBy || "popular";
    this.queryParam.minPrice = this.$route.query.minPrice || null;
    this.queryParam.maxPrice = this.$route.query.maxPrice || null;
    this.queryParam.attributeValues = this.$route.query.attributeValues?.split(',').filter(Boolean) || [];
    this.sortingDefault = this.sortingOptions.find(s => s.value === this.queryParam.sortBy) || this.sortingOptions[0];
    this.getList();
  },
};
</script>
<style scoped>
.filter-drawer {
  background: #FFFBF3;
  border-right: 1px solid #e0e0e0;
}
.filter-mobile-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e0e0e0;
}
.filter-content {
  padding: 0;
}
.filter-header {
  padding: 24px;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e0e0e0;
}
.filter-title {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
  color: #000;
}
.clear-filter-btn {
  background: none;
  border: none;
  color: #ff3b30;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  padding: 0;
  text-decoration: none;
}
.clear-filter-btn:hover {
  text-decoration: underline;
}
.filter-section {
  padding: 20px 24px;
  border-bottom: 1px solid #e0e0e0;
}
.filter-section-title {
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: #000;
}
.unselect-btn {
  background: none;
  border: none;
  color: #ff3b30;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  padding: 0;
  margin-bottom: 16px;
  display: block;
  text-decoration: none;
}
.unselect-btn:hover {
  text-decoration: underline;
}
.price-range-inputs {
  margin-top: 12px;
}
.price-input {
  font-size: 13px;
}
.filter-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.filter-option-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #333;
  cursor: pointer;
  padding: 2px 0;
}
.filter-option-item.clickable:hover {
  color: #000;
}
.filter-option-item.child-category {
  padding-left: 20px;
}
.filter-option-item.active {
  font-weight: 600;
}
.filter-radio,
.filter-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #000;
  flex-shrink: 0;
}
.filter-option-text {
  flex: 1;
  line-height: 1.4;
}
@media (min-width: 1264px) {
  .filter-col {
    width: 320px;
    flex: 0 0 320px;
    position: sticky;
    top: 20px;
    align-self: flex-start;
  }
  
  .filter-drawer {
    height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
  }
  
  .filter-content {
    flex: 1;
    overflow-y: auto;
  }
}
@media (max-width: 1263px) {
  .filter-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: 320px;
    max-width: 90vw;
    height: 100vh;
    z-index: 9999;
    box-shadow: -8px 0 30px rgba(0, 0, 0, 0.2);
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .filter-drawer.open {
    transform: translateX(0);
  }
  
  .filter-content {
    height: calc(100vh - 70px);
  }
}
.product-content {
  padding-left: 40px;
  padding-top: 0;
}
@media (max-width: 1263px) {
  .product-content {
    padding-left: 0;
  }
}
.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex: 1;
}
.mobile-filter-btn {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 10px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.page-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #000;
}
.page-subtitle {
  font-size: 13px;
  color: #666;
  margin: 0;
}
.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}
.sort-label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}
.sort-select {
  min-width: 180px;
  font-size: 13px;
}
.products-section {
  margin-bottom: 48px;
}
.product-grid {
  margin: 0 -10px;
}
.product-grid > .v-col {
  padding: 10px;
}
.no-products-found {
  padding: 80px 20px;
  text-align: center;
  font-size: 18px;
  color: #999;
}
.pagination-section {
  display: flex;
  justify-content: center;
  padding: 40px 0;
}
@media (max-width: 768px) {
  .content-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .header-left {
    width: 100%;
  }
  
  .header-right {
    width: 100%;
  }
  
  .page-title {
    font-size: 20px;
  }
  
  .sort-select {
    flex: 1;
  }
}
:deep(.v-field__outline) {
  border-radius: 4px;
}
:deep(.v-field--variant-outlined .v-field__outline__start) {
  border-radius: 4px 0 0 4px;
}
:deep(.v-field--variant-outlined .v-field__outline__end) {
  border-radius: 0 4px 4px 0;
}
:deep(.v-field__input) {
  font-size: 13px;
  padding-top: 4px;
  padding-bottom: 4px;
}
:deep(.v-field--variant-outlined .v-field__outline) {
  border-color: #ddd;
}
:deep(.v-field--focused .v-field__outline) {
  border-color: #000;
}
:deep(.v-select__selection-text) {
  font-size: 13px;
  color: #333;
}
:deep(.v-list-item__content) {
  font-size: 13px;
}
:deep(.v-pagination__list) {
  gap: 8px;
}
:deep(.v-pagination__item) {
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  min-width: 40px;
  height: 40px;
  font-size: 14px;
  color: #333;
}
:deep(.v-pagination__item--is-active) {
  background-color: #000;
  color: #fff;
  border-color: #000;
}
:deep(.v-pagination__prev .v-icon),
:deep(.v-pagination__next .v-icon) {
  font-size: 18px;
}
</style>