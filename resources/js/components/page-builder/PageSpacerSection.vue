<template>
  <section class="page-section spacer-section" :class="`space-${section.data?.spacing_size || 'md'}`">
    <div v-if="section.data?.line_toggle" class="divider-line"></div>
  </section>
</template>

<script>
export default { props: { section: { type: Object, required: true } } };
</script>

<style scoped>
.spacer-section { width: 100%; }
.space-sm { padding: .75rem 0; }
.space-md { padding: 1.5rem 0; }
.space-lg { padding: 2.5rem 0; }
.space-xl { padding: 4rem 0; }
.divider-line { height: 1px; width: 100%; background: rgba(76, 56, 42, .15); }
</style>
