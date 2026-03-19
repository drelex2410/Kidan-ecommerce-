<template>
  <section class="page-section editorial-quote-section">
    <div class="editorial-quote-shell">
      <blockquote v-if="quoteText" class="editorial-quote-block">
        <p class="editorial-quote-text">"{{ quoteText }}"</p>
        <footer v-if="author" class="editorial-quote-author">{{ author }}</footer>
      </blockquote>
    </div>
  </section>
</template>

<script>
export default {
  name: "PageEditorialQuoteSection",
  props: {
    section: {
      type: Object,
      required: true,
    },
  },
  computed: {
    quoteText() {
      return this.section.data?.quote_text || "";
    },
    author() {
      return this.section.data?.author || "";
    },
  },
};
</script>

<style scoped>
.editorial-quote-section {
  background: var(--editorial-bg, #f5f1ea);
  padding: clamp(3.5rem, 7vw, 5.5rem) 1.25rem clamp(4rem, 8vw, 6rem);
}

.editorial-quote-shell {
  max-width: 900px;
  margin: 0 auto;
}

.editorial-quote-block {
  margin: 0;
  text-align: center;
}

.editorial-quote-text {
  margin: 0;
  color: var(--editorial-ink, #13110f);
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: clamp(1.75rem, 3.2vw, 3rem);
  font-weight: 400;
  line-height: 1.45;
}

.editorial-quote-author {
  margin-top: 1.5rem;
  color: var(--editorial-muted, #6b655d);
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-style: normal;
}

@media (max-width: 599px) {
  .editorial-quote-text {
    line-height: 1.35;
  }
}
</style>
