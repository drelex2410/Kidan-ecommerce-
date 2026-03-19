<template>
  <div class="page-builder" :class="layoutClass">
    <template v-for="(section, index) in visibleSections" :key="section.section_key || section.id">
      <component
        v-if="shouldRenderSection(section, index)"
        :is="componentMap[section.type] || componentMap.rich_text"
        :section="section"
        @tab-change="handleTabChange(section, $event)"
      />
    </template>
  </div>
</template>

<script>
import PageAboutHeroSplitSection from "./PageAboutHeroSplitSection.vue";
import PageCtaBannerSection from "./PageCtaBannerSection.vue";
import PageEditorialIntroSection from "./PageEditorialIntroSection.vue";
import PageEditorialQuoteSection from "./PageEditorialQuoteSection.vue";
import PageHeroSection from "./PageHeroSection.vue";
import PageImageContentPanelSection from "./PageImageContentPanelSection.vue";
import PageImageGallerySection from "./PageImageGallerySection.vue";
import PageImageTextSection from "./PageImageTextSection.vue";
import PageMultiColumnFeaturesSection from "./PageMultiColumnFeaturesSection.vue";
import PageQuoteSection from "./PageQuoteSection.vue";
import PageRichTextSection from "./PageRichTextSection.vue";
import PageSpacerSection from "./PageSpacerSection.vue";
import PageTabsContentSection from "./PageTabsContentSection.vue";
import PageVisionMissionSection from "./PageVisionMissionSection.vue";
import PageVisionMissionSplitSection from "./PageVisionMissionSplitSection.vue";

const EDITORIAL_TYPES = [
  "about_hero_split",
  "editorial_intro",
  "tabs_content",
  "image_content_panel",
  "vision_mission_split",
  "editorial_quote",
];

export default {
  name: "PageSectionRenderer",
  components: {
    PageAboutHeroSplitSection,
    PageEditorialIntroSection,
    PageTabsContentSection,
    PageImageContentPanelSection,
    PageVisionMissionSplitSection,
    PageEditorialQuoteSection,
    PageHeroSection,
    PageRichTextSection,
    PageImageTextSection,
    PageMultiColumnFeaturesSection,
    PageVisionMissionSection,
    PageQuoteSection,
    PageCtaBannerSection,
    PageImageGallerySection,
    PageSpacerSection,
  },
  props: {
    sections: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      tabStateMap: {},
    };
  },
  computed: {
    componentMap() {
      return {
        about_hero_split: "PageAboutHeroSplitSection",
        editorial_intro: "PageEditorialIntroSection",
        tabs_content: "PageTabsContentSection",
        image_content_panel: "PageImageContentPanelSection",
        vision_mission_split: "PageVisionMissionSplitSection",
        editorial_quote: "PageEditorialQuoteSection",
        hero: "PageHeroSection",
        rich_text: "PageRichTextSection",
        image_text_split: "PageImageTextSection",
        multi_column_features: "PageMultiColumnFeaturesSection",
        vision_mission: "PageVisionMissionSection",
        quote: "PageQuoteSection",
        cta_banner: "PageCtaBannerSection",
        image_gallery: "PageImageGallerySection",
        spacer: "PageSpacerSection",
      };
    },
    visibleSections() {
      return (this.sections || []).filter(
        (section) => section && section.is_visible !== false
      );
    },
    hasEditorialSections() {
      return this.visibleSections.some((section) =>
        EDITORIAL_TYPES.includes(section.type)
      );
    },
    layoutClass() {
      return {
        "page-builder--editorial": this.hasEditorialSections,
      };
    },
  },
  methods: {
    handleTabChange(section, payload) {
      const sectionKey = section?.section_key || section?.id;
      if (!sectionKey) {
        return;
      }

      this.tabStateMap = {
        ...this.tabStateMap,
        [sectionKey]: payload,
      };
    },
    shouldRenderSection(section, index) {
      const visibilityMode = section?.data?.tab_visibility || "always";
      if (visibilityMode !== "previous_tab_default_only") {
        return true;
      }

      const anchorSection = this.findPreviousTabsSection(index);
      if (!anchorSection) {
        return true;
      }

      const anchorKey = anchorSection.section_key || anchorSection.id;
      const defaultIndex = Number(anchorSection?.data?.default_tab ?? 0);
      const activeIndex = Number(
        this.tabStateMap[anchorKey]?.activeIndex ?? defaultIndex
      );

      return activeIndex === defaultIndex;
    },
    findPreviousTabsSection(index) {
      for (let cursor = index - 1; cursor >= 0; cursor -= 1) {
        const candidate = this.visibleSections[cursor];
        if (candidate?.type === "tabs_content") {
          return candidate;
        }
      }

      return null;
    },
  },
};
</script>

<style scoped>
.page-builder {
  width: 100%;
}

.page-builder--editorial {
  --editorial-bg: #f5f1ea;
  --editorial-ink: #13110f;
  --editorial-muted: #6b655d;
  --editorial-accent: #9d150d;
  --editorial-panel-dark: #4c3022;
  --editorial-panel-soft: #dacbaa;
  --editorial-font-family: Candara, Calibri, "Segoe UI", sans-serif;
  --editorial-paragraph-size: clamp(0.75rem, 1.2vw, 0.9375rem);
  --editorial-tab-font-size: clamp(0.675rem, 0.95vw, 0.8125rem);
  background: var(--editorial-bg);
  color: var(--editorial-ink);
  font-family: var(--editorial-font-family);
}
</style>
