<template>
    <section class="featured-section section">
        <div class="container">
            <h2 class="section-title">Featured Collections</h2>
            <div class="collections-grid">
                <div v-for="collection in collections" :key="collection.id" class="collection-card card">
                    <div class="collection-image">
                        <img :src="collection.image" :alt="collection.title" />
                        <div class="collection-overlay">
                            <button class="btn-primary collection-btn">Shop Now</button>
                        </div>
                    </div>
                    <div class="collection-info">
                        <h3 class="collection-title">{{ collection.title }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const collections = ref([
    {
        id: 1,
        title: 'Modern Royalty',
        image: 'https://images.unsplash.com/photo-1617019114583-affb34d1b3cd?w=600&h=800&fit=crop'
    },
    {
        id: 2,
        title: 'Designer Spotlight',
        image:
            "https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=600&h=800&fit=crop&q=80",
    },
    {
        id: 3,
        title: 'Seasonal Picks',
        image: 'https://images.unsplash.com/photo-1609709295948-17d77cb2a69b?w=600&h=800&fit=crop'
    }
]);
</script>

<style scoped>
.featured-section {
    background-color: var(--bg-color);
    transition: background-color 0.3s ease;
}

.collections-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.collection-card {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.collection-card:hover {
    transform: translateY(-8px);
}

.collection-image {
    position: relative;
    width: 100%;
    height: 500px;
    overflow: hidden;
}

.collection-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.collection-card:hover .collection-image img {
    transform: scale(1.05);
}

.collection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.collection-card:hover .collection-overlay {
    opacity: 1;
}

.collection-btn {
    transform: translateY(20px);
    transition: transform 0.3s ease 0.1s;
}

.collection-card:hover .collection-btn {
    transform: translateY(0);
}

.collection-info {
    padding: 1.5rem;
    text-align: center;
    background-color: var(--card-bg);
    transition: background-color 0.3s ease;
}

.collection-title {
    font-size: 1.5rem;
    font-weight: 300;
    letter-spacing: 0.5px;
    color: var(--text-color);
}

/* ===================================
   RESPONSIVE DESIGN
   =================================== */

@media (max-width: 1024px) {
    .collections-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .collection-image {
        height: 400px;
    }

    .collection-title {
        font-size: 1.3rem;
    }
}

@media (max-width: 768px) {
    .collections-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .collection-image {
        height: 350px;
    }

    .collection-info {
        padding: 1.25rem;
    }

    .collection-title {
        font-size: 1.2rem;
    }
}
</style>
