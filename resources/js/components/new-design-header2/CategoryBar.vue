<template>
  <div class="category-bar" v-if="categories.length && isScrolled">
    <div class="category-bar-container d-flex align-center">
      <!-- Visible categories (responsive count) -->
      <div v-for="(category, i) in visibleCategories" :key="i" class="category-wrapper"
        @mouseenter="showSubcategories(category)" @mouseleave="startHideTimer">
        <router-link :to="{ name: 'Category', params: { categorySlug: category.slug } }" class="category-link">
          {{ category.name }}
        </router-link>
      </div>

      <!-- More button (shown when there are hidden categories) -->
      <button v-if="hiddenCategories.length > 0" @click="openModal" @mouseenter="startHoverTimer"
        @mouseleave="clearHoverTimer" class="more-button" aria-label="More categories">
        More
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>
    </div>

    <!-- Subcategories Mega Menu -->
    <div v-if="activeCategory && activeCategory.children && processedSubcategories.length > 0" class="mega-menu"
      @mouseenter="clearHideTimer" @mouseleave="hideSubcategories">
      <div class="mega-menu-container">
        <div class="mega-menu-grid">
          <!-- Regular subcategories -->
          <div v-for="(subcategory, index) in processedSubcategories" :key="index" class="subcategory-section">
            <router-link :to="{ name: 'Category', params: { categorySlug: subcategory.slug } }" class="subcategory-item"
              @click="hideSubcategories">
              <div class="subcategory-name">{{ subcategory.name }}</div>
            </router-link>
          </div>

          <!-- View All link at the end -->
          <router-link v-if="activeCategory && activeCategory.slug"
            :to="{ name: 'Category', params: { categorySlug: activeCategory.slug } }"
            class="subcategory-item view-all-item" @click="hideSubcategories">
            <div class="subcategory-name view-all-text">View All {{ activeCategory.name }}</div>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Modal for hidden categories -->
    <div v-if="showMoreModal" class="modal-overlay" @click.self="closeModal" @mouseleave="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>All Categories</h3>
          <button @click="closeModal" class="close-button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
          </button>
        </div>
        <div class="modal-grid">
          <router-link v-for="(category, i) in hiddenCategories" :key="`hidden-${i}`"
            :to="{ name: 'Category', params: { categorySlug: category.slug } }" class="modal-category-link"
            @click="closeModal">
            {{ category.name }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    categories: { type: Array, required: true },
    isScrolled: { type: Boolean, required: true },
  },

  data() {
    return {
      showMoreModal: false,
      hoverTimeout: null,
      hideTimeout: null,
      activeCategory: null,
      visibleCount: 11,
    };
  },

  computed: {
    visibleCategories() {
      this.updateVisibleCount();
      return this.categories.slice(0, this.visibleCount);
    },

    hiddenCategories() {
      return this.categories.slice(this.visibleCount);
    },

    // Process subcategories to remove "View All" from the list
    processedSubcategories() {
      if (!this.activeCategory || !this.activeCategory.children || !this.activeCategory.children.data) {
        return [];
      }

      // Filter out items with "view all" in their name (case insensitive)
      return this.activeCategory.children.data.filter(subcategory => {
        const name = subcategory.name.toLowerCase();
        return !name.includes('view all');
      });
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
        this.visibleCount = 11;
      }
    },

    showSubcategories(category) {
      this.clearHideTimer();
      this.activeCategory = category;
    },

    startHideTimer() {
      this.hideTimeout = window.setTimeout(() => {
        this.hideSubcategories();
      }, 200);
    },

    clearHideTimer() {
      if (this.hideTimeout) {
        window.clearTimeout(this.hideTimeout);
        this.hideTimeout = null;
      }
    },

    hideSubcategories() {
      this.clearHideTimer();
      this.activeCategory = null;
    },

    startHoverTimer() {
      this.hoverTimeout = window.setTimeout(() => {
        this.showMoreModal = true;
      }, 300);
    },

    clearHoverTimer() {
      if (this.hoverTimeout) {
        window.clearTimeout(this.hoverTimeout);
        this.hoverTimeout = null;
      }
    },

    openModal() {
      this.clearHoverTimer();
      this.showMoreModal = true;
    },

    closeModal() {
      this.clearHoverTimer();
      this.showMoreModal = false;
    },

    handleKeydown(e) {
      if (e.key === 'Escape') {
        if (this.showMoreModal) {
          this.closeModal();
        }
        if (this.activeCategory) {
          this.hideSubcategories();
        }
      }
    },

    handleClickOutside(e) {
      if (this.showMoreModal && !e.target.closest('.modal-content') && !e.target.closest('.more-button')) {
        this.closeModal();
      }
    },
  },

  watch: {
    showMoreModal(newValue) {
      if (newValue) {
        document.addEventListener('click', this.handleClickOutside);
        document.body.classList.add('modal-open');
      } else {
        document.removeEventListener('click', this.handleClickOutside);
        document.body.classList.remove('modal-open');
      }
    },
  },

  mounted() {
    this.updateVisibleCount();
    window.addEventListener('resize', this.updateVisibleCount);
    document.addEventListener('keydown', this.handleKeydown);
  },

  beforeUnmount() {
    window.removeEventListener('resize', this.updateVisibleCount);
    document.removeEventListener('keydown', this.handleKeydown);
    document.removeEventListener('click', this.handleClickOutside);
    this.clearHoverTimer();
    this.clearHideTimer();
    document.body.classList.remove('modal-open');
  },
};
</script>
<style scoped>
.category-bar {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  background: #FFFBF3;
  position: relative;
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
  transition: all 0.3s ease;
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
  background: #1a1a1a;
  transition: width 0.3s ease;
}

.category-wrapper:hover .category-link::after {
  width: 100%;
}

/* Mega Menu Styles */
.mega-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  z-index: 999;
  animation: slideDown 0.2s ease;
  border-top: 1px solid rgba(0, 0, 0, 0.08);
}

.mega-menu-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 3rem;
}

.mega-menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.75rem;
}

.subcategory-section {
  display: flex;
}

.subcategory-item {
  text-decoration: none;
  color: #1a1a1a;
  transition: all 0.3s ease;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  width: 100%;
  padding: 12px;
  border: 1px solid transparent;
  background: #f8f9fa;
}

.subcategory-item:hover {
  background: white;
  border-color: #1a1a1a;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.subcategory-name {
  font-size: 0.875rem;
  font-weight: 500;
  text-align: center;
  line-height: 1.3;
}

/* View All item styling */
.view-all-item {
  background: #1a1a1a;
  color: white;
  grid-column: 1 / -1;
  /* Make it span full width */
  max-width: 200px;
  margin: 0.5rem auto 0;
}

.view-all-item:hover {
  background: #333;
  border-color: #1a1a1a;
  color: white;
}

.view-all-text {
  color: white;
  font-weight: 600;
}

.view-all-item:hover .view-all-text {
  color: white;
}

/* More button styles */
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
  border-radius: 4px;
  transition: background-color 0.3s ease;
  flex-shrink: 0;
  white-space: nowrap;
}

.more-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

/* Modal styles */
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
  background: white;
  border-radius: 8px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 800px;
  width: 90%;
  max-height: 70vh;
  overflow-y: auto;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
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
  transition: background-color 0.3s ease;
}

.close-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
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
  border-radius: 6px;
  transition: all 0.3s ease;
  font-size: 0.875rem;
}

.modal-category-link:hover {
  background-color: rgba(0, 0, 0, 0.05);
  transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .category-bar-container {
    gap: 2rem;
    padding: 0 2rem;
    justify-content: flex-start;
  }

  .mega-menu-container {
    padding: 1rem 2rem;
  }

  .mega-menu-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.5rem;
  }

  .subcategory-item {
    padding: 10px;
  }
}

@media (max-width: 768px) {
  .category-bar-container {
    padding: 0 1rem !important;
    gap: 1.5rem;
  }

  .mega-menu-container {
    padding: 1rem;
  }

  .mega-menu-grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .modal-content {
    width: 95%;
    margin-top: 60px;
  }

  .modal-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }

  .subcategory-name {
    font-size: 0.8125rem;
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

  .mega-menu-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .modal-content {
    max-height: 60vh;
    width: 100%;
    border-radius: 0;
    margin-top: 0;
    padding-top: env(safe-area-inset-top);
  }

  .modal-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .modal-header {
    padding: 1rem;
    position: sticky;
    top: 0;
    background: #FFFBF3;
    z-index: 1;
  }

  .subcategory-name {
    font-size: 0.75rem;
  }

  .view-all-item {
    max-width: 160px;
  }
}

/* For very small screens */
@media (max-width: 360px) {
  .category-bar-container {
    gap: 0.75rem;
  }

  .modal-grid,
  .mega-menu-grid {
    grid-template-columns: 1fr;
  }
}

/* Prevent body scroll when modal is open */
:deep(body.modal-open) {
  overflow: hidden;
}
</style>