<template>
  <div class="tab-layout press-events-layout">
    <section class="press-section">
      <h2 v-if="tab.intro_title" class="section-heading">{{ tab.intro_title }}</h2>
      <p v-if="tab.intro_body" class="section-copy">{{ tab.intro_body }}</p>
    </section>

    <section class="press-section">
      <h2 v-if="tab.content_title" class="section-heading">{{ tab.content_title }}</h2>
      <p v-if="tab.content_body" class="section-copy">{{ tab.content_body }}</p>
      <div class="media-showcase">
        <div class="media-grid">
          <article
            v-for="(item, index) in primaryItems"
            :key="`${item.title}-${index}`"
            class="media-card"
          >
            <div v-if="item.image" class="media-image-shell">
              <img :src="item.image" :alt="item.title || 'Media item'" />
              <div class="media-card-badge" :class="{ 'is-video': isVideo(item) }" aria-hidden="true">
                <span v-if="isVideo(item)" class="media-card-play"></span>
                <span v-else class="media-card-mark">K</span>
              </div>
            </div>
            <p class="media-meta">
              <span>{{ item.meta }}</span>
              <span v-if="item.submeta">• {{ item.submeta }}</span>
            </p>
            <h3>{{ item.title }}</h3>
            <p class="media-description">{{ item.description }}</p>
          </article>
        </div>
        <a
          v-if="tab.button_text && tab.button_link"
          class="media-cta"
          :href="tab.button_link"
        >
          <span class="media-cta-icon">↗</span>
          <span>{{ tab.button_text }}</span>
        </a>
      </div>
    </section>

    <section class="press-section">
      <h2 v-if="tab.extra_title" class="section-heading">{{ tab.extra_title }}</h2>
      <p v-if="tab.extra_body" class="section-copy">{{ tab.extra_body }}</p>
      <div class="media-showcase">
        <div class="media-grid">
          <article
            v-for="(item, index) in secondaryItems"
            :key="`${item.title}-${index}`"
            class="media-card"
          >
            <div v-if="item.image" class="media-image-shell">
              <img :src="item.image" :alt="item.title || 'Event item'" />
              <div class="media-card-badge" :class="{ 'is-video': isVideo(item) }" aria-hidden="true">
                <span v-if="isVideo(item)" class="media-card-play"></span>
                <span v-else class="media-card-mark">K</span>
              </div>
            </div>
            <p class="media-meta">
              <span>{{ item.meta }}</span>
              <span v-if="item.submeta">• {{ item.submeta }}</span>
            </p>
            <h3>{{ item.title }}</h3>
            <p class="media-description">{{ item.description }}</p>
          </article>
        </div>
        <a
          v-if="tab.extra_button_text && tab.extra_button_link"
          class="media-cta"
          :href="tab.extra_button_link"
        >
          <span class="media-cta-icon">↗</span>
          <span>{{ tab.extra_button_text }}</span>
        </a>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: "PageTabPressEventsContent",
  props: {
    tab: {
      type: Object,
      required: true,
    },
  },
  computed: {
    primaryItems() {
      return this.tab.items || [];
    },
    secondaryItems() {
      return this.tab.items_secondary || [];
    },
  },
  methods: {
    isVideo(item) {
      return String((item && item.meta) || "").toLowerCase().includes("video");
    },
  },
};
</script>

<style scoped>
.press-events-layout {
  color: #171513;
  font-family: Candara, Calibri, "Segoe UI", sans-serif;
  display: grid;
  gap: 2.6rem;
}

.section-heading {
  margin: 0 0 1rem;
  font-size: clamp(1.5875rem, 2.2vw, 1.6875rem);
  font-weight: 700;
  line-height: 1.15;
}

.section-copy {
  margin: 0 0 1.5rem;
  font-size: clamp(1.125rem, 2.4vw, 1.5rem);
  line-height: 1.7;
  white-space: pre-line;
}

.media-showcase {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 2rem;
  align-items: start;
}

.media-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.media-image-shell {
  position: relative;
}

.media-card img {
  display: block;
  width: 100%;
  aspect-ratio: 0.8;
  object-fit: cover;
  border-radius: 4px;
}

.media-card-badge {
  position: absolute;
  left: 0.9rem;
  bottom: 0.9rem;
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 999px;
  background: #0f0d0c;
  color: #fff;
}

.media-card-badge.is-video {
  width: 38px;
  height: 32px;
  border-radius: 8px;
  background: #f4511e;
}

.media-card-play {
  width: 0;
  height: 0;
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-left: 12px solid #fff;
  margin-left: 3px;
}

.media-card-mark {
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.media-meta {
  margin: 0.7rem 0 0.3rem;
  color: #55514b;
  font-size: 0.95rem;
  line-height: 1.4;
}

.media-card h3 {
  margin: 0;
  font-size: clamp(1.2rem, 1.8vw, 1.6rem);
  font-weight: 700;
  line-height: 1.35;
}

.media-description {
  margin: 0.45rem 0 0;
  color: #55514b;
  font-size: 1rem;
  line-height: 1.45;
  white-space: pre-line;
}

.media-cta {
  display: grid;
  justify-items: center;
  gap: 0.85rem;
  color: #171513;
  font-size: 1rem;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
  align-self: center;
}

.media-cta-icon {
  display: grid;
  place-items: center;
  width: 54px;
  height: 54px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.55);
  font-size: 1.8rem;
  line-height: 1;
}

@media (max-width: 1199px) {
  .media-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 899px) {
  .media-showcase {
    grid-template-columns: 1fr;
  }

  .media-cta {
    justify-self: start;
    grid-auto-flow: column;
    align-items: center;
  }
}

@media (max-width: 599px) {
  .media-grid {
    grid-template-columns: 1fr;
  }
}
</style>
