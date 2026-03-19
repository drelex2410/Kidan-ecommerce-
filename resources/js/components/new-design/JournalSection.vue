<template>
  <section class="journal-section section">
    <div class="container">
      <div class="journal-container">
        <div class="journal-content">
          <h2 class="journal-heading">From Kidan Journal</h2>
          <h3 class="journal-title">African Fashion Trends for 2024</h3>
          <p class="journal-author">Akieno Danbur-Pepple | July 2025</p>
          <p class="journal-excerpt">
            Discover the latest in contemporary African fashion, where heritage meets modern luxury. 
            From bold prints to refined minimalism, explore how designers are redefining global style.
          </p>
          <button class="btn-primary journal-cta">Read More</button>
        </div>
        <div class="journal-image">
          <img 
          src="https://images.unsplash.com/photo-1616683693742-d0c8f2c8f6b7?w=600&h=700&fit=crop"
            alt="African fashion editorial"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// Journal section - static content component
</script>

<style scoped>
.journal-section {
  background-color: var(--bg-color);
  transition: background-color 0.3s ease;
}

.journal-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.journal-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.journal-heading {
  font-size: 1.75rem;
  font-weight: 300;
  color: var(--text-color);
  letter-spacing: 0.5px;
  margin-bottom: 0;
}

.journal-title {
  font-size: 2.5rem;
  font-weight: 400;
  color: var(--text-color);
  line-height: 1.2;
  letter-spacing: 0.5px;
}

.journal-author {
  font-family: 'Lato', sans-serif;
  font-size: 0.9rem;
  color: var(--accent-color);
  font-style: italic;
}

.journal-excerpt {
  font-family: 'Lato', sans-serif;
  font-size: 1.05rem;
  line-height: 1.8;
  color: var(--text-color);
  opacity: 0.85;
}

.journal-cta {
  align-self: flex-start;
  margin-top: 1rem;
}

.journal-image {
  position: relative;
  height: 500px;
  overflow: hidden;
  border-radius: 0;
  box-shadow: var(--shadow-md);
}

.journal-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.journal-image:hover img {
  transform: scale(1.05);
}

/* ===================================
   RESPONSIVE DESIGN
   =================================== */

@media (max-width: 1024px) {
  .journal-container {
    gap: 3rem;
  }

  .journal-title {
    font-size: 2rem;
  }

  .journal-image {
    height: 400px;
  }
}

@media (max-width: 768px) {
  .journal-container {
    grid-template-columns: 1fr;
    gap: 2rem;
  }

  .journal-content {
    text-align: center;
    align-items: center;
  }

  .journal-heading {
    font-size: 1.5rem;
  }

  .journal-title {
    font-size: 1.75rem;
  }

  .journal-excerpt {
    font-size: 1rem;
  }

  .journal-image {
    height: 350px;
  }
}
</style>