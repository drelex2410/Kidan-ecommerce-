<template>
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-columns">
        <div class="footer-column">
          <h4 class="footer-title">Shop</h4>
          <ul class="footer-links">
            <li><a href="#women">Women</a></li>
            <li><a href="#men">Men</a></li>
            <li><a href="#beauty">Beauty</a></li>
            <li><a href="#home">Home & Living</a></li>
          </ul>
        </div>

        <div class="footer-column">
          <h4 class="footer-title">About</h4>
          <ul class="footer-links">
            <li><a href="#story">Our Story</a></li>
            <li><a href="#designers">Designers</a></li>
            <li><a href="#journal">Journal</a></li>
            <li><a href="#sustainability">Sustainability</a></li>
          </ul>
        </div>

        <div class="footer-column">
          <h4 class="footer-title">Customer Care</h4>
          <ul class="footer-links">
            <li><a href="#contact">Contact Us</a></li>
            <li><a href="#shipping">Shipping & Returns</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#size-guide">Size Guide</a></li>
          </ul>
        </div>

        <div class="footer-column">
          <h4 class="footer-title">Connect</h4>
          <ul class="footer-links">
            <li><a href="#instagram">Instagram</a></li>
            <li><a href="#facebook">Facebook</a></li>
            <li><a href="#twitter">Twitter</a></li>
            <li><a href="#pinterest">Pinterest</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p class="footer-copyright">
          &copy; {{ currentYear }} Kidan. Luxury African Heritage Fashion. All rights reserved.
        </p>
        <div class="footer-legal">
          <a href="#privacy">Privacy Policy</a>
          <span class="separator">|</span>
          <a href="#terms">Terms of Service</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref } from 'vue';

const currentYear = ref(new Date().getFullYear());
</script>

<style scoped>
.footer {
  background-color: var(--nav-bg);
  color: var(--nav-text);
  padding: 4rem 0 2rem;
  margin-top: 4rem;
  transition: background-color 0.3s ease;
}

.footer-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.footer-columns {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 3rem;
  margin-bottom: 3rem;
  padding-bottom: 3rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-column {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.footer-title {
  font-size: 1.1rem;
  font-weight: 500;
  font-family: 'Lato', sans-serif;
  letter-spacing: 1px;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
  color: #b8a688 !important; /* force color */
}

.footer-links {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-links a {
  font-family: 'Lato', sans-serif;
  font-size: 0.95rem;
  color: var(--nav-text);
  opacity: 0.8;
  transition: opacity 0.2s ease;
}

.footer-links a:hover {
  opacity: 1;
}

.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.footer-copyright {
  font-family: 'Lato', sans-serif;
  font-size: 0.9rem;
  opacity: 0.7;
}

.footer-legal {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-family: 'Lato', sans-serif;
  font-size: 0.9rem;
}

.footer-legal a {
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.footer-legal a:hover {
  opacity: 1;
}

.separator {
  opacity: 0.5;
}

/* ===================================
   RESPONSIVE DESIGN
   =================================== */

@media (max-width: 1024px) {
  .footer-columns {
    grid-template-columns: repeat(2, 1fr);
    gap: 2.5rem;
  }
}

@media (max-width: 768px) {
  .footer {
    padding: 3rem 0 1.5rem;
  }

  .footer-container {
    padding: 0 1rem;
  }

  .footer-columns {
    grid-template-columns: 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .footer-copyright,
  .footer-legal {
    font-size: 0.85rem;
  }
}
</style>