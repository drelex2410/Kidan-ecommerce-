<template>
  <div
    class="category-bar"
    :class="{ 'is-visible': visible }"
    v-if="combinedItems.length"
  >
    <div class="category-bar-container d-flex align-center">
      <template v-for="(item, i) in visibleItems" :key="item.kind === 'menu' ? item.id : item.id || i">
        <div
          v-if="item.kind === 'menu'"
          class="category-wrapper"
        >
          <router-link
            :to="item.link || '/'"
            class="category-link"
            @click="requestClose"
          >
            {{ item.label }}
          </router-link>
        </div>

        <div
          v-else
          class="category-wrapper"
          @mouseenter="activateCategory(item)"
        >
          <router-link
            :to="{ name: 'Category', params: { categorySlug: item.slug } }"
            class="category-link"
            :class="{ 'is-active': activeCategory && activeCategory.id === item.id }"
            @click="requestClose"
          >
            {{ item.name }}
          </router-link>
        </div>
      </template>

      <button
        v-if="hiddenItems.length > 0"
        @click="openModal"
        @mouseenter="startHoverTimer"
        @mouseleave="clearHoverTimer"
        class="more-button"
        aria-label="More categories"
      >
        More
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </button>
    </div>

    <transition name="dropdown-fade">
      <div
        v-if="shouldShowMegaMenu"
        class="mega-menu"
        @mouseenter="clearHideTimer"
        @mouseleave="startHideTimer"
      >
        <div class="mega-menu-container">
          <div class="mega-menu-panel" :class="{ 'is-single-panel': !hasSubSubcategoryPanel }">
            <div class="subcategory-list">
              <router-link
                v-for="(subcategory, index) in activeCategoryChildren"
                :key="subcategory.id || index"
                :to="{ name: 'Category', params: { categorySlug: subcategory.slug } }"
                class="subcategory-item"
                :class="{
                  'is-active': activeSubcategory && activeSubcategory.id === subcategory.id,
                  'has-children': hasChildren(subcategory),
                }"
                @mouseenter="activateSubcategory(subcategory)"
                @click="requestClose"
              >
                <span class="subcategory-name">{{ subcategory.name }}</span>
                <i v-if="hasChildren(subcategory)" class="las la-angle-right subcategory-caret"></i>
              </router-link>

              <router-link
                v-if="activeCategory && activeCategory.slug"
                :to="{ name: 'Category', params: { categorySlug: activeCategory.slug } }"
                class="subcategory-item view-all-item"
                @mouseenter="clearActiveSubcategory"
                @click="requestClose"
              >
                <span class="subcategory-name">View All {{ activeCategory.name }}</span>
              </router-link>
            </div>

            <transition name="submenu-fade">
              <div v-if="hasSubSubcategoryPanel" class="sub-subcategory-panel">
                <div class="sub-subcategory-header">
                  <router-link
                  :to="{ name: 'Category', params: { categorySlug: activeSubcategory.slug } }"
                  class="sub-subcategory-parent"
                  @click="requestClose"
                >
                    {{ activeSubcategory.name }}
                  </router-link>
                </div>

                <div class="sub-subcategory-list">
                  <router-link
                    v-for="(child, index) in activeSubcategoryChildren"
                    :key="child.id || index"
                    :to="{ name: 'Category', params: { categorySlug: child.slug } }"
                    class="sub-subcategory-item"
                    @click="requestClose"
                  >
                    {{ child.name }}
                  </router-link>
                </div>
              </div>
            </transition>
          </div>
        </div>
      </div>
    </transition>

    <div v-if="showMoreModal" class="modal-overlay" @click.self="closeModal" @mouseleave="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>All Categories</h3>
          <button @click="closeModal" class="close-button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
        </div>
        <div class="modal-grid">
          <template v-for="(item, i) in hiddenItems" :key="item.kind === 'menu' ? item.id : item.id || `hidden-${i}`">
            <router-link
              v-if="item.kind === 'menu'"
              :to="item.link || '/'"
              class="modal-category-link"
              @click="requestClose"
            >
              {{ item.label }}
            </router-link>

            <router-link
              v-else
              :to="{ name: 'Category', params: { categorySlug: item.slug } }"
              class="modal-category-link"
              @click="requestClose"
            >
              {{ item.name }}
            </router-link>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  emits: ["menu-state-change", "request-close"],

  props: {
    categories: { type: Array, required: true },
    menuItems: { type: Object, default: () => ({}) },
    visible: { type: Boolean, default: false },
  },

  data() {
    return {
      showMoreModal: false,
      hoverTimeout: null,
      hideTimeout: null,
      activeCategory: null,
      activeSubcategory: null,
      visibleCount: 11,
    };
  },

  computed: {
    normalizedMenuItems() {
      return Object.entries(this.menuItems || {}).map(([label, link], index) => ({
        kind: "menu",
        id: `menu-${index}-${label}`,
        label,
        link,
      }));
    },

    normalizedCategories() {
      return this.categories.map((category) => ({
        ...category,
        kind: "category",
      }));
    },

    combinedItems() {
      return [...this.normalizedMenuItems, ...this.normalizedCategories];
    },

    visibleItems() {
      return this.combinedItems.slice(0, this.visibleCount);
    },

    hiddenItems() {
      return this.combinedItems.slice(this.visibleCount);
    },

    activeCategoryChildren() {
      return this.normalizeChildren(this.activeCategory);
    },

    activeSubcategoryChildren() {
      return this.normalizeChildren(this.activeSubcategory);
    },

    shouldShowMegaMenu() {
      return !!this.activeCategory && this.activeCategoryChildren.length > 0;
    },

    hasSubSubcategoryPanel() {
      return !!this.activeSubcategory && this.activeSubcategoryChildren.length > 0;
    },
  },

  methods: {
    updateVisibleCount() {
      const width = window.innerWidth;

      if (width <= 480) {
        this.visibleCount = 4;
      } else if (width <= 768) {
        this.visibleCount = 6;
      } else if (width <= 1024) {
        this.visibleCount = 8;
      } else {
        this.visibleCount = 12;
      }
    },

    normalizeChildren(category) {
      const children = Array.isArray(category?.children)
        ? category.children
        : Array.isArray(category?.children?.data)
          ? category.children.data
          : [];

      return children.filter((child) => !this.isViewAllItem(child));
    },

    hasChildren(category) {
      return this.normalizeChildren(category).length > 0;
    },

    isViewAllItem(category) {
      const name = (category?.name || "").toLowerCase();
      return name.includes("view all");
    },

    activateCategory(category) {
      if (!this.visible) {
        return;
      }

      this.clearHideTimer();
      this.activeCategory = category;
      this.activeSubcategory = null;
      this.emitMenuState();
    },

    activateSubcategory(subcategory) {
      if (!this.visible) {
        return;
      }

      this.clearHideTimer();
      this.activeSubcategory = this.hasChildren(subcategory) ? subcategory : null;
      this.emitMenuState();
    },

    clearActiveSubcategory() {
      this.activeSubcategory = null;
      this.emitMenuState();
    },

    startHideTimer() {
      this.clearHideTimer();
      this.hideTimeout = window.setTimeout(() => {
        this.closeAllMenus();
      }, 220);
    },

    clearHideTimer() {
      if (this.hideTimeout) {
        window.clearTimeout(this.hideTimeout);
        this.hideTimeout = null;
      }
    },

    closeAllMenus() {
      this.clearHideTimer();
      this.clearHoverTimer();
      this.activeCategory = null;
      this.activeSubcategory = null;
      this.showMoreModal = false;
      this.emitMenuState();
    },

    startHoverTimer() {
      this.hoverTimeout = window.setTimeout(() => {
        this.showMoreModal = true;
        this.emitMenuState();
      }, 300);
    },

    clearHoverTimer() {
      if (this.hoverTimeout) {
        window.clearTimeout(this.hoverTimeout);
        this.hoverTimeout = null;
      }
    },

    openModal() {
      if (!this.visible) {
        return;
      }

      this.clearHoverTimer();
      this.showMoreModal = true;
      this.emitMenuState();
    },

    closeModal() {
      this.clearHoverTimer();
      this.showMoreModal = false;
      this.emitMenuState();
    },

    requestClose() {
      this.closeAllMenus();
      this.$emit("request-close");
    },

    handleKeydown(event) {
      if (event.key === "Escape") {
        if (this.showMoreModal) {
          this.closeModal();
        }
        if (this.activeCategory) {
          this.closeAllMenus();
        }
      }
    },

    handleClickOutside(event) {
      if (this.showMoreModal && !event.target.closest(".modal-content") && !event.target.closest(".more-button")) {
        this.closeModal();
      }
    },

    emitMenuState() {
      this.$emit("menu-state-change", this.shouldShowMegaMenu || this.showMoreModal);
    },
  },

  watch: {
    visible(newValue) {
      if (!newValue) {
        this.closeAllMenus();
      }
    },

    showMoreModal(newValue) {
      if (newValue) {
        document.addEventListener("click", this.handleClickOutside);
        document.body.classList.add("modal-open");
      } else {
        document.removeEventListener("click", this.handleClickOutside);
        document.body.classList.remove("modal-open");
      }
    },
  },

  mounted() {
    this.updateVisibleCount();
    window.addEventListener("resize", this.updateVisibleCount);
    document.addEventListener("keydown", this.handleKeydown);
  },

  beforeUnmount() {
    window.removeEventListener("resize", this.updateVisibleCount);
    document.removeEventListener("keydown", this.handleKeydown);
    document.removeEventListener("click", this.handleClickOutside);
    document.body.classList.remove("modal-open");
    this.clearHoverTimer();
    this.clearHideTimer();
    this.$emit("menu-state-change", false);
  },
};
</script>

