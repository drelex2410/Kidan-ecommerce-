<template>
    <div class="hero-banner-section">
        <div v-if="loading" class="banner-skeleton">
            <v-skeleton-loader type="image" height="600"></v-skeleton-loader>
        </div>
        <div v-else class="hero-slide">
            <div class="hero-image-wrapper">
                <img :src="banners[0]?.img" alt="Background Banner" @error="imageFallback($event)" class="hero-image" />
                <div class="hero-overlay"></div>
            </div>
            <div class="hero-content">
                <div class="hero-text-left">
                    <h2 class="hero-subtitle">{{ banners[0]?.subtitle || 'Walk into any' }}</h2>
                    <h2 class="hero-subtitle">{{ banners[0]?.subtitle2 || 'room & Own it.' }}</h2>
                </div>
                <div class="hero-center-image">
                    <img :src="banners[1]?.img" :alt="banners[1]?.title" @error="imageFallback($event)" class="center-product-img" />
                </div>
                <div class="hero-text-right">
                    <h1 class="hero-title">{{ banners[1]?.title }}</h1>
                    <router-link :to="banners[1]?.link || '/'" class="hero-cta">
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
    }),
    async created() {
        const res = await this.call_api("get", "setting/home/banner_section_two");
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
    height: 100vh;
    min-height: 600px;
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
    background: linear-gradient(to right,
            rgba(0, 0, 0, 0.6) 0%,
            rgba(0, 0, 0, 0.3) 50%,
            rgba(0, 0, 0, 0.6) 100%);
}

.hero-content {
    position: relative;
   
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 5%;
    max-width: 1800px;
    margin: 0 auto;
}

.hero-text-left {
    flex: 1;
    max-width: 400px;
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
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 5rem);
    font-weight: 900;
    color: #FFFBF3;
    margin: 0 0 2rem 0;
    line-height: 0.9;
    letter-spacing: -0.02em;
    text-transform: uppercase;
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
    .hero-content {
        padding: 0 3%;
    }

    .hero-center-image {
        margin: 0 1.5rem;
    }
}

@media (max-width: 960px) {
    .hero-banner-section {
        min-height: 500px;
    }

    .hero-content {
        flex-direction: column;
        justify-content: center;
        gap: 2rem;
        padding: 3rem 2rem;
    }

    .hero-text-left,
    .hero-text-right {
        max-width: 100%;
        text-align: center;
    }

    .hero-center-image {
        margin: 0;
        width: clamp(250px, 50vw, 350px);
        height: clamp(300px, 50vw, 420px);
    }

    .hero-title {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 600px) {
    .hero-banner-section {
        min-height: 600px;
    }

    .hero-content {
        padding: 2rem 1rem;
        gap: 1.5rem;
    }

    .hero-subtitle {
        font-size: 1.3rem;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-center-image {
        width: 220px;
        height: 280px;
    }

    .hero-cta {
        padding: 0.8rem 2rem;
        font-size: 0.8rem;
    }
}
</style>