<template>
  <section class="hero-main-section">
    <div class="mb-5">
      <div class="hero-content-wrapper">
        <div class="gutters-7 md-gutters-10 lh-0" v-if="loading">
          <div class="px-0">
            <v-skeleton-loader
              type="image"
              height="350"
              class="loader"
            ></v-skeleton-loader>
          </div>
        </div>
       
        <div class="gutters-7 md-gutters-10 lh-0" v-else>
          <!-- Main Large Slider -->
          <div class="px-0">
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
                <router-link 
                  :to="slider.link" 
                  class="slide-link"
                >
                  <div class="slide-content main-slide">
                    <div class="slide-overlay"></div>
                    <img :src="slider.img" alt="Hero Slider" />
                    <div class="slide-text">
                      <button class="btn-primary">Shop Now</button>
                    </div>
                  </div>
                </router-link>
              </swiper-slide>
            </swiper>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from "swiper/vue";

export default {
  name: 'HeroMainSlider',
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
    try {
      const res = await this.call_api("get", "setting/home/sliders");
      if (res.data.success) {
        this.sliders = res.data.data;
        this.loading = false;
        
        // Debug: log the data to see what's actually there
        console.log('Sliders data:', this.sliders);
      }
    } catch (error) {
      console.error('Error fetching sliders:', error);
      this.loading = false;
    }
  },
};
</script>

<style scoped>
.hero-main-section {
  background-color: #b8a688;
  background-image: linear-gradient(180deg, #d4c4a8 0%, #b8a688 100%);
  position: relative;
  overflow: hidden;
  width: 100vw;
  margin-left: calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
}

.hero-content-wrapper {
  width: 100%;
  margin: 0;
}

/* Loader Styles */
.loader {
  height: 350px !important;
  background: rgba(255, 255, 255, 0.1) !important;
  border-radius: 0 !important;
  width: 100%;
}

/* Remove all gutters and padding */
.gutters-7 > [class*="col-"] {
  padding: 0 !important;
}
.gutters-7 {
  margin: 0 !important;
}
.px-0 {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

/* Swiper Container Styles */
.mySwiper {
  width: 100%;
  height: 100%;
  border-radius: 0;
  overflow: hidden;
}
.main-swiper {
  height: 350px;
  width: 100%;
}

/* Slide Link Styles */
.slide-link {
  display: block;
  width: 100%;
  height: 100%;
  text-decoration: none;
  color: inherit;
}

/* Slide Content Styles */
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
  padding: 1.75rem;
  z-index: 2;
  color: #f5f1e8;
  animation: fadeInUp 0.6s ease-out;
}
.main-slide .slide-text h2 {
  font-size: 3rem;
  font-weight: 300;
  margin-bottom: 0.6rem;
  letter-spacing: 1.1px;
}
.main-slide .slide-text p {
  font-size: 1.25rem;
  margin-bottom: 1.25rem;
  opacity: 0.9;
}

/* Button Styles */
.btn-primary {
  background-color: #f5f1e8;
  color: #b8a688;
  padding: 0.9rem 2.25rem;
  border: none;
  border-radius: 5px;
  font-size: 1.1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1.1px;
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
  width: 45px;
  height: 45px;
  border-radius: 50%;
}
:deep(.swiper-button-next:after),
:deep(.swiper-button-prev:after) {
  font-size: 1.3rem;
}
:deep(.swiper-pagination-bullet) {
  background: #f5f1e8;
  opacity: 0.5;
  width: 9px;
  height: 9px;
}
:deep(.swiper-pagination-bullet-active) {
  opacity: 1;
  background: #f5f1e8;
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(25px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Design */
@media (min-width: 960px) {
  .hero-main-section {
   
  }
 
  .loader {
    height: 500px !important;
  }
 
  .main-swiper {
    height: 500px;
  }
 
  .main-slide .slide-text h2 {
    font-size: 4rem;
  }
 
  .main-slide .slide-text p {
    font-size: 1.6rem;
  }
 
  .btn-primary {
    padding: 1rem 2.5rem;
    font-size: 1.25rem;
  }
}

@media (max-width: 768px) {
  .hero-main-section {
  }
 
  .loader {
    height: 300px !important;
  }
 
  .main-swiper {
    height: 300px;
  }
 
  .slide-text {
    padding: 1.25rem;
  }
 
  .main-slide .slide-text h2 {
    font-size: 2.2rem;
  }
 
  .main-slide .slide-text p {
    font-size: 1.1rem;
  }
 
  .btn-primary {
    padding: 0.7rem 1.75rem;
    font-size: 0.95rem;
  }
}

@media (max-width: 480px) {
  .hero-main-section {
  }
 
  .loader {
    height: 220px !important;
  }
 
  .main-swiper {
    height: 220px;
  }
 
  .slide-text {
    padding: 0.9rem;
  }
 
  .main-slide .slide-text h2 {
    font-size: 1.8rem;
  }
 
  .main-slide .slide-text p {
    font-size: 0.9rem;
  }
 
  .btn-primary {
    padding: 0.55rem 1.4rem;
    font-size: 0.85rem;
  }
}

/* Ensure full width on all screen sizes */
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