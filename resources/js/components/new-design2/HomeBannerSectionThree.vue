<template>
    <div class="triple-banner-section">
        <div v-if="loading" class="banner-skeleton">
            <v-skeleton-loader type="image" height="500"></v-skeleton-loader>
        </div>
        <swiper v-else :slides-per-view="carouselOption.slidesPerView" :space-between="carouselOption.spaceBetween"
            :breakpoints="carouselOption.breakpoints" :loop="true" :autoplay="carouselOption.autoplay"
            :modules="modules" :pagination="{ clickable: true }" class="triple-swiper">
            <swiper-slide v-for="(banner, i) in banners" :key="i">
                <router-link :to="banner.link || '/'" class="triple-slide">
                    <div class="triple-image-wrapper">
                        <img :src="banner.img" :alt="`Banner ${i + 1}`" @error="imageFallback($event)"
                            class="triple-image" />
                        <div class="triple-overlay"></div>
                    </div>
                </router-link>
            </swiper-slide>
        </swiper>
    </div>
</template>

<script>
import { Autoplay, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from "swiper/vue";

export default {
    components: {
        Swiper,
        SwiperSlide,
    },
    setup() {
        return {
            modules: [Autoplay, Pagination],
        };
    },
    data: () => ({
        loading: true,
        banners: [],
        carouselOption: {
            slidesPerView: 3,
            spaceBetween: 20,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false,
            },
            breakpoints: {
                0: {
                    slidesPerView: 1,
                    spaceBetween: 0
                },
                599: {
                    slidesPerView: 1,
                    spaceBetween: 0
                },
                960: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                1264: {
                    slidesPerView: 3,
                    spaceBetween: 20
                },
                1904: {
                    slidesPerView: 3,
                    spaceBetween: 20
                },
            }
        },
    }),
    async created() {
        const res = await this.call_api("get", "setting/home/banner_section_three");
        if (res.data.success) {
            this.banners = res.data.data;
            this.loading = false;
        }
    }
}
</script>

<style scoped>
/* ===================================
   TRIPLE BANNER SECTION
   =================================== */
.triple-banner-section {
    width: 100%;
    height: 400px;
    position: relative;
    background: #f8f8f8;
    margin: 2rem 0;
    overflow: hidden;
}

.banner-skeleton {
    width: 100%;
    height: 100%;
    padding: 0 1rem;
}

/* ===================================
   SWIPER STYLES
   =================================== */
.triple-swiper {
    width: 100%;
    height: 100%;
    padding: 0 1rem;
}

.triple-swiper :deep(.swiper-pagination) {
    bottom: 1rem;
    z-index: 20;
}

.triple-swiper :deep(.swiper-pagination-bullet) {
    width: 7px;
    height: 7px;
    background: #ffffff;
    opacity: 0.5;
    transition: all 0.3s ease;
    box-shadow: 0 0 3px rgba(0, 0, 0, 0.15);
}

.triple-swiper :deep(.swiper-pagination-bullet-active) {
    opacity: 1;
    width: 20px;
    border-radius: 4px;
    background: #ffffff;
}

/* ===================================
   TRIPLE SLIDE
   =================================== */
.triple-slide {
    position: relative;
    width: 100%;
    height: 100%;
    display: block;
    text-decoration: none;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.triple-slide:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

.triple-image-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.triple-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.triple-slide:hover .triple-image {
    transform: scale(1.07);
}

.triple-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top,
            rgba(0, 0, 0, 0.28) 0%,
            transparent 65%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.triple-slide:hover .triple-overlay {
    opacity: 1;
}

/* ===================================
   RESPONSIVE STYLES
   =================================== */


@media (max-width: 960px) {
    .triple-banner-section {
        height: 350px;
        margin: 1.5rem 0;
    }

    .triple-slide {
        border-radius: 8px;
    }
}

@media (max-width: 600px) {
    .triple-banner-section {
        height: 300px;
        margin: 1rem 0;
    }

    .triple-swiper {
        padding: 0 0.5rem;
    }

    .triple-swiper :deep(.swiper-pagination) {
        bottom: 0.75rem;
    }

    .triple-swiper :deep(.swiper-pagination-bullet) {
        width: 6px;
        height: 6px;
    }

    .triple-swiper :deep(.swiper-pagination-bullet-active) {
        width: 16px;
    }
}

@media (max-width: 400px) {
    .triple-banner-section {
        height: 250px;
    }

    .triple-swiper :deep(.swiper-pagination-bullet) {
        width: 5px;
        height: 5px;
    }

    .triple-swiper :deep(.swiper-pagination-bullet-active) {
        width: 14px;
    }
}
</style>