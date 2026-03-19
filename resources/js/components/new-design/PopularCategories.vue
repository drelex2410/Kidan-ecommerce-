<template>
  <div class="categories-section">
    <div class="categories-container">
      <div class="categories-header">
        <h2 class="categories-title">{{ $t('popular_categories') }}</h2>
        <router-link
          :to="{ name: 'AllCategories' }"
          class="view-all-link"
        >
          <span>{{ $t('view_all') }}</span>
          <i class="las la-angle-right"></i>
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="categories-scroll-container">
        <div class="categories-scroll">
          <div
            v-for="i in 8"
            :key="i"
            class="category-card-skeleton"
          >
            <v-skeleton-loader
              type="image"
              height="120"
            ></v-skeleton-loader>
          </div>
        </div>
      </div>

      <!-- Categories Horizontal Scroll -->
      <div v-else class="categories-scroll-wrapper">
        <button 
          v-if="showLeftArrow"
          class="scroll-arrow scroll-arrow-left"
          @click="scrollLeft"
          aria-label="Scroll left"
        >
          <i class="las la-angle-left"></i>
        </button>

        <div 
          class="categories-scroll-container"
          ref="scrollContainer"
          @scroll="handleScroll"
        >
          <div class="categories-scroll">
            <router-link
              v-for="category in categories"
              :key="category.id"
              class="category-card"
              :to="{ name: 'Category', params: {categorySlug: category.slug}}"
            >
              <div class="category-image-wrapper">
                <img
                  :src="category.banner"
                  :alt="category.name"
                  @error="imageFallback($event)"
                  class="category-image"
                >
                <div class="category-overlay"></div>
              </div>
              <div class="category-name">{{ category.name }}</div>
            </router-link>
          </div>
        </div>

        <button 
          v-if="showRightArrow"
          class="scroll-arrow scroll-arrow-right"
          @click="scrollRight"
          aria-label="Scroll right"
        >
          <i class="las la-angle-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PopularCategories',

  data: () => ({
    loading: true,
    categories: [],
    showLeftArrow: false,
    showRightArrow: false,
  }),

  async created() {
    await this.fetchCategories();
  },

  mounted() {
    this.$nextTick(() => {
      this.checkArrows();
      window.addEventListener('resize', this.checkArrows);
    });
  },

  beforeUnmount() {
    window.removeEventListener('resize', this.checkArrows);
  },

  methods: {
    async fetchCategories() {
      try {
        const res = await this.call_api("get", "setting/home/popular_categories");
        if (res.data.success) {
          const rawCategories = Array.isArray(res.data?.data?.data) ? res.data.data.data : [];
          const uniqueCategories = new Map();
          rawCategories.forEach((category) => {
            if (!category || !category.id || Number(category.featured) !== 1) {
              return;
            }
            if (!uniqueCategories.has(category.id)) {
              uniqueCategories.set(category.id, category);
            }
          });
          this.categories = Array.from(uniqueCategories.values());
          this.$nextTick(() => {
            this.checkArrows();
          });
        }
      } catch (error) {
        console.error('Error fetching categories:', error);
      } finally {
        this.loading = false;
      }
    },

    imageFallback(event) {
      event.target.src = '/placeholder-category.jpg';
    },

    handleScroll() {
      this.checkArrows();
    },

    checkArrows() {
      if (!this.$refs.scrollContainer) return;
      
      const container = this.$refs.scrollContainer;
      const scrollLeft = container.scrollLeft;
      const scrollWidth = container.scrollWidth;
      const clientWidth = container.clientWidth;

      this.showLeftArrow = scrollLeft > 10;
      this.showRightArrow = scrollLeft < scrollWidth - clientWidth - 10;
    },

    scrollLeft() {
      if (!this.$refs.scrollContainer) return;
      
      const container = this.$refs.scrollContainer;
      const scrollAmount = container.clientWidth * 0.8;
      
      container.scrollBy({
        left: -scrollAmount,
        behavior: 'smooth'
      });
    },

    scrollRight() {
      if (!this.$refs.scrollContainer) return;
      
      const container = this.$refs.scrollContainer;
      const scrollAmount = container.clientWidth * 0.8;
      
      container.scrollBy({
        left: scrollAmount,
        behavior: 'smooth'
      });
    },
  },
};
</script>

<style scoped>
/* ===================================
   CATEGORIES SECTION
   =================================== */
.categories-section {
  background-color: #f8f8f8;
  padding: 1.5rem 0;
  margin: 1.5rem 0;
}

.categories-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1rem;
}

