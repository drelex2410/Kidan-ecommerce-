<template>
  <section class="page-section vision-mission-section">
    <div class="vision-shell">
      <div class="vision-copy">
        <article class="vision-card">
          <h2>{{ section.data?.vision_title || "Vision" }}</h2>
          <p>{{ section.data?.vision_text }}</p>
        </article>
        <article class="vision-card">
          <h2>{{ section.data?.mission_title || "Mission" }}</h2>
          <p>{{ section.data?.mission_text }}</p>
        </article>
      </div>
      <div v-if="section.data?.image || section.data?.image_2" class="vision-media">
        <img :src="section.data?.image || section.data?.image_2" :alt="section.data?.title || 'Vision image'" />
      </div>
    </div>
  </section>
</template>

<script>
export default { props: { section: { type: Object, required: true } } };
</script>

<style scoped>
.vision-mission-section { padding: 1.5rem 0; }
.vision-shell { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: stretch; }
.vision-copy { display: grid; gap: 1rem; }
.vision-card {
  padding: 1.5rem;
  background: #e8d5c4;
  border-radius: 22px;
}
.vision-card h2 { margin: 0 0 .75rem; color: #24170e; font-size: clamp(1.4rem, 2.5vw, 2rem); }
.vision-card p { margin: 0; color: #4b3c31; line-height: 1.75; }
.vision-media img { width: 100%; height: 100%; min-height: 300px; object-fit: cover; border-radius: 22px; display: block; }
@media (min-width: 900px) {
  .vision-shell { grid-template-columns: minmax(0, 1fr) minmax(320px, .9fr); }
  .vision-copy { grid-template-rows: repeat(2, 1fr); }
}
</style>
