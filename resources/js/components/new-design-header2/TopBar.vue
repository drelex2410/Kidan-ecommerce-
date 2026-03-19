<template>
    <div class="topbar">
        <!-- Top Banner -->
        <div
            v-if="topBannerVisible && !loading && data.top_banner?.img"
            class="top-banner-container"
        >
            <dynamic-link
                :to="data.top_banner.link"
                append-class="banner-link"
            >
                <img 
                    :src="data.top_banner.img" 
                    alt="top banner"
                    class="banner-image"
                />
            </dynamic-link>
            <button
                class="banner-close-btn"
                @click="closeTopBanner"
                aria-label="Close banner"
            >
                <i class="las la-times"></i>
            </button>
        </div>

        <div class="topbar-divider-full" />
    </div>
</template>

<script>
export default {
    props: {
        loading: { type: Boolean, required: true, default: true },
        data: {
            type: Object,
            default: {},
        },
    },
    data: () => ({
        topBannerVisible: false,
    }),
    methods: {
        closeTopBanner() {
            this.topBannerVisible = false;
            this.setSession("shopTopBanner", "hidden");
        },
    },
    created() {
        if (this.checkSession("shopTopBanner") != "hidden") {
            this.topBannerVisible = true;
        }
    },
};
</script>

<style scoped>
.topbar {
    position: relative;
    z-index: 999;
    background-color: #b8a688 !important;
    border-bottom: none !important;
    color: #000 !important;
}

/* TOP BANNER */
.top-banner-container {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #2e1a1a 0%, #3d2222 100%);
    height: 50px;
}

.banner-link {
    display: block;
    line-height: 0;
    height: 100%;
    text-decoration: none;
}

.banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.banner-close-btn {
    position: absolute;
    top: 8px;
    right: 12px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    z-index: 2;
}

.banner-close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.05);
}

/* FULL WIDTH DIVIDER */
.topbar-divider-full {
    height: 1px;
    background: linear-gradient(90deg, transparent, #efefef, transparent);
    width: 100%;
}

@media (max-width: 768px) {
    .top-banner-container {
        height: 40px;
    }

    .topbar-divider-full {
        display: none;
    }
}
</style>
