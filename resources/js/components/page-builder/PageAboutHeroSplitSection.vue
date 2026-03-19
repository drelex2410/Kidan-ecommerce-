<template>
  <section class="page-section about-hero-section">
    <div class="about-hero-shell" :class="{ 'has-badge': !!badgeImage }">
      <div class="about-hero-copy" :class="`align-${alignment}`">
        <h1 class="about-hero-title" :style="titleStyles">{{ title }}</h1>
        <p v-if="subtitle" class="about-hero-subtitle">{{ subtitle }}</p>
      </div>

      <div v-if="heroImage" class="about-hero-media">
        <img :src="heroImage" :alt="imageAlt" />
      </div>

      <div v-if="badgeImage" class="about-hero-badge">
        <img :src="badgeImage" :alt="`${title} badge`" />
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "PageAboutHeroSplitSection",
  props: {
    section: {
      type: Object,
      required: true,
    },
  },
  computed: {
    title() {
      return this.section.data?.title || this.section.data?.heading || "";
    },
    subtitle() {
      return this.section.data?.subtitle || this.section.data?.subheading || "";
    },
    heroImage() {
      return this.section.data?.image || null;
    },
    badgeImage() {
      return this.section.data?.badge_image || this.section.data?.image_2 || null;
    },
    alignment() {
      return this.section.data?.alignment || "left";
    },
    imageAlt() {
      return this.section.data?.image_alt || this.title || "About hero image";
    },
    titleStyles() {
      const maxWidth = Number(this.section.data?.title_max_width);

      return maxWidth > 0 ? { maxWidth: `${maxWidth}px` } : {};
    },
  },
};
</script>

<style scoped>
.about-hero-section {
  background: var(--editorial-bg, #f5f1ea);
  padding: clamp(3rem, 7vw, 5.5rem) 1.25rem clamp(3.75rem, 7vw, 5.5rem);
}

.about-hero-shell {
  position: relative;
  max-width: 1170px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  align-items: end;
}

.about-hero-shell.has-badge {
  padding-bottom: 2rem;
}

.about-hero-copy {
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  gap: 1rem;
}

.about-hero-copy.align-center {
  align-items: center;
  text-align: center;
}

.about-hero-copy.align-right {
  align-items: flex-end;
  text-align: right;
}

.about-hero-title {
  margin: 0;
  color: var(--editorial-ink, #13110f);
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: clamp(2.7rem, 8vw, 5.2rem);
  font-weight: 700;
  line-height: 0.92;
  letter-spacing: -0.06em;
  text-transform: uppercase;
}

.about-hero-subtitle {
  margin: 0;
  max-width: 28rem;
  color: var(--editorial-muted, #6b655d);
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-weight: 400;
  line-height: 1.6;
}

.about-hero-media {
  position: relative;
}

.about-hero-media img {
  display: block;
  width: 100%;
  aspect-ratio: 5 / 6;
  object-fit: cover;
  object-position: center;
}

.about-hero-badge {
  position: absolute;
  left: 50%;
  bottom: 0;
  transform: translate(-50%, 50%);
  width: clamp(72px, 11vw, 108px);
  z-index: 2;
}

.about-hero-badge img {
  display: block;
  width: 100%;
  height: auto;
}

@media (min-width: 900px) {
  .about-hero-shell {
    grid-template-columns: minmax(0, 0.95fr) minmax(360px, 0.9fr);
    gap: clamp(2.5rem, 5vw, 4.5rem);
  }

  .about-hero-copy {
    padding-bottom: 5rem;
  }

  .about-hero-badge {
    left: 49%;
    width: clamp(80px, 8vw, 112px);
  }
}

@media (max-width: 899px) {
  .about-hero-copy {
    gap: 0.75rem;
  }

  .about-hero-subtitle {
    max-width: 20rem;
  }
}
</style>
