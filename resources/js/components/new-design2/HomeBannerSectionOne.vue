<template>
    <div class="hero-banner-section">
        <div v-if="loading" class="banner-skeleton">
            <v-skeleton-loader type="image" height="400"></v-skeleton-loader>
        </div>
        <swiper 
            v-else
            :slides-per-view="1" 
            :autoplay="carouselOption.autoplay"
            :loop="true"
            :modules="modules"
            :pagination="{ clickable: true }"
            class="hero-swiper"
        >
            <swiper-slide v-for="(banner, i) in banners" :key="i">
                <router-link :to="banner.link || '/'" class="hero-slide">
                    <div class="hero-image-wrapper">
                        <img 
                            :src="banner.img" 
                            :alt="`Banner ${i + 1}`" 
                            @error="imageFallback($event)"
                            class="hero-image"
                        />
                        <div class="hero-overlay"></div>
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
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
        },
    }),
    async created() {
        const res = await this.call_api("get", "setting/home/banner_section_one");
        if (res.data.success) {
            this.banners = res.data.data;
            this.loading = false;
        }
    }
}
</script>

<style scoped>
.hero-banner-section {
    width: 100%;
    height: 300px;
    position: relative;
    background: #FFFBF3;
}

.banner-skeleton {
    width: 100%;
    height: 100%;
}

.hero-swiper {
    width: 100%;
    height: 100%;
}

.hero-swiper :deep(.swiper-pagination) {
    bottom: 2rem;
    z-index: 20;
}

.hero-swiper :deep(.swiper-pagination-bullet) {
    width: 8px;
    height: 8px;
    background: #FFFBF3;
    opacity: 0.5;
    transition: all 0.3s ease;
}

.hero-swiper :deep(.swiper-pagination-bullet-active) {
    opacity: 1;
    width: 24px;
    border-radius: 5px;
}

.hero-slide {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    display: block;
    text-decoration: none;
}

.hero-image-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.hero-slide:hover .hero-image {
    transform: scale(1.05);
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.2) 0%, transparent 50%);
    opacity: 0;
    transition: opacity 0.5s ease;
}

.hero-slide:hover .hero-overlay {
    opacity: 1;
}

@media (max-width: 960px) {
    .hero-banner-section {
        height: 350px;
    }
}

@media (max-width: 600px) {
    .hero-banner-section {
        height: 250px;
    }
    .hero-swiper :deep(.swiper-pagination) {
        bottom: 1.5rem;
    }
}

@media (max-width: 400px) {
    .hero-banner-section {
        height: 200px;
    }
    .hero-swiper :deep(.swiper-pagination-bullet) {
        width: 6px;
        height: 6px;
    }
    .hero-swiper :deep(.swiper-pagination-bullet-active) {
        width: 20px;
    }
}
</style>