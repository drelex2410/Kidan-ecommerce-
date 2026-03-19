<template>
  <section class="trending-section section">
    <div class="container">
      <h2 class="section-title">Trending Products</h2>
      <div class="products-grid">
        <div 
          v-for="product in products" 
          :key="product.id"
          class="product-card card"
        >
          <div class="product-image">
            <img :src="product.image" :alt="product.name" />
            <div class="product-overlay">
              <button class="btn-quick-view">Quick View</button>
            </div>
          </div>
          <div class="product-info">
            <h3 class="product-name">{{ product.name }}</h3>
            <p class="product-price">{{ formatPrice(product.price) }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';

const products = ref([
  {
    id: 1,
    name: 'Silk-Wrap Dress',
    price: 59900,
    image: "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=500&h=600&fit=crop&q=80",
  },
  {
    id: 2,
    name: 'Embroidered Tunic',
    price: 58900,
    image: 'https://images.unsplash.com/photo-1564859228273-274232fdb516?w=500&h=600&fit=crop'
  },
  {
    id: 3,
    name: 'Leather Crossbody Bag',
    price: 5500,
    image: 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=500&h=600&fit=crop'
  },
  {
    id: 4,
    name: 'Beaded Drop Earrings',
    price: 8090,
    image: 'https://images.unsplash.com/photo-1535632787350-4e68ef0ac584?w=500&h=600&fit=crop'
  }
]);

const formatPrice = (price) => {
  return `$${(price / 100).toFixed(2)}`;
};
</script>

<style scoped>
.trending-section {
  background-color: var(--bg-color);
  transition: background-color 0.3s ease;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
}

.product-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.product-card:hover {
  transform: translateY(-8px);
}

.product-image {
  position: relative;
  width: 100%;
  height: 400px;
  overflow: hidden;
  background-color: #f0f0f0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
  transform: scale(1.08);
}

.product-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
  opacity: 1;
}

.btn-quick-view {
  background-color: var(--nav-text);
  color: var(--accent-color);
  padding: 0.75rem 1.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  border: none;
  cursor: pointer;
  transform: translateY(20px);
  transition: all 0.3s ease 0.1s;
}

.product-card:hover .btn-quick-view {
  transform: translateY(0);
}

.btn-quick-view:hover {
  background-color: var(--accent-color);
  color: var(--nav-text);
}

.product-info {
  padding: 1.5rem 1rem;
  text-align: center;
  background-color: var(--card-bg);
  transition: background-color 0.3s ease;
}

.product-name {
  font-size: 1.1rem;
  font-weight: 400;
  color: var(--text-color);
  margin-bottom: 0.5rem;
  letter-spacing: 0.3px;
}

.product-price {
  font-size: 1rem;
  font-weight: 600;
  color: var(--accent-color);
  font-family: 'Lato', sans-serif;
}

/* ===================================
   RESPONSIVE DESIGN
   =================================== */

@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .product-image {
    height: 350px;
  }
}

@media (max-width: 768px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }

  .product-image {
    height: 280px;
  }

  .product-info {
    padding: 1rem 0.75rem;
  }

  .product-name {
    font-size: 1rem;
  }

  .product-price {
    font-size: 0.95rem;
  }
}

@media (max-width: 480px) {
  .products-grid {
    grid-template-columns: 1fr;
  }

  .product-image {
    height: 350px;
  }
}
</style>