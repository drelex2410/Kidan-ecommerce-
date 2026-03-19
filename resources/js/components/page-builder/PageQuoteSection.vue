<template>
  <section class="page-section quote-section">
    <blockquote class="quote-shell">
      <p class="quote-text">"{{ section.data?.quote_text }}"</p>
      <footer v-if="section.data?.author" class="quote-author">- {{ section.data.author }}</footer>
    </blockquote>
  </section>
</template>

<script>
export default { props: { section: { type: Object, required: true } } };
</script>

<style scoped>
.quote-section { padding: 1rem 0 2rem; }
.quote-shell { margin: 0 auto; max-width: 820px; text-align: center; padding: 1rem; }
.quote-text { margin: 0; color: #2c2019; font-size: clamp(1.35rem, 3.5vw, 2.2rem); line-height: 1.5; }
.quote-author { margin-top: .85rem; color: #8b7260; text-transform: uppercase; letter-spacing: .18em; font-size: .78rem; }
</style>
