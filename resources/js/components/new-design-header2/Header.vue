<template>
  <header :class="['header-sticky', { 'sticky-header': !isHomePage, 'static-header': isHomePage }]">
    <LogoBar
      :loading="loading"
      :data="data"
      :is-home-page="isHomePage"
    />
  </header>
</template>

<script>
import { mapGetters } from "vuex";
import LogoBar from "./LogoBar.vue";
export default {
  props: {
    isHomePage: {
      type: Boolean,
      default: false
    }
  },
  data: () => ({
    loading: true,
    data: {},
  }),
  components: {
    LogoBar,
  },
  computed: {
    ...mapGetters("app", ["generalSettings"]),
  },
  methods: {
    async getDetails() {
      const res = await this.call_api("get", `setting/header`);
      if (res.status === 200) {
        this.data = res.data;
        this.loading = false;
      }
    },
  },
  created() {
    this.getDetails();
  },
};
</script>

<style scoped>
.header-sticky {
  width: 100%;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 1000;
}

.static-header {
  position: static;
}
</style>