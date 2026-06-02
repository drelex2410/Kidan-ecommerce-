<template>
  <section class="faq-section">
    <div class="faq-inner">
      <header class="faq-header">
        <h1>{{ title }}</h1>
        <p>{{ subtitle }}</p>
      </header>

      <div class="faq-list">
        <article v-for="(item, index) in items" :key="index" class="faq-item">
          <span class="faq-marker" aria-hidden="true"></span>
          <div>
            <h2>{{ item.title }}</h2>
            <div class="faq-answer" v-html="item.description"></div>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "PageFaqListSection",
  props: { section: { type: Object, required: true } },
  computed: {
    data() {
      return this.section.data || {};
    },
    title() {
      return this.data.title || this.data.heading || "Frequently Asked Questions";
    },
    subtitle() {
      return this.data.subtitle || this.data.subheading || "";
    },
    items() {
      return this.data.items || [];
    },
  },
};
</script>

<style scoped>
.faq-section {
  background: #fbf7ef;
  padding: 110px 18px 84px;
  color: #090806;
}
.faq-inner {
  width: min(1040px, 100%);
  margin: 0 auto;
}
.faq-header {
  margin-bottom: 54px;
  text-align: center;
}
.faq-header h1 {
  margin: 0 0 8px;
  font-size: 34px;
  font-weight: 900;
  line-height: 1.15;
}
.faq-header p {
  margin: 0;
  font-size: 17px;
}
.faq-list {
  display: grid;
  gap: 26px;
}
.faq-item {
  display: grid;
  grid-template-columns: 36px 1fr;
  gap: 24px;
  align-items: start;
}
.faq-marker {
  width: 22px;
  height: 22px;
  margin-top: 4px;
  border: 7px solid #050403;
  border-radius: 50%;
}
.faq-item h2 {
  margin: 0 0 10px;
  font-size: 20px;
  font-weight: 900;
  line-height: 1.25;
}
.faq-answer {
  color: #67615a;
  font-size: 18px;
  line-height: 1.55;
}
.faq-answer :deep(p) {
  margin: 0 0 6px;
}
.faq-answer :deep(ul),
.faq-answer :deep(ol) {
  margin: 4px 0 6px 22px;
  padding: 0;
}
.faq-answer :deep(strong) {
  color: #080604;
  font-weight: 900;
}
@media (max-width: 700px) {
  .faq-section { padding-top: 70px; }
  .faq-item { grid-template-columns: 28px 1fr; gap: 14px; }
  .faq-answer { font-size: 16px; }
  .faq-header h1 { font-size: 30px; }
}
</style>
