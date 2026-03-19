<template>
  <section class="page-section rich-text-section">
    <div class="section-shell">
      <h2 v-if="section.data?.title" class="section-title">{{ section.data.title }}</h2>
      <div class="rich-copy" v-html="section.data?.content || section.data?.subtitle"></div>
    </div>
  </section>
</template>

<script>
export default { props: { section: { type: Object, required: true } } };
</script>

<style scoped>
.rich-text-section { padding: 1.5rem 0; }
.section-shell { max-width: 860px; margin: 0 auto; }
.section-title { margin-bottom: 1rem; font-size: clamp(1.6rem, 3vw, 2.5rem); color: #24170e; }
.rich-copy { color: #4d4035; line-height: 1.8; font-size: 1rem; }
.rich-copy :deep(h1), .rich-copy :deep(h2), .rich-copy :deep(h3) { color: #24170e; line-height: 1.2; margin-top: 1.2em; }
.rich-copy :deep(img) { max-width: 100%; height: auto; }
</style>
