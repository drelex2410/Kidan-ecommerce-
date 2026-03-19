<template>
  <section class="page-section hero-section" :class="`align-${alignment}`">
    <div class="hero-shell">
      <div class="hero-copy">
        <p v-if="section.data?.title" class="eyebrow">{{ section.data.title }}</p>
        <h1 class="hero-heading">{{ heading }}</h1>
        <p v-if="subheading" class="hero-subheading">{{ subheading }}</p>
        <a v-if="section.data?.button_link && section.data?.button_text" :href="section.data.button_link" class="hero-button">
          {{ section.data.button_text }}
        </a>
      </div>
      <div v-if="section.data?.image" class="hero-media">
        <img :src="section.data.image" :alt="heading" />
      </div>
    </div>
  </section>
</template>

<script>
export default {
  props: { section: { type: Object, required: true } },
  computed: {
    heading() {
      return this.section.data?.heading || this.section.data?.title || "";
    },
    subheading() {
      return this.section.data?.subheading || this.section.data?.subtitle || "";
    },
    alignment() {
      return this.section.data?.alignment || "left";
    },
  },
};
</script>

<style scoped>
.hero-section { padding: 2.5rem 0 1.5rem; }
.hero-shell {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  align-items: center;
  background: linear-gradient(135deg, #f6ede2 0%, #fffaf3 100%);
  border-radius: 28px;
  overflow: hidden;
}
.hero-copy { padding: 1.5rem; }
.eyebrow { margin: 0 0 .75rem; text-transform: uppercase; letter-spacing: .18em; font-size: .75rem; color: #8c6b4e; }
.hero-heading { margin: 0; font-size: clamp(2rem, 5vw, 4rem); line-height: 1.05; color: #24170e; }
.hero-subheading { margin: 1rem 0 0; max-width: 40rem; font-size: 1rem; line-height: 1.7; color: #5c4738; }
.hero-button {
  display: inline-flex; margin-top: 1.25rem; padding: .9rem 1.3rem; border-radius: 999px;
  background: #2d1f17; color: #fff; text-decoration: none; font-weight: 700;
}
.hero-media img { width: 100%; height: 100%; min-height: 280px; object-fit: cover; display: block; }
.align-center .hero-copy { text-align: center; }
.align-right .hero-copy { text-align: right; margin-left: auto; }
@media (min-width: 768px) {
  .hero-shell { grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr); }
  .hero-copy { padding: 2.5rem; }
}
@media (min-width: 1200px) {
  .hero-copy { padding: 4rem; }
  .hero-media img { min-height: 420px; }
}
</style>