<style scoped>
.category-bar {
  --menu-surface: #FFFBF3;
  --menu-hover: rgba(128, 0, 0, 0.08);
  --menu-border: rgba(0, 0, 0, 0.08);
  --menu-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
  border-bottom: 1px solid var(--menu-border);
  background: #FFFBF3;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transform: translateY(-8px);
  transition:
    opacity 0.18s ease,
    transform 0.18s ease,
    visibility 0s linear 0.18s;
  z-index: 1001;
}

.category-bar.is-visible {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
  transform: translateY(0);
  transition:
    opacity 0.18s ease,
    transform 0.18s ease,
    visibility 0s linear 0s;
}

.category-bar-container {
  max-width: none !important;
  width: 100%;
  margin: 0 auto;
  padding: 0 3rem;
  gap: 2.5rem;
  min-height: 60px;
  box-sizing: border-box;
  justify-content: center;
  flex-wrap: nowrap;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.category-bar-container::-webkit-scrollbar {
  display: none;
}

.category-wrapper {
  position: relative;
  flex-shrink: 0;
}

.category-link {
  color: #1a1a1a;
  text-decoration: none;
  font-size: 0.875rem;
  letter-spacing: 0.5px;
  transition: color 0.24s ease;
  position: relative;
  white-space: nowrap;
  display: block;
}

.category-link::after {
  content: "";
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 0;
  height: 2px;
  background: #800000;
  transition: width 0.24s ease;
}

.category-link:hover,
.category-link.is-active {
  color: #800000;
}

.category-link:hover::after,
.category-link.is-active::after {
  width: 100%;
}

.mega-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--menu-surface);
  box-shadow: var(--menu-shadow);
  z-index: 999;
  border-top: 1px solid var(--menu-border);
}

