<template>
    <div class="wishlist-container">
        <h1 class="wishlist-title">{{ $t("my_wishlist") }}</h1>

        <div v-if="getWislistProducts.length > 0" class="products-grid">
            <div v-for="(product, i) in getWislistProducts" :key="i" class="product-card-wrapper">
                <product-box :product-details="product" :is-loading="!wislistLoaded" />
            </div>
        </div>

        <div v-else class="empty-state">
            <div class="heart-icon">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path
                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                    </path>
                </svg>
            </div>
            <p class="empty-message">Your Wishlist is empty.</p>
            <button class="shop-btn" @click="shopNewArrivals">
                SHOP NEW ARRIVALS
            </button>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
    data: () => ({
        currentPage: 1,
        totalPages: 1,
    }),
    computed: {
        ...mapGetters("wishlist", [
            "wislistLoaded",
            "getWislistProducts"
        ]),
    },
    methods: {
        shopNewArrivals() {
            this.$router.push({ name: "Home" });
        }
    }
}
</script>

<style scoped>
.wishlist-container {
    padding: 24px 0;
    padding-left: 56px;
    max-width: 100%;
    min-height: 60vh;
}

.wishlist-title {
    font-size: 24px;
    font-weight: 700;
    opacity: 0.8;
    margin-bottom: 40px;
    margin-top: 24px;
    display: none;
    /* Hidden when empty state is shown in the design */
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 234px);
    gap: 16px;
    justify-content: start;
}

/* Responsive breakpoints */
@media (max-width: 1400px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
        max-width: 1000px;
    }
}

@media (max-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
        max-width: 750px;
    }

    .wishlist-container {
        padding-left: 32px;
    }
}

@media (max-width: 900px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
        max-width: 720px;
    }
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 500px;
    }

    .wishlist-container {
        padding-left: 16px;
        padding-right: 16px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .wishlist-title {
        font-size: 20px;
        margin-bottom: 24px;
    }
}

.product-card-wrapper {
    width: 100%;
    max-width: 234px;
    height: 362px;
}

/* Empty State Styles */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 80px 20px;
    min-height: 500px;
}

.heart-icon {
    margin-bottom: 32px;
    color: #000;
    opacity: 0.8;
}

.heart-icon svg {
    width: 120px;
    height: 120px;
}

.empty-message {
    font-size: 18px;
    color: #333;
    margin-bottom: 32px;
    font-weight: 400;
}

.shop-btn {
    background-color: #8B0000;
    color: white;
    border: none;
    padding: 14px 32px;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
    cursor: pointer;
    border-radius: 2px;
    transition: background-color 0.3s ease;
    text-transform: uppercase;
}

.shop-btn:hover {
    background-color: #6d0000;
}

.shop-btn:active {
    transform: translateY(1px);
}
</style>