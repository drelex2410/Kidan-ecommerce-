<template>
  <product-collection-page
    hero-eyebrow="Collection"
    :hero-title="heroTitle"
    :hero-description="heroDescription"
    :filters-title="filtersTitle"
    :sorting-options="sortingOptions"
    :sort-by="queryParam.sortBy"
    :products="products"
    :loading="loading"
    :loading-message="'Loading products...'"
    :empty-message="$t('no_product_found')"
    :total-products="totalProducts"
    :total-pages="totalPages"
    :page="queryParam.page"
    :is-desktop="isDesktop"
    :filter-panel-open="filterPanelOpen"
    @toggle-filter="toggleFilterPanel"
    @clear-filters="clearAllFilters"
    @sort-change="sortUpdate"
    @page-change="pageSwitch"
  >
    <template #filters>
      <section class="filter-section">
        <div class="filter-section-header">
          <h3 class="section-title">{{ $t("price") }}</h3>
          <button class="section-clear" type="button" @click="clearPrice">Unselect All</button>
        </div>

        <div class="price-field-group">
          <label class="price-label" for="listing-min-price">Minimum Price</label>
          <v-text-field
            id="listing-min-price"
            variant="outlined"
            type="number"
            density="compact"
            v-model="queryParam.minPrice"
            :placeholder="'Under NGN 20,000'"
            hide-details
            class="price-input"
          ></v-text-field>
        </div>

        <div class="price-field-group">
          <label class="price-label" for="listing-max-price">Maximum Price</label>
          <v-text-field
            id="listing-max-price"
            variant="outlined"
            type="number"
            density="compact"
            v-model="queryParam.maxPrice"
            :placeholder="'NGN 15,000 - 50,000'"
            hide-details
            class="price-input"
            @change="filterByPriceRange"
          ></v-text-field>
        </div>

        <button class="apply-price" type="button" @click="filterByPriceRange">Apply Price</button>
      </section>

      <section class="filter-section">
        <div class="filter-section-header">
          <h3 class="section-title">{{ $t("categories") }}</h3>
          <button class="section-clear" type="button" @click="clearCategories">Unselect All</button>
        </div>

        <div class="filter-options">
          <template v-if="is_empty_obj(currentCategory)">
            <button
              v-for="category in rootCategories"
              :key="category.id"
              type="button"
              class="filter-option"
              @click="$router.push({ name: 'Category', params: { categorySlug: category.slug } })"
            >
              <span class="option-indicator"></span>
              <span class="option-text">{{ category.name }}</span>
            </button>
          </template>
          <template v-else>
            <button type="button" class="filter-option" @click="$router.push({ name: 'Shop' })">
              <span class="option-indicator"></span>
              <span class="option-text">{{ $t("all_categories") }}</span>
            </button>
            <button
              v-if="!is_empty_obj(parentCategory)"
              type="button"
              class="filter-option"
              @click="$router.push({ name: 'Category', params: { categorySlug: parentCategory.slug } })"
            >
              <span class="option-indicator"></span>
              <span class="option-text">{{ parentCategory.name }}</span>
            </button>
            <button type="button" class="filter-option selected">
              <span class="option-indicator"></span>
              <span class="option-text">{{ currentCategory.name }}</span>
            </button>
            <button
              v-for="category in childCategories"
              :key="category.id"
              type="button"
              class="filter-option"
              @click="$router.push({ name: 'Category', params: { categorySlug: category.slug } })"
            >
              <span class="option-indicator"></span>
              <span class="option-text">{{ category.name }}</span>
            </button>
          </template>
        </div>
      </section>

      <section v-if="!isBrandRoute && allBrands.length > 0" class="filter-section">
        <div class="filter-section-header">
          <h3 class="section-title">{{ $t("brands") }}</h3>
          <button class="section-clear" type="button" @click="clearBrands">Unselect All</button>
        </div>

        <div class="filter-options">
          <button
            v-for="brand in allBrands"
            :key="brand.id"
            type="button"
            class="filter-option"
            :class="{ selected: queryParam.brandIds.includes(String(brand.id)) || queryParam.brandIds.includes(brand.id) }"
            @click="brandChange(brand.id)"
          >
            <span class="option-indicator"></span>
            <span class="option-text">{{ brand.name }}</span>
          </button>
        </div>
      </section>

      <section
        v-for="attribute in attributes"
        :key="attribute.id"
        class="filter-section"
      >
        <div class="filter-section-header">
          <h3 class="section-title">{{ attribute.name }}</h3>
          <button class="section-clear" type="button" @click="clearAttribute(attribute)">Unselect All</button>
        </div>

        <div class="filter-options">
          <button
            v-for="value in attribute.values.data"
            :key="value.id"
            type="button"
            class="filter-option"
            :class="{ selected: queryParam.attributeValues.includes(String(value.id)) || queryParam.attributeValues.includes(value.id) }"
            @click="attributeValueChange(value.id)"
          >
            <span class="option-indicator"></span>
            <span class="option-text">{{ value.name }}</span>
          </button>
        </div>
      </section>
    </template>
  </product-collection-page>