.mega-menu-container {
  width: 100%;
  margin: 0 auto;
  padding: 0 3rem 1.25rem;
  box-sizing: border-box;
}

.mega-menu-panel {
  display: grid;
  grid-template-columns: minmax(240px, 300px) minmax(260px, 1fr);
  background: var(--menu-surface);
  border: 1px solid var(--menu-border);
  border-radius: 18px;
  overflow: hidden;
}

.mega-menu-panel.is-single-panel {
  grid-template-columns: minmax(240px, 320px);
  width: fit-content;
  min-width: min(100%, 320px);
}

.subcategory-list,
.sub-subcategory-panel {
  background: var(--menu-surface);
}

.subcategory-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 1rem;
}

.sub-subcategory-panel {
  border-left: 1px solid var(--menu-border);
  padding: 1rem 1.1rem;
  min-width: 0;
}

.sub-subcategory-header {
  padding-bottom: 0.75rem;
  margin-bottom: 0.75rem;
  border-bottom: 1px solid var(--menu-border);
}

.sub-subcategory-parent {
  text-decoration: none;
  color: #1a1a1a;
  font-size: 0.95rem;
  font-weight: 600;
  transition: color 0.24s ease;
}

.sub-subcategory-parent:hover {
  color: #800000;
}

.sub-subcategory-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.5rem;
}

.subcategory-item,
.sub-subcategory-item {
  text-decoration: none;
  color: #1a1a1a;
  transition:
    color 0.24s ease,
    border-color 0.24s ease,
    background-color 0.24s ease,
    transform 0.24s ease;
}

