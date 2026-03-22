<template>
  <section class="checkout-section">
    <div class="checkout-section__heading">
      <h2>{{ title }}</h2>
      <slot name="action"></slot>
    </div>
    <div class="checkout-section__body">
      <slot></slot>
    </div>
  </section>
</template>

<script>
export default {
  name: "CheckoutSectionCard",
  props: {
    title: {
      type: String,
      required: true,
    },
  },
};
</script>

<style scoped>
.checkout-section {
  border-top: 1px solid #ddd4c8;
  padding: 34px 0 30px;
}

.checkout-section__heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.checkout-section__heading h2 {
  margin: 0;
  font-size: 18px;
  line-height: 1.2;
  color: #17130f;
  font-weight: 700;
}

.checkout-section__body {
  width: 100%;
}
</style>
