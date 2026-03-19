<template>
  <div class="homepage">
    <section class="products-section">
      <v-container class="products-container">
        <div class="content-wrapper">
          <div class="promo-banner">
            <div class="banner-content">
              <p class="banner-items">{{ products.length }} Items</p>
              <h2 class="banner-title">Today's Deal</h2>
              <p class="banner-description">
                Explore our best selling catalogue, Join<br />
                hundreds of people with exquisite taste.
              </p>
              <dynamic-link to="/todays-deal" append-class="discover-btn-wrapper">
                <button class="discover-btn">DISCOVER MORE</button>
              </dynamic-link>
            </div>
          </div>

          <div class="products-slider-container">
            <button v-if="products.length > visibleCount" class="slider-arrow slider-arrow-left" @click="slideLeft">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>

            <div v-if="loading" class="products-slider">
              <div v-for="i in 4" :key="i" class="product-skeleton">
                <v-skeleton-loader type="image" height="400"></v-skeleton-loader>
                <v-skeleton-loader type="text" class="mt-3"></v-skeleton-loader>
                <v-skeleton-loader type="text" width="60%"></v-skeleton-loader>
              </div>
            </div>

            <div v-else-if="products.length > 0" class="products-slider" ref="slider">
              <div class="slider-wrapper">
                <transition-group name="slide-fade" tag="div" class="slider-transition-wrapper">
                  <product-box 
                    v-for="(product, i) in visibleProducts" 
                    :key="product.id || i" 
                    :product-details="product" 
                    :is-loading="loading" 
                    class="slider-item"
                  />
                </transition-group>
              </div>
            </div>
            <div v-else class="empty-state">
              No Today&apos;s Deal products are available right now.
            </div>

            <button v-if="products.length > visibleCount" class="slider-arrow slider-arrow-right" @click="slideRight">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
      </v-container>
    </section>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: true,
    products: [],
    currentIndex: 0,
    visibleCount: 4,
    slideDirection: 'right'
  }),
  computed: {
    visibleProducts() {
      if (this.products.length <= this.visibleCount) {
        return this.products;
      }
      
      const endIndex = this.currentIndex + this.visibleCount;
      if (endIndex <= this.products.length) {
        return this.products.slice(this.currentIndex, endIndex);
      } else {
        return [
          ...this.products.slice(this.currentIndex),
          ...this.products.slice(0, endIndex - this.products.length)
        ];
      }
    }
  },
  async created() {
    try {
      const res = await this.call_api("get", "setting/home/product_section_one");
      if (res.data.success) {
        this.products = res.data.data.products.data;
        this.loading = false;
      }
    } catch (error) {
      console.error("Error fetching products:", error);
      this.loading = false;
    }
  },
  methods: {
    slideLeft() {
      if (this.products.length <= this.visibleCount) return;
      
      this.slideDirection = 'left';
      this.currentIndex--;
      if (this.currentIndex < 0) {
        this.currentIndex = this.products.length - this.visibleCount;
      }
    },
    slideRight() {
      if (this.products.length <= this.visibleCount) return;
      
      this.slideDirection = 'right';
      this.currentIndex++;
      if (this.currentIndex > this.products.length - this.visibleCount) {
        this.currentIndex = 0;
      }
    }
  }
};
</script>

<style scoped>
.products-section {
  background: #FFFBF3;
  padding: 1rem 0;
}

.products-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

.content-wrapper {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

.promo-banner {
  border-radius: 12px;
  padding: 2.5rem 2rem;
  flex-shrink: 0;
  width: 320px;
  background: transparent;
  height: fit-content;
}

.banner-items {
  font-size: 0.875rem;
  color: #666;
  margin: 0 0 0.5rem;
}

.banner-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 1rem;
}

.banner-description {
  font-size: 0.95rem;
  color: #666;
  line-height: 1.6;
  margin: 0 0 2rem;
}

