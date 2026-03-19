<template>
  <section class="hero-section">
    <div class="mb-5">
      <v-container class="pt-md-6 pb-0 px-0 px-md-3">
        <v-row
          class="gutters-7 md-gutters-10 lh-0"
          v-if="loading"
        >
          <v-col cols="12" lg="6">
            <v-skeleton-loader
              type="image"
              height="310"
              class="loader"
            ></v-skeleton-loader>
          </v-col>
          <v-col cols="6" lg="3">
            <v-skeleton-loader
              type="image"
              height="310"
              class="loader"
            ></v-skeleton-loader>
          </v-col>
          <v-col cols="6" lg="3" class="d-flex justify-space-between flex-column">
            <v-skeleton-loader
              type="image"
              height="145"
              class="right-first loader-half"
            ></v-skeleton-loader>
            <v-skeleton-loader
              type="image"
              height="145"
              class="loader-half"
            ></v-skeleton-loader>
          </v-col>
        </v-row>
        
        <v-row class="gutters-7 md-gutters-10 lh-0" v-else>
          <!-- Main Large Slider -->
          <v-col cols="12" lg="6">
            <swiper
              :spaceBetween="0"
              :centeredSlides="true"
              :autoplay="carouselOption.autoplay"
              :pagination="{ clickable: true }"
              :navigation="true"
              :loop="true"
              :modules="modules"
              class="mySwiper main-swiper"
            >
              <swiper-slide
                v-for="(slider, i) in sliders.one"
                :key="i"
              >
                <div class="slide-content main-slide">
                  <div class="slide-overlay"></div>
                  <img :src="slider.image" :alt="slider.title" />
                  <div class="slide-text">
                    <h2>{{ slider.title }}</h2>
                    <p>{{ slider.subtitle }}</p>
                    <button class="btn-primary">{{ slider.cta }}</button>
                  </div>
                </div>
              </swiper-slide>
            </swiper>
          </v-col>

          <!-- Medium Slider -->
          <v-col cols="6" lg="3">
            <swiper
              :spaceBetween="0"
              :centeredSlides="true"
              :autoplay="{ delay: 3000, disableOnInteraction: false }"
              :pagination="{ clickable: true }"
              :loop="true"
              :modules="modules"
              class="mySwiper medium-swiper"
            >
              <swiper-slide
                v-for="(slider, i) in sliders.two"
                :key="i"
              >
                <div class="slide-content medium-slide">
                  <div class="slide-overlay"></div>
                  <img :src="slider.image" :alt="slider.title" />
                  <div class="slide-text">
                    <h3>{{ slider.title }}</h3>
                  </div>
                </div>
              </swiper-slide>
            </swiper>
          </v-col>

          <!-- Small Sliders Stack -->
          <v-col cols="6" lg="3" class="d-flex justify-space-between flex-column">
            <swiper
              :spaceBetween="0"
              :centeredSlides="true"
              :autoplay="{ delay: 3500, disableOnInteraction: false }"
              :pagination="{ clickable: true }"
              :loop="true"
              :modules="modules"
              class="right-first w-100 mySwiper small-swiper"
            >
              <swiper-slide
                v-for="(slider, i) in sliders.three"
                :key="i"
              >
                <div class="slide-content small-slide">
                  <div class="slide-overlay"></div>
                  <img :src="slider.image" :alt="slider.title" />
                  <div class="slide-text">
                    <h4>{{ slider.title }}</h4>
                  </div>
                </div>
              </swiper-slide>
            </swiper>
            
            <swiper
              :spaceBetween="0"
              :centeredSlides="true"
              :autoplay="{ delay: 4000, disableOnInteraction: false }"
              :pagination="{ clickable: true }"
              :loop="true"
              :modules="modules"
              class="w-100 mySwiper small-swiper"
            >
              <swiper-slide
                v-for="(slider, i) in sliders.four"
                :key="i"
              >
                <div class="slide-content small-slide">
                  <div class="slide-overlay"></div>
                  <img :src="slider.image" :alt="slider.title" />
                  <div class="slide-text">
                    <h4>{{ slider.title }}</h4>
                  </div>
                </div>
              </swiper-slide>
            </swiper>
          </v-col>
        </v-row>
      </v-container>
    </div>
  </section>
</template>

<script>
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from "swiper/vue";

export default {
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
        delay: 2500,
        disableOnInteraction: false,
      },
    },
  }),
  async created() {
    const res = await this.call_api("get", "setting/home/sliders");
    if (res.data.success) {
      this.sliders = res.data.data;
      this.loading = false;
    }
  },
};
</script>

<style scoped>
.hero-section {
  background-color: #b8a688;
  background-image: linear-gradient(180deg, #d4c4a8 0%, #b8a688 100%);
  position: relative;
  overflow: hidden;
  padding: 2rem 0;
}

/* Loader Styles */
.loader {
  height: 200px !important;
  background: rgba(255, 255, 255, 0.1) !important;
  border-radius: 8px;
}

.loader-half {
  height: 92px !important;
  background: rgba(255, 255, 255, 0.1) !important;
  border-radius: 8px;
}

/* Grid Styles */
.row.gutters-7 > [class*="col-"] {
  padding-top: 7px;
  padding-bottom: 7px;
}

.col-lg-6 {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.col-lg-3:nth-of-type(2) {
  padding-left: 0px;
}

.col-lg-3:nth-of-type(3) {
  padding-right: 0px;
}

.right-first {
  margin-bottom: 14px;
}

.row {
  margin-left: 0;
  margin-right: 0;
}

/* Swiper Container Styles */
.mySwiper {
  width: 100%;
  height: 100%;
  border-radius: 8px;
  overflow: hidden;
}

.main-swiper {
  height: 200px;
}

.medium-swiper {
  height: 200px;
}

.small-swiper {
  height: 92px;
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
  padding: 1.5rem;
  z-index: 2;
  color: #f5f1e8;
  animation: fadeInUp 0.6s ease-out;
}

.main-slide .slide-text h2 {
  font-size: 2.5rem;
  font-weight: 300;
  margin-bottom: 0.5rem;
  letter-spacing: 1px;
}

.main-slide .slide-text p {
  font-size: 1.1rem;
  margin-bottom: 1rem;
  opacity: 0.9;
}

.medium-slide .slide-text h3 {
  font-size: 1.5rem;
  font-weight: 300;
  letter-spacing: 0.5px;
}

.small-slide .slide-text h4 {
  font-size: 1rem;
  font-weight: 400;
  letter-spacing: 0.5px;
}

/* Button Styles */
.btn-primary {
  background-color: #f5f1e8;
  color: #b8a688;
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.btn-primary:hover {
  background-color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Swiper Navigation Styles */
:deep(.swiper-button-next),
:deep(.swiper-button-prev) {
  color: #f5f1e8;
  background: rgba(0, 0, 0, 0.3);
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

:deep(.swiper-button-next:after),
:deep(.swiper-button-prev:after) {
  font-size: 1.2rem;
}

:deep(.swiper-pagination-bullet) {
  background: #f5f1e8;
  opacity: 0.5;
}

:deep(.swiper-pagination-bullet-active) {
  opacity: 1;
  background: #f5f1e8;
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

/* RTL Support */
.v-application-is-rtl .col-lg-3:nth-of-type(2) {
  padding-left: 7px;
  padding-right: 0;
}

.v-application-is-rtl .col-lg-3:nth-of-type(3) {
  padding-right: 7px;
  padding-left: 0;
}

/* Responsive Design */
@media (min-width: 960px) {
  .loader {
    height: 310px !important;
  }
  
  .loader-half {
    height: 145px !important;
  }
  
  .right-first {
    margin-bottom: 20px;
  }
  
  .row {
    margin-left: -10px;
    margin-right: -10px;
  }
  
  .row.md-gutters-10 > [class*="col-"] {
    padding-top: 10px;
    padding-bottom: 10px;
  }
  
  .col-lg-6 {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
  
  .col-lg-3:nth-of-type(2) {
    padding-left: 10px;
  }
  
  .col-lg-3:nth-of-type(3) {
    padding-right: 10px;
  }
  
  .v-application-is-rtl .col-lg-3 {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }
  
  .main-swiper {
    height: 310px;
  }
  
  .medium-swiper {
    height: 310px;
  }
  
  .small-swiper {
    height: 145px;
  }
  
  .main-slide .slide-text h2 {
    font-size: 3.5rem;
  }
  
  .medium-slide .slide-text h3 {
    font-size: 2rem;
  }
  
  .small-slide .slide-text h4 {
    font-size: 1.2rem;
  }
}

@media (max-width: 768px) {
  .slide-text {
    padding: 1rem;
  }
  
  .main-slide .slide-text h2 {
    font-size: 1.8rem;
  }
  
  .main-slide .slide-text p {
    font-size: 0.9rem;
  }
  
  .btn-primary {
    padding: 0.6rem 1.5rem;
    font-size: 0.9rem;
  }
}
</style>