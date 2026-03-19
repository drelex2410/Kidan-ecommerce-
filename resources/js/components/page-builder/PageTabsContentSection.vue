<template>
  <section class="page-section tabs-content-section">
    <div class="tabs-content-shell">
      <p v-if="sectionHeading" class="tabs-section-heading">{{ sectionHeading }}</p>

      <div class="tabs-nav" role="tablist" aria-label="About content tabs">
        <button
          v-for="(tab, index) in tabs"
          :key="`${tab.tab_label}-${index}`"
          type="button"
          class="tabs-nav-button"
          :class="{ 'is-active': index === activeIndex }"
          :aria-selected="index === activeIndex ? 'true' : 'false'"
          @click="activeIndex = index"
        >
          {{ tab.tab_label || `Tab ${index + 1}` }}
        </button>
      </div>

      <div v-if="activeTab" class="tabs-panel">
        <component :is="activeComponent" :tab="activeTab" />
      </div>
    </div>
  </section>
</template>

<script>
import PageTabBasicContent from "./tabs/PageTabBasicContent.vue";
import PageTabCareerContent from "./tabs/PageTabCareerContent.vue";
import PageTabPartnershipContent from "./tabs/PageTabPartnershipContent.vue";
import PageTabPressEventsContent from "./tabs/PageTabPressEventsContent.vue";
import PageTabTribeContent from "./tabs/PageTabTribeContent.vue";
import PageTabYouthContent from "./tabs/PageTabYouthContent.vue";

export default {
  name: "PageTabsContentSection",
  emits: ["tab-change"],
  components: {
    PageTabBasicContent,
    PageTabCareerContent,
    PageTabPartnershipContent,
    PageTabPressEventsContent,
    PageTabTribeContent,
    PageTabYouthContent,
  },
  props: {
    section: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      activeIndex: 0,
    };
  },
  computed: {
    sectionHeading() {
      return this.section.data?.title || "";
    },
    tabs() {
      return this.section.data?.tabs || [];
    },
    defaultIndex() {
      const rawIndex = Number(this.section.data?.default_tab);

      if (Number.isNaN(rawIndex) || rawIndex < 0 || rawIndex >= this.tabs.length) {
        return 0;
      }

      return rawIndex;
    },
    activeTab() {
      return this.tabs[this.activeIndex] || this.tabs[0] || null;
    },
    activeComponent() {
      const componentMap = {
        basic: "PageTabBasicContent",
        career_showcase: "PageTabCareerContent",
        tribe_rewards: "PageTabTribeContent",
        partnership_cta: "PageTabPartnershipContent",
        youth_program: "PageTabYouthContent",
        press_events: "PageTabPressEventsContent",
      };

      return componentMap[this.activeTab?.layout] || "PageTabBasicContent";
    },
  },
  watch: {
    tabs: {
      deep: true,
      handler() {
        this.activeIndex = this.defaultIndex;
        this.emitTabChange();
      },
    },
    activeIndex() {
      this.emitTabChange();
    },
  },
  methods: {
    emitTabChange() {
      this.$emit("tab-change", {
        activeIndex: this.activeIndex,
        activeTab: this.activeTab,
      });
    },
  },
  created() {
    this.activeIndex = this.defaultIndex;
    this.emitTabChange();
  },
};
</script>

<style scoped>
.tabs-content-section {
  background: var(--editorial-bg, #f5f1ea);
  padding: 0 1.25rem clamp(3rem, 5vw, 4.5rem);
}

.tabs-content-shell {
  max-width: 1110px;
  margin: 0 auto;
}

.tabs-section-heading {
  margin: 0 0 1rem;
  color: var(--editorial-muted, #6b655d);
  font-size: 0.88rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.tabs-nav {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  overflow-x: auto;
  padding: 0 0 0.6rem;
  border-bottom: 1px solid rgba(19, 17, 15, 0.12);
  scrollbar-width: none;
}

.tabs-nav::-webkit-scrollbar {
  display: none;
}

.tabs-nav-button {
  flex: 0 0 auto;
  padding: 0 0 0.95rem;
  border: 0;
  border-bottom: 2px solid transparent;
  background: transparent;
  color: #625b54;
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-tab-font-size, clamp(0.5rem, 0.9vw, 0.675rem));
  font-weight: 400;
  line-height: 1.2;
  cursor: pointer;
  transition: color 0.2s ease, border-color 0.2s ease;
  white-space: nowrap;
}

.tabs-nav-button:hover {
  color: var(--editorial-ink, #13110f);
}

.tabs-nav-button.is-active {
  color: var(--editorial-accent, #9d150d);
  border-color: var(--editorial-accent, #9d150d);
  font-weight: 700;
}

.tabs-panel {
  padding-top: clamp(1.75rem, 4vw, 2.55rem);
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
}

.tabs-panel :deep(p),
.tabs-panel :deep(li),
.tabs-panel :deep(.tab-body),
.tabs-panel :deep(.section-copy),
.tabs-panel :deep(.section-subheading),
.tabs-panel :deep(.partnership-list),
.tabs-panel :deep(.media-description),
.tabs-panel :deep(.tribe-closing),
.tabs-panel :deep(.tribe-row-value),
.tabs-panel :deep(.youth-feature-item p) {
  font-family: var(--editorial-font-family, Candara, Calibri, "Segoe UI", sans-serif);
  font-size: var(--editorial-paragraph-size, clamp(0.5625rem, 1.2vw, 0.75rem));
  font-weight: 400;
}

@media (max-width: 767px) {
  .tabs-nav {
    gap: 1rem;
    justify-content: flex-start;
  }

  .tabs-nav-button {
    font-size: var(--editorial-tab-font-size, 0.675rem);
    padding-bottom: 0.75rem;
  }

  .tabs-panel {
    padding-top: 1.5rem;
  }
}
</style>