.discover-btn {
  background: #8b0000;
  color: white;
  border: none;
  padding: 0.875rem 2rem;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: 1px;
  cursor: pointer;
  border-radius: 4px;
  transition: background 0.3s ease;
  width: 100%;
}

.discover-btn-wrapper {
  display: block;
  text-decoration: none;
}

.discover-btn:hover {
  background: #a00000;
}

.products-slider-container {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
  min-width: 0; /* Important for flex child to respect overflow */
}

.products-slider {
  flex: 1;
  width: 100%;
      padding: 0 5%;
  overflow: hidden;
  min-width: 0; /* Important for flex child to respect overflow */
}

.empty-state {
  flex: 1;
  min-height: 260px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem;
  color: #6b6b6b;
  font-size: 1rem;
  border: 1px solid #e7dfd1;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.6);
}

.slider-wrapper {
  display: flex;
  width: 100%;
  overflow-x: auto;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
}

.slider-wrapper::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.slider-transition-wrapper {
  display: flex;
  flex: 0 0 auto;
  width: 100%;
  gap: 1.5rem;
}

.slider-item {
  flex: 0 0 calc(25% - 1.125rem); /* 4 items with gap adjustment */
  min-width: 0; /* Important for flex child to respect overflow */
  transition: all 0.5s ease;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.5s ease;
}

.slide-fade-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.slide-fade-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.slide-fade-enter-to,
.slide-fade-leave-from {
  opacity: 1;
  transform: translateX(0);
}

.slide-fade-move {
  transition: transform 0.5s ease;
}

.slider-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: #FFFBF3;
  border: 1px solid #e0e0e0;
  border-radius: 50%;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.slider-arrow:hover {
  background: #f5f5f5;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.slider-arrow-left {
  left: -24px;
}

.slider-arrow-right {
  right: -24px;
}

.slider-arrow svg {
  color: #333;
}

.product-skeleton {
  display: flex;
  flex: 0 0 calc(25% - 1.125rem);
  background: #FFFBF3;
  border-radius: 12px;
  overflow: hidden;
  padding: 1rem;
  flex-direction: column;
}

.products-slider ::v-deep .product-box {
  height: 100%;
  display: flex;
  flex-direction: column;
  min-width: 0; /* Important for flex child to respect overflow */
}

/* Loading state layout */
.products-slider:has(.product-skeleton) {
  display: flex;
  gap: 1.5rem;
  width: 100%;
}

.products-slider:has(.product-skeleton) .product-skeleton {
  flex: 0 0 calc(25% - 1.125rem);
}

@media (max-width: 1400px) {
  .slider-item,
  .product-skeleton {
    flex: 0 0 calc(25% - 1.125rem);
  }
}

@media (max-width: 1200px) {
  .content-wrapper {
    flex-direction: column;
  }

  .promo-banner {
    width: 100%;
  }

  .slider-item,
  .product-skeleton {
    flex: 0 0 calc(25% - 1.125rem);
  }
}

@media (max-width: 960px) {
  .slider-item,
  .product-skeleton {
    flex: 0 0 calc(50% - 0.75rem);
  }

  .slider-transition-wrapper {
    gap: 1rem;
  }

  .products-container {
    padding: 0;
  }

  .slider-arrow-left {
    left: -16px;
  }

  .slider-arrow-right {
    right: -16px;
  }
}

@media (max-width: 768px) {
   .slider-item,
    .product-skeleton {
        flex: 0 0 calc(50% - 0.75rem);
    }

  .slider-arrow {
    width: 40px;
    height: 40px;
  }
}

@media (max-width: 600px) {
  .products-container {
    padding: 0;
  }

  .products-section {
    padding: 2.5rem 0;
  }

  .promo-banner {
    padding: 2rem 1.5rem;
  }

  .banner-title {
    font-size: 1.5rem;
  }


  .product-skeleton {
    flex: 0 0 calc(100% - 0.5rem); /* 1 item on mobile */
  }

  .slider-transition-wrapper {
    gap: 0.5rem;
  }

  .slider-arrow {
    display: none;
  }
}
</style>
