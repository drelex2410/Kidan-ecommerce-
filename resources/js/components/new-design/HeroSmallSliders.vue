<template>
  <v-container 
    :fluid="isMobile" 
    class="hero-small-section"
  >
    <div class="mb-5">
      <!-- Loading State -->
      <div v-if="loading" class="horizontal-scroll-container">
        <div class="scroll-wrapper">
          <div class="scroll-content">
            <div v-for="i in 4" :key="i" class="slide-item">
              <v-skeleton-loader
                type="image"
                height="200"
                class="loader"
              ></v-skeleton-loader>
            </div>
          </div>
        </div>
      </div>

      <!-- Horizontal Scrollable Content -->
      <div v-else class="horizontal-scroll-container">
        <button 
          class="scroll-arrow scroll-arrow-left" 
          @click="scrollLeft"
          :disabled="scrollPosition <= 0"
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <div class="scroll-wrapper" ref="scrollContainer">
          <div class="scroll-content">
            <!-- First Row - 3 items -->
            <div class="row-container">
              <router-link
                v-for="(slider, i) in sliders.two" 
                :key="`medium-${i}`" 
                :to="slider.link"
                class="slide-item"
              >
                <div class="slide-content medium-slide">
                  <div class="slide-overlay"></div>
                  <img :src="slider.img" alt="Hero Slider" />
                  <div class="slide-text">
                    <h3>Featured Collection</h3>
                  </div>
                </div>
              </router-link>
            </div>

            <!-- Second Row - 3 items -->
            <div class="row-container">
              <router-link
                v-for="(slider, i) in sliders.three" 
                :key="`small-1-${i}`" 
                :to="slider.link"
                class="slide-item"
              >
                <div class="slide-content small-slide">
                  <div class="slide-overlay"></div>
                  <img :src="slider.img" alt="Hero Slider" />
                  <div class="slide-text">
                    <h4>New Arrivals</h4>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </div>

        <button 
          class="scroll-arrow scroll-arrow-right" 
          @click="scrollRight"
          :disabled="scrollPosition >= maxScroll"
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
    </div>
  </v-container>
</template>

<script>
export default {
  name: 'HeroHorizontalSlider',
  data: () => ({
    loading: true,
    sliders: null,
    scrollPosition: 0,
    maxScroll: 0,
    isMobile: false
  }),
  async created() {
    const res = await this.call_api("get", "setting/home/sliders");
    if (res.data.success) {
      this.sliders = res.data.data;
      this.loading = false;

      this.$nextTick(() => {
        this.calculateMaxScroll();
      });
    }
  },
  mounted() {
    this.checkMobile();
    window.addEventListener('resize', this.handleResize);
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.handleResize);
  },
  methods: {
    handleResize() {
      this.checkMobile();
      this.calculateMaxScroll();
    },
    checkMobile() {
      this.isMobile = window.innerWidth < 960;
    },
    scrollLeft() {
      const container = this.$refs.scrollContainer;
      if (!container) return;

      const scrollAmount = container.clientWidth * 0.8;
      this.scrollPosition = Math.max(0, this.scrollPosition - scrollAmount);
      container.scrollTo({
        left: this.scrollPosition,
        behavior: 'smooth'
      });
    },
    scrollRight() {
      const container = this.$refs.scrollContainer;
      if (!container) return;

      const scrollAmount = container.clientWidth * 0.8;
      this.scrollPosition = Math.min(
        this.maxScroll, 
        this.scrollPosition + scrollAmount
      );
      container.scrollTo({
        left: this.scrollPosition,
        behavior: 'smooth'
      });
    },
    calculateMaxScroll() {
      const container = this.$refs.scrollContainer;
      if (!container) return;

      const content = container.querySelector('.scroll-content');
      if (!content) return;

      this.maxScroll = content.scrollWidth - container.clientWidth;
    }
  }
};
</script>

<style scoped>
.hero-small-section {
  position: relative;
  overflow: hidden;
  padding: 2rem 0;
}

/* Horizontal Scroll Container */
.horizontal-scroll-container {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.scroll-wrapper {
  overflow-x: auto;
  overflow-y: hidden;
  scroll-behavior: smooth;
  width: 100%;
  padding: 10px 0;
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scroll-wrapper::-webkit-scrollbar {
  display: none;
}

.row-container {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.slide-item {
  flex: 0 0 auto;
  width: 280px;
  height: 280px;
  border-radius: 8px;
  overflow: hidden;
}

.scroll-content {
  display: flex;
  gap: 10px;
  width: max-content;
}

/* Scroll Arrows */
.scroll-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.8);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  color: #333;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
}

.scroll-arrow:hover:not(:disabled) {
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.scroll-arrow:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.scroll-arrow-left {
  left: 10px;
}

.scroll-arrow-right {
  right: 10px;
}

/* Loader Styles */
.loader {
  height: 280px !important;
  width: 280px !important;
  background: rgba(255, 255, 255, 0.1) !important;
  border-radius: 8px;
}

/* Slide Content Styles */
.slide-content {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.slide-content img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  transition: transform 0.5s ease;
}

.slide-content:hover img {
  transform: scale(1.05);
}

.slide-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.5));
  z-index: 1;
}

.slide-text {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1rem;
  z-index: 2;
  color: #f5f1e8;
  animation: fadeInUp 0.6s ease-out;
}

.slide-text h3,
.slide-text h4 {
  font-weight: 400;
  letter-spacing: 0.5px;
  margin: 0;
}

.slide-text h3 {
  font-size: 1.2rem;
}

.slide-text h4 {
  font-size: 1rem;
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Design */
@media (min-width: 960px) {
  .slide-item {
    width: 320px;
    height: 300px;
  }

  .loader {
    height: 300px !important;
    width: 320px !important;
  }

  .slide-text h3 {
    font-size: 1.4rem;
  }

  .slide-text h4 {
    font-size: 1.1rem;
  }
}

@media (max-width: 768px) {
  .slide-item {
    width: 240px;
    height: 240px;
  }

  .loader {
    height: 240px !important;
    width: 240px !important;
  }

  .scroll-arrow {
    width: 36px;
    height: 36px;
  }

  .scroll-arrow-left {
    left: 5px;
  }

  .scroll-arrow-right {
    right: 5px;
  }

  .slide-text {
    padding: 0.75rem;
  }

  .slide-text h3 {
    font-size: 1.1rem;
  }

  .slide-text h4 {
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .slide-item {
    width: 200px;
    height: 150px;
  }

  .loader {
    height: 150px !important;
    width: 200px !important;
  }

  .scroll-arrow {
    width: 32px;
    height: 32px;
  }

  .slide-text h3 {
    font-size: 1rem;
  }

  .slide-text h4 {
    font-size: 0.85rem;
  }
}
</style>