<template>
  <div class="tab-layout youth-layout">
    <section class="youth-section">
      <h2 v-if="tab.intro_title" class="section-heading">{{ tab.intro_title }}</h2>
      <p v-if="tab.intro_body" class="section-copy">{{ tab.intro_body }}</p>
    </section>

    <section class="youth-feature-shell">
      <div class="youth-feature-list">
        <article
          v-for="(item, index) in items"
          :key="`${item.title}-${index}`"
          class="youth-feature-item"
        >
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
        </article>
      </div>

      <div v-if="tab.image" class="youth-feature-media">
        <img :src="tab.image" :alt="tab.intro_title || 'Kidan Youth'" />
      </div>
    </section>

    <p v-if="tab.closing_body" class="section-copy">{{ tab.closing_body }}</p>

    <div v-if="statementLines.length" class="youth-statements">
      <p v-for="(line, index) in statementLines" :key="`${line.text}-${index}`">
        {{ line.text }}
      </p>
    </div>

    <p v-if="tab.footer_text" class="section-copy">{{ tab.footer_text }}</p>
  </div>
</template>

<script>
export default {
  name: "PageTabYouthContent",
  props: {
    tab: {
      type: Object,
      required: true,
    },
  },
  computed: {
    items() {
      return this.tab.items || [];
    },
    statementLines() {
      return this.tab.statement_lines || [];
    },
  },
};
</script>

<style scoped>
.youth-layout {
  color: #171513;
  font-family: Candara, Calibri, "Segoe UI", sans-serif;
  display: grid;
  gap: 2.4rem;
}

.section-heading {
  margin: 0 0 1rem;
  font-size: clamp(1.5875rem, 2.2vw, 1.6875rem);
  font-weight: 700;
  line-height: 1.15;
}

.section-copy {
  margin: 0;
  font-size: clamp(1.125rem, 2.4vw, 1.5rem);
  line-height: 1.7;
  white-space: pre-line;
}

.youth-feature-shell {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(280px, 0.95fr);
  gap: 2rem;
  align-items: start;
}

.youth-feature-list {
  display: grid;
}

.youth-feature-item {
  padding: 1rem 0;
  border-top: 1px solid rgba(23, 21, 19, 0.18);
}

.youth-feature-item:first-child {
  border-top: 0;
  padding-top: 0;
}

.youth-feature-item h3 {
  margin: 0 0 0.4rem;
  font-size: clamp(1.45rem, 2.3vw, 1.85rem);
  font-weight: 700;
  line-height: 1.25;
}

.youth-feature-item p {
  margin: 0;
  color: #54514c;
  font-size: clamp(1rem, 1.75vw, 1.15rem);
  line-height: 1.55;
  white-space: pre-line;
}

.youth-feature-media img {
  display: block;
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
}

.youth-statements {
  display: grid;
  gap: 0.35rem;
}

.youth-statements p {
  margin: 0;
  font-size: clamp(1.35rem, 2.2vw, 1.8rem);
  font-weight: 700;
  line-height: 1.35;
}

@media (max-width: 899px) {
  .youth-feature-shell {
    grid-template-columns: 1fr;
  }
}
</style>
