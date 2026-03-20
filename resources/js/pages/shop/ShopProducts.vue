<template>
  <product-collection-page
    hero-eyebrow="Shop Collection"
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
    @toggle-filter="toggleFilterDrawer"
    @clear-filters="clearAllFilters"
    @sort-change="sortUpdate"
    @page-change="pageSwitch"
  >
    <template #filters>
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
              @click="$router.push({ name: 'ShopProducts', params: { categorySlug: category.slug, slug: $route.params.slug } })"
            >
              <span class="option-indicator"></span>
              <span class="option-text">{{ category.name }}</span>
            </button>
          </template>
          <template v-else>
            <button
              type="button"
              class="filter-option"
              @click="$router.push({ name: 'ShopProducts', params: { slug: $route.params.slug } })"
            >
              <span class="option-indicator"></span>
              <span class="option-text">{{ $t("all_categories") }}</span>
            </button>
            <button
              v-if="!is_empty_obj(parentCategory)"
              type="button"
              class="filter-option"
              @click="$router.push({ name: 'ShopProducts', params: { categorySlug: parentCategory.slug, slug: $route.params.slug } })"
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
              @click="$router.push({ name: 'ShopProducts', params: { categorySlug: category.slug, slug: $route.params.slug } })"
            >
              <span class="option-indicator"></span>
              <span class="option-text">{{ category.name }}</span>
            </button>
          </template>
        </div>
      </section>

      <section class="filter-section">
        <div class="filter-section-header">
          <h3 class="section-title">{{ $t("price") }}</h3>
          <button class="section-clear" type="button" @click="clearPrice">Unselect All</button>
        </div>

        <div class="price-field-group">
          <label class="price-label" for="shop-min-price">Minimum Price</label>
          <v-text-field
            id="shop-min-price"
            type="number"
            variant="outlined"
            density="compact"
            v-model="queryParam.minPrice"
            :placeholder="$t('min_price')"
            hide-details
            class="price-input"
          ></v-text-field>
        </div>

        <div class="price-field-group">
          <label class="price-label" for="shop-max-price">Maximum Price</label>
          <v-text-field
            id="shop-max-price"
            type="number"
            variant="outlined"
            density="compact"
            v-model="queryParam.maxPrice"
            :placeholder="$t('max_price')"
            hide-details
            class="price-input"
          ></v-text-field>
        </div>

        <button class="apply-price" type="button" @click="filterByPriceRange">Apply Price</button>
      </section>

      <section v-if="allBrands.length > 0" class="filter-section">
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
import ProductCollectionPage from "../../components/product/ProductCollectionPage.vue";

export default {
  components: {
    ProductCollectionPage,
  },
  data: () => ({
    loading: true,
    isDesktop: false,
    filterPanelOpen: false,
    totalProducts: 0,
    totalPages: 1,
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
    shopName() {
      return this.$parent?.shopDetails?.name || "Shop";
    },
    heroTitle() {
      if (this.displayKeyword) {
        return `${this.shopName}: ${this.$t("search_results_for")} "${this.displayKeyword}"`;
      }

      if (!this.is_empty_obj(this.currentCategory)) {
        return `${this.shopName}: ${this.currentCategory.name}`;
      }

      return `${this.shopName}: ${this.$t("all_products")}`;
    },
    heroDescription() {
      return `Browse ${this.shopName}'s catalogue with the same collection layout, filters, and product grid used on Today's Deal.`;
    },
    filtersTitle() {
      return `Refine ${this.shopName}`;
    },
  },
  watch: {
    "$route.fullPath"() {
      this.syncStateFromRoute();
      this.getList({
        page: this.queryParam.page,
        categorySlug: this.queryParam.categorySlug,
        brandIds: this.queryParam.brandIds,
        attributeValues: this.queryParam.attributeValues,
        keyword: this.queryParam.keyword,
        sortBy: this.queryParam.sortBy,
        minPrice: this.queryParam.minPrice,
        maxPrice: this.queryParam.maxPrice,
      });
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
    syncStateFromRoute() {
      this.queryParam.categorySlug = this.$route.params.categorySlug || null;
      this.queryParam.keyword = this.$route.params.keyword || null;
      this.queryParam.page = Number(this.$route.query.page) || 1;
      this.queryParam.sortBy = this.$route.query.sortBy || "popular";
      this.queryParam.minPrice = this.$route.query.minPrice || null;
      this.queryParam.maxPrice = this.$route.query.maxPrice || null;
      this.queryParam.attributeValues = this.$route.query.attributeValues
        ? String(this.$route.query.attributeValues).split(",").filter(Boolean)
        : [];
      this.queryParam.brandIds = this.$route.query.brandIds
        ? String(this.$route.query.brandIds).split(",").filter(Boolean)
        : [];
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
    clearAllFilters() {
      this.queryParam.brandIds = [];
      this.queryParam.attributeValues = [];
      this.queryParam.minPrice = null;
      this.queryParam.maxPrice = null;
      this.$router.push({ query: {} }).catch(() => {});
      this.getList({});
    },
    clearCategories() {
      this.$router.push({ name: "ShopProducts", params: { slug: this.$route.params.slug } }).catch(() => {});
    },
    clearPrice() {
      this.queryParam.minPrice = null;
      this.queryParam.maxPrice = null;
      this.getList({});
    },
    clearBrands() {
      this.queryParam.brandIds = [];
      this.$router.push({ query: { ...this.$route.query, brandIds: [] } }).catch(() => {});
      this.getList({});
    },
    clearAttribute(attribute) {
      attribute.values.data.forEach((value) => {
        const index = this.queryParam.attributeValues.findIndex((item) => String(item) === String(value.id));
        if (index > -1) {
          this.queryParam.attributeValues.splice(index, 1);
        }
      });
      this.$router.push({ query: { ...this.$route.query, attributeValues: this.queryParam.attributeValues } }).catch(() => {});
      this.getList({});
    },
    pageSwitch(pageNumber) {
      this.queryParam.page = pageNumber;
      this.$router.push({ query: { ...this.$route.query, page: pageNumber } }).catch(() => {});
      this.getList({ page: pageNumber });
    },
    sortUpdate(sort) {
      this.queryParam.sortBy = sort;
      this.$router.push({ query: { ...this.$route.query, sortBy: this.queryParam.sortBy } }).catch(() => {});
      this.getList({ sortBy: sort });
    },
    brandChange(id) {
      const normalizedId = String(id);
      const index = this.queryParam.brandIds.findIndex((value) => String(value) === normalizedId);
      if (index > -1) {
        this.queryParam.brandIds.splice(index, 1);
      } else {
        this.queryParam.brandIds.push(id);
      }

      this.$router.push({ query: { ...this.$route.query, brandIds: this.queryParam.brandIds } }).catch(() => {});
      this.getList({});
    },
    attributeValueChange(id) {
      const normalizedId = String(id);
      const index = this.queryParam.attributeValues.findIndex((value) => String(value) === normalizedId);
      if (index > -1) {
        this.queryParam.attributeValues.splice(index, 1);
      } else {
        this.queryParam.attributeValues.push(id);
      }

      this.$router.push({ query: { ...this.$route.query, attributeValues: this.queryParam.attributeValues } }).catch(() => {});
      this.getList({});
    },
    filterByPriceRange() {
      const priceRange = {
        minPrice: this.queryParam.minPrice,
        maxPrice: this.queryParam.maxPrice,
      };

      this.$router.push({ query: { ...this.$route.query, ...priceRange } }).catch(() => {});
      this.getList({});
    },
    toggleFilterDrawer(status) {
      this.filterPanelOpen = status;
    },
    async getList(obj) {
      this.loading = true;
      const params = { ...this.queryParam, ...obj };

      let url = `shop/${this.$route.params.slug}/products?`;
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
        this.products = res.data.products.data;
        this.attributes = res.data.attributes.data;
        this.allBrands = res.data.allBrands.data;
        this.rootCategories = res.data.rootCategories.data;
        this.parentCategory = res.data.parentCategory || {};
        this.currentCategory = res.data.currentCategory || {};
        this.childCategories = res.data.childCategories ? res.data.childCategories.data : [];
        this.totalPages = res.data.totalPage;
        this.totalProducts = res.data.total;
        this.queryParam.page = res.data.currentPage;
      }
    },
  },
  created() {
    this.syncStateFromRoute();
  },
  mounted() {
    this.handleViewportChange();
    window.addEventListener("resize", this.handleViewportChange);
    this.getList({
      page: this.queryParam.page,
      categorySlug: this.queryParam.categorySlug,
      brandIds: this.queryParam.brandIds,
      attributeValues: this.queryParam.attributeValues,
      keyword: this.queryParam.keyword,
      sortBy: this.queryParam.sortBy,
      minPrice: this.queryParam.minPrice,
      maxPrice: this.queryParam.maxPrice,
    });
  },
};
</script>