.subcategory-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  width: 100%;
  padding: 12px 14px;
  border: 1px solid transparent;
  border-radius: 12px;
  background: transparent;
}

.subcategory-item:hover,
.subcategory-item.is-active {
  color: #800000;
  background: var(--menu-hover);
  border-color: rgba(128, 0, 0, 0.18);
  transform: translateX(4px);
}

.subcategory-item.has-children .subcategory-caret {
  font-size: 1rem;
}

.subcategory-name {
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.3;
}

.view-all-item {
  margin-top: 0.25rem;
  font-weight: 600;
}

.sub-subcategory-item {
  display: flex;
  align-items: center;
  min-height: 48px;
  padding: 12px 14px;
  border: 1px solid transparent;
  border-radius: 12px;
  background: transparent;
  font-size: 0.875rem;
  line-height: 1.35;
}

.sub-subcategory-item:hover {
  color: #800000;
  background: var(--menu-hover);
  border-color: rgba(128, 0, 0, 0.18);
}

.more-button {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  color: #1a1a1a;
  font-size: 0.875rem;
  letter-spacing: 0.5px;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 6px;
  transition: color 0.24s ease, background-color 0.24s ease;
  flex-shrink: 0;
  white-space: nowrap;
}

.more-button:hover {
  color: #800000;
  background-color: rgba(128, 0, 0, 0.06);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  z-index: 1000;
  padding-top: 100px;
}

.modal-content {
  background: #FFFBF3;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 800px;
  width: 90%;
  max-height: 70vh;
  overflow-y: auto;
  animation: modal-slide-down 0.3s ease;
}

@keyframes modal-slide-down {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active,
.submenu-fade-enter-active,
.submenu-fade-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to,
.submenu-fade-enter-from,
.submenu-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1a1a1a;
}

.close-button {
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.3s ease, color 0.24s ease;
}

.close-button:hover {
  background-color: rgba(128, 0, 0, 0.06);
  color: #800000;
}

.modal-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
  padding: 1.5rem;
}

.modal-category-link {
  color: #1a1a1a;
  text-decoration: none;
  padding: 12px 16px;
  border-radius: 10px;
  transition: all 0.24s ease;
  font-size: 0.875rem;
}

.modal-category-link:hover {
  background-color: rgba(128, 0, 0, 0.06);
  color: #800000;
  transform: translateY(-2px);
}

@media (max-width: 1024px) {
  .category-bar {
    display: none;
  }

  .category-bar-container {
    gap: 2rem;
    padding: 0 2rem;
    justify-content: flex-start;
  }

  .mega-menu-container {
    padding: 0 2rem 1rem;
  }

  .mega-menu-panel {
    grid-template-columns: minmax(220px, 280px) minmax(220px, 1fr);
  }

  .sub-subcategory-list {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}

@media (max-width: 768px) {
  .category-bar-container {
    padding: 0 1rem !important;
    gap: 1.5rem;
  }

  .mega-menu-container {
    padding: 0 1rem 1rem;
  }

  .mega-menu-panel,
  .mega-menu-panel.is-single-panel {
    width: 100%;
    min-width: 0;
    grid-template-columns: 1fr;
  }

  .sub-subcategory-panel {
    border-left: none;
    border-top: 1px solid var(--menu-border);
  }

  .modal-content {
    width: 95%;
    margin-top: 60px;
  }

  .modal-grid,
  .sub-subcategory-list {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
}

@media (max-width: 480px) {
  .category-bar-container {
    padding: 0 0.875rem !important;
    gap: 1rem;
  }

  .category-link,
  .more-button {
    font-size: 0.8125rem;
  }

  .modal-content {
    max-height: 60vh;
    width: 100%;
    border-radius: 0;
    margin-top: 0;
    padding-top: env(safe-area-inset-top);
  }

  .modal-grid,
  .sub-subcategory-list {
    grid-template-columns: repeat(2, 1fr);
  }

  .modal-header {
    padding: 1rem;
    position: sticky;
    top: 0;
    background: #FFFBF3;
    z-index: 1;
  }

  .subcategory-name,
  .sub-subcategory-item {
    font-size: 0.8125rem;
  }
}

@media (max-width: 360px) {
  .category-bar-container {
    gap: 0.75rem;
  }

  .modal-grid,
  .sub-subcategory-list {
    grid-template-columns: 1fr;
  }
}

:deep(body.modal-open) {
  overflow: hidden;
}
</style>
