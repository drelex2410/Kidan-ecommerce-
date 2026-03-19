<template>
  <div class="today-deal-page">
    <div v-if="!isDesktop && filterPanelOpen" class="filter-overlay" @click="toggleFilterPanel(false)"></div>

    <section class="hero-section">
      <div class="today-deal-shell">
        <p class="hero-eyebrow">Collection</p>
        <h1 class="hero-title">Today's Deal</h1>
        <p class="hero-description">
          Explore our best selling catalogue, Join hundreds of people
          with exquisite taste.
        </p>
      </div>
    </section>

    <section class="listing-section">
      <div class="today-deal-shell">
        <div class="toolbar">
          <div class="toolbar-left">
            <button class="filter-trigger" type="button" @click="toggleFilterPanel(!filterPanelOpen)">
              FILTER
            </button>
            <span class="toolbar-count">{{ totalProducts }} {{ totalProducts === 1 ? 'Item' : 'Items' }}</span>
          </div>

          <div class="toolbar-right">
            <span class="sort-label">SORT BY</span>
            <v-select
              v-model="queryParam.sortBy"
              :items="sortingOptions"
              item-title="name"
              item-value="value"
              variant="outlined"
              density="compact"
              hide-details
              class="sort-select"
              @update:modelValue="sortUpdate"
            />
          </div>
        </div>

        <div class="collection-layout">
          <aside class="filters-panel" :class="{ open: filterPanelOpen, collapsed: isDesktop && !filterPanelOpen }">
            <div class="filters-header">
              <div>
                <p class="filters-label">Filters</p>
                <h2 class="filters-title">Refine Today&apos;s Deal</h2>
              </div>
              <button class="clear-link" type="button" @click="clearAllFilters">Clear Filter</button>
            </div>

            <button v-if="!isDesktop" class="filters-close" type="button" @click="toggleFilterPanel(false)">
              Close
            </button>

            <div class="filter-sections">
              <section v-if="filters.categories.length" class="filter-section">
                <h3 class="section-title">Categories</h3>
                <button class="section-clear" type="button" @click="clearCategories">Unselect All</button>

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
                <h3 class="section-title">{{ section.title }}</h3>
                <button class="section-clear" type="button" @click="clearAttributeSection(section.values)">Unselect All</button>

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
                <h3 class="section-title">Price</h3>
                <button class="section-clear" type="button" @click="clearPrice">Unselect All</button>

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
            </div>
          </aside>

          <div class="products-panel">
            <div v-if="loading" class="state-card">Loading Today&apos;s Deal products...</div>
            <div v-else-if="products.length === 0" class="state-card">No Today&apos;s Deal products are available right now.</div>
            <v-row v-else class="product-grid">
              <v-col
                v-for="product in products"
                :key="product.id"
                cols="6"
                sm="6"
                md="4"
                lg="3"
              >
                <product-box :product-details="product" :is-loading="loading" />
              </v-col>
            </v-row>

            <div v-if="!loading && totalPages > 1" class="pagination-section">
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
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { useHead } from "@unhead/vue";

export default {
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

<style scoped>
.today-deal-page {
  background: #f5f1e8;
  min-height: 100vh;
}

.today-deal-shell {
  width: min(90%, 1600px);
  margin: 0 auto;
}

.hero-section {
  padding: 4.5rem 0 2.5rem;
  border-bottom: 1px solid rgba(42, 34, 26, 0.12);
}

.hero-eyebrow {
  margin: 0 0 0.5rem;
  text-align: center;
  font-size: 0.85rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #8a7f72;
}

.hero-title {
  margin: 0;
  text-align: center;
  font-size: clamp(2.4rem, 4vw, 4rem);
  line-height: 1.05;
  color: #17120d;
}

.hero-description {
  max-width: 36rem;
  margin: 1rem auto 0;
  text-align: center;
  font-size: 1rem;
  line-height: 1.7;
  color: #6e6459;
}

.listing-section {
  padding: 2rem 0 4rem;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.75rem;
}

.toolbar-left,
.toolbar-right {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.filter-trigger {
  border: 1px solid rgba(23, 18, 13, 0.14);
  background: #fbf8f1;
  color: #17120d;
  padding: 0.9rem 1.15rem;
  font-size: 0.8rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
}

.toolbar-count,
.sort-label {
  color: #7b7166;
  font-size: 0.95rem;
}

.sort-label {
  letter-spacing: 0.08em;
}

.sort-select {
  min-width: 190px;
}

.collection-layout {
  display: flex;
  align-items: flex-start;
  gap: 2rem;
}

.filters-panel {
  width: 320px;
  flex: 0 0 320px;
  background: #efebe3;
  border: 1px solid rgba(23, 18, 13, 0.08);
  transition: width 0.25s ease, opacity 0.25s ease, transform 0.25s ease;
}

.filters-panel.collapsed {
  width: 0;
  flex-basis: 0;
  opacity: 0;
  border-width: 0;
  overflow: hidden;
}

.filters-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.5rem 1.5rem 1rem;
}

.filters-label {
  margin: 0;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8a7f72;
}

.filters-title {
  margin: 0.4rem 0 0;
  font-size: 1.25rem;
  color: #17120d;
}

.clear-link,
.section-clear {
  border: 0;
  background: none;
  color: #9f1f1f;
  text-decoration: underline;
  cursor: pointer;
  padding: 0;
  font-size: 0.95rem;
}

.filters-close {
  display: none;
}

.filter-sections {
  padding: 0 1.5rem 1.5rem;
}

.filter-section {
  padding: 1.5rem 0;
  border-top: 1px solid rgba(23, 18, 13, 0.1);
}

.filter-section:first-child {
  border-top: 0;
  padding-top: 1rem;
}

.section-title {
  margin: 0;
  font-size: 1.6rem;
  color: #17120d;
}

.section-clear {
  margin-top: 0.45rem;
  display: inline-block;
}

.filter-options {
  margin-top: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.filter-option {
  border: 0;
  background: none;
  padding: 0;
  display: flex;
  align-items: center;
  gap: 0.85rem;
  text-align: left;
  cursor: pointer;
  color: #17120d;
}

.option-indicator,
.color-swatch {
  width: 24px;
  height: 24px;
  border-radius: 999px;
  border: 1px solid #bcb3a8;
  flex: 0 0 24px;
  position: relative;
}

.filter-option.selected .option-indicator::after {
  content: "";
  position: absolute;
  inset: 5px;
  border-radius: 999px;
  background: #050505;
}

.filter-option.selected .color-swatch {
  box-shadow: 0 0 0 3px #050505 inset;
}

.option-text {
  font-size: 1.08rem;
  line-height: 1.35;
}

.price-field-group + .price-field-group {
  margin-top: 1rem;
}

.price-label {
  display: block;
  font-size: 0.9rem;
  color: #6e6459;
  margin-bottom: 0.4rem;
}

.price-input {
  width: 100%;
  border: 1px solid rgba(23, 18, 13, 0.16);
  background: #fbf8f1;
  padding: 0.9rem 1rem;
  font-size: 0.95rem;
  color: #17120d;
}

.apply-price {
  margin-top: 1rem;
  border: 0;
  background: #17120d;
  color: #fbf8f1;
  padding: 0.85rem 1rem;
  width: 100%;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.8rem;
}

.price-hint {
  margin: 0.85rem 0 0;
  color: #7b7166;
  font-size: 0.9rem;
  line-height: 1.5;
}

.products-panel {
  flex: 1;
  min-width: 0;
}

.product-grid {
  margin: -0.6rem;
}

.product-grid > .v-col {
  padding: 0.6rem;
}

.state-card {
  min-height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  background: rgba(255, 255, 255, 0.58);
  border: 1px solid rgba(23, 18, 13, 0.08);
  color: #6e6459;
  font-size: 1.05rem;
  padding: 2rem;
}

.pagination-section {
  display: flex;
  justify-content: center;
  padding-top: 2.5rem;
}

.filter-overlay {
  position: fixed;
  inset: 0;
  background: rgba(23, 18, 13, 0.35);
  z-index: 35;
}

@media (max-width: 1099px) {
  .today-deal-shell {
    width: min(92%, 1600px);
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-left,
  .toolbar-right {
    justify-content: space-between;
  }

  .sort-select {
    min-width: 0;
    flex: 1;
  }

  .filters-panel {
    position: fixed;
    inset: 0 auto 0 0;
    height: 100vh;
    max-width: 360px;
    width: min(86vw, 360px);
    z-index: 40;
    transform: translateX(-100%);
    overflow-y: auto;
  }

  .filters-panel.open {
    transform: translateX(0);
  }

  .filters-panel.collapsed {
    width: min(86vw, 360px);
    flex-basis: auto;
    opacity: 1;
    border-width: 1px;
  }

  .filters-close {
    display: inline-flex;
    margin: 0 1.5rem 0.5rem;
    border: 0;
    background: none;
    color: #17120d;
    font-size: 0.95rem;
    text-decoration: underline;
    cursor: pointer;
    padding: 0;
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding: 3.5rem 0 2rem;
  }

  .section-title {
    font-size: 1.35rem;
  }

  .option-text {
    font-size: 1rem;
  }
}

:deep(.v-field--variant-outlined .v-field__outline) {
  border-color: rgba(23, 18, 13, 0.16);
}

:deep(.v-field__input),
:deep(.v-select__selection-text) {
  font-size: 0.95rem;
  color: #17120d;
}

:deep(.v-pagination__item) {
  background: #fbf8f1;
  border: 1px solid rgba(23, 18, 13, 0.14);
  color: #17120d;
}

:deep(.v-pagination__item--is-active) {
  background: #17120d;
  color: #fbf8f1;
}
</style>
