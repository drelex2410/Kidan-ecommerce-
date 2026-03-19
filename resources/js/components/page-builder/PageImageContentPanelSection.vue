<template>
  <section class="page-section image-content-panel-section">
    <div class="image-content-panel-shell">
      <div v-if="image" class="image-content-panel-media">
        <img :src="image" :alt="imageAlt" />
      </div>

      <div class="image-content-panel-copy" :class="themeClass">
        <div class="image-content-panel-copy-inner">
          <h2 v-if="title" class="image-content-panel-title">{{ title }}</h2>
          <p v-if="subtitle" class="image-content-panel-subtitle">{{ subtitle }}</p>

          <ul v-if="bullets.length" class="image-content-panel-list">
            <li v-for="(bullet, index) in bullets" :key="`${bullet.text}-${index}`">
              {{ bullet.text }}
            </li>
          </ul>

          <p v-if="body" class="image-content-panel-body">{{ body }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "PageImageContentPanelSection",
  props: {
    section: {
      type: Object,
      required: true,
    },
  },
  computed: {
    title() {
      return this.section.data?.title || "";
    },
    subtitle() {
      return this.section.data?.subtitle || "";
    },
    body() {
      return this.section.data?.content || "";
    },
    image() {
      return this.section.data?.image || null;
    },
    imageAlt() {
      return this.section.data?.image_alt || this.title || "Content image";
    },
    bullets() {
      return this.section.data?.bullets || [];
    },
    themeClass() {
      return `theme-${this.section.data?.panel_theme || "mocha"}`;
    },
  },
};
</script>

<style scoped>
.image-content-panel-section {
  background: var(--editorial-bg, #f5f1ea);
  padding: clamp(1.5rem, 4vw, 2rem) 0 0;
}

.image-content-panel-shell {
  max-width: 1170px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
}

.image-content-panel-media img {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 320px;
  object-fit: cover;
}

.image-content-panel-copy {
  display: flex;
  align-items: center;
  min-height: 100%;
}

.image-content-panel-copy-inner {
  width: 100%;
  padding: clamp(2rem, 6vw, 3.5rem);
}

.image-content-panel-title {
  margin: 0 0 1rem;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: clamp(2rem, 3vw, 2.7rem);
  font-weight: 700;
  line-height: 1.05;
}

.image-content-panel-subtitle {
  margin: 0 0 1rem;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-weight: 400;
  line-height: 1.7;
}

.image-content-panel-list {
  margin: 0 0 1.5rem;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.65rem;
}

.image-content-panel-list li {
  position: relative;
  padding-left: 1.35rem;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-weight: 400;
  line-height: 1.6;
}

.image-content-panel-list li::before {
  content: "-";
  position: absolute;
  left: 0;
  top: 0;
}

.image-content-panel-body {
  margin: 0;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-weight: 400;
  line-height: 1.85;
}

.theme-mocha {
  background: var(--editorial-panel-dark, #4c3022);
  color: #f8efe5;
}

.theme-ink {
  background: #2e2722;
  color: #f7f2ec;
}

.theme-sand {
  background: var(--editorial-panel-soft, #dacbaa);
  color: var(--editorial-ink, #13110f);
}

@media (min-width: 960px) {
  .image-content-panel-shell {
    grid-template-columns: minmax(0, 1.02fr) minmax(0, 0.98fr);
    align-items: stretch;
  }

  .image-content-panel-media img,
  .image-content-panel-copy {
    min-height: 420px;
  }
}

@media (max-width: 959px) {
  .image-content-panel-copy {
    min-height: auto;
  }
}
</style>
