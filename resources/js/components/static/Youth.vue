<template>
  <div class="youth-page">
    <div class="mb-10">
      <h2 class="main-heading mb-4">The KIDAN Youth</h2>
      <p class="intro-text mb-0">
        Program is designed to empower young Africans with real skills, real experience, and real opportunities. 
        Rooted in culture, craftsmanship, innovation, and regenerative living, the program goes beyond education, it 
        provides hands-on training, business support, and pathways to success.
      </p>
    </div>

    <v-row class="mb-10">
      <v-col cols="12" md="6" class="pr-md-6">
        <div class="program-item mb-8">
          <h3 class="program-heading mb-2">Craftsmanship & Maker Training</h3>
          <p class="program-description">
            Skills in fashion, product design, home goods, beauty formulation, and artisan craft.
          </p>
        </div>

        <div class="program-item mb-8">
          <h3 class="program-heading mb-2">Internships & Real Work Experience</h3>
          <p class="program-description">
            Placements within KIDAN and partner brands to learn, build, and grow.
          </p>
        </div>

        <div class="program-item mb-8">
          <h3 class="program-heading mb-2">Business Review & Mentorship</h3>
          <p class="program-description">
            Guidance on branding, pricing, product development, and strategy.
          </p>
        </div>

        <div class="program-item mb-8">
          <h3 class="program-heading mb-2">Start-up Support & Launch Assistance</h3>
          <p class="program-description">
            Helping youth-owned businesses access markets, visibility, and growth opportunities.
          </p>
        </div>

        <div class="program-item mb-8">
          <h3 class="program-heading mb-2">Community Projects & Cultural Innovation Labs</h3>
          <p class="program-description">
            Spaces to create, collaborate, and shape future-forward African ideas.
          </p>
        </div>
      </v-col>

      <v-col cols="12" md="6" class="pl-md-6">
        <img 
          src="/public/assets/img/youth1.jpg" 
          class="youth-image"
          alt="KIDAN Youth"
        />
      </v-col>
    </v-row>

    <div class="mission-section mb-10">
      <p class="mission-text mb-6">
        The KIDAN Youth Program exists to lift young creators, innovators, and entrepreneurs into the spotlight, 
        giving them the tools and support to build sustainable businesses and meaningful careers.
      </p>

      <div class="values-list mb-6">
        <p class="value-item">We don't just train youth.</p>
        <p class="value-item">We help them rise.</p>
        <p class="value-item">We help them create.</p>
        <p class="value-item">We help them launch.</p>
      </div>

      <p class="tagline">KIDAN - empowering the next generation of African creators.</p>
    </div>
  </div>
</template>

<style scoped>
.youth-page {
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

.program-item {
  text-align: left;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
}

.program-heading {
  font-size: 18px;
  font-weight: 600;
  color: #000;
  line-height: 1.4;
}

.program-description {
  font-size: 14px;
  line-height: 1.6;
  color: #666;
  margin: 0;
}

.youth-image {
  width: 100%;
  height: 100%;
  max-height: 600px;
  object-fit: cover;
  display: block;
}

.mission-section {
  text-align: left;
}

.mission-text {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
  margin-bottom: 1.5rem;
}

.values-list {
  margin-bottom: 1.5rem;
}

.value-item {
  font-size: 16px;
  line-height: 1.6;
  color: #333;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.tagline {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
  margin: 0;
}

@media (max-width: 959px) {
  .youth-page {
    padding: 2rem 1rem;
  }
  
  .main-heading {
    font-size: clamp(22px, 5vw, 28px);
  }

  .youth-image {
    max-height: 400px;
    margin-top: 2rem;
  }

  .pr-md-6,
  .pl-md-6 {
    padding-left: 12px !important;
    padding-right: 12px !important;
  }
}

@media (max-width: 599px) {
  .youth-page {
    padding: 1.5rem 1rem;
  }
  
  .main-heading {
    font-size: clamp(20px, 5vw, 24px);
    margin-bottom: 1rem;
  }
  
  .intro-text,
  .mission-text,
  .value-item,
  .tagline {
    font-size: 15px;
    line-height: 1.7;
  }
  
  .program-heading {
    font-size: 16px;
  }

  .program-description {
    font-size: 13px;
  }

  .program-item {
    margin-bottom: 1.5rem !important;
  }

  .youth-image {
    max-height: 300px;
    margin-top: 1rem;
  }
}
</style>