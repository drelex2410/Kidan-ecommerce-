<template>
  <div class="collection-page">
    <div v-if="!isDesktop && filterPanelOpen" class="filter-overlay" @click="$emit('toggle-filter', false)"></div>

    <section class="hero-section">
      <div class="collection-shell">
        <p class="hero-eyebrow">{{ heroEyebrow }}</p>
        <h1 class="hero-title">{{ heroTitle }}</h1>
        <p v-if="heroDescription" class="hero-description">
          {{ heroDescription }}
        </p>
      </div>
    </section>

    <section class="listing-section">
      <div class="collection-shell">
        <div class="toolbar">
          <div class="toolbar-left">
            <button class="filter-trigger" type="button" @click="$emit('toggle-filter', !filterPanelOpen)">
              FILTER
            </button>
            <span class="toolbar-count">{{ totalProducts }} {{ totalProducts === 1 ? "Item" : "Items" }}</span>
          </div>

          <div class="toolbar-right">
            <span class="sort-label">{{ sortLabel }}</span>
            <v-select
              :model-value="sortBy"
              :items="sortingOptions"
              item-title="name"
              item-value="value"
              variant="outlined"
              density="compact"
              hide-details
              class="sort-select"
              @update:modelValue="$emit('sort-change', $event)"
            />
          </div>
        </div>

        <div class="collection-layout">
          <aside class="filters-panel" :class="{ open: filterPanelOpen, collapsed: isDesktop && !filterPanelOpen }">
            <div class="filters-header">
              <div>
                <p class="filters-label">{{ filtersEyebrow }}</p>
                <h2 class="filters-title">{{ filtersTitle }}</h2>
              </div>
              <button class="clear-link" type="button" @click="$emit('clear-filters')">Clear Filter</button>
            </div>

            <button v-if="!isDesktop" class="filters-close" type="button" @click="$emit('toggle-filter', false)">
              Close
            </button>

            <div class="filter-sections">
              <slot name="filters" />
            </div>
          </aside>

          <div class="products-panel">
            <div v-if="loading" class="state-card">{{ loadingMessage }}</div>
            <div v-else-if="products.length === 0" class="state-card">{{ emptyMessage }}</div>
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
                :model-value="page"
                :length="totalPages"
                prev-icon="las la-angle-left"
                next-icon="las la-angle-right"
                :total-visible="7"
                elevation="0"
                @update:modelValue="$emit('page-change', $event)"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  props: {
    heroEyebrow: { type: String, default: "Collection" },
    heroTitle: { type: String, required: true },
    heroDescription: { type: String, default: "" },
    filtersEyebrow: { type: String, default: "Filters" },
    filtersTitle: { type: String, required: true },
    sortLabel: { type: String, default: "SORT BY" },
    sortingOptions: { type: Array, default: () => [] },
    sortBy: { type: String, default: "popular" },
    products: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    loadingMessage: { type: String, default: "Loading products..." },
    emptyMessage: { type: String, default: "No products are available right now." },
    totalProducts: { type: Number, default: 0 },
    totalPages: { type: Number, default: 1 },
    page: { type: Number, default: 1 },
    isDesktop: { type: Boolean, default: false },
    filterPanelOpen: { type: Boolean, default: false },
  },
  emits: ["toggle-filter", "clear-filters", "sort-change", "page-change"],
};
</script>

<style>
.collection-page {
  background: #f5f1e8;
  min-height: 100vh;
}

.collection-shell {
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
  max-width: 42rem;
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
}

.filters-close {
  display: none;
}

.filter-sections {
  display: flex;
  flex-direction: column;
}

.filter-section {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid rgba(23, 18, 13, 0.08);
}

.filter-section:first-child {
  border-top: 1px solid rgba(23, 18, 13, 0.08);
}

.section-title {
  margin: 0;
  font-size: 0.95rem;
  color: #17120d;
}

.filter-section-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-options {
  display: grid;
  gap: 0.7rem;
}

.filter-option,
.filter-option-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border: 0;
  background: transparent;
  padding: 0;
  text-align: left;
  cursor: pointer;
  color: #423a31;
}

.filter-option.selected,
.filter-option-item.active {
  color: #17120d;
  font-weight: 600;
}

.option-indicator {
  width: 0.9rem;
  height: 0.9rem;
  border: 1px solid rgba(23, 18, 13, 0.35);
  border-radius: 50%;
  background: transparent;
  flex-shrink: 0;
}

.filter-option.selected .option-indicator,
.filter-option-item.active .option-indicator {
  background: #17120d;
  border-color: #17120d;
}

.option-text,
.filter-option-text {
  line-height: 1.4;
}

.color-option .color-swatch {
  width: 0.95rem;
  height: 0.95rem;
  border-radius: 50%;
  border: 1px solid rgba(23, 18, 13, 0.12);
  flex-shrink: 0;
}

.price-field-group {
  margin-bottom: 1rem;
}

.price-label {
  display: block;
  margin-bottom: 0.45rem;
  color: #5b5248;
  font-size: 0.88rem;
}

.price-input,
.price-input .v-field {
  background: #fbf8f1;
}

.apply-price {
  border: 0;
  background: #17120d;
  color: #fff;
  padding: 0.8rem 1rem;
  cursor: pointer;
  width: 100%;
}

.price-hint {
  margin: 0.85rem 0 0;
  color: #7b7166;
  font-size: 0.88rem;
}

.products-panel {
  flex: 1;
  min-width: 0;
}

.state-card {
  padding: 3rem 1.5rem;
  background: #fbf8f1;
  border: 1px solid rgba(23, 18, 13, 0.08);
  color: #5b5248;
  text-align: center;
}

.product-grid {
  margin: 0;
}

.product-grid > .v-col {
  padding-top: 0;
}

.pagination-section {
  margin-top: 2rem;
  display: flex;
  justify-content: center;
}

.filter-overlay {
  position: fixed;
  inset: 0;
  background: rgba(23, 18, 13, 0.38);
  z-index: 20;
}

@media (max-width: 1099px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-left,
  .toolbar-right {
    justify-content: space-between;
  }

  .collection-layout {
    display: block;
  }

  .filters-panel {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 30;
    width: min(88vw, 340px);
    max-width: 340px;
    transform: translateX(-100%);
    overflow-y: auto;
  }

  .filters-panel.open {
    transform: translateX(0);
  }

  .filters-panel.collapsed {
    width: min(88vw, 340px);
    flex-basis: auto;
    opacity: 1;
    border-width: 1px;
    overflow: auto;
  }

  .filters-close {
    display: inline-flex;
    align-self: flex-end;
    margin: 0 1.5rem 1rem;
    border: 0;
    background: none;
    color: #17120d;
    cursor: pointer;
  }
}

@media (max-width: 767px) {
  .hero-section {
    padding-top: 3rem;
  }

  .collection-shell {
    width: min(92%, 1600px);
  }

  .sort-select {
    min-width: 0;
    width: 100%;
  }

  .toolbar-right {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
