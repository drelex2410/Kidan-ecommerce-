<template>
  <section class="hero-main-section">
    <div class="hero-content-wrapper">
      <div class="gutters-0" v-if="loading">
        <div class="px-0">
          <v-skeleton-loader type="image" height="600" class="loader"></v-skeleton-loader>
        </div>
      </div>
      <div class="gutters-0" v-else>
        <div class="px-0">
          <swiper :spaceBetween="0" :centeredSlides="true" :autoplay="carouselOption.autoplay"
            :pagination="{ clickable: true }" :navigation="true" :loop="true" :modules="modules"
            class="mySwiper main-swiper">
            <swiper-slide v-for="(slider, i) in sliders.one" :key="i">
              <router-link :to="slider.link || '/'" class="slide-link">
                <div class="slide-content main-slide">
                  <div class="slide-overlay"></div>
                  <img :src="slider.img" alt="Hero Slider" />
                  <div class="slide-text-container">
                    <h1 class="slide-title">
                      {{ slider.title || '' }}
                    </h1>
                    <p class="slide-subtitle">
                      {{ slider.subtitle || '' }}
                    </p>
                  </div>
                </div>
              </router-link>
            </swiper-slide>
          </swiper>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import { Autoplay, Navigation, Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";

export default {
  name: "HeroMainSlider",
  components: {
    Swiper,
    SwiperSlide,
  },
  setup() {
    return {
      modules: [Autoplay, Pagination, Navigation],
    };
  },
  data: () => ({
    loading: true,
    sliders: null,
    carouselOption: {
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },
    },
  }),
  async created() {
    try {
      const res = await this.call_api("get", "setting/home/sliders");
      if (res.data.success) {
        this.sliders = res.data.data;
        this.loading = false;
      }
    } catch (error) {
      console.error("Error fetching sliders:", error);
      this.loading = false;
    }
  },
};
</script>

<style scoped>
.hero-main-section {
  background-color: #FFFBF3;
  position: relative;
  overflow: clip;
  width: 100vw;
  max-width: 100vw;
  margin-left: calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  padding: 0;
}

.hero-content-wrapper {
  width: 100%;
  margin: 0;
}

.loader {
  height: clamp(460px, 72svh, 750px) !important;
  background: rgba(0, 0, 0, 0.05) !important;
  border-radius: 0 !important;
  width: 100%;
}

.gutters-0>[class*="col-"] {
  padding: 0 !important;
}

.gutters-0 {
  margin: 0 !important;
}

.px-0 {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.mySwiper {
  width: 100%;
  height: 100%;
  border-radius: 0;
  overflow: hidden;
}

.main-swiper {
  height: clamp(460px, 72svh, 750px);
  min-height: 460px;
  width: 100%;
}

.slide-link {
  display: block;
  width: 100%;
  height: 100%;
  text-decoration: none;
  color: inherit;
}

.slide-content {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
  cursor: pointer;
}

.slide-content img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  transition: transform 0.8s ease;
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
  background:
    linear-gradient(180deg, rgba(0, 0, 0, 0.12) 0%, rgba(0, 0, 0, 0.42) 100%),
    linear-gradient(90deg, rgba(0, 0, 0, 0.46) 0%, rgba(0, 0, 0, 0.16) 48%, rgba(0, 0, 0, 0.28) 100%);
  z-index: 1;
}

.slide-text-container {
  position: absolute;
  left: clamp(18px, 6vw, 88px);
  right: clamp(18px, 5vw, 52px);
  bottom: clamp(52px, 7vw, 88px);
  z-index: 2;
  color: #ffffff;
  max-width: min(620px, calc(100% - 36px));
  animation: fadeInUp 0.9s ease-out;
}

.slide-title {
  font-size: clamp(2rem, 5.3vw, 5rem);
  font-weight: 300;
  line-height: 1.02;
  margin: 0 0 1rem 0;
  letter-spacing: -1px;
  text-transform: capitalize;
  max-width: 10ch;
  text-wrap: balance;
}

.slide-title::first-line {
  font-weight: 400;
}

.slide-subtitle {
  font-size: clamp(0.95rem, 1.35vw, 1.1rem);
  font-weight: 300;
  line-height: 1.55;
  margin: 0;
  max-width: min(44ch, 100%);
  opacity: 0.95;
  text-wrap: pretty;
}

:deep(.swiper-button-next),
:deep(.swiper-button-prev) {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.3);
  opacity: 0;
  transition: all 0.3s ease;
}

.mySwiper:hover :deep(.swiper-button-next),
.mySwiper:hover :deep(.swiper-button-prev) {
  opacity: 1;
}

:deep(.swiper-button-next:hover),
:deep(.swiper-button-prev:hover) {
  background: rgba(255, 255, 255, 0.3);
}

:deep(.swiper-button-next:after),
:deep(.swiper-button-prev:after) {
  font-size: 1.4rem;
  font-weight: bold;
}

:deep(.swiper-pagination) {
  bottom: 30px !important;
}

:deep(.swiper-pagination-bullet) {
  background: #FFFBF3;
  opacity: 0.5;
  width: 10px;
  height: 10px;
  transition: all 0.3s ease;
}

:deep(.swiper-pagination-bullet-active) {
  opacity: 1;
  background: #FFFBF3;
  width: 30px;
  border-radius: 5px;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(22px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Design */
@media (min-width: 1200px) {
  .main-swiper {
    height: 750px;
  }

  .loader {
    height: 750px !important;
  }

  .slide-text-container {
    max-width: 640px;
  }
}

@media (min-width: 960px) and (max-width: 1199px) {
  .main-swiper {
    height: 620px;
  }

  .loader {
    height: 620px !important;
  }
}

@media (max-width: 959px) {
  .main-swiper {
    height: min(68svh, 600px);
    min-height: 500px;
  }

  .loader {
    height: min(68svh, 600px) !important;
    min-height: 500px;
  }

  .slide-text-container {
    left: 24px;
    right: 24px;
    bottom: 72px;
    max-width: 78%;
  }

  :deep(.swiper-button-next),
  :deep(.swiper-button-prev) {
    display: none;
  }
}

@media (max-width: 768px) {
  .main-swiper {
    height: min(66svh, 560px);
    min-height: 470px;
  }

  .loader {
    height: min(66svh, 560px) !important;
    min-height: 470px;
  }

  .slide-text-container {
    left: 20px;
    right: 20px;
    bottom: 64px;
    max-width: 90%;
  }

  .slide-title {
    margin-bottom: 0.75rem;
    max-width: 12ch;
  }

  .slide-subtitle {
    margin: 0;
    max-width: 34ch;
  }

  :deep(.swiper-pagination) {
    bottom: 18px !important;
  }
}

@media (max-width: 480px) {
  .main-swiper {
    height: min(64svh, 500px);
    min-height: 430px;
  }

  .loader {
    height: min(64svh, 500px) !important;
    min-height: 430px;
  }

  .slide-text-container {
    left: 16px;
    right: 16px;
    bottom: 56px;
    max-width: calc(100% - 32px);
  }

  .slide-title {
    max-width: 13ch;
  }

  .slide-subtitle {
    margin: 0;
    max-width: 28ch;
  }

  :deep(.swiper-pagination-bullet) {
    width: 8px;
    height: 8px;
  }

  :deep(.swiper-pagination-bullet-active) {
    width: 22px;
  }
}

:deep(.v-container) {
  max-width: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
}

:deep(.row) {
  margin: 0 !important;
  padding: 0 !important;
}

:deep(.col) {
  padding: 0 !important;
  margin: 0 !important;
}
</style>