</template>

<script>
import { useHead } from "@unhead/vue";
import ProductCollectionPage from "../components/product/ProductCollectionPage.vue";

export default {
  components: { ProductCollectionPage },
  data: () => ({
    metaTitle: "",
    metaDescription: "",
    loading: true,
    isDesktop: false,
    filterPanelOpen: false,
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
    displayKeyword() {
      return decodeURIComponent(String(this.queryParam.keyword || ""))
        .replace(/_/g, " ")
        .trim();
    },
    heroTitle() {
      if (this.displayKeyword) {
        return `${this.$t("search_results_for")} "${this.displayKeyword}"`;
      }

      if (!this.is_empty_obj(this.currentCategory)) {
        return this.currentCategory.name;
      }

      return this.$t("all_products");
    },
    heroDescription() {
      if (this.displayKeyword) {
        return `Browse matching products for "${this.displayKeyword}" in our storefront catalogue.`;
      }

      if (!this.is_empty_obj(this.currentCategory)) {
        return `Browse every product available in ${this.currentCategory.name}.`;
      }

      return "Browse the full storefront catalogue with the same filters and product layout as Today's Deal.";
    },
    filtersTitle() {
      if (!this.is_empty_obj(this.currentCategory)) {
        return `Refine ${this.currentCategory.name}`;
      }

      return "Refine Product Listing";
    },
  },
  watch: {
    metaTitle(newTitle) {
      this.updateHead(newTitle, this.metaDescription);
    },
    metaDescription(newDesc) {
      this.updateHead(this.metaTitle, newDesc);
    },
    filterPanelOpen(open) {
      document.body.classList.toggle("overflow-hidden", !this.isDesktop && open);
    },
  },
  beforeUnmount() {
    document.body.classList.remove("overflow-hidden");
    window.removeEventListener("resize", this.handleViewportChange);
  },
  methods: {
    updateHead(title, description) {
      useHead({
        title,
        meta: [{ name: "description", content: description }],
      });
    },
    handleViewportChange() {
      const nextIsDesktop = window.innerWidth >= 1100;

      if (nextIsDesktop && !this.isDesktop) {
        this.filterPanelOpen = true;
      }

      if (!nextIsDesktop && this.isDesktop) {
        this.filterPanelOpen = false;
      }

      this.isDesktop = nextIsDesktop;
      document.body.classList.toggle("overflow-hidden", !this.isDesktop && this.filterPanelOpen);
    },
    toggleFilterPanel(status) {
      this.filterPanelOpen = status;
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
      this.$router.push({ name: "Shop" }).catch(() => {});
    },
    clearBrands() {
      this.queryParam.brandIds = [];
      this.$router.push({ query: { ...this.$route.query, brandIds: [] } }).catch(() => {});
      this.getList();
    },
    clearAttribute(attribute) {
      attribute.values.data.forEach((value) => {
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
    },
    brandChange(id) {
      const normalizedId = String(id);
      const existingIndex = this.queryParam.brandIds.findIndex((value) => String(value) === normalizedId);
      if (existingIndex > -1) {
        this.queryParam.brandIds.splice(existingIndex, 1);
      } else {
        this.queryParam.brandIds.push(id);
      }
      this.$router.push({ query: { ...this.$route.query, brandIds: this.queryParam.brandIds } }).catch(() => {});
      this.getList();
    },
    attributeValueChange(id) {
      const normalizedId = String(id);
      const existingIndex = this.queryParam.attributeValues.findIndex((value) => String(value) === normalizedId);
      if (existingIndex > -1) {
        this.queryParam.attributeValues.splice(existingIndex, 1);
      } else {
        this.queryParam.attributeValues.push(id);
      }
      this.$router.push({ query: { ...this.$route.query, attributeValues: this.queryParam.attributeValues } }).catch(() => {});
      this.getList();
    },
    filterByPriceRange() {
      this.$router.push({
        query: { ...this.$route.query, minPrice: this.queryParam.minPrice, maxPrice: this.queryParam.maxPrice },
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
        this.metaTitle = res.data.metaTitle || res.data.categoryMetaTitle || this.heroTitle;
        this.metaDescription = res.data.metaDescription || res.data.categoryMetaDescription || "";
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
    this.queryParam.attributeValues = this.$route.query.attributeValues?.split(",").filter(Boolean) || [];
  },
  mounted() {
    this.handleViewportChange();
    window.addEventListener("resize", this.handleViewportChange);
    this.getList();
  },
};
</script>
