<template>
  <div class="partnership-page">
    <div class="mb-10">
      <h2 class="main-heading mb-4">KIDAN Welcomes</h2>
      <p class="intro-text mb-0">
        Partners and affiliates who share our vision for modern African luxury, community empowerment, and 
        regenerative living. Our ecosystem brings together designers, creators, retailers, influencers, marketers, 
        entrepreneurs, and distributors who want to grow with us and contribute to a connected, sustainable African 
        future.
      </p>
    </div>

    <div class="benefits-section mb-10">
      <h3 class="section-heading mb-6">Through KIDAN Partnerships & Affiliates, you can:</h3>
      <ul class="benefits-list">
        <li>Collaborate on exclusive products and cultural experiences.</li>
        <li>Distribute or resell KIDAN lifestyle goods.</li>
        <li>Earn commissions through content, referrals, and affiliate sales.</li>
        <li>Host KIDAN pop-ups, delivery points, or retail activations.</li>
        <li>Showcase your brand or craft within the KIDAN ecosystem.</li>
        <li>Partner on marketing, storytelling, and community initiatives</li>
      </ul>
    </div>

    <div class="closing-section mb-10">
      <p class="closing-text mb-0">
        Whether you're a designer, influencer, marketer, retailer, creator, or business owner, KIDAN offers a platform 
        where visibility, growth, and meaningful impact come together. Join us and become part of a global lifestyle 
        ecosystem that celebrates culture, craftsmanship, and contemporary African living. Partner with KIDAN
      </p>
    </div>

    <div class="cta-section mb-10">
      <button class="join-btn">JOIN US</button>
    </div>
  </div>
</template>

<style scoped>
.partnership-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 3rem 1rem;
}

.main-heading {
  font-size: clamp(24px, 5vw, 32px);
  font-weight: 700;
  color: #000;
  line-height: 1.2;
  text-align: left;
}

.intro-text {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
  text-align: left;
}

.benefits-section {
  text-align: left;
}

.section-heading {
  font-size: 20px;
  font-weight: 600;
  color: #000;
  line-height: 1.4;
  text-align: left;
}

.benefits-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.benefits-list li {
  font-size: 16px;
  line-height: 1.9;
  color: #333;
  margin-bottom: 0.5rem;
  position: relative;
  padding-left: 0;
}

.benefits-list li::before {
  content: "• ";
  font-weight: bold;
  margin-right: 0.5rem;
}

.closing-section {
  text-align: left;
}

.closing-text {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
  text-align: left;
}

.cta-section {
  text-align: left;
}

.join-btn {
  background: #8b0000;
  color: white;
  border: none;
  padding: 12px 36px;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 1px;
  cursor: pointer;
  text-transform: uppercase;
  transition: background 0.3s ease;
}

.join-btn:hover {
  background: #6b0000;
}

@media (max-width: 959px) {
  .partnership-page {
    padding: 2rem 1rem;
  }
  
  .main-heading {
    font-size: clamp(22px, 5vw, 28px);
  }
}

@media (max-width: 599px) {
  .partnership-page {
    padding: 1.5rem 1rem;
  }
  
  .main-heading {
    font-size: clamp(20px, 5vw, 24px);
    margin-bottom: 1rem;
  }
  
  .intro-text,
  .closing-text {
    font-size: 15px;
    line-height: 1.7;
  }
  
  .section-heading {
    font-size: 18px;
  }
  
  .benefits-list li {
    font-size: 15px;
    line-height: 1.8;
  }
  
  .join-btn {
    padding: 10px 30px;
    font-size: 13px;
  }
}
</style>