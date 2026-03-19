<template>
  <section class="page-section gallery-section">
    <h2 v-if="section.data?.title" class="gallery-title">{{ section.data.title }}</h2>
    <div class="gallery-grid">
      <div v-for="(image, index) in section.data?.gallery_images || []" :key="index" class="gallery-card">
        <img :src="image" :alt="`${section.data?.title || 'Gallery'} ${index + 1}`" />
      </div>
    </div>
  </section>
</template>

<script>
export default { props: { section: { type: Object, required: true } } };
</script>

<style scoped>
.gallery-section { padding: 1.5rem 0; }
.gallery-title { margin: 0 0 1rem; font-size: clamp(1.5rem, 3vw, 2.3rem); color: #24170e; }
.gallery-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
.gallery-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; display: block; border-radius: 22px; }
@media (min-width: 700px) { .gallery-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 1100px) { .gallery-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
</style>
