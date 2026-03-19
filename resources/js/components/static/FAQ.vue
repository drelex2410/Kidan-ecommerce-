<template>
  <div class="faq-page">
    <div class="page-header text-center mb-10">
      <h1 class="text-h3 font-weight-bold mb-2">Frequently Asked Questions</h1>
      <p class="text-subtitle-1" style="color: #666;">You have questions? we have answers.</p>
    </div>

    <div class="faq-content">
      <div 
        v-for="(faq, index) in faqs" 
        :key="index" 
        class="faq-item mb-4"
      >
        <button 
          class="faq-question" 
          @click="toggleFaq(index)"
          :aria-expanded="activeFaq === index"
        >
          <span class="question-icon">Q</span>
          <span class="question-text">{{ faq.question }}</span>
          <span class="toggle-icon" :class="{ 'active': activeFaq === index }">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </button>
        
        <transition name="fade-slide">
          <div v-if="activeFaq === index" class="faq-answer">
            <div v-html="faq.answer"></div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FAQPage',
  data() {
    return {
      activeFaq: null,
      faqs: [
        {
          question: 'What is KIDANSTORE?',
          answer: `KIDANSTORE is a contemporary African lifestyle and fashion ecosystem offering curated fashion, beauty, home essentials, and luxury lifestyle products. We combine culture, craftsmanship, and modern living to deliver a refined shopping experience.`
        },
        {
          question: 'Where is KIDANSTORE based?',
          answer: `We are based across Africa with growing physical touchpoints, delivery partners, and pop-up experiences in key cities.`
        },
        {
          question: 'How do I place an order?',
          answer: `Simply browse our collections, add items to your cart, and checkout using your preferred payment option. You will receive an order confirmation immediately after purchase.`
        },
        {
          question: 'What payment methods do you accept?',
          answer: `We accept:
            <ul>
              <li>Debit and credit cards</li>
              <li>Bank transfers</li>
              <li>Mobile payments</li>
              <li>Paystack, Flutterwave, Stripe and Paypal</li>
              <li>Select wallet options</li>
              <li>KIDAN gift cards (coming soon)</li>
              <li>Instalment/BNPL options may be available depending on your location</li>
            </ul>`
        },
        {
          question: 'How long does delivery take?',
          answer: `Delivery times vary by city and country, but most orders are fulfilled within:
            <ul>
              <li>1-3 days within major cities</li>
              <li>3-7 days nationwide</li>
              <li>7-14 days for regional/international delivery</li>
            </ul>
            You will receive tracking updates throughout the process.`
        },
        {
          question: 'What are KIDAN Delivery Points?',
          answer: `KIDAN Delivery Points are approved partner locations where customers can:
            <ul>
              <li>Pick up orders</li>
              <li>Drop off returns</li>
              <li>And later, offer more connected local delivery</li>
            </ul>
            This helps reduce delays and creates a community-powered delivery network.`
        },
        {
          question: 'Can I return or exchange items?',
          answer: `Yes. KIDANSTORE offers returns or exchanges within 7 days of receiving your order, as long as the item is unused, unwashed, and in original packaging.<br><br>(Certain products like beauty or intimate items may not be eligible.)`
        },
        {
          question: 'Do you ship internationally?',
          answer: `Yes. We ship to selected countries. International customers will see shipping options at checkout.`
        },
        {
          question: 'How do I join the KIDAN Tribe?',
          answer: `All customers automatically join the KIDAN Tribe loyalty program.<br><br>Your membership tier grows as you shop, unlocking exclusive benefits, early access, rewards, and lifestyle perks.`
        },
        {
          question: 'What are the KIDAN Tribe tiers?',
          answer: `<ul>
              <li>KIDAN Tribe Member - Silver card</li>
              <li>KIDAN Tribe Insider - Gold Card</li>
              <li>KIDAN Tribe Maven - Black Card</li>
              <li>KIDAN Tribe Royal - Maroon VIP Card</li>
            </ul>
            Each tier offers increasing rewards, benefits, and exclusive access.`
        },
        {
          question: 'How do I become a KIDAN Designer Partner?',
          answer: `Designers, brands, and creators can join KIDAN to distribute their fashion, beauty, home, or lifestyle products.<br><br>We seek emerging designers with mentorship and visibility.<br><br>Contact: <strong>Business@kidanstore.com</strong>`
        },
        {
          question: 'How do I partner as an influencer or marketer?',
          answer: `Influencers, creators, and marketers can join the KIDAN Affiliates Program to earn commissions, access campaigns, and collaborate on brand storytelling.<br><br>Contact: <strong>Business@kidanstore.com</strong>`
        },
        {
          question: 'What is the KIDAN Youth Program?',
          answer: `A development program for young Africans offering hands-on craftsmanship training, internships, business support, creative labs, and startup assistance.`
        },
        {
          question: 'How can I contact KIDANSTORE?',
          answer: `For general inquiries: <strong>hello@kidanglobal.com</strong><br>
            For support: <strong>support@kidanstore.com</strong><br>
            For press, partnerships, or wholesale, visit our Contact Page.`
        },
        {
          question: 'Where can I follow KIDAN?',
          answer: `Instagram: <strong>@kidanstore</strong><br>
            TikTok: <strong>@kidanstore</strong><br>
            Website: <strong>www.kidanstore.com</strong>`
        }
      ]
    }
  },
  methods: {
    toggleFaq(index) {
      this.activeFaq = this.activeFaq === index ? null : index;
    }
  }
}
</script>

<style scoped>
.faq-page {
  max-width: 1000px;
  margin: 0 auto;
  padding: 4rem 2rem;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.page-header {
  margin-bottom: 3rem;
}

.page-header h1 {
  font-size: 2rem;
  font-weight: 600;
  color: #000;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.page-header p {
  font-size: 1rem;
  color: #666;
  font-weight: 400;
}

.faq-content {
  max-width: 900px;
  margin: 0 auto;
}

.faq-item {
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 0;
}

.faq-question {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem 0;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
}

.faq-question:hover {
  opacity: 0.7;
}

.question-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #000;
  color: #fff;
  border-radius: 50%;
  font-size: 0.875rem;
  font-weight: 600;
  flex-shrink: 0;
}

.question-text {
  flex: 1;
  font-size: 1rem;
  font-weight: 600;
  color: #000;
  line-height: 1.5;
}

.toggle-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.3s ease;
  color: #000;
}

.toggle-icon.active {
  transform: rotate(180deg);
}

.faq-answer {
  padding: 0 0 1.5rem 3rem;
  font-size: 0.9375rem;
  line-height: 1.7;
  color: #555;
}

.faq-answer ul {
  margin: 0.75rem 0;
  padding-left: 1.25rem;
  list-style-type: disc;
}

.faq-answer li {
  margin-bottom: 0.35rem;
  color: #555;
}

.faq-answer strong {
  color: #000;
  font-weight: 600;
}

/* Transition animations */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
}

/* Responsive design */
@media (max-width: 768px) {
  .faq-page {
    padding: 2.5rem 1.25rem;
  }

  .page-header h1 {
    font-size: 1.5rem;
  }

  .page-header p {
    font-size: 0.9375rem;
  }

  .faq-question {
    padding: 1.25rem 0;
    gap: 0.75rem;
  }

  .question-icon {
    width: 28px;
    height: 28px;
    font-size: 0.8125rem;
  }

  .question-text {
    font-size: 0.9375rem;
  }

  .faq-answer {
    padding: 0 0 1.25rem 2.5rem;
    font-size: 0.875rem;
  }
}

@media (max-width: 480px) {
  .faq-page {
    padding: 2rem 1rem;
  }

  .page-header h1 {
    font-size: 1.375rem;
  }

  .faq-answer {
    padding: 0 0 1rem 0;
    margin-left: 0;
  }
}
</style>