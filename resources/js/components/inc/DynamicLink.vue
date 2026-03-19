<template>
  <a
    :href="goTo"
    v-if="externalLink"
    :class="[appendClass, $attrs.class]"
  >
    <slot />
  </a>
  <router-link
    :to="goTo"
    :class="[appendClass, $attrs.class]"
    v-else
  >
    <slot />
  </router-link>
</template>

<script>
export default {
  name: "DynamicLink",
  inheritAttrs: false,
  props: {
    to: {
      required: true,
      validator: (prop) => typeof prop === "string" || prop === null,
    },
    appendClass: {
      default: "",
    },
  },
  data: () => ({
    externalLink: false,
  }),
  computed: {
    goTo() {
      this.externalLink =
        typeof this.to === "string" && this.to.slice(0, 4) == "http"
          ? true
          : false;
      return typeof this.to === "string" ? this.to : "";
    },
  },
};
</script>
