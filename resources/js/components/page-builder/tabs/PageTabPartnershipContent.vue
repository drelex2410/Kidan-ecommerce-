<template>
  <div class="tab-layout partnership-layout">
    <section class="partnership-section">
      <h2 v-if="tab.intro_title" class="section-heading">{{ tab.intro_title }}</h2>
      <p v-if="tab.intro_body" class="section-copy">{{ tab.intro_body }}</p>
    </section>

    <section v-if="tab.content_title || benefits.length" class="partnership-section">
      <h3 v-if="tab.content_title" class="section-subheading">{{ tab.content_title }}</h3>
      <ul v-if="benefits.length" class="partnership-list">
        <li v-for="(item, index) in benefits" :key="`${item.title}-${index}`">
          {{ item.title || item.description }}
        </li>
      </ul>
    </section>

    <p v-if="tab.closing_body" class="section-copy">{{ tab.closing_body }}</p>

    <a
      v-if="tab.button_text && tab.button_link"
      class="partnership-button"
      :href="tab.button_link"
    >
      {{ tab.button_text }}
    </a>
  </div>
</template>

<script>
export default {
  name: "PageTabPartnershipContent",
  props: {
    tab: {
      type: Object,
      required: true,
    },
  },
  computed: {
    benefits() {
      return this.tab.items || [];
    },
  },
};
</script>

<style scoped>
.partnership-layout {
  color: #171513;
  font-family: Candara, Calibri, "Segoe UI", sans-serif;
  display: grid;
  gap: 2rem;
}

.section-heading {
  margin: 0 0 1rem;
  font-size: clamp(1.5875rem, 2.2vw, 1.6875rem);
  font-weight: 700;
  line-height: 1.15;
}

.section-subheading {
  margin: 0 0 1rem;
  font-size: clamp(1.5rem, 2.5vw, 1.75rem);
  font-weight: 400;
  line-height: 1.5;
}

.section-copy {
  margin: 0;
  font-size: clamp(1.125rem, 2.4vw, 1.5rem);
  line-height: 1.7;
  white-space: pre-line;
}

.partnership-list {
  margin: 0;
  padding-left: 1.35rem;
  display: grid;
  gap: 0.7rem;
  font-size: clamp(1.125rem, 2.4vw, 1.5rem);
  line-height: 1.55;
}

.partnership-button {
  justify-self: start;
  display: inline-flex;
  padding: 0.95rem 1.7rem;
  background: #99180d;
  color: #fff;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  letter-spacing: 0.03em;
}
</style>
