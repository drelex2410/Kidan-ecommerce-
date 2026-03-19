<template>
  <div class="press-page">
    <div class="press-section mb-12">
      <h2 class="section-heading mb-6">Press</h2>
      <p class="section-text mb-0">
        We collaborate with media platforms, journalists, creators, and cultural storytellers to share the KIDAN vision 
        with the world. Our press features spotlight our work in design, lifestyle, regenerative living, and African 
        innovation, amplifying the voices and stories that shape our universe.
      </p>
    </div>

    <div class="popup-section mb-12">
      <h2 class="section-heading mb-6">Virtual Pop-Ups</h2>
      <p class="section-text mb-8">
        KIDAN hosts virtual pop-up experiences that allow customers around the world to explore our collections, 
        designers, and lifestyle products from anywhere. These digital activations offer early access to new drops, 
        limited editions, and behind-the-scenes previews of the KIDAN universe.
      </p>

      <div class="media-grid mb-2">
        <div class="media-card" v-for="i in 4" :key="'popup-' + i">
          <div class="media-thumbnail">
            <img src="/public/assets/img/press1.jpg" alt="Virtual Pop-Up" />
            <div class="play-button">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </div>
          </div>
          <div class="media-meta">
            <p class="media-type">Video • 11 November 2025</p>
            <h4 class="media-title">Nigerian x India<br>Fusion The New Wave</h4>
            <p class="media-handle">@kidanstoreNG</p>
          </div>
        </div>
      </div>

      <div class="view-more-wrapper">
        <a href="#" class="view-more-link">
          <span>View More</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 17L17 7M17 7H7M17 7V17"/>
          </svg>
        </a>
      </div>
    </div>

    <div class="events-section mb-12">
      <h2 class="section-heading mb-6">Events</h2>
      <p class="section-text mb-8">
        From intimate brand moments to large-scale cultural activations, KIDAN events are designed to connect 
        people, celebrate creativity, and build community. We curate experiences that blend fashion, craft, design, 
        wellness, hospitality, and culture, both online and in physical spaces.
      </p>

      <div class="media-grid mb-2">
        <div class="media-card" v-for="i in 4" :key="'event-' + i">
          <div class="media-thumbnail">
            <img src="/public/assets/img/press2.jpg" alt="Event" />
            <div class="play-button">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </div>
          </div>
          <div class="media-meta">
            <p class="media-type">Magazine • 11 November 2025</p>
            <h4 class="media-title">Nigerian x India<br>Fusion The New Wave</h4>
            <p class="media-handle">By Irnulo Yimika</p>
          </div>
        </div>
      </div>

      <div class="view-more-wrapper">
        <a href="#" class="view-more-link">
          <span>View More</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 17L17 7M17 7H7M17 7V17"/>
          </svg>
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.press-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 3rem 1rem;
}

.press-section,
.popup-section,
.events-section {
  text-align: left;
}

.section-heading {
  font-size: clamp(24px, 5vw, 32px);
  font-weight: 700;
  color: #000;
  line-height: 1.2;
}

.section-text {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
}

.media-grid {
  display: grid;
  grid-template-columns: repeat(4, 219px);
  gap: 1.5rem;
  justify-content: start;
}

.media-card {
  text-align: left;
  width: 219px;
}

.media-thumbnail {
  position: relative;
  width: 219px;
  height: 260px;
  overflow: hidden;
  border-radius: 4px;
  margin-bottom: 0.75rem;
}

.media-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.play-button {
  position: absolute;
  bottom: 12px;
  left: 12px;
  width: 40px;
  height: 40px;
  background: #ff4500;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.3s ease;
}

.play-button:hover {
  background: #e03d00;
}

.media-meta {
  padding: 0;
}

.media-type {
  font-size: 11px;
  color: #666;
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.media-title {
  font-size: 14px;
  font-weight: 600;
  color: #000;
  line-height: 1.4;
  margin-bottom: 0.25rem;
}

.media-handle {
  font-size: 12px;
  color: #999;
  margin: 0;
}

.view-more-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-right: 0;
}

.view-more-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 14px;
  font-weight: 600;
  color: #000;
  text-decoration: none;
  transition: opacity 0.3s ease;
}

.view-more-link:hover {
  opacity: 0.7;
}

.view-more-link svg {
  flex-shrink: 0;
}

@media (max-width: 1200px) {
  .media-grid {
    grid-template-columns: repeat(auto-fit, 219px);
  }
}

@media (max-width: 959px) {
  .press-page {
    padding: 2rem 1rem;
  }

  .media-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .media-card {
    width: 100%;
  }

  .media-thumbnail {
    width: 100%;
    height: auto;
    aspect-ratio: 219/260;
  }

  .section-heading {
    font-size: clamp(22px, 5vw, 28px);
  }
}

@media (max-width: 599px) {
  .press-page {
    padding: 1.5rem 1rem;
  }

  .media-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }

  .section-heading {
    font-size: clamp(20px, 5vw, 24px);
    margin-bottom: 1rem;
  }

  .section-text {
    font-size: 15px;
    line-height: 1.7;
  }

  .media-title {
    font-size: 13px;
  }

  .media-type,
  .media-handle {
    font-size: 10px;
  }

  .play-button {
    width: 32px;
    height: 32px;
  }

  .play-button svg {
    width: 16px;
    height: 16px;
  }

  .view-more-link {
    font-size: 13px;
  }

  .view-more-link svg {
    width: 14px;
    height: 14px;
  }
}
</style>