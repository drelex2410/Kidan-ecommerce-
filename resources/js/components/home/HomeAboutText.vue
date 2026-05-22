<template>
  <v-container fluid class="pa-0">
    <v-row no-gutters class="hero-section">
      <v-col cols="12" class="position-relative">
        <div class="background-shapes">
          <div class="shape-red"></div>
          <div class="shape-cream"></div>
          <div class="shape-brown"></div>
        </div>
        
        <div v-if="!loading" class="content-wrapper">
          <div class="text-content" v-html="contentHtml"></div>
          
          <div class="media-container">
            <div class="media-wrapper" v-if="mediaUrl">
              <!-- Video -->
              <div v-if="mediaType === 'video' && videoId" class="video-container">
                <button
                  v-if="!isVideoPlaying"
                  type="button"
                  class="video-preview"
                  @click="startVideoPlayback"
                  :aria-label="`Play ${videoTitle}`"
                >
                  <img
                    :src="videoPosterUrl"
                    alt=""
                    class="video-poster"
                  >
                  <div class="play-overlay">
                    <div class="play-button">
                      <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                        <circle cx="40" cy="40" r="40" fill="#FF0000" opacity="0.95"/>
                        <path d="M32 24L56 40L32 56V24Z" fill="white"/>
                      </svg>
                    </div>
                  </div>
                </button>
                <iframe
                  v-else
                  width="100%"
                  height="100%"
                  :src="activeEmbedUrl"
                  :title="videoTitle"
                  frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  referrerpolicy="strict-origin-when-cross-origin"
                  allowfullscreen
                ></iframe>
              </div>
              
              <!-- Image -->
              <div v-else class="image-container">
                <img :src="mediaUrl" alt="Fashion content" class="hero-image">
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="loading-wrapper">
          <v-progress-circular indeterminate color="black" size="64"></v-progress-circular>
        </div>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  data: () => ({
    loading: true,
    contentHtml: "",
    configuredYoutubeUrl: null,
    mediaUrl: null,
    mediaType: 'image',
    isVideoPlaying: false,
  }),
  async created() {
    try {
      const homeRes = await this.call_api("get", "setting/home/home_about_text");
      if (homeRes.data.success) {
        this.applyHomeAboutPayload(homeRes.data.data);
      }

      if (!this.mediaUrl) {
        const firstBlog = await this.fetchHomepageLeadStory();
        if (firstBlog) {
          this.mediaUrl = firstBlog.banner;
          this.mediaType = 'image';
        }
      }
    } catch (error) {
      console.error("Error fetching data:", error);
    } finally {
      this.loading = false;
    }
  },
  methods: {
    applyHomeAboutPayload(payload) {
      if (payload && typeof payload === "object" && !Array.isArray(payload)) {
        this.contentHtml = payload.content || "";

        if (this.isYoutubeUrl(payload.youtube_url)) {
          this.configuredYoutubeUrl = payload.youtube_url;
          this.mediaUrl = payload.youtube_url;
          this.mediaType = "video";
          this.isVideoPlaying = false;
        }

        return;
      }

      this.contentHtml = payload || "";
    },
    async fetchHomepageLeadStory() {
      const requests = [
        "recent-blogs?limit=1",
        "all-blogs/search?page=1&per_page=1",
        "all-blog-categories",
      ];

      for (const url of requests) {
        try {
          const res = await this.call_api("get", url);
          const blogs = this.extractBlogsFromResponse(res?.data);

          if (blogs.length > 0) {
            return blogs[0];
          }
        } catch (error) {
          console.warn(`Homepage lead story request failed for ${url}`, error);
        }
      }

      return null;
    },
    extractBlogsFromResponse(data) {
      if (!data || data.success === false) {
        return [];
      }

      if (Array.isArray(data?.blogs?.data)) {
        return data.blogs.data;
      }

      if (Array.isArray(data?.recentBlogs?.data)) {
        return data.recentBlogs.data;
      }

      return [];
    },
    isYoutubeUrl(url) {
      return !!this.extractYouTubeId(url);
    },
    startVideoPlayback() {
      this.isVideoPlaying = true;
    },
    getEmbedUrl(url) {
      const videoId = this.extractYouTubeId(url);
      if (videoId) {
        return `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1&playsinline=1&controls=1&iv_load_policy=3`;
      }
      return null;
    },
    extractYouTubeId(url) {
      if (typeof url !== "string" || url.trim() === "") {
        return null;
      }

      try {
        const parsedUrl = new URL(url);
        const hostname = parsedUrl.hostname.replace(/^www\./, "");

        if (hostname === "youtu.be") {
          const candidate = parsedUrl.pathname.split("/").filter(Boolean)[0];
          return candidate && candidate.length === 11 ? candidate : null;
        }

        if (hostname === "youtube.com" || hostname === "m.youtube.com" || hostname === "youtube-nocookie.com") {
          if (parsedUrl.searchParams.get("v")) {
            const candidate = parsedUrl.searchParams.get("v");
            return candidate && candidate.length === 11 ? candidate : null;
          }

          const segments = parsedUrl.pathname.split("/").filter(Boolean);
          const embedIndex = segments.findIndex((segment) => ["embed", "shorts", "live", "v"].includes(segment));

          if (embedIndex !== -1 && segments[embedIndex + 1]) {
            const candidate = segments[embedIndex + 1];
            return candidate.length === 11 ? candidate : null;
          }
        }
      } catch (error) {
        return null;
      }

      return null;
    }
  },
  computed: {
    videoId() {
      return this.mediaType === "video" ? this.extractYouTubeId(this.mediaUrl) : null;
    },
    activeEmbedUrl() {
      return this.mediaType === "video" && this.isVideoPlaying
        ? this.getEmbedUrl(this.mediaUrl)
        : null;
    },
    videoPosterUrl() {
      return this.videoId
        ? `https://i.ytimg.com/vi/${this.videoId}/maxresdefault.jpg`
        : "";
    },
    videoTitle() {
      return "Fashion Trends Video";
    }
  }
};
</script>

<style scoped>
.hero-section {
  min-height: clamp(520px, 58vw, 680px);
  position: relative;
  overflow: hidden;
  background: #d4cdb8;
}

.background-shapes {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.shape-red {
  position: absolute;
  left: 0;
  bottom: 0;
  width: min(30%, 460px);
  height: min(82%, 560px);
  background: #8B0000;
  border-radius: 0 300px 0 0;
  z-index: 1;
}

.shape-cream {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: #d4cdb8;
  clip-path: polygon(0 0, 78% 0, 75% 100%, 18% 100%);
  z-index: 2;
}

.shape-brown {
  position: absolute;
  right: 0;
  top: 0;
  width: min(22%, 360px);
  height: min(28%, 190px);
  background: #3a2820;
  clip-path: polygon(25% 0, 100% 0, 100% 100%, 60% 100%);
}

.content-wrapper {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: min(96%, 1540px);
  padding: clamp(48px, 5vw, 88px) clamp(20px, 4.5vw, 72px);
  min-height: clamp(520px, 58vw, 680px);
  gap: clamp(32px, 4vw, 72px);
  margin: 0 auto;
}

.loading-wrapper {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: clamp(520px, 58vw, 680px);
}

.text-content {
  flex: 0 1 42%;
  max-width: 560px;
  min-width: 0;
  color: #000;
}

.text-content::v-deep * {
  color: #000 !important;
}

.text-content::v-deep h1 {
  font-size: clamp(2.7rem, 5vw, 5rem);
  font-weight: 700;
  line-height: 1.05;
  margin-bottom: 24px;
  font-family: 'Open Sans', sans-serif;
  letter-spacing: -1.6px;
  text-wrap: balance;
  max-width: 10ch;
}

.text-content::v-deep h2 {
  font-size: clamp(2rem, 4vw, 3.8rem);
  font-weight: 700;
  line-height: 1.08;
  margin-bottom: 20px;
  letter-spacing: -1px;
  text-wrap: balance;
  max-width: 11ch;
}

.text-content::v-deep h3 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 16px;
}

.text-content::v-deep p {
  font-size: clamp(1rem, 1.45vw, 1.35rem);
  color: #4a4a4a !important;
  font-weight: 400;
  margin-bottom: 16px;
  line-height: 1.55;
  font-family: 'Open Sans', sans-serif;
  max-width: 28ch;
}

.text-content::v-deep strong,
.text-content::v-deep b {
  font-weight: 700;
}

.text-content::v-deep ul,
.text-content::v-deep ol {
  margin-left: 20px;
  margin-bottom: 16px;
}

.text-content::v-deep li {
  margin-bottom: 8px;
}

.media-container {
  flex: 0 1 55%;
  max-width: 830px;
  min-width: 0;
  position: relative;
}

.media-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
}

.video-container {
  position: relative;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
}

.video-preview {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
}

.video-poster {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 24px;
}

.video-container iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 24px;
}

.play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.play-button {
  transition: transform 0.3s ease;
  filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
}

.media-wrapper:hover .play-button {
  transform: scale(1.15);
}

.image-container {
  position: relative;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
}

.hero-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 24px;
}

@media (max-width: 1200px) {
  .content-wrapper {
    width: min(97%, 1440px);
    padding: 64px 40px;
    gap: 44px;
  }
  
  .text-content::v-deep h1 {
    max-width: 11ch;
  }
  
  .text-content::v-deep h2 {
    max-width: 12ch;
  }
}

@media (max-width: 960px) {
  .hero-section {
    min-height: unset;
  }

  .content-wrapper {
    flex-direction: column;
    width: min(97%, 860px);
    min-height: unset;
    padding: 48px 24px 56px;
    gap: 32px;
    text-align: center;
  }
  
  .text-content {
    max-width: 100%;
  }
  
  .text-content::v-deep h1 {
    max-width: 100%;
  }
  
  .text-content::v-deep h2 {
    max-width: 100%;
  }

  .text-content::v-deep p {
    max-width: 34ch;
    margin-inline: auto;
  }
  
  .media-container {
    width: 100%;
    max-width: min(100%, 760px);
  }
  
  .shape-red {
    width: min(48%, 320px);
    height: 56%;
    left: -6%;
    top: auto;
  }
  
  .shape-cream {
    clip-path: polygon(0 0, 100% 0, 100% 73%, 0 85%);
  }
  
  .shape-brown {
    width: min(34%, 220px);
    height: 18%;
  }
}

@media (max-width: 600px) {
  .content-wrapper {
    width: 100%;
    padding: 36px 16px 44px;
    gap: 24px;
  }
  
  .text-content::v-deep h1 {
    margin-bottom: 16px;
  }
  
  .text-content::v-deep h2 {
    margin-bottom: 14px;
  }
  
  .text-content::v-deep p {
    max-width: 100%;
  }
  
  .shape-red {
    width: min(58%, 220px);
    height: 40%;
    left: -14%;
    border-radius: 0 220px 0 0;
  }

  .shape-brown {
    width: min(34%, 150px);
    height: 14%;
  }

  .media-wrapper,
  .video-poster,
  .video-container iframe,
  .hero-image {
    border-radius: 18px;
  }

  .play-button svg {
    width: 60px;
    height: 60px;
  }
}
</style>
