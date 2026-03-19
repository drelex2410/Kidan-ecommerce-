<template>
  <div class="tab-layout tribe-layout">
    <section class="tribe-section">
      <h2 v-if="tab.intro_title" class="section-heading">{{ tab.intro_title }}</h2>
      <p v-if="tab.intro_body" class="section-copy">{{ tab.intro_body }}</p>
    </section>

    <section class="tribe-hero">
      <div class="tribe-title-block">
        <div class="tribe-display-title">{{ displayTitle }}</div>
      </div>
      <div class="tribe-reward-block">
        <div class="tribe-medal">Award</div>
        <div class="tribe-reward-title">{{ rewardTitle }}</div>
        <div class="tribe-stars" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </section>

    <section v-if="tiers.length" class="tribe-table-shell">
      <div class="tribe-table">
        <div
          v-for="(tier, index) in tiers"
          :key="`${tier.title}-${index}`"
          class="tribe-row"
        >
          <div class="tribe-row-title">{{ tier.title }}</div>
          <div class="tribe-row-value">
            <span class="tribe-gem"></span>
            <span>{{ tier.description }}</span>
          </div>
        </div>
      </div>
    </section>

    <p v-if="tab.closing_body" class="tribe-closing">{{ tab.closing_body }}</p>
  </div>
</template>

<script>
export default {
  name: "PageTabTribeContent",
  props: {
    tab: {
      type: Object,
      required: true,
    },
  },
  computed: {
    displayTitle() {
      return this.tab.display_title || "KIDAN TRIBE\nTIERS";
    },
    rewardTitle() {
      return this.tab.reward_title || "Reward";
    },
    tiers() {
      return this.tab.items || [];
    },
  },
};
</script>

<style scoped>
.tribe-layout {
  color: #171513;
  font-family: Candara, Calibri, "Segoe UI", sans-serif;
  display: grid;
  gap: 2.5rem;
}

.section-heading {
  margin: 0 0 1rem;
  font-size: clamp(1.5875rem, 2.2vw, 1.6875rem);
  font-weight: 700;
  line-height: 1.15;
}

.section-copy,
.tribe-closing {
  margin: 0;
  font-size: clamp(1.125rem, 2.4vw, 1.5rem);
  line-height: 1.7;
  white-space: pre-line;
}

.tribe-hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 2rem;
  align-items: center;
}

.tribe-display-title {
  white-space: pre-line;
  font-size: clamp(2.4rem, 5vw, 4rem);
  font-weight: 700;
  line-height: 0.95;
  letter-spacing: -0.04em;
}

.tribe-reward-block {
  position: relative;
  min-height: 180px;
  min-width: 260px;
  display: grid;
  place-items: center;
  gap: 0.75rem;
}

.tribe-medal {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 88px;
  height: 88px;
  border-radius: 999px;
  background: #f0e9cf;
  color: #1a1714;
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
}

.tribe-reward-title {
  position: relative;
  z-index: 2;
  font-size: clamp(2.2rem, 4vw, 3.25rem);
  font-weight: 400;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.tribe-stars {
  position: absolute;
  inset: 0;
}

.tribe-stars span {
  position: absolute;
  width: 72px;
  height: 72px;
  background: #efc21e;
  clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 92%, 50% 70%, 21% 92%, 32% 57%, 2% 35%, 39% 35%);
  opacity: 0.95;
}

.tribe-stars span:nth-child(1) {
  right: 1rem;
  top: 0.5rem;
}

.tribe-stars span:nth-child(2) {
  right: 4.5rem;
  top: 3.5rem;
  transform: scale(0.8);
}

.tribe-stars span:nth-child(3) {
  right: 0;
  bottom: 0.6rem;
  transform: scale(0.75);
}

.tribe-table-shell {
  padding: 0.5rem 0 0;
}

.tribe-table {
  border: 1px solid rgba(23, 21, 19, 0.16);
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.18);
}

.tribe-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(220px, 0.95fr);
  border-top: 1px solid rgba(23, 21, 19, 0.14);
}

.tribe-row:first-child {
  border-top: 0;
}

.tribe-row-title,
.tribe-row-value {
  padding: 1.5rem 1.7rem;
  font-size: clamp(1.35rem, 2.2vw, 1.85rem);
  line-height: 1.4;
}

.tribe-row-title {
  font-weight: 700;
  border-right: 1px solid rgba(23, 21, 19, 0.14);
}

.tribe-row-value {
  display: flex;
  align-items: center;
  gap: 0.9rem;
}

.tribe-gem {
  width: 30px;
  height: 30px;
  background: linear-gradient(145deg, #d8e8ff, #9fc2eb);
  clip-path: polygon(50% 0%, 92% 25%, 72% 100%, 28% 100%, 8% 25%);
}

@media (max-width: 839px) {
  .tribe-hero {
    grid-template-columns: 1fr;
  }

  .tribe-reward-block {
    justify-self: start;
  }

  .tribe-row {
    grid-template-columns: 1fr;
  }

  .tribe-row-title {
    border-right: 0;
    border-bottom: 1px solid rgba(23, 21, 19, 0.14);
  }
}
</style>
