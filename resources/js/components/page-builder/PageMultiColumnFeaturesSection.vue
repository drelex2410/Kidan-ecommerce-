<template>
  <section class="page-section features-section">
    <div class="features-shell">
      <div class="features-header">
        <h2 v-if="section.data?.title" class="features-title">{{ section.data.title }}</h2>
        <p v-if="section.data?.subtitle" class="features-subtitle">{{ section.data.subtitle }}</p>
      </div>
      <div class="features-grid">
        <article v-for="(item, index) in items" :key="index" class="feature-card">
          <img v-if="item.image" :src="item.image" :alt="item.title || 'Feature image'" class="feature-image" />
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
        </article>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  props: { section: { type: Object, required: true } },
  computed: {
    items() {
      return this.section.data?.items || [];
    },
  },
};
</script>

<style scoped>
.features-section { padding: 1.5rem 0; }
.features-header { margin-bottom: 1.25rem; }
.features-title { margin: 0 0 .5rem; font-size: clamp(1.6rem, 3vw, 2.5rem); color: #24170e; }
.features-subtitle { margin: 0; color: #6c594a; }
.features-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
.feature-card {
  padding: 1.25rem;
  border-radius: 20px;
  background: #fff;
  border: 1px solid rgba(82, 56, 34, .08);
  box-shadow: 0 18px 45px rgba(64, 42, 26, .06);
}
.feature-card h3 { margin: 0 0 .5rem; color: #24170e; font-size: 1.1rem; }
.feature-card p { margin: 0; line-height: 1.7; color: #5e4d41; }
.feature-image { width: 56px; height: 56px; object-fit: cover; border-radius: 14px; margin-bottom: .85rem; }
@media (min-width: 700px) { .features-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 1100px) { .features-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
</style>
