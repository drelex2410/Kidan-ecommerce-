<template>
  <div class="checkout-stepper">
    <div
      v-for="step in steps"
      :key="step.id"
      class="checkout-stepper__item"
      :class="{
        'is-active': currentStep === step.id,
        'is-complete': currentStep > step.id || completeAll,
      }"
    >
      <div class="checkout-stepper__circle">{{ step.id }}</div>
      <div class="checkout-stepper__label">{{ step.label }}</div>
      <div
        v-if="step.id < steps.length"
        class="checkout-stepper__line"
      ></div>
    </div>
  </div>
</template>

<script>
export default {
  name: "CheckoutStepper",
  props: {
    currentStep: {
      type: Number,
      default: 1,
    },
    completeAll: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      steps: [
        { id: 1, label: "Shipping" },
        { id: 2, label: "Payment" },
        { id: 3, label: "Complete" },
      ],
    };
  },
};
</script>

<style scoped>
.checkout-stepper {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 0;
  margin: 20px 0 26px;
}

.checkout-stepper__item {
  position: relative;
  min-width: 172px;
  text-align: center;
}

.checkout-stepper__circle {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 1px solid #d9d1c4;
  color: #d0c8bd;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-family: "Cormorant Garamond", "Times New Roman", serif;
  background: transparent;
  position: relative;
  z-index: 2;
}

.checkout-stepper__label {
  margin-top: 12px;
  color: #d0c8bd;
  font-size: 14px;
  font-weight: 500;
}

.checkout-stepper__line {
  position: absolute;
  top: 20px;
  left: calc(50% + 21px);
  width: calc(100% - 42px);
  height: 1px;
  background: #ddd4c8;
  z-index: 1;
}

.checkout-stepper__item.is-active .checkout-stepper__circle,
.checkout-stepper__item.is-complete .checkout-stepper__circle {
  border-color: #921b12;
  color: #921b12;
}

.checkout-stepper__item.is-active .checkout-stepper__label,
.checkout-stepper__item.is-complete .checkout-stepper__label {
  color: #1b1711;
}

.checkout-stepper__item.is-complete .checkout-stepper__line {
  background: #921b12;
}

@media (max-width: 900px) {
  .checkout-stepper {
    justify-content: flex-start;
    overflow-x: auto;
    padding-bottom: 8px;
  }

  .checkout-stepper__item {
    min-width: 132px;
  }
}
</style>
