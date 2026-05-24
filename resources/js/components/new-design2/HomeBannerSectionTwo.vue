<template>
    <div class="hero-banner-section">
        <div v-if="loading" class="banner-skeleton">
            <v-skeleton-loader type="image" height="600"></v-skeleton-loader>
        </div>
        <div v-else class="hero-slide">
            <div class="hero-image-wrapper">
                <img :src="backgroundBanner?.img || fallbackImage" alt="Background Banner" @error="imageFallback($event)" class="hero-image" />
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <div class="hero-text-left">
                    <h2 class="hero-subtitle">{{ banners[0]?.subtitle || 'Walk into any' }}</h2>
                    <h2 class="hero-subtitle">{{ banners[0]?.subtitle2 || 'room & Own it.' }}</h2>
                </div>
                <div class="hero-center-image">
                    <img :src="productBanner?.img || fallbackImage" :alt="productBanner?.title || 'Featured product'" @error="imageFallback($event)" class="center-product-img" />
                </div>
                <div class="hero-text-right">
                    <h1 class="hero-title">{{ productBanner?.title || '' }}</h1>
                    <router-link :to="productBanner?.link || '/'" class="hero-cta">
                        <span>DISCOVER MORE</span>
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
        banners: [],
        fallbackImage: "/assets/img/placeholder.png",
    }),
    computed: {
        backgroundBanner() {
            return this.banners.find((banner) => banner.slot === "background") || null;
        },
        productBanner() {
            return this.banners.find((banner) => banner.slot === "product") || null;
        },
    },
    async created() {
        const res = await this.call_api("get", "setting/home/banner_section_two");
        if (res.data.success) {
            this.banners = Array.isArray(res.data.data) ? res.data.data : [];
            this.loading = false;
        }
    }
}
</script>

<style scoped>
.hero-banner-section {
    width: 100%;
    height: min(92svh, 860px);
    min-height: 620px;
    position: relative;
    overflow: hidden;
}

.banner-skeleton {
    width: 100%;
    height: 100%;
}

.hero-slide {
    position: relative;
    width: 100%;
    height: 100%;
}

.hero-image-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        linear-gradient(180deg,
            rgba(0, 0, 0, 0.1) 0%,
            rgba(0, 0, 0, 0.45) 100%),
        linear-gradient(to right,
            rgba(0, 0, 0, 0.68) 0%,
            rgba(0, 0, 0, 0.18) 50%,
            rgba(0, 0, 0, 0.72) 100%);
}

.hero-content {
    position: relative;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: clamp(1.5rem, 3vw, 3rem);
    padding: clamp(2rem, 4vw, 3.5rem) clamp(1rem, 5vw, 5rem);
    max-width: 1800px;
    margin: 0 auto;
}

.hero-text-left {
    flex: 1;
    max-width: 400px;
    min-width: 0;
}

.hero-subtitle {
    font-size: clamp(1.5rem, 3vw, 2.5rem);
    font-weight: 300;
    color: #FFFBF3;
    margin: 0;
    line-height: 1.3;
    letter-spacing: 0.02em;
}

.hero-center-image {
    flex: 0 0 auto;
    width: clamp(280px, 25vw, 400px);
    height: clamp(350px, 35vw, 500px);
    background: #FFFBF3;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    margin: 0 2rem;
}

.center-product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-text-right {
    flex: 1;
    max-width: 500px;
    text-align: left;
    min-width: 0;
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 5rem);
    font-weight: 900;
    color: #FFFBF3;
    margin: 0 0 2rem 0;
    line-height: 0.9;
    letter-spacing: -0.02em;
    text-transform: uppercase;
    text-wrap: balance;
}

.hero-cta {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: transparent;
    color: #ffffff;
    text-decoration: none;
    border: 2px solid #FFFBF3;
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 0.15em;
    transition: all 0.3s ease;
    text-transform: uppercase;
}

.hero-cta:hover {
    background: #FFFBF3;
    color: #000000;
    transform: translateY(-2px);
}

@media (max-width: 1200px) {
    .hero-banner-section {
        height: min(84svh, 780px);
        min-height: 580px;
    }

    .hero-content {
        padding-inline: clamp(1rem, 4vw, 2.5rem);
    }

    .hero-center-image {
        margin: 0 1.5rem;
    }
}

@media (max-width: 960px) {
    .hero-banner-section {
        height: auto;
        min-height: 0;
    }

    .hero-content {
        flex-direction: column;
        justify-content: center;
        gap: 1.75rem;
        padding: 3rem 1.5rem 3.5rem;
    }

    .hero-text-left,
    .hero-text-right {
        max-width: 100%;
        text-align: center;
    }

    .hero-center-image {
        margin: 0;
        width: min(100%, clamp(250px, 50vw, 350px));
        height: clamp(300px, 50vw, 420px);
    }

    .hero-title {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 600px) {
    .hero-banner-section {
        min-height: 0;
    }

    .hero-content {
        gap: 1.25rem;
        padding: 2.25rem 1rem 2.75rem;
    }

    .hero-subtitle {
        font-size: 1.3rem;
        line-height: 1.25;
    }

    .hero-title {
        font-size: 2rem;
        line-height: 0.95;
    }

    .hero-center-image {
        width: min(100%, 220px);
        height: 280px;
    }

    .hero-cta {
        padding: 0.8rem 2rem;
        font-size: 0.8rem;
    }
}
</style>
