<template>
    <div class="products-section">
        <v-container class="products-container">
            <div class="content-wrapper">
                <div class="promo-banner">
                    <div class="banner-content">
                        <p class="banner-items">{{ products.length }} Items</p>
                        <h2 class="banner-title">{{ title }}</h2>
                        <p class="banner-description">
                            Discover our exclusive beauty collection, crafted<br />to enhance your natural radiance and confidence.
                        </p>
                        <dynamic-link to="/women-beauty" append-class="discover-btn-wrapper">
                            <button class="discover-btn">DISCOVER MORE</button>
                        </dynamic-link>
                    </div>
                </div>

                <div class="products-slider-container">
                    <button class="slider-arrow slider-arrow-left" @click="slideLeft">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>

                    <div v-if="loading" class="products-slider">
                        <div v-for="i in 4" :key="i" class="product-skeleton">
                            <v-skeleton-loader type="image" height="400"></v-skeleton-loader>
                            <v-skeleton-loader type="text" class="mt-3"></v-skeleton-loader>
                            <v-skeleton-loader type="text" width="60%"></v-skeleton-loader>
                        </div>
                    </div>

                    <div v-else class="products-slider" ref="slider">
                        <transition-group name="slide-fade" tag="div" class="slider-transition-wrapper">
                            <product-box v-for="(product, i) in visibleProducts" :key="product.id || i"
                                :product-details="product" :is-loading="loading" class="slider-item" />
                        </transition-group>
                    </div>

                    <button class="slider-arrow slider-arrow-right" @click="slideRight">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>
        </v-container>
    </div>
</template>

<script>
export default {
    data: () => ({
        loading: true,
        title: "",
        products: [],
        currentIndex: 0,
        visibleCount: 4,
        slideDirection: 'right'
    }),
    computed: {
        visibleProducts() {
            if (this.products.length <= this.visibleCount) {
                return this.products;
            }

            const endIndex = this.currentIndex + this.visibleCount;
            if (endIndex <= this.products.length) {
                return this.products.slice(this.currentIndex, endIndex);
            } else {
                return [
                    ...this.products.slice(this.currentIndex),
                    ...this.products.slice(0, endIndex - this.products.length)
                ];
            }
        }
    },
    async created() {
        try {
            const res = await this.call_api("get", "setting/home/product_section_five");
            if (res.data.success) {
                this.title = res.data.data.title;
                this.products = res.data.data.products.data;
                this.loading = false;
            }
        } catch (error) {
            console.error("Error fetching women & beauty products:", error);
            this.loading = false;
        }
    },
    methods: {
        slideLeft() {
            if (this.products.length <= this.visibleCount) return;

            this.slideDirection = 'left';
            this.currentIndex--;
            if (this.currentIndex < 0) {
                this.currentIndex = this.products.length - this.visibleCount;
            }
        },
        slideRight() {
            if (this.products.length <= this.visibleCount) return;

            this.slideDirection = 'right';
            this.currentIndex++;
            if (this.currentIndex > this.products.length - this.visibleCount) {
                this.currentIndex = 0;
            }
        }
    }
};
</script>

<style scoped>
.products-section {
    background: #fff;
    padding: 4rem 0;
}

.products-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 3rem;
}

.content-wrapper {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

.promo-banner {
    border-radius: 12px;
    padding: 2.5rem 2rem;
    flex-shrink: 0;
    width: 320px;
    background: transparent;
    height: fit-content;
}

.banner-items {
    font-size: 0.875rem;
    color: #666;
    margin: 0 0 0.5rem;
}

.banner-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 1rem;
}

.banner-description {
    font-size: 0.95rem;
    color: #666;
    line-height: 1.6;
    margin: 0 0 2rem;
}

.discover-btn-wrapper {
    display: block;
    text-decoration: none;
}

.discover-btn {
    background: #8b0000;
    color: white;
    border: none;
    padding: 0.875rem 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 1px;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.3s ease;
    width: 100%;
}

.discover-btn:hover {
    background: #a00000;
}

.products-slider-container {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
}

.products-slider {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    flex: 1;
    width: 100%;
    overflow: hidden;
}

.slider-transition-wrapper {
    display: contents;
}

.slider-item {
    transition: all 0.5s ease;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.5s ease;
}

.slide-fade-enter-from {
    opacity: 0;
    transform: translateX(30px);
}

.slide-fade-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

.slide-fade-enter-to,
.slide-fade-leave-from {
    opacity: 1;
    transform: translateX(0);
}

.slide-fade-move {
    transition: transform 0.5s ease;
}

.slider-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.slider-arrow:hover {
    background: #f5f5f5;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.slider-arrow-left {
    left: -24px;
}

.slider-arrow-right {
    right: -24px;
}

.slider-arrow svg {
    color: #333;
}

.product-skeleton {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    padding: 1rem;
    display: flex;
    flex-direction: column;
}

.products-slider ::v-deep .product-box {
    height: 100%;
    display: flex;
    flex-direction: column;
}

@media (max-width: 1400px) {
    .products-slider {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 1200px) {
    .content-wrapper {
        flex-direction: column;
    }

    .promo-banner {
        width: 100%;
    }

    .products-slider {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 960px) {
    .products-slider {
        grid-template-columns: repeat(3, 1fr);
    }

    .products-container {
        padding: 0 2rem;
    }

    .slider-arrow-left {
        left: -16px;
    }

    .slider-arrow-right {
        right: -16px;
    }
}

@media (max-width: 768px) {
    .products-slider {
        grid-template-columns: repeat(2, 1fr);
    }

    .slider-arrow {
        width: 40px;
        height: 40px;
    }
}

@media (max-width: 600px) {
    .products-container {
        padding: 0 1rem;
    }

    .products-section {
        padding: 2.5rem 0;
    }

    .promo-banner {
        padding: 2rem 1.5rem;
    }

    .banner-title {
        font-size: 1.5rem;
    }

    .products-slider {
        gap: 1rem;
    }

    .slider-arrow {
        display: none;
    }
}
</style>