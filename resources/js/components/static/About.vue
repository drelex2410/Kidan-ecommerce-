<template>
  <div class="about-page">
    <div class="mb-10">
      <h2 class="text-h3 font-weight-bold mb-4">About Kidan</h2>
      <p class="text-body-1" style="max-width: 100%; color: #333; line-height: 1.7;">
        KIDANSTORE is a modern African luxury and lifestyle house, created to celebrate elegance, heritage, and the 
        evolving expression of the global African woman.
      </p>
    </div>

    <v-row class="mb-0">
      <v-col cols="12" md="6" class="pr-md-2 pb-0 pa-0">
        <img 
          src="/public/assets/img/about1.jpg" 
          class="main-image"
          alt="Main"
        />
      </v-col>

      <v-col cols="12" md="6" class="pl-md-2 pb-0 pa-0">
        <div class="what-card pa-10" style="color:white !important;">
          <h3 class="text-h4 font-weight-bold white--text mb-6">What we do</h3>
          <p class="text-body-1 white--text mb-2" style="opacity: 0.95; line-height: 1.9;">
            At KIDANSTORE, we curate:
          </p>
          <ul class="white--text mb-6 pl-5" style="opacity: 0.95; line-height: 2; list-style: none;">
            <li>- Contemporary fashion</li>
            <li>- African luxury designers</li>
            <li>- Accessories, beauty, and lifestyle pieces</li>
            <li>- Home, fragrance, and everyday essentials</li>
            <li>- Exclusive KIDAN collections</li>
          </ul>
          <p class="text-body-1 white--text mb-0" style="opacity: 0.95; line-height: 1.9;">
            KIDANSTORE blends premium aesthetic standards with the warmth of African identity, creating a shopping experience that feels both 
            global and deeply personal.
          </p>
        </div>
      </v-col>
    </v-row>

    <v-row class="mb-0">
      <v-col cols="12" md="6" class="pr-md-2 pb-0 pa-0">
        <div class="vision-mission-card pa-10">
          <div class="mb-10">
            <h3 class="text-h4 font-weight-bold mb-4">Our Vision</h3>
            <p class="text-body-1 mb-0" style="line-height: 1.9; color: #333;">
              To become Africa's leading Fashion and Lifestyle destination, known globally for Curation, Craftsmanship, Culture and Accessibility. 
              KIDANSTORE is more than an e-commerce platform - it is a modern luxury house and brand universe.
            </p>
          </div>

          <div>
            <h3 class="text-h4 font-weight-bold mb-4">Our Mission</h3>
            <p class="text-body-1 mb-0" style="line-height: 1.9; color: #333;">
              To make exceptional style accessible, intentional, and beautifully curated. Founded with a belief that fashion is both identity and 
              power, KIDANSTORE brings together premium fashion, African designers, global luxury accents, and everyday essentials into one 
              refined, seamless shopping experience.
            </p>
          </div>
        </div>
      </v-col>

      <v-col cols="12" md="6" class="pl-md-2 pb-0 pa-0">
        <img 
          src="/public/assets/img/about2.jpg" 
          class="vision-image"
          alt="Vision"
        />
      </v-col>
    </v-row>

    <div class="quote-section text-center py-12 mt-6">
      <blockquote class="quote-text mb-2">
        "Fashion is about dressing according to what's fashionable.<br>
        Style is more about being yourself."
      </blockquote>
      <p class="text-body-2" style="color: #999; font-style: italic; letter-spacing: 1px;">— OSCAR DE LA RENTA</p>
    </div>
  </div>
</template>

<style scoped>
.main-image, .vision-image {
  width: 720px;
  height: 480px;
  object-fit: cover;
  display: block;
  max-width: 100%;
}

.what-card {
  background: #4a3729;
  height: 480px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.vision-mission-card {
  background: #e8d5c4;
  height: 480px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.quote-section {
  background: transparent;
}
.quote-text {
  color: #333;
  font-weight: 400;
  line-height: 1.5;
  font-style: normal;
  font-size: 1.6rem;
}

@media (max-width: 1263px) {
  .main-image, .vision-image {
    width: 100%;
    height: auto;
    aspect-ratio: 3/2;
  }
}

@media (max-width: 959px) {
  .main-image, .vision-image {
    width: 100%;
    height: auto;
    aspect-ratio: 3/2;
  }
  .what-card, .vision-mission-card {
    height: auto;
    min-height: 400px;
    margin-top: 0;
  }
  .pa-0 {
    padding: 0 !important;
  }
}

@media (max-width: 599px) {
  .main-image, .vision-image {
    width: 100%;
    height: auto;
    aspect-ratio: 4/3;
  }
  .what-card, .vision-mission-card {
    min-height: 350px;
    padding: 2rem !important;
  }
}
</style>