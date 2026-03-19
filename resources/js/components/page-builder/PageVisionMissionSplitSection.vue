<template>
  <section class="page-section vision-mission-split-section">
    <div class="vision-mission-split-shell">
      <div class="vision-mission-copy" :class="themeClass">
        <div class="vision-mission-copy-inner">
          <article class="vision-mission-block">
            <h2>{{ visionTitle }}</h2>
            <p>{{ visionBody }}</p>
          </article>

          <article class="vision-mission-block">
            <h2>{{ missionTitle }}</h2>
            <p>{{ missionBody }}</p>
          </article>
        </div>
      </div>

      <div v-if="image" class="vision-mission-media">
        <img :src="image" :alt="imageAlt" />
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "PageVisionMissionSplitSection",
  props: {
    section: {
      type: Object,
      required: true,
    },
  },
  computed: {
    visionTitle() {
      return this.section.data?.vision_title || "Our Vision";
    },
    visionBody() {
      return this.section.data?.vision_text || "";
    },
    missionTitle() {
      return this.section.data?.mission_title || "Our Mission";
    },
    missionBody() {
      return this.section.data?.mission_text || "";
    },
    image() {
      return this.section.data?.image || this.section.data?.image_2 || null;
    },
    imageAlt() {
      return this.section.data?.image_alt || this.section.data?.title || "Vision and mission image";
    },
    themeClass() {
      return `theme-${this.section.data?.background_style || "sand"}`;
    },
  },
};
</script>

<style scoped>
.vision-mission-split-section {
  background: var(--editorial-bg, #f5f1ea);
  padding: 0 0 clamp(2.5rem, 5vw, 3.25rem);
}

.vision-mission-split-shell {
  max-width: 1170px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
}

.vision-mission-copy {
  display: flex;
  align-items: center;
}

.vision-mission-copy-inner {
  width: 100%;
  padding: clamp(2rem, 6vw, 3.5rem);
  display: grid;
  gap: 2rem;
}

.vision-mission-block h2 {
  margin: 0 0 0.9rem;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: clamp(1.75rem, 2.8vw, 2.45rem);
  font-weight: 700;
  line-height: 1.05;
}

.vision-mission-block p {
  margin: 0;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-weight: 400;
  line-height: 1.8;
}

.vision-mission-media img {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 320px;
  object-fit: cover;
}

.theme-sand {
  background: var(--editorial-panel-soft, #dacbaa);
  color: var(--editorial-ink, #13110f);
}

.theme-stone {
  background: #e5ddd1;
  color: var(--editorial-ink, #13110f);
}

.theme-light {
  background: #f9f6f0;
  color: var(--editorial-ink, #13110f);
}

@media (min-width: 960px) {
  .vision-mission-split-shell {
    grid-template-columns: minmax(0, 0.98fr) minmax(0, 1.02fr);
    align-items: stretch;
  }

  .vision-mission-media img,
  .vision-mission-copy {
    min-height: 420px;
  }
}
</style>
