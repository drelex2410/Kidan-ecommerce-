<template>
  <section id="stores" class="store-section">
    <div class="store-inner">
      <h2>{{ title }}</h2>
      <div class="store-grid">
        <article v-for="(item, index) in items" :key="index" class="store-card">
          <div class="store-image" :style="{ backgroundImage: `url('${imageFor(item, index)}')` }"></div>
          <div class="store-copy">
            <p>{{ item.meta || "Address" }}</p>
            <h3>{{ item.title }}</h3>
            <a :href="item.button_link || '#'" target="_blank" rel="noopener">
              {{ item.button_text || "GET DIRECTIONS" }}
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script>
const FALLBACKS = [
  "/assets/img/trackorderbanner.jpg",
  "/assets/img/about1.jpg",
  "/assets/img/about2.jpg",
  "/assets/img/about_hero.jpg",
];

export default {
  name: "PageContactStoreGridSection",
  props: { section: { type: Object, required: true } },
  computed: {
    data() {
      return this.section.data || {};
    },
    title() {
      return this.data.title || "Find Our Stores";
    },
    items() {
      return this.data.items || [];
    },
  },
  methods: {
    imageFor(item, index) {
      return item.image || FALLBACKS[index % FALLBACKS.length];
    },
  },
};
</script>

<style scoped>
.store-section {
  background: #fbf7ef;
  padding: 34px 44px 72px;
}
.store-inner {
  width: min(1500px, 100%);
  margin: 0 auto;
}
.store-inner h2 {
  margin: 0 0 28px;
  color: #080604;
  font-size: 24px;
  font-weight: 900;
}
.store-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}
.store-card {
  overflow: hidden;
  border-radius: 3px;
  background: #fff;
}
.store-image {
  height: 178px;
  background-position: center;
  background-size: cover;
}
.store-copy {
  padding: 20px 24px 28px;
}
.store-copy p {
  margin: 0 0 12px;
  color: #5f5850;
  font-size: 16px;
}
.store-copy h3 {
  min-height: 48px;
  margin: 0 0 28px;
  color: #050403;
  font-size: 15px;
  line-height: 1.35;
  font-weight: 900;
}
.store-copy a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 150px;
  min-height: 44px;
  border-radius: 4px;
  background: #990000;
  color: #fff;
  font-family: Georgia, serif;
  font-size: 13px;
  font-weight: 800;
  text-decoration: none;
}
@media (max-width: 1100px) {
  .store-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 620px) {
  .store-section { padding: 30px 16px 54px; }
  .store-grid { grid-template-columns: 1fr; }
}
</style>
