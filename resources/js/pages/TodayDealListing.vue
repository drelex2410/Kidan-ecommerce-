<template>
  <product-collection-page
    hero-eyebrow="Collection"
    hero-title="Today's Deal"
    hero-description="Explore our best selling catalogue, Join hundreds of people with exquisite taste."
    filters-title="Refine Today&apos;s Deal"
    :sorting-options="sortingOptions"
    :sort-by="queryParam.sortBy"
    :products="products"
    :loading="loading"
    loading-message="Loading Today's Deal products..."
    empty-message="No Today's Deal products are available right now."
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
      <section v-if="filters.categories.length" class="filter-section">
        <div class="filter-section-header">
          <h3 class="section-title">Categories</h3>
          <button class="section-clear" type="button" @click="clearCategories">Unselect All</button>
        </div>

        <div class="filter-options">
          <button
            v-for="category in filters.categories"
            :key="category.id"
            type="button"
            class="filter-option"
            :class="{ selected: isCategorySelected(category.id) }"
            @click="toggleCategory(category.id)"
          >
            <span class="option-indicator"></span>
            <span class="option-text">{{ category.name }}</span>
          </button>
        </div>
      </section>

      <section
        v-for="section in attributeSections"
        :key="section.key"
        class="filter-section"
      >
        <div class="filter-section-header">
          <h3 class="section-title">{{ section.title }}</h3>
          <button class="section-clear" type="button" @click="clearAttributeSection(section.values)">Unselect All</button>
        </div>

        <div class="filter-options">
          <button
            v-for="value in section.values"
            :key="value.id"
            type="button"
            class="filter-option"
            :class="[{ selected: isAttributeSelected(value.id) }, section.kind === 'color' ? 'color-option' : '']"
            @click="toggleAttributeValue(value.id)"
          >
            <span
              v-if="section.kind === 'color'"
              class="color-swatch"
              :style="resolveColorSwatchStyle(value.name)"
            ></span>
            <span v-else class="option-indicator"></span>
            <span class="option-text">{{ value.name }}</span>
          </button>
        </div>
      </section>

      <section class="filter-section">
        <div class="filter-section-header">
          <h3 class="section-title">Price</h3>
          <button class="section-clear" type="button" @click="clearPrice">Unselect All</button>
        </div>

        <div class="price-field-group">
          <label class="price-label" for="today-deal-min-price">Minimum Price</label>
          <input
            id="today-deal-min-price"
            v-model="draftPrice.min"
            class="price-input"
            type="number"
            min="0"
            placeholder="Under NGN 20,000"
            @keyup.enter="applyPrice"
          />
        </div>

        <div class="price-field-group">
          <label class="price-label" for="today-deal-max-price">Maximum Price</label>
          <input
            id="today-deal-max-price"
            v-model="draftPrice.max"
            class="price-input"
            type="number"
            min="0"
            placeholder="NGN 20,000 - 50,000"
            @keyup.enter="applyPrice"
          />
        </div>

        <button class="apply-price" type="button" @click="applyPrice">Apply Price</button>

        <p v-if="filters.priceRange.max > 0" class="price-hint">
          Available range: {{ formatCurrency(filters.priceRange.min) }} - {{ formatCurrency(filters.priceRange.max) }}
        </p>
      </section>
    </template>
  </product-collection-page>
</template>

<script>
import { useHead } from "@unhead/vue";
import ProductCollectionPage from "../components/product/ProductCollectionPage.vue";

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
    products: [],
    filters: {
      categories: [],
      colors: [],
      sizes: [],
      materials: [],
      attributes: [],
      priceRange: {
        min: 0,
        max: 0,
      },
    },
    queryParam: {
      page: 1,
      categoryIds: [],
      attributeValues: [],
      sortBy: "popular",
      minPrice: null,
      maxPrice: null,
    },
    draftPrice: {
      min: null,
      max: null,
    },
  }),
  computed: {
    sortingOptions() {
      return [
        { name: "Most Popular", value: "popular" },
        { name: "Latest First", value: "latest" },
        { name: "Oldest First", value: "oldest" },
        { name: "Higher Price First", value: "highest_price" },
        { name: "Lower Price First", value: "lowest_price" },
      ];
    },
    attributeSections() {
      const sections = [];

      if (this.filters.colors.length) {
        sections.push({ key: "colors", title: "Colors", values: this.filters.colors, kind: "color" });
      }

      if (this.filters.sizes.length) {
        sections.push({ key: "sizes", title: "Clothing Size", values: this.filters.sizes, kind: "default" });
      }

      if (this.filters.materials.length) {
        sections.push({ key: "materials", title: "Material", values: this.filters.materials, kind: "default" });
      }

      this.filters.attributes.forEach((attribute) => {
        sections.push({
          key: `attribute-${attribute.id}`,
          title: attribute.name,
          values: attribute.values,
          kind: "default",
        });
      });

      return sections;
    },
  },
  watch: {
    "$route.query": {
      immediate: true,
      handler() {
        this.syncStateFromRoute();
        this.getList();
      },
    },
    filterPanelOpen(isOpen) {
      document.body.classList.toggle("overflow-hidden", !this.isDesktop && isOpen);
    },
  },
  mounted() {
    this.handleViewportChange();
    window.addEventListener("resize", this.handleViewportChange);
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleViewportChange);
    document.body.classList.remove("overflow-hidden");
  },
  methods: {
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
    syncStateFromRoute() {
      this.queryParam.page = Number(this.$route.query.page) || 1;
      this.queryParam.sortBy = this.$route.query.sortBy || "popular";
      this.queryParam.categoryIds = this.parseQueryList(this.$route.query.categoryIds);
      this.queryParam.attributeValues = this.parseQueryList(this.$route.query.attributeValues);
      this.queryParam.minPrice = this.$route.query.minPrice || null;
      this.queryParam.maxPrice = this.$route.query.maxPrice || null;
      this.draftPrice.min = this.queryParam.minPrice;
      this.draftPrice.max = this.queryParam.maxPrice;
    },
    parseQueryList(value) {
      if (!value) {
        return [];
      }

      return String(value)
        .split(",")
        .map((item) => Number(item))
        .filter((item) => Number.isInteger(item) && item > 0);
    },
    serializeQuery(nextState = {}) {
      const state = { ...this.queryParam, ...nextState };
      const query = {};

      if (state.page > 1) {
        query.page = state.page;
      }

      if (state.sortBy && state.sortBy !== "popular") {
        query.sortBy = state.sortBy;
      }

      if (state.categoryIds.length) {
        query.categoryIds = state.categoryIds.join(",");
      }

      if (state.attributeValues.length) {
        query.attributeValues = state.attributeValues.join(",");
      }

      if (state.minPrice) {
        query.minPrice = state.minPrice;
      }

      if (state.maxPrice) {
        query.maxPrice = state.maxPrice;
      }

      return query;
    },
    pushRoute(nextState = {}) {
      this.$router.push({
        name: "TodayDeal",
        query: this.serializeQuery(nextState),
      }).catch(() => {});
    },
    toggleFilterPanel(status) {
      this.filterPanelOpen = status;
    },
    toggleCategory(categoryId) {
      const exists = this.queryParam.categoryIds.includes(categoryId);
      const categoryIds = exists
        ? this.queryParam.categoryIds.filter((id) => id !== categoryId)
        : [...this.queryParam.categoryIds, categoryId];

      this.pushRoute({ page: 1, categoryIds });
    },
    toggleAttributeValue(attributeValueId) {
      const exists = this.queryParam.attributeValues.includes(attributeValueId);
      const attributeValues = exists
        ? this.queryParam.attributeValues.filter((id) => id !== attributeValueId)
        : [...this.queryParam.attributeValues, attributeValueId];

      this.pushRoute({ page: 1, attributeValues });
    },
    clearCategories() {
      this.pushRoute({ page: 1, categoryIds: [] });
    },
    clearAttributeSection(values) {
      const valueIds = values.map((value) => value.id);
      const attributeValues = this.queryParam.attributeValues.filter((id) => !valueIds.includes(id));
      this.pushRoute({ page: 1, attributeValues });
    },
    clearPrice() {
      this.draftPrice.min = null;
      this.draftPrice.max = null;
      this.pushRoute({ page: 1, minPrice: null, maxPrice: null });
    },
    clearAllFilters() {
      this.draftPrice.min = null;
      this.draftPrice.max = null;
      this.pushRoute({
        page: 1,
        categoryIds: [],
        attributeValues: [],
        minPrice: null,
        maxPrice: null,
      });
    },
    applyPrice() {
      this.pushRoute({
        page: 1,
        minPrice: this.draftPrice.min || null,
        maxPrice: this.draftPrice.max || null,
      });
    },
    sortUpdate(sortBy) {
      this.pushRoute({ page: 1, sortBy });
    },
    pageSwitch(page) {
      this.pushRoute({ page });
    },
    isCategorySelected(categoryId) {
      return this.queryParam.categoryIds.includes(categoryId);
    },
    isAttributeSelected(attributeValueId) {
      return this.queryParam.attributeValues.includes(attributeValueId);
    },
    resolveColorSwatchStyle(colorName) {
      const normalized = String(colorName || "").trim().toLowerCase();
      const colorMap = {
        wine: "#5b0f22",
        burgundy: "#6d102c",
        nude: "#c7a27c",
        cream: "#efe7d3",
        grey: "#7c7c7c",
      };
      const background = colorMap[normalized] || normalized || "#111111";

      return {
        backgroundColor: background,
        borderColor: normalized === "white" ? "#bfb8aa" : background,
      };
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat("en-NG", {
        style: "currency",
        currency: "NGN",
        maximumFractionDigits: 0,
      }).format(Number(amount || 0));
    },
    normalizeFilters(filters = {}) {
      return {
        categories: filters.categories || [],
        colors: filters.colors || [],
        sizes: filters.sizes || [],
        materials: filters.materials || [],
        attributes: filters.attributes || [],
        priceRange: filters.priceRange || { min: 0, max: 0 },
      };
    },
    updateHead() {
      useHead({
        title: "Today's Deal",
        meta: [
          {
            name: "description",
            content: "Explore our curated Today's Deal product collection.",
          },
        ],
      });
    },
    async getList() {
      this.loading = true;
      this.updateHead();

      let url = "product/todays-deal?";
      url += `page=${this.queryParam.page}`;
      url += this.queryParam.sortBy ? `&sort_by=${this.queryParam.sortBy}` : "";
      url += this.queryParam.categoryIds.length ? `&category_ids=${this.queryParam.categoryIds.join(",")}` : "";
      url += this.queryParam.attributeValues.length ? `&attribute_values=${this.queryParam.attributeValues.join(",")}` : "";
      url += this.queryParam.minPrice ? `&min_price=${this.queryParam.minPrice}` : "";
      url += this.queryParam.maxPrice ? `&max_price=${this.queryParam.maxPrice}` : "";

      try {
        const res = await this.call_api("get", url);

        if (res.data.success) {
          this.products = res.data.products.data;
          this.totalProducts = res.data.total;
          this.totalPages = res.data.totalPage;
          this.filters = this.normalizeFilters(res.data.filters);
        } else {
          this.products = [];
          this.totalProducts = 0;
          this.totalPages = 1;
          this.filters = this.normalizeFilters();
        }
      } catch (error) {
        this.products = [];
        this.totalProducts = 0;
        this.totalPages = 1;
        this.filters = this.normalizeFilters();
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
