<template>
  <div class="popular-categories-section" v-if="loading || categories.length">
    <div class="category-container">
      <div v-if="loading" class="carousel-container">
        <div class="categories-carousel">
          <div v-for="i in 5" :key="i" class="category-card-skeleton"></div>
        </div>
      </div>
      <div v-else class="carousel-wrapper">
        <div ref="carousel" class="categories-carousel" :style="{ transform: `translateX(-${currentOffset}px)` }"
          @mouseenter="pauseAutoScroll" @mouseleave="resumeAutoScroll">
          <router-link v-for="category in categories" :key="category.id" class="category-card"
            :to="{ name: 'Category', params: { categorySlug: category.slug } }">
            <div class="category-content">
              <div class="category-text">
                <h3 class="category-title">{{ category.name }}</h3>
                <p class="category-subtitle">Explore Category</p>
              </div>
              <div class="category-icon">
                <i class="las la-arrow-right up-right"></i>
              </div>
            </div>
            <div class="category-image-wrapper">
              <img :src="category.banner" :alt="category.name" class="category-image">
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: true,
    categories: [],
    currentIndex: 0,
    cardWidth: 300,
    gap: 20,
    cardsPerView: 4,
    autoScrollInterval: null,
    isHovered: false,
    isTransitioning: false
  }),
  computed: {
    stepWidth() {
      return this.cardWidth + this.gap;
    },
    maxIndex() {
      return Math.max(this.categories.length - this.cardsPerView, 0);
    },
    currentOffset() {
      return this.currentIndex * this.stepWidth;
    }
  },
  async created() {
    const res = await this.call_api("get", "setting/home/popular_categories");
    if (res.data.success) {
      const rawCategories = Array.isArray(res.data?.data?.data) ? res.data.data.data : [];
      const uniqueCategories = new Map();

      rawCategories.forEach((category) => {
        if (!category || !category.id) {
          return;
        }

        // Defensive filter in addition to backend filtering.
        if (Number(category.featured) !== 1) {
          return;
        }

        if (!uniqueCategories.has(category.id)) {
          uniqueCategories.set(category.id, category);
        }
      });

      this.categories = Array.from(uniqueCategories.values());
      this.loading = false;
      this.$nextTick(() => {
        this.calculateDimensions();
        this.startAutoScroll();
        window.addEventListener('resize', this.calculateDimensions);
      });
    }
  },
  beforeUnmount() {
    this.stopAutoScroll();
    window.removeEventListener('resize', this.calculateDimensions);
  },
  methods: {
    calculateDimensions() {
      if (window.innerWidth <= 480) {
        this.cardWidth = 280;
        this.gap = 16;
        this.cardsPerView = 1;
      } else if (window.innerWidth <= 768) {
        this.cardWidth = 250;
        this.gap = 16;
        this.cardsPerView = 2;
      } else if (window.innerWidth <= 1024) {
        this.cardWidth = 280;
        this.gap = 20;
        this.cardsPerView = 3;
      } else {
        this.cardWidth = 300;
        this.gap = 20;
        this.cardsPerView = 4;
      }

      if (this.currentIndex > this.maxIndex) {
        this.currentIndex = 0;
      }
    },
    startAutoScroll() {
      this.stopAutoScroll();

      if (this.categories.length <= this.cardsPerView) {
        return;
      }

      this.autoScrollInterval = setInterval(() => {
        if (!this.isHovered && !this.isTransitioning) {
          this.scrollNext();
        }
      }, 4000);
    },
    stopAutoScroll() {
      if (this.autoScrollInterval) {
        clearInterval(this.autoScrollInterval);
      }
    },
    pauseAutoScroll() {
      this.isHovered = true;
    },
    resumeAutoScroll() {
      this.isHovered = false;
    },
    scrollNext() {
      if (this.isTransitioning) return;
      if (this.maxIndex <= 0) return;

      this.isTransitioning = true;
      this.currentIndex = this.currentIndex >= this.maxIndex ? 0 : this.currentIndex + 1;
      setTimeout(() => {
        this.isTransitioning = false;
      }, 600);
    },
  },
};
</script>

<style scoped>
.popular-categories-section {
  background: #FFFBF3;
  overflow: hidden;
  padding: 0;
  display: flex;
  justify-content: center;
}

