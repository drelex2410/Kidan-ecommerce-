<!-- StaticLayout.vue -->
<template>
  <v-container fluid class="pa-0 ma-0">
    <!-- Foundation Section -->
    <div class="foundation-section">
      <v-row no-gutters>
        <!-- Left: Text Content -->
        <v-col cols="12" md="7" class="foundation-text-col">
          <div class="px-6 px-sm-8 px-md-16 py-6 py-sm-8 py-md-12">
            <h1 class="text-h3 text-sm-h2 font-weight-bold mb-2">OUR FOUNDATION</h1>
            <p class="text-body-2 text-sm-body-1 mb-0" style="color: #999;">Walk with us and find out who we are.</p>
          </div>
        </v-col>
        
        <!-- Right: Image with Logo Overlay -->
        <v-col cols="12" md="5" class="pa-0">
          <div class="foundation-image-wrapper">
            <!-- Responsive Image Container -->
            <div class="responsive-image-container">
              <img 
                src="/public/assets/img/about_hero.jpg" 
                class="foundation-image"
                alt="Foundation"
                :style="imageStyle"
              />
              <!-- Logo Overlay - Bottom Left -->
              <div class="logo-overlay">
                <div class="logo-circle-large">
                  <span class="logo-text-large">AK</span>
                </div>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>
      
      <!-- Foundation Description Text - Below Image -->
      <v-row no-gutters>
        <v-col cols="12" class="px-6 px-sm-8 px-md-16 py-6 py-sm-8 py-md-10">
          <p class="text-body-2 text-sm-body-1 mb-0 text-center description-text">
            <strong>KIDAN</strong> is a lifestyle ecosystem that integrates <strong>African culture, luxury, fashion, hospitality, and regenerative 
            living</strong> into a unified, modern experience. Rooted in <strong>contemporary African aesthetics</strong> and driven by 
            <strong>sustainable innovation</strong>, KIDAN exists to shape a new narrative - one where Africa's heritage and future meet 
            beautifully. We are committed to "creating a beautiful African future through design, nature, craftsmanship, 
            and products." Our work bridges physical spaces, curated products, and immersive experiences creating a 
            world where elegance, culture, and innovation coexist seamlessly.
          </p>
        </v-col>
      </v-row>
    </div>

    <!-- Horizontal Navigation -->
    <div class="horizontal-nav">
      <v-container fluid class="px-6 px-sm-8 px-md-16">
        <div class="d-flex justify-center align-center flex-wrap py-3 nav-container">
          <router-link 
            v-for="link in horizontalLinks" 
            :key="link.to"
            :to="'/' + link.to"
            class="nav-link text-body-2 text-decoration-none"
            active-class="nav-link-active"
          >
            {{ link.text }}
          </router-link>
        </div>
      </v-container>
    </div>

    <!-- Page Content -->
    <div class="page-content">
      <v-container fluid class="px-6 px-sm-8 px-md-16 py-6 py-sm-8 py-md-12">
        <router-view />
      </v-container>
    </div>
  </v-container>
</template>

<script>
export default {
  data: () => ({
    horizontalLinks: [
      { text: "About Us", to: "about" },
      { text: "Career", to: "career" },
      { text: "Kidan Tribe", to: "tribe" },
      { text: "Partnership & Affiliate", to: "partnership" },
      { text: "Kidan Youth", to: "youth" },
      { text: "Press & Events", to: "press" },
    ],
    imageDimensions: {
      width: 540,
      height: 440
    }
  }),
  computed: {
    imageStyle() {
      return {
        width: `${this.imageDimensions.width}px`,
        height: `${this.imageDimensions.height}px`,
        maxWidth: '100%',
        objectFit: 'cover'
      };
    }
  }
};
</script>

<style scoped>
.foundation-section {
  background: #FFFBF3;
  border-bottom: 1px solid #e0e0e0;
}
.foundation-text-col {
  display: flex;
  align-items: flex-start;
}
.foundation-image-wrapper {
  position: relative;
  width: 100%;
  overflow: hidden;
}
.responsive-image-container {
  position: relative;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f0f0f0;
}
.foundation-image {
  display: block;
  object-fit: cover;
  transition: all 0.3s ease;
}
.logo-overlay {
  position: absolute;
  bottom: 20px;
  left: 20px;
  z-index: 10;
}
.logo-circle-large {
  width: 70px;
  height: 70px;
  background: #000;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.logo-text-large {
  color: #fff;
  font-size: 24px;
  font-weight: bold;
  letter-spacing: 2px;
}
.description-text {
  line-height: 1.7;
  color: #333;
  font-size: 14px;
}
.horizontal-nav {
  background: #FFFBF3;
  border-bottom: 1px solid #e0e0e0;
}
.nav-container {
  gap: 24px;
}
.nav-link {
  color: #666;
  font-weight: 500;
  font-size: 13px;
  padding: 8px 0;
  position: relative;
  transition: color 0.3s;
  white-space: nowrap;
}
.nav-link:hover {
  color: #000;
}
.nav-link-active {
  color: #8b0000;
  font-weight: 600;
}
.nav-link-active::after {
  content: '';
  position: absolute;
  bottom: -16px;
  left: 0;
  right: 0;
  height: 2px;
  background: #8b0000;
}
.page-content {
  background: #fafafa;
  min-height: 500px;
}

/* Mobile (up to 599px) */
@media (max-width: 599px) {
  .responsive-image-container {
    height: auto;
    min-height: 300px;
  }
  .foundation-image {
    width: 100% !important;
    height: auto !important;
    min-height: 300px;
    max-height: 440px;
  }
  .logo-overlay {
    bottom: 15px;
    left: 15px;
  }
  .logo-circle-large {
    width: 60px;
    height: 60px;
  }
  .logo-text-large {
    font-size: 20px;
    letter-spacing: 1px;
  }
  .description-text {
    font-size: 13px;
    line-height: 1.65;
  }
  .nav-container {
    gap: 16px;
  }
  .nav-link {
    font-size: 12px;
  }
}

/* Tablet (600px - 959px) */
@media (min-width: 600px) and (max-width: 959px) {
  .responsive-image-container {
    height: 440px;
  }
  .foundation-image {
    width: 540px !important;
    height: 440px !important;
  }
  .logo-overlay {
    bottom: 25px;
    left: 25px;
  }
  .logo-circle-large {
    width: 80px;
    height: 80px;
  }
  .logo-text-large {
    font-size: 26px;
  }
  .description-text {
    font-size: 15px;
    line-height: 1.75;
  }
  .nav-container {
    gap: 32px;
  }
  .nav-link {
    font-size: 13.5px;
  }
}

/* Desktop (960px - 1263px) */
@media (min-width: 960px) and (max-width: 1263px) {
  .responsive-image-container {
    height: 440px;
  }
  .foundation-image {
    width: 540px !important;
    height: 440px !important;
  }
  .logo-overlay {
    bottom: 30px;
    left: 30px;
  }
  .logo-circle-large {
    width: 90px;
    height: 90px;
  }
  .logo-text-large {
    font-size: 30px;
    letter-spacing: 2.5px;
  }
  .description-text {
    font-size: 16px;
    line-height: 1.8;
  }
  .nav-container {
    gap: 48px;
  }
  .nav-link {
    font-size: 14px;
  }
}

/* Large Desktop (1264px and above) */
@media (min-width: 1264px) {
  .responsive-image-container {
    height: 440px;
  }
  .foundation-image {
    width: 540px !important;
    height: 440px !important;
  }
  .logo-overlay {
    bottom: 35px;
    left: 35px;
  }
  .logo-circle-large {
    width: 100px;
    height: 100px;
  }
  .logo-text-large {
    font-size: 32px;
    letter-spacing: 3px;
  }
}

/* Extra large screens (1400px+) - Optional enhancement */
@media (min-width: 1400px) {
  .responsive-image-container {
    height: 500px;
  }
  .foundation-image {
    width: 600px !important;
    height: 500px !important;
  }
}

/* For very small mobile devices */
@media (max-width: 399px) {
  .responsive-image-container {
    min-height: 250px;
  }
  .foundation-image {
    min-height: 250px;
  }
  .logo-overlay {
    bottom: 10px;
    left: 10px;
  }
  .logo-circle-large {
    width: 50px;
    height: 50px;
  }
  .logo-text-large {
    font-size: 18px;
    letter-spacing: 1px;
  }
  .nav-container {
    gap: 12px;
  }
}
</style>