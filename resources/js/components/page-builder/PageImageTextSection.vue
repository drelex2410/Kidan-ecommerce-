<template>
  <section class="page-section split-section">
    <div class="split-shell" :class="`image-${imagePosition}`">
      <div class="split-media" v-if="section.data?.image">
        <img :src="section.data.image" :alt="section.data?.title || 'Page image'" />
      </div>
      <div class="split-copy">
        <h2 v-if="section.data?.title" class="split-title">{{ section.data.title }}</h2>
        <p v-if="section.data?.subtitle" class="split-subtitle">{{ section.data.subtitle }}</p>
        <div class="split-body" v-html="section.data?.content"></div>
        <a v-if="section.data?.button_link && section.data?.button_text" class="split-link" :href="section.data.button_link">
          {{ section.data.button_text }}
        </a>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  props: { section: { type: Object, required: true } },
  computed: {
    imagePosition() {
      return this.section.data?.image_position || "left";
    },
  },
};
</script>

<style scoped>
.split-section { padding: 1.5rem 0; }
.split-shell {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  align-items: stretch;
}
.split-media img { width: 100%; height: 100%; min-height: 280px; object-fit: cover; border-radius: 22px; display: block; }
.split-copy {
  background: #f0e0d1;
  border-radius: 22px;
  padding: 1.5rem;
  color: #3e2d24;
}
.split-title { margin: 0 0 .75rem; font-size: clamp(1.5rem, 3vw, 2.4rem); color: #24170e; }
.split-subtitle { margin: 0 0 .75rem; color: #7c6451; }
.split-body { line-height: 1.75; }
.split-link { display: inline-flex; margin-top: 1rem; color: #24170e; font-weight: 700; text-decoration: none; }
@media (min-width: 768px) {
  .split-shell { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
  .split-shell.image-right .split-media { order: 2; }
  .split-copy { padding: 2.25rem; }
}
</style>