@media (min-width: 768px) {
  .categories-container {
    padding: 0 2rem;
  }
  
  .categories-section {
    padding: 2rem 0;
  }
}

/* ===================================
   HEADER
   =================================== */
.categories-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.categories-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #2e1a1a;
  margin: 0;
  letter-spacing: -0.5px;
}

@media (min-width: 768px) {
  .categories-title {
    font-size: 1.5rem;
  }
  
  .categories-header {
    margin-bottom: 1.5rem;
  }
}

@media (min-width: 960px) {
  .categories-title {
    font-size: 1.75rem;
  }
}

.view-all-link {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #8b4513;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
  padding: 0.375rem 0.625rem;
  border-radius: 6px;
}

.view-all-link:hover {
  color: #6b3410;
  background-color: rgba(139, 69, 19, 0.05);
  transform: translateX(3px);
}

.view-all-link i {
  font-size: 1rem;
  transition: transform 0.2s;
}

.view-all-link:hover i {
  transform: translateX(3px);
}

@media (min-width: 768px) {
  .view-all-link {
    font-size: 0.95rem;
  }
}

/* ===================================
   HORIZONTAL SCROLL
   =================================== */
.categories-scroll-wrapper {
  position: relative;
}

.categories-scroll-container {
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
  scroll-behavior: smooth;
}

.categories-scroll-container::-webkit-scrollbar {
  display: none;
}

.categories-scroll {
  display: flex;
  gap: 0.5rem;
  padding: 0.25rem 0 0.75rem;
}

@media (min-width: 640px) {
  .categories-scroll {
    gap: 0.75rem;
  }
}

@media (min-width: 960px) {
  .categories-scroll {
    gap: 1rem;
  }
}

@media (min-width: 1264px) {
  .categories-scroll {
    gap: 1.25rem;
  }
}

/* ===================================
   SCROLL ARROWS
   =================================== */
.scroll-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  background: #fff;
  border: 1px solid #e8e8e8;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  transition: all 0.3s ease;
  color: #8b4513;
  font-size: 1.25rem;
}

.scroll-arrow:hover {
  background: #8b4513;
  color: #fff;
  transform: translateY(-50%) scale(1.1);
}

.scroll-arrow-left {
  left: -15px;
}

.scroll-arrow-right {
  right: -15px;
}

@media (max-width: 768px) {
  .scroll-arrow {
    width: 32px;
    height: 32px;
    font-size: 1.125rem;
  }
  
  .scroll-arrow-left {
    left: -8px;
  }
  
  .scroll-arrow-right {
    right: -8px;
  }
}

/* ===================================
   CATEGORY CARD
   =================================== */
.category-card {
  display: block;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  transition: all 0.3s ease;
  text-decoration: none;
  border: 1px solid #e8e8e8;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  flex-shrink: 0;
  width: 100px;
}

@media (min-width: 640px) {
  .category-card {
    width: 120px;
  }
}

@media (min-width: 960px) {
  .category-card {
    width: 140px;
  }
}

.category-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #d0d0d0;
}

.category-image-wrapper {
  position: relative;
  width: 100%;
  padding-top: 100%;
  overflow: hidden;
  background-color: #f5f5f5;
}

.category-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.category-card:hover .category-image {
  transform: scale(1.05);
}

.category-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0) 50%,
    rgba(0, 0, 0, 0.2) 100%
  );
  opacity: 0;
  transition: opacity 0.3s ease;
}

.category-card:hover .category-overlay {
  opacity: 1;
}

.category-name {
  padding: 0.5rem 0.375rem;
  font-size: 0.7rem;
  font-weight: 600;
  color: #333;
  text-align: center;
  text-transform: capitalize;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.2s;
  background: #fff;
  line-height: 1.2;
}

.category-card:hover .category-name {
  color: #8b4513;
}

@media (min-width: 640px) {
  .category-name {
    font-size: 0.75rem;
    padding: 0.625rem 0.5rem;
  }
}

@media (min-width: 960px) {
  .category-name {
    font-size: 0.8rem;
    padding: 0.75rem 0.625rem;
  }
}

/* ===================================
   LOADING STATE
   =================================== */
.category-card-skeleton {
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #e8e8e8;
  flex-shrink: 0;
  width: 100px;
}

@media (min-width: 640px) {
  .category-card-skeleton {
    width: 120px;
  }
}

@media (min-width: 960px) {
  .category-card-skeleton {
    width: 140px;
  }
}

.category-card-skeleton :deep(.v-skeleton-loader) {
  border-radius: 10px;
}

.category-card-skeleton :deep(.v-skeleton-loader__image) {
  height: 120px !important;
}
</style>
