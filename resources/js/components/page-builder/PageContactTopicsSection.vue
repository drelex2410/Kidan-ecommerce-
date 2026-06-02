<template>
  <section class="contact-topics">
    <div class="topics-inner">
      <h2>{{ title }}</h2>
      <p>{{ subtitle }}</p>
      <div class="topic-grid">
        <button
          v-for="(item, index) in items"
          :key="index"
          type="button"
          @click="selectInquiry(item.title)"
        >
          {{ item.title }}
        </button>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "PageContactTopicsSection",
  props: { section: { type: Object, required: true } },
  computed: {
    data() {
      return this.section.data || {};
    },
    title() {
      return this.data.title || this.data.heading || "";
    },
    subtitle() {
      return this.data.subtitle || this.data.subheading || "";
    },
    items() {
      return this.data.items || [];
    },
  },
  methods: {
    selectInquiry(value) {
      window.dispatchEvent(new CustomEvent("kidan-contact-inquiry", { detail: { inquiry_type: value } }));
      document.querySelector(".contact-form-panel")?.scrollIntoView({ behavior: "smooth", block: "center" });
    },
  },
};
</script>

<style scoped>
.contact-topics {
  background: #fbf7ef;
  padding: 58px 16px 36px;
}
.topics-inner {
  width: min(920px, 100%);
  margin: 0 auto;
  text-align: center;
}
.topics-inner h2 {
  margin: 0 0 10px;
  color: #070604;
  font-size: 34px;
  line-height: 1.15;
  font-weight: 900;
}
.topics-inner p {
  margin: 0 0 26px;
  color: #12100d;
  font-size: 18px;
}
.topic-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 16px;
}
.topic-grid button {
  border: 0;
  border-radius: 3px;
  padding: 18px 28px;
  background: #fff;
  color: #100d0b;
  font-size: 18px;
  box-shadow: 0 1px 0 rgba(30, 20, 10, .02);
}
@media (max-width: 560px) {
  .topics-inner h2 { font-size: 28px; }
  .topics-inner p,
  .topic-grid button { font-size: 16px; }
  .topic-grid button { width: 100%; }
}
</style>
