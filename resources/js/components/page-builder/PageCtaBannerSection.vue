<template>
  <section class="page-section cta-section" :class="backgroundClass">
    <div class="cta-shell">
      <div>
        <p v-if="section.data?.title" class="cta-kicker">{{ section.data.title }}</p>
        <h2 class="cta-heading">{{ section.data?.title || section.data?.heading }}</h2>
        <p v-if="section.data?.subtitle || section.data?.content" class="cta-copy">
          {{ section.data?.subtitle || section.data?.content }}
        </p>
      </div>
      <a v-if="section.data?.button_link && section.data?.button_text" class="cta-button" :href="section.data.button_link">
        {{ section.data.button_text }}
      </a>
    </div>
  </section>
</template>

<script>
export default {
  props: { section: { type: Object, required: true } },
  computed: {
    backgroundClass() {
      return `tone-${this.section.data?.background_style || "sand"}`;
    },
  },
};
</script>

<style scoped>
.cta-section { padding: 1.5rem 0 2.5rem; }
.cta-shell {
  display: grid; grid-template-columns: 1fr; gap: 1rem;
  padding: 1.5rem; border-radius: 26px; align-items: center;
}
.tone-sand .cta-shell { background: linear-gradient(135deg, #e8d5c4 0%, #f7ecdf 100%); }
.tone-dark .cta-shell { background: linear-gradient(135deg, #2f2119 0%, #4b3428 100%); color: #fff; }
.tone-light .cta-shell { background: #fff; border: 1px solid rgba(82,56,34,.08); }
.cta-kicker { margin: 0 0 .4rem; text-transform: uppercase; letter-spacing: .18em; font-size: .72rem; opacity: .72; }
.cta-heading { margin: 0; font-size: clamp(1.5rem, 3vw, 2.6rem); line-height: 1.15; }
.cta-copy { margin: .85rem 0 0; line-height: 1.7; max-width: 38rem; }
.cta-button {
  justify-self: start; display: inline-flex; padding: .95rem 1.35rem;
  border-radius: 999px; background: #24170e; color: #fff; text-decoration: none; font-weight: 700;
}
.tone-dark .cta-button { background: #fff; color: #2f2119; }
@media (min-width: 900px) {
  .cta-shell { grid-template-columns: 1fr auto; padding: 2.5rem; }
  .cta-button { justify-self: end; }
}
</style>