.category-container {
  width: 100%;
  height: auto;
  padding: 0;
}

.carousel-wrapper {
  position: relative;
  overflow: hidden;
  padding: 0;
  height: auto;
}

.categories-carousel {
  display: flex;
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
  height: auto;
  padding: 0;
}

.category-card {
  position: relative;
  display: flex;
  flex-direction: column;
  text-decoration: none;
  overflow: hidden;
  background: #FFFBF3;
  transition: all 0.3s ease;
  flex-shrink: 0;
  width: 300px;
  height: 400px;
  border: 1px solid #e5e5e5;
  border-radius: 0;
}

.category-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
}

.category-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.25rem 1.5rem;
  background: #FFFBF3;
  z-index: 2;
}

.category-text {
  flex: 1;
}

.category-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 0.25rem 0;
  text-transform: capitalize;
  letter-spacing: -0.01em;
}

.category-subtitle {
  font-size: 0.875rem;
  color: #757575;
  margin: 0;
  font-weight: 400;
}

.category-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  border-radius: 50%;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.category-icon i {
  font-size: 1.25rem;
  color: #1a1a1a;
  transition: transform 0.3s ease;
}

.category-icon i.up-right {
  transform: rotate(-45deg);
  display: inline-block;
}

.category-card:hover .category-icon {
  background: #1a1a1a;
}

.category-card:hover .category-icon i {
  color: #ffffff;
}

.category-card:hover .category-icon i.up-right {
  transform: rotate(-45deg) translate(2px, -2px);
}

.category-image-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: #f8f8f8;
  flex-grow: 1;
}

.category-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.category-card:hover .category-image {
  transform: scale(1.08);
}

.category-card-skeleton {
  background: #f8f8f8;
  overflow: hidden;
  flex-shrink: 0;
  width: 300px;
  height: 400px;
  border-radius: 0;
}

@media (max-width: 1440px) {
  .category-container {
    width: 100%;
    padding: 0 20px;
  }


  .category-card {
    width: 300px;
    height: 400px;
  }

  .category-card-skeleton {
    width: 300px;
    height: 400px;
  }
}

@media (max-width: 1024px) {
  .category-container {
    padding: 0 16px;
  }


  .category-card {
    width: 280px;
    height: 370px;
  }

  .category-card-skeleton {
    width: 280px;
    height: 370px;
  }

  .category-content {
    padding: 1.125rem 1.25rem;
  }

  .category-title {
    font-size: 1.125rem;
  }

  .category-icon {
    width: 32px;
    height: 32px;
  }

  .category-icon i {
    font-size: 1.125rem;
  }
}

@media (max-width: 768px) {
  .category-container {
    padding: 0 16px;
  }



  .category-card {
    width: 250px;
    height: 330px;
  }

  .category-card-skeleton {
    width: 250px;
    height: 330px;
  }

  .category-content {
    padding: 1rem 1rem;
  }

  .category-title {
    font-size: 1rem;
  }

  .category-subtitle {
    font-size: 0.8125rem;
  }

  .category-icon {
    width: 30px;
    height: 30px;
  }

  .category-icon i {
    font-size: 1rem;
  }
}

@media (max-width: 480px) {
  .popular-categories-section {
    padding: 0;
  }

  .category-container {
    padding: 0 16px;
  }



  .category-card {
    width: 280px;
    height: 350px;
  }

  .category-card-skeleton {
    width: 280px;
    height: 350px;
  }

  .category-content {
    padding: 0.875rem 1rem;
  }

  .category-title {
    font-size: 1rem;
    line-height: 1.2;
  }

  .category-subtitle {
    font-size: 0.75rem;
  }

  .category-icon {
    width: 28px;
    height: 28px;
  }

  .category-icon i {
    font-size: 0.875rem;
  }
}

@media (max-width: 360px) {
  .category-card {
    width: 260px;
    height: 330px;
  }

  .category-card-skeleton {
    width: 260px;
    height: 330px;
  }

  .category-content {
    padding: 0.75rem 0.875rem;
  }

  .category-title {
    font-size: 0.9375rem;
  }

  .category-icon {
    width: 26px;
    height: 26px;
  }

  .category-icon i {
    font-size: 0.8125rem;
  }
}
</style>
