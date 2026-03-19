<template>
  <div class="tab-layout tab-layout-basic">
    <h2 v-if="tab.content_title" class="tab-heading">{{ tab.content_title }}</h2>
    <div v-if="tab.content_body" class="tab-body" v-html="tab.content_body"></div>
    <a
      v-if="tab.button_text && tab.button_link"
      class="tab-button"
      :href="tab.button_link"
    >
      {{ tab.button_text }}
    </a>
  </div>
</template>

<script>
export default {
  name: "PageTabBasicContent",
  props: {
    tab: {
      type: Object,
      required: true,
    },
  },
};
</script>

<style scoped>
.tab-layout-basic {
  color: #171513;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
}

.tab-heading {
  margin: 0 0 1.1rem;
  font-size: clamp(1.5875rem, 2.2vw, 1.6875rem);
  font-weight: 700;
  line-height: 1.15;
}

.tab-body {
  max-width: 62rem;
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-weight: 400;
  line-height: 1.7;
}

.tab-body :deep(p) {
  margin: 0;
}

.tab-button {
  display: inline-flex;
  margin-top: 1.5rem;
  padding: 0.95rem 1.55rem;
  background: #99180d;
  color: #fff;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
}
</style>
