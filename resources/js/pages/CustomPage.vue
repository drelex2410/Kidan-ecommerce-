<template>
    <div class="pb-6 custom-page-responsiveness">
        <template v-if="loading">
            <v-container>
                <v-skeleton-loader
                    type="table-heading, divider, list-item-three-line, image, article"
                ></v-skeleton-loader>
            </v-container>
        </template>
        <template v-else>
            <template v-if="hasSections">
                <PageSectionRenderer v-if="usesEditorialLayout" :sections="page.sections" />
                <v-container v-else>
                    <PageSectionRenderer :sections="page.sections" />
                </v-container>
            </template>
            <v-container v-else>
                <h1 class="mb-7 mt-4">{{ page.title }}</h1>
                <div v-html="page.content"></div>
            </v-container>
        </template>
    </div>
</template>

<script>
import { useHead } from '@unhead/vue'
import PageSectionRenderer from "../components/page-builder/PageSectionRenderer.vue";

const EDITORIAL_TYPES = [
    "about_hero_split",
    "editorial_intro",
    "tabs_content",
    "image_content_panel",
    "vision_mission_split",
    "editorial_quote",
];

export default {
    components: {
        PageSectionRenderer,
    },

    data: () =>{
        return {
            loading: true,
            metaTitle: '',
            page: {}
        }
    },
    watch:{
        metaTitle(newTitle){
            this.updateHead(newTitle);
        },
        '$route.fullPath': {
            handler() {
                this.loadPage();
            }
        },
    },
    computed: {
        effectiveSlug() {
            return this.$route.meta.pageSlugOverride || this.$route.params.pageSlug;
        },
        hasSections() {
            return Array.isArray(this.page.sections) && this.page.sections.length > 0;
        },
        usesEditorialLayout() {
            return this.hasSections && this.page.sections.some((section) => EDITORIAL_TYPES.includes(section.type));
        },
    },
    methods:{
    updateHead(title) {
      useHead({
        title: title,
      });
    },
    async loadPage(){
        this.loading = true;
        const res = await this.call_api("get", `page/${this.effectiveSlug}`);
        if(res.data.success){
            this.metaTitle = res.data.data.title
            this.page = res.data.data
        }else{
            this.snack({
                message: res.data.message,
                color: "red"
            });
            this.$router.push({ name: "404" });
        }
        this.loading = false
    }
  },
    async created(){
        await this.loadPage();
    }
}
</script>
