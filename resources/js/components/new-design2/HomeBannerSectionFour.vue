<template>
    <div class="quad-banner-section">
        <div v-if="loading" class="banner-skeleton">
            <v-skeleton-loader type="image" height="100%"></v-skeleton-loader>
        </div>

        <div v-else class="banner-container">
            <div class="banner-grid">
                <swiper :slides-per-view="1" :space-between="0" :loop="true" :autoplay="carouselOption.autoplay"
                    :modules="modules" :pagination="{ clickable: true }" class="main-swiper">
                    <swiper-slide v-for="(banner, i) in swiperBanners" :key="i">
                        <router-link :to="banner.link || '/'" class="banner-slide">
                            <img :src="banner.img" :alt="`Banner ${i + 1}`" @error="imageFallback($event)" />
                        </router-link>
                    </swiper-slide>
                </swiper>

                <div class="newsletter-section" v-if="newsletterBanner">
                    <div class="newsletter-image">
                        <img :src="newsletterBanner.img" alt="Newsletter" @error="imageFallback($event)" />
                    </div>
                    <div class="newsletter-overlay">
                        <div class="newsletter-content">
                            <h3 class="newsletter-title">Stay up to date on the latest offers & trends.</h3>

                            <v-form @submit.prevent="subscribe()" class="newsletter-form">
                                <div class="input-wrapper">
                                    <v-text-field placeholder="Your Email" type="email" v-model="subscribeForm.email"
                                        hide-details="auto" required variant="outlined" class="email-input">
                                    </v-text-field>
                                    <button type="submit" class="submit-arrow-btn" :disabled="subscribeFormLoading">
                                        <i class="las la-arrow-right"></i>
                                    </button>
                                </div>

                                <p v-for="error of v$.subscribeForm.email.$errors" :key="error.$uid" class="error-text">
                                    {{ error.$message }}
                                </p>
                            </v-form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="testimonials-section">
                <swiper :slides-per-view="1" :space-between="30" :loop="true" :autoplay="testimonialAutoplay"
                    :modules="modules" :navigation="true" class="testimonials-swiper"
                    :breakpoints="testimonialBreakpoints">
                    <swiper-slide v-for="(testimonial, i) in testimonials" :key="i">
                        <div class="testimonial-card">
                            <div class="quote-icon">
                                <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                                    <circle cx="30" cy="30" r="30" fill="white" />
                                    <text x="30" y="42" text-anchor="middle" fill="#8b1a1a" font-size="36"
                                        font-family="Georgia, serif">"</text>
                                </svg>
                            </div>
                            <p class="testimonial-text">{{ testimonial.text }}</p>
                            <p class="testimonial-author">{{ testimonial.author }}</p>
                        </div>
                    </swiper-slide>
                </swiper>
            </div>
        </div>
    </div>
</template>

<script>
import { Autoplay, Pagination, Navigation } from 'swiper/modules';
import { Swiper, SwiperSlide } from "swiper/vue";
import { useVuelidate } from "@vuelidate/core";
import { email, required } from "@vuelidate/validators";

export default {
    components: {
        Swiper,
        SwiperSlide,
    },
    setup() {
        return {
            modules: [Autoplay, Pagination, Navigation],
            v$: useVuelidate(),
        };
    },
    data: () => ({
        loading: true,
        banners: [],
        swiperBanners: [],
        newsletterBanner: null,
        subscribeForm: { email: "" },
        subscribeFormLoading: false,
        carouselOption: {
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
        },
        testimonialAutoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        testimonialBreakpoints: {
            0: {
                slidesPerView: 1,
            },
            960: {
                slidesPerView: 3,
            },
        },
        testimonials: [
            {
                text: "Fast delivery and exactly what I ordered! The packaging was beautiful too, it really added to the experience. Couldn't be happier with my order.",
                author: "Oladapo Abioye"
            },
            {
                text: "Loved the shopping experience! Everything was so easy to find, the layout was super clean, and checkout was quick and stress-free. I'll definitely be back for more.",
                author: "Gold D. Roger"
            },
            {
                text: "Customer service was amazing! I had a quick question about sizing, and they responded almost immediately with helpful advice. Great team and great experience.",
                author: "Sir Lewis Hamilton"
            }
        ]
    }),
    validations() {
        return {
            subscribeForm: {
                email: { required, email },
            },
        };
    },
    methods: {
        async subscribe() {
            const isValid = await this.v$.$validate();
            if (!isValid) return;
            this.subscribeFormLoading = true;
            const res = await this.call_api("post", "subscribe", this.subscribeForm);
            this.snack({ message: res.data.message });
            this.subscribeFormLoading = false;
            if (res.data.success) {
                this.subscribeForm.email = "";
            }
        },
        imageFallback(event) {
            event.target.src = '/assets/img/placeholder.png';
        }
    },
    async created() {
        const res = await this.call_api("get", "setting/home/banner_section_four");
        if (res.data.success) {
            this.banners = res.data.data;
            if (this.banners.length > 0) {
                this.newsletterBanner = this.banners[this.banners.length - 1];
                this.swiperBanners = this.banners.slice(0, this.banners.length - 1);
            } else {
                this.swiperBanners = [];
                this.newsletterBanner = null;
            }
            this.loading = false;
        }

    }
}
</script>

<style scoped>
.quad-banner-section {
    width: 100%;
    position: relative;
    background: #FFFBF3;
    overflow: hidden;
}

.banner-skeleton {
    width: 100%;
    height: 700px;
}

.banner-container {
    width: 100%;
}

.banner-grid {
    width: 100%;
    height: 700px;
    position: relative;
}

.main-swiper {
    width: 100%;
    height: 100%;
    position: relative;
}

.main-swiper :deep(.swiper-pagination) {
    bottom: 2rem;
}

.main-swiper :deep(.swiper-pagination-bullet) {
    width: 8px;
    height: 8px;
    background: #FFFBF3;
    opacity: 0.6;
    transition: all 0.3s ease;
}

.main-swiper :deep(.swiper-pagination-bullet-active) {
    opacity: 1;
    width: 24px;
    border-radius: 4px;
}

.banner-slide {
    width: 100%;
    height: 100%;
    display: block;
    position: relative;
    overflow: hidden;
}

.banner-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

/* Newsletter Section - Corrected */
.newsletter-section {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    height: 600px;
    z-index: 10;
    display: flex;
    flex-direction: column;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.newsletter-image {
    width: 100%;
    height: 350px;
    flex-shrink: 0;
    overflow: hidden;
}

.newsletter-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.newsletter-overlay {
    flex: 1;
    width: 100%;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    padding: 24px 32px;
    display: flex;
    align-items: center;
    pointer-events: auto;
}

.newsletter-content {
    width: 100%;
}

.newsletter-title {
    font-size: 17px;
    font-weight: 500;
    color: #000;
    margin-bottom: 16px;
    line-height: 1.4;
    text-align: center;
}

.newsletter-form {
    width: 100%;
}

.input-wrapper {
    position: relative;
    width: 100%;
}

.email-input {
    width: 100%;
}

.email-input :deep(.v-field) {
    border-radius: 4px;
    border: 1px solid #d4d4d4;
    background: #FFFBF3;
    padding-right: 56px;
    min-height: 48px;
}

.email-input :deep(.v-field__input) {
    padding: 12px 16px;
    font-size: 14px;
    color: #666;
    min-height: 48px;
}

.email-input :deep(.v-field--focused) {
    border-color: #000;
}

.email-input :deep(.v-field__outline) {
    display: none;
}

.submit-arrow-btn {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.submit-arrow-btn:hover {
    transform: translateY(-50%) translateX(4px);
}

.submit-arrow-btn i {
    font-size: 22px;
    color: #8b1a1a;
}

.submit-arrow-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.error-text {
    color: #ff4444;
    font-size: 12px;
    margin-top: 6px;
    text-align: center;
}

/* Testimonials Section */
.testimonials-section {
    background: #8b1a1a;
    padding: 60px 40px;
    position: relative;
}

.testimonials-swiper {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.testimonials-swiper :deep(.swiper-button-prev),
.testimonials-swiper :deep(.swiper-button-next) {
    color: #fff;
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transition: all 0.3s ease;
}

.testimonials-swiper :deep(.swiper-button-prev):hover,
.testimonials-swiper :deep(.swiper-button-next):hover {
    background: rgba(255, 255, 255, 0.2);
}

.testimonials-swiper :deep(.swiper-button-prev)::after,
.testimonials-swiper :deep(.swiper-button-next)::after {
    font-size: 20px;
}

.testimonial-card {
    text-align: center;
    padding: 20px;
    color: #fff;
}

.quote-icon {
    margin: 0 auto 20px;
    width: 60px;
    height: 60px;
}

.testimonial-text {
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 20px;
    color: rgba(255, 255, 255, 0.95);
}

.testimonial-author {
    font-size: 16px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
}

/* Responsive Design */
@media (max-width: 1400px) {
    .banner-grid {
        height: 650px;
    }
    
    .banner-skeleton {
        height: 650px;
    }
}

@media (max-width: 1200px) {
    .banner-grid {
        height: 600px;
    }
    
    .banner-skeleton {
        height: 600px;
    }
    
    .newsletter-section {
        width: 450px;
        height: 550px;
    }
    
    .newsletter-image {
        height: 320px;
    }
    
    .newsletter-overlay {
        padding: 22px 28px;
    }
    
    .newsletter-title {
        font-size: 16px;
        margin-bottom: 14px;
    }
    
    .testimonials-section {
        padding: 50px 30px;
    }
}

@media (max-width: 960px) {
    .banner-grid {
        height: 550px;
    }
    
    .banner-skeleton {
        height: 550px;
    }
    
    .newsletter-section {
        width: 400px;
        height: 480px;
    }
    
    .newsletter-image {
        height: 270px;
    }
    
    .main-swiper :deep(.swiper-pagination) {
        bottom: 1.5rem;
    }
    
    .newsletter-overlay {
        padding: 20px 24px;
    }
    
    .newsletter-title {
        font-size: 15px;
        margin-bottom: 14px;
    }
    
    .testimonials-section {
        padding: 40px 20px;
    }
    
    .testimonial-text {
        font-size: 15px;
    }
    
    .testimonial-author {
        font-size: 15px;
    }
}

@media (max-width: 768px) {
    .banner-grid {
        height: 500px;
    }
    
    .banner-skeleton {
        height: 500px;
    }
    
    .newsletter-section {
        width: 85%;
        max-width: 360px;
        height: 420px;
    }
    
    .newsletter-image {
        height: 240px;
    }
}

@media (max-width: 600px) {
    .banner-grid {
        height: 450px;
    }
    
    .banner-skeleton {
        height: 450px;
    }
    
    .newsletter-section {
        width: 85%;
        max-width: 320px;
        height: 380px;
    }
    
    .newsletter-image {
        height: 210px;
    }
    
    .main-swiper :deep(.swiper-pagination) {
        bottom: 1rem;
    }
    
    .main-swiper :deep(.swiper-pagination-bullet) {
        width: 6px;
        height: 6px;
    }
    
    .main-swiper :deep(.swiper-pagination-bullet-active) {
        width: 20px;
    }
    
    .newsletter-overlay {
        padding: 16px 18px;
    }
    
    .newsletter-title {
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .email-input :deep(.v-field) {
        min-height: 42px;
    }
    
    .email-input :deep(.v-field__input) {
        padding: 10px 14px;
        font-size: 13px;
        min-height: 42px;
    }
    
    .submit-arrow-btn {
        width: 32px;
        height: 32px;
    }
    
    .submit-arrow-btn i {
        font-size: 18px;
    }
    
    .testimonials-section {
        padding: 35px 15px;
    }
    
    .testimonial-text {
        font-size: 14px;
    }
    
    .testimonial-author {
        font-size: 14px;
    }
    
    .quote-icon {
        width: 50px;
        height: 50px;
    }
    
    .testimonials-swiper :deep(.swiper-button-prev),
    .testimonials-swiper :deep(.swiper-button-next) {
        width: 40px;
        height: 40px;
    }
    
    .testimonials-swiper :deep(.swiper-button-prev)::after,
    .testimonials-swiper :deep(.swiper-button-next)::after {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .banner-grid {
        height: 400px;
    }
    
    .banner-skeleton {
        height: 400px;
    }
    
    .newsletter-section {
        width: 90%;
        max-width: 280px;
        height: 340px;
    }
    
    .newsletter-image {
        height: 190px;
    }
    
    .newsletter-overlay {
        padding: 14px 16px;
    }
    
    .newsletter-title {
        font-size: 13px;
        margin-bottom: 10px;
    }
}

@media (max-width: 400px) {
    .banner-grid {
        height: 380px;
    }
    
    .banner-skeleton {
        height: 380px;
    }
    
    .newsletter-section {
        width: 92%;
        max-width: 260px;
        height: 320px;
    }
    
    .newsletter-image {
        height: 180px;
    }
    
    .newsletter-title {
        font-size: 12px;
        margin-bottom: 8px;
    }
    
    .newsletter-overlay {
        padding: 12px 14px;
    }
    
    .email-input :deep(.v-field) {
        min-height: 40px;
    }
    
    .email-input :deep(.v-field__input) {
        padding: 8px 12px;
        font-size: 12px;
        min-height: 40px;
    }
    
    .submit-arrow-btn {
        width: 30px;
        height: 30px;
    }
    
    .submit-arrow-btn i {
        font-size: 16px;
    }
}
</style>