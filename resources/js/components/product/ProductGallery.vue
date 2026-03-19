<template>
    <div>
        <template v-if="isLoading">
            <v-skeleton-loader
                type="image"
                class="mb-4"
                height="420"
            ></v-skeleton-loader>
            <v-row class="gutters-10">
                <v-col>
                    <v-skeleton-loader
                        type="image"
                        class="mb-2"
                        height="90"
                    ></v-skeleton-loader>
                </v-col>
                <v-col>
                    <v-skeleton-loader
                        type="image"
                        class="mb-2"
                        height="90"
                    ></v-skeleton-loader>
                </v-col>
                <v-col>
                    <v-skeleton-loader
                        type="image"
                        class="mb-2"
                        height="90"
                    ></v-skeleton-loader>
                </v-col>
                <v-col>
                    <v-skeleton-loader
                        type="image"
                        class="mb-2"
                        height="90"
                    ></v-skeleton-loader>
                </v-col>
            </v-row>
        </template>
        <div
            class="product-gallery-shell"
            :style="galleryStyle"
            v-show="!isLoading"
        >
            <!--  -->
            
                <swiper 
                    :style="{
                        '--swiper-navigation-color': '#fff',
                        '--swiper-pagination-color': '#fff',
                    }"
                    :thumbs="{ swiper: thumbsSwiper }"
                    :spaceBetween="10"
                    :navigation="true"
                    :modules="modules"
                    class="mySwiper2 border-thin"
                >
                    <swiper-slide v-for="(photo, i) in galleryImgaes" :key="i">
                        <ProductImageZoom :imageSrc="selectedVariation.image ? selectedVariation.image: photo"/>
                    </swiper-slide>
                </swiper>
            

           
            <swiper
                @swiper="setThumbsSwiper"
                :spaceBetween="10"
                :slidesPerView="4"
                :freeMode="true"
                :watchSlidesProgress="true"
                :modules="modules"
                class="mySwiper"
            >
                <swiper-slide v-for="(photo, i) in galleryImgaes" :key="i">
                    <img :src="photo" class="border-thin" />
                </swiper-slide>

            </swiper>
        </div>
    </div>
</template>

<script>
import { ref } from "vue";
// Import Swiper Vue.js components
import { Swiper, SwiperSlide } from "swiper/vue";

// zoom
import ProductImageZoom from '../../components/ProductImageZoom.vue';

// import required modules
import { FreeMode, Navigation, Thumbs } from "swiper/modules";
export default {
    props: {
        isLoading: { type: Boolean, default: true },
        galleryImgaes: { type: Array, required: true, default: () => [] },
        selectedVariation: { type: Object, default: () => {} },
        desktopImageWidth: { type: Number, default: null },
        desktopImageHeight: { type: Number, default: null },
    },
    components: {
        Swiper,
        SwiperSlide,
        ProductImageZoom
    },
    computed: {
        galleryStyle() {
            const width = this.desktopImageWidth ? `${this.desktopImageWidth}px` : "100%";
            const height = this.desktopImageHeight ? `${this.desktopImageHeight}px` : "auto";

            return {
                "--gallery-desktop-width": width,
                "--gallery-desktop-height": height,
            };
        },
    },
    setup() {
        const thumbsSwiper = ref(null);

        const setThumbsSwiper = (swiper) => {
            thumbsSwiper.value = swiper;
        };

        return {
            thumbsSwiper,
            setThumbsSwiper,
            modules: [FreeMode, Navigation, Thumbs],
        };
    },
};
</script>

<style scoped>
.product-gallery-shell {
    width: 100%;
}

.swiper {
    width: 100%;
    height: 100%;
}

.swiper-slide {
    text-align: center;
    font-size: 18px;
    background: #fff;

    /* Center slide text vertically */
    display: flex;
    justify-content: center;
    align-items: center;
}

.swiper-slide img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.swiper {
    width: 100%;
    height: 300px;
    margin-left: auto;
    margin-right: auto;
}

.swiper-slide {
    background-size: cover;
    background-position: center;
}

.mySwiper2 {
    width: 100%;
    aspect-ratio: 520 / 680;
    height: auto;
    width: 100%;
}

.mySwiper {
    height: auto;
    box-sizing: border-box;
    padding: 14px 0 0;
}

.mySwiper .swiper-slide {
    width: 25%;
    height: auto;
    aspect-ratio: 1 / 1.15;
    opacity: 0.4;
}

.mySwiper .swiper-slide-thumb-active {
    opacity: 1;
}

.swiper-slide img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

:deep(.mySwiper2 .image-container) {
    height: 100%;
}

:deep(.mySwiper2 .product-image) {
    object-fit: cover;
}

@media (min-width: 1264px) {
    .product-gallery-shell,
    .mySwiper,
    .mySwiper2 {
        max-width: var(--gallery-desktop-width, 100%);
    }

    .mySwiper2 {
        height: var(--gallery-desktop-height, auto);
        aspect-ratio: auto;
    }
}
</style>
