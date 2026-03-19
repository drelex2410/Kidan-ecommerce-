<template>
  <div class="shipping-policy-page">
    <div class="page-header text-center mb-12">
      <h1 class="text-h3 font-weight-bold mb-0">KIDAN SHIPPING & DELIVERY POLICY</h1>
    </div>

    <div class="policy-content">
      <div class="intro-section mb-10">
        <p class="text-body-1" style="line-height: 1.8; color: #555;">
          We aim to bring African luxury to your doorstep swiftly, securely, and beautifully packaged.
        </p>
      </div>

      <div class="section mb-8">
        <p class="text-body-1 mb-3" style="line-height: 1.8; color: #555;">
          Delivery Locations:
        </p>
        <p class="text-body-1 mb-2" style="line-height: 1.8; color: #555;">
          <strong>KIDAN</strong> ships in Nigeria within All 36 states in Nigeria, including the FCT.
        </p>
        <p class="text-body-1 mb-2" style="line-height: 1.8; color: #555;">
          <strong>KIDAN</strong> ships to all 54 Countries in African continents.
        </p>
        <p class="text-body-1" style="line-height: 1.8; color: #555;">
          International customers will see shipping options at checkout.
        </p>
      </div>

      <div class="section mb-8">
        <h2 class="text-h5 font-weight-bold mb-4">Processing Time</h2>
        <p class="text-body-1" style="line-height: 1.8; color: #555;">
          Orders are typically processed within 1–5 business days. Orders placed after 2 PM or during weekends/public 
          holidays will be processed on the next working day
        </p>
      </div>

      <div class="section mb-8">
        <h2 class="text-h5 font-weight-bold mb-4">Delivery Timeline</h2>
        <p class="text-body-1" style="line-height: 1.8; color: #555;">
          Within Nigeria: 2–7 business days Across Africa: 7–14 business days (depending on destination and courier 
          availability)
        </p>
      </div>

      <div class="section mb-8">
        <h2 class="text-h5 font-weight-bold mb-4">Shipping Fees</h2>
        <p class="text-body-1" style="line-height: 1.8; color: #555;">
          Calculated at checkout based on location and weight. Free shipping may apply for orders above ₦100,000 
          within Nigeria.
        </p>
      </div>

      <div class="section mb-8">
        <h2 class="text-h5 font-weight-bold mb-4">Order Tracking</h2>
        <p class="text-body-1" style="line-height: 1.8; color: #555;">
          Once your order is shipped, you'll receive an email or SMS with your tracking number and courier link.
        </p>
      </div>

      <div class="section mb-0">
        <h2 class="text-h5 font-weight-bold mb-4">Failed Deliveries</h2>
        <p class="text-body-1" style="line-height: 1.8; color: #555;">
          If delivery is unsuccessful due to an incorrect address or failed contact, the order will be returned to us. A re-delivery fee may apply.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shipping-policy-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 3rem 1.5rem 5rem;
}

.page-header {
  padding-bottom: 2.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.policy-content {
  padding-top: 2.5rem;
}

.intro-section {
  margin-bottom: 2.5rem;
}

.section {
  margin-bottom: 2.5rem;
}

.section:last-child {
  margin-bottom: 0;
}

@media (max-width: 599px) {
  .shipping-policy-page {
    padding: 2rem 1rem 3rem;
  }
  
  .page-header h1 {
    font-size: 1.5rem !important;
  }
  
  .section h2 {
    font-size: 1.25rem !important;
  }
}
</style>