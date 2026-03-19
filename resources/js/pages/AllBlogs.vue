<template>
  <div class="journal-page">
    <section v-if="!loading && !isFilteredView && activeHero" class="journal-hero-shell">
      <div class="journal-hero-index">
        <button
          v-for="(post, index) in heroPosts"
          :key="post.slug"
          type="button"
          :class="['journal-hero-index-item', { 'is-active': index === heroIndex }]"
          @click="setHeroIndex(index)"
        >
          {{ String(index + 1).padStart(2, '0') }}
        </button>
      </div>

      <div class="journal-hero">
        <transition name="journal-hero-fade" mode="out-in">
          <div :key="`hero-copy-${activeHero.slug || heroIndex}`" class="journal-hero-copy">
            <p class="journal-eyebrow">
              {{ activeHero.category || 'Magazine' }}
              <span class="journal-dot">•</span>
              {{ activeHero.created_at }}
            </p>
            <h1 class="journal-hero-title">{{ activeHero.title }}</h1>
            <p v-if="activeHero.author" class="journal-hero-author">By {{ activeHero.author }}</p>

            <div class="journal-hero-actions">
              <button type="button" class="journal-read-button" @click="openBlogModal(activeHero)">
                {{ activeHero.hero_button_label || 'Read' }}
              </button>

              <div v-if="heroPosts.length > 1" class="journal-hero-nav">
                <button type="button" class="journal-arrow" @click="navigateHero('prev')">
                  <i class="las la-arrow-left"></i>
                </button>
                <button type="button" class="journal-arrow" @click="navigateHero('next')">
                  <i class="las la-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>
        </transition>

        <div class="journal-hero-media">
          <transition name="journal-hero-fade" mode="out-in">
            <div :key="`hero-media-${activeHero.slug || heroIndex}`" class="journal-hero-image-frame">
              <img
                v-if="activeHero.banner"
                :src="activeHero.banner"
                :alt="activeHero.title"
                class="journal-hero-image"
              >
              <div v-else class="journal-image-placeholder">No image available</div>
            </div>
          </transition>
        </div>
      </div>
    </section>

    <section class="journal-toolbar">
      <div class="journal-toolbar-head">
        <p class="journal-section-label">
          <span v-if="isFilteredView && searchKeyword">Search results for "{{ searchKeyword }}"</span>
          <span v-else-if="isFilteredView && currentCategory && currentCategory.name">{{ currentCategory.name }}</span>
          <span v-else>Find Magazines, Articles & Press Releases</span>
        </p>
        <p class="journal-section-count" v-if="!loading">{{ totalMaterials }} {{ totalMaterials === 1 ? 'Material' : 'Materials' }}</p>
      </div>

      <div class="journal-toolbar-controls">
        <form class="journal-search" @submit.prevent="search()">
          <input v-model="queryParamBlog.searchKeyword" type="search" :placeholder="$t('search')">
          <button type="submit" aria-label="Search">
            <i class="las la-search"></i>
          </button>
        </form>

        <div class="journal-categories" v-if="blogCategories.length">
          <router-link :to="{ name: listingRouteName }" :class="['journal-category-link', { 'is-active': !currentCategory || is_empty_obj(currentCategory) }]">
            All
          </router-link>
          <router-link
            v-for="category in blogCategories"
            :key="category.id"
            :to="{ name: filterRouteName, params: { categorySlug: category.slug } }"
            :class="['journal-category-link', { 'is-active': currentCategory && currentCategory.slug === category.slug }]"
          >
            {{ category.name }}
          </router-link>
        </div>
      </div>
    </section>

    <section v-if="!loading && !isFilteredView && firstGridPosts.length" class="journal-grid-section">
      <div class="journal-post-grid">
        <article
          v-for="post in firstGridPosts"
          :key="post.slug"
          class="journal-card"
          @click="openBlogModal(post)"
        >
          <div class="journal-card-image-wrap">
            <img v-if="post.banner" :src="post.banner" :alt="post.title" class="journal-card-image">
            <div v-else class="journal-image-placeholder journal-image-placeholder--card">No image</div>
          </div>
          <div class="journal-card-copy">
            <p class="journal-card-meta">{{ post.category || 'Magazine' }} <span class="journal-dot">•</span> {{ post.created_at }}</p>
            <h3 class="journal-card-title">{{ post.title }}</h3>
            <p v-if="post.author" class="journal-card-author">By {{ post.author }}</p>
          </div>
        </article>
      </div>
    </section>

    <section v-if="!loading && !isFilteredView && mixedSection" class="journal-mixed-section">
      <div class="journal-editorial-card">
        <div class="journal-editorial-image-wrap">
          <img
            v-if="mixedSection.image"
            :src="mixedSection.image"
            :alt="mixedSection.title"
            class="journal-editorial-image"
          >
          <div v-else class="journal-image-placeholder journal-image-placeholder--editorial">Editorial image</div>
        </div>
        <div class="journal-editorial-copy">
          <h2 class="journal-editorial-title">{{ mixedSection.title }}</h2>
          <div class="journal-editorial-content" v-html="mixedSection.content"></div>
        </div>
      </div>

      <div class="journal-product-panel">
        <article v-for="product in mixedSection.products" :key="product.id" class="journal-product-card">
          <router-link :to="{ name: 'ProductDetails', params: { slug: product.slug } }" class="journal-product-link">
            <div class="journal-product-image-wrap">
              <img v-if="product.image" :src="product.image" :alt="product.name" class="journal-product-image">
              <div v-else class="journal-image-placeholder journal-image-placeholder--product">Product image</div>
            </div>
            <div class="journal-product-copy">
              <h3 class="journal-product-title">{{ product.name }}</h3>
              <p v-if="product.description" class="journal-product-description">{{ product.description }}</p>
              <p class="journal-product-price">{{ product.formatted_price }}</p>
            </div>
          </router-link>
        </article>
      </div>
    </section>

    <section v-if="!loading && !isFilteredView && secondGridPosts.length" class="journal-grid-section">
      <div class="journal-post-grid">
        <article
          v-for="post in secondGridPosts"
          :key="post.slug"
          class="journal-card"
          @click="openBlogModal(post)"
        >
          <div class="journal-card-image-wrap">
            <img v-if="post.banner" :src="post.banner" :alt="post.title" class="journal-card-image">
            <div v-else class="journal-image-placeholder journal-image-placeholder--card">No image</div>
          </div>
          <div class="journal-card-copy">
            <p class="journal-card-meta">{{ post.category || 'Magazine' }} <span class="journal-dot">•</span> {{ post.created_at }}</p>
            <h3 class="journal-card-title">{{ post.title }}</h3>
            <p v-if="post.author" class="journal-card-author">By {{ post.author }}</p>
          </div>
        </article>
      </div>

      <div v-if="canLoadMoreSecondSection" class="journal-load-more-wrap">
        <button type="button" class="journal-load-more-button" @click="loadMoreSecondSection" :disabled="loadingMoreJournal">
          {{ loadingMoreJournal ? 'Loading...' : 'Load More' }}
        </button>
      </div>
    </section>

    <section v-if="!loading && !isFilteredView && videoCards.length" class="journal-video-section">
      <div class="journal-video-grid">
        <button
          v-for="(video, index) in videoCards"
          :key="`${video.blog_slug}-${video.video_id}`"
          type="button"
          :class="['journal-video-card', `video-card-${index + 1}`]"
          @click="openVideoModal(video)"
        >
          <img :src="video.thumbnail" :alt="video.title" class="journal-video-thumb">
          <span class="journal-video-overlay"></span>
          <span class="journal-video-play"><i class="las la-play"></i></span>
          <span class="journal-video-title">{{ video.title }}</span>
        </button>
      </div>
    </section>

    <section v-if="!loading && (isFilteredView || !journal) && blogs.length" class="journal-grid-section journal-grid-section--results">
      <div class="journal-post-grid">
        <article
          v-for="post in blogs"
          :key="post.slug"
          class="journal-card"
          @click="openBlogModal(post)"
        >
          <div class="journal-card-image-wrap">
            <img v-if="post.banner" :src="post.banner" :alt="post.title" class="journal-card-image">
            <div v-else class="journal-image-placeholder journal-image-placeholder--card">No image</div>
          </div>
          <div class="journal-card-copy">
            <p class="journal-card-meta">{{ post.category || 'Magazine' }} <span class="journal-dot">•</span> {{ post.created_at }}</p>
            <h3 class="journal-card-title">{{ post.title }}</h3>
            <p v-if="post.author" class="journal-card-author">By {{ post.author }}</p>
          </div>
        </article>
      </div>

      <div class="journal-pagination" v-if="totalPages > 1">
        <v-pagination
          v-model="queryParamBlog.page"
          :length="totalPages"
          prev-icon="las la-angle-left"
          next-icon="las la-angle-right"
          :total-visible="6"
          @update:modelValue="pageSwitch"
        ></v-pagination>
      </div>
    </section>

    <section v-if="loading" class="journal-loading">
      <div class="journal-loading-block journal-loading-block--hero"></div>
      <div class="journal-loading-grid">
        <div v-for="item in 6" :key="item" class="journal-loading-block journal-loading-block--card"></div>
      </div>
    </section>

    <section v-if="!loading && !blogs.length" class="journal-empty">
      <h2>{{ $t('no_blog_found') }}</h2>
      <p>Publish journal posts from the admin panel to populate this page.</p>
    </section>

    <div v-if="modalOpen" class="journal-modal-backdrop" @click.self="closeBlogModal">
      <div class="journal-modal">
        <button type="button" class="journal-modal-close" @click="closeBlogModal">
          <i class="las la-times"></i> <span>Close</span>
        </button>

        <div v-if="modalLoading" class="journal-modal-loading">Loading article...</div>

        <template v-else-if="activeBlogDetails">
          <div class="journal-modal-media">
            <img v-if="activeBlogDetails.banner" :src="activeBlogDetails.banner" :alt="activeBlogDetails.title" class="journal-modal-image">
            <div v-else class="journal-image-placeholder journal-image-placeholder--modal">No featured image</div>
          </div>

          <div class="journal-modal-content">
            <div class="journal-modal-header">
              <p class="journal-modal-meta">
                <span v-if="activeBlogDetails.category">{{ activeBlogDetails.category }}</span>
                <span v-if="activeBlogDetails.category && activeBlogDetails.created_at" class="journal-dot">•</span>
                <span v-if="activeBlogDetails.created_at">{{ activeBlogDetails.created_at }}</span>
              </p>
              <h2 class="journal-modal-title">{{ activeBlogDetails.title }}</h2>
              <p v-if="activeBlogDetails.author" class="journal-modal-author">By {{ activeBlogDetails.author }}</p>
            </div>

            <div v-if="activeBlogDetails.modal_summary" class="journal-modal-summary" v-html="activeBlogDetails.modal_summary"></div>
            <div class="journal-modal-body" v-html="activeBlogDetails.description"></div>

            <div v-if="activeBlogDetails.related_products && activeBlogDetails.related_products.length" class="journal-modal-products">
              <h3>Related Items</h3>
              <div class="journal-modal-product-grid">
                <article v-for="product in activeBlogDetails.related_products" :key="product.id" class="journal-product-card">
                  <router-link :to="{ name: 'ProductDetails', params: { slug: product.slug } }" class="journal-product-link" @click="closeBlogModal">
                    <div class="journal-product-image-wrap">
                      <img v-if="product.image" :src="product.image" :alt="product.name" class="journal-product-image">
                      <div v-else class="journal-image-placeholder journal-image-placeholder--product">Product image</div>
                    </div>
                    <div class="journal-product-copy">
                      <h3 class="journal-product-title">{{ product.name }}</h3>
                      <p v-if="product.description" class="journal-product-description">{{ product.description }}</p>
                      <p class="journal-product-price">{{ product.formatted_price }}</p>
                    </div>
                  </router-link>
                </article>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <div v-if="videoModalOpen && activeVideo" class="journal-video-modal-backdrop" @click.self="closeVideoModal">
      <div class="journal-video-modal">
        <button type="button" class="journal-video-modal-close" @click="closeVideoModal">
          <i class="las la-times"></i>
        </button>
        <iframe
          :src="activeVideo.embed_url"
          :title="activeVideo.title"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        ></iframe>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  head() {
    return {
      title: this.pageTitle,
    };
  },
  data: () => ({
    loading: true,
    loadingMoreJournal: false,
    modalLoading: false,
    blogs: [],
    journalFeedPosts: [],
    blogCategories: [],
    journal: null,
    currentCategory: {},
    totalPages: 1,
    totalMaterials: 0,
    searchKeyword: "",
    heroIndex: 0,
    modalOpen: false,
    activeBlogSlug: null,
    activeBlogDetails: null,
    blogCache: {},
    videoModalOpen: false,
    activeVideo: null,
    heroAutoRotateTimer: null,
    journalLoadedPage: 1,
    secondSectionVisibleCount: 6,
    queryParamBlog: {
      page: 1,
      categorySlug: null,
      searchKeyword: "",
    },
  }),
  computed: {
    routeBlogSlug() {
      return this.$route.params.slug || null;
    },
    listingRouteName() {
      return ["Journal", "JournalFilter", "JournalSearch"].includes(this.$route.name) ? "Journal" : "AllBlogs";
    },
    filterRouteName() {
      return ["Journal", "JournalFilter", "JournalSearch"].includes(this.$route.name)
        ? "JournalFilter"
        : "AllBlogsFilter";
    },
    searchRouteName() {
      return ["Journal", "JournalFilter", "JournalSearch"].includes(this.$route.name)
        ? "JournalSearch"
        : "SearchBlogs";
    },
    isFilteredView() {
      return !!this.searchKeyword || (this.currentCategory && !this.is_empty_obj(this.currentCategory));
    },
    heroPosts() {
      return this.journal?.hero_posts || [];
    },
    activeHero() {
      return this.heroPosts[this.heroIndex] || null;
    },
    firstGridPosts() {
      return this.journalFeedPosts.slice(0, 6);
    },
    mixedSection() {
      return this.journal?.mixed_section || null;
    },
    secondGridPosts() {
      return this.journalFeedPosts.slice(6, 6 + this.secondSectionVisibleCount);
    },
    videoCards() {
      return this.journal?.videos || [];
    },
    canLoadMoreSecondSection() {
      if (this.isFilteredView) {
        return false;
      }

      return this.journalFeedPosts.length > 6 && (
        this.secondSectionVisibleCount < Math.max(this.journalFeedPosts.length - 6, 0) ||
        this.journalFeedPosts.length < this.totalMaterials
      );
    },
    pageTitle() {
      if (this.searchKeyword) {
        return `${this.$i18n.t('search_results_for')} "${this.searchKeyword}"`;
      }
      if (this.currentCategory && !this.is_empty_obj(this.currentCategory)) {
        return this.currentCategory.name;
      }
      if (["Journal", "JournalFilter", "JournalSearch"].includes(this.$route.name)) {
        return "Journal";
      }
      return this.$i18n.t('all_blogs');
    },
  },
  methods: {
    async getBlogCategories() {
      const res = await this.call_api("get", "all-blog-categories");
      if (res?.data?.success) {
        this.blogCategories = res.data.data || [];
      }
    },
    async getBlogList(obj = {}) {
      this.loading = true;
      const params = { ...this.queryParamBlog, ...obj };
      let url = `all-blogs/search?page=${params.page || 1}&per_page=${params.searchKeyword || params.categorySlug ? 12 : 18}`;
      url += params.categorySlug ? `&category_slug=${params.categorySlug}` : "";
      url += params.searchKeyword ? `&searchKeyword=${encodeURIComponent(params.searchKeyword)}` : "";

      try {
        const res = await this.call_api("get", url);
        if (res?.data?.success) {
          this.blogs = res.data.blogs?.data || [];
          this.journal = res.data.journal || null;
          this.currentCategory = res.data.currentCategory || {};
          this.totalPages = res.data.totalPage || 1;
          this.totalMaterials = res.data.total || this.blogs.length;
          this.queryParamBlog.page = res.data.currentPage || 1;
          this.journalLoadedPage = res.data.currentPage || 1;
          this.searchKeyword = params.searchKeyword || "";
          this.syncJournalFeed(this.blogs, {
            append: !params.searchKeyword && !params.categorySlug && (params.page || 1) > 1,
            resetVisibleCount: (params.page || 1) <= 1 || !!params.searchKeyword || !!params.categorySlug,
          });
          if (this.heroIndex >= this.heroPosts.length) {
            this.heroIndex = 0;
          }
          this.restartHeroAutoRotate();
          if (this.routeBlogSlug && this.activeBlogSlug !== this.routeBlogSlug) {
            this.openBlogModal({ slug: this.routeBlogSlug });
          }
        }
      } catch (error) {
        this.snack({
          message: this.$i18n.t("something_went_wrong"),
          color: "red",
        });
      } finally {
        this.loading = false;
      }
    },
    search() {
      this.$router.push({
        name: this.searchRouteName,
        params: this.queryParamBlog.searchKeyword ? { searchKeyword: this.queryParamBlog.searchKeyword } : {},
        query: { page: 1 },
      }).catch(() => {});
    },
    pageSwitch(pageNumber) {
      this.$router.push({
        query: {
          ...this.$route.query,
          page: pageNumber,
        },
      }).catch(() => {});
      window.scrollTo({ top: 0, behavior: "smooth" });
    },
    setHeroIndex(index) {
      this.heroIndex = index;
      this.restartHeroAutoRotate();
    },
    navigateHero(direction) {
      if (!this.heroPosts.length) {
        return;
      }

      if (direction === "next") {
        this.heroIndex = (this.heroIndex + 1) % this.heroPosts.length;
      }

      if (direction === "prev") {
        this.heroIndex = (this.heroIndex - 1 + this.heroPosts.length) % this.heroPosts.length;
      }

      this.restartHeroAutoRotate();
    },
    syncJournalFeed(posts, options = {}) {
      const { append = false, resetVisibleCount = false } = options;

      if (this.isFilteredView) {
        this.journalFeedPosts = [];
        this.secondSectionVisibleCount = 6;
        return;
      }

      if (append) {
        const existingSlugs = new Set(this.journalFeedPosts.map((post) => post.slug));
        const nextPosts = posts.filter((post) => !existingSlugs.has(post.slug));
        this.journalFeedPosts = [...this.journalFeedPosts, ...nextPosts];
      } else {
        this.journalFeedPosts = posts;
      }

      if (resetVisibleCount) {
        this.secondSectionVisibleCount = 6;
      }
    },
    async loadMoreSecondSection() {
      const nextVisibleCount = this.secondSectionVisibleCount + 6;
      const loadedRemainingCount = Math.max(this.journalFeedPosts.length - 6, 0);

      if (nextVisibleCount <= loadedRemainingCount) {
        this.secondSectionVisibleCount = nextVisibleCount;
        return;
      }

      if (this.journalFeedPosts.length >= this.totalMaterials || this.loadingMoreJournal) {
        this.secondSectionVisibleCount = Math.min(nextVisibleCount, loadedRemainingCount);
        return;
      }

      this.loadingMoreJournal = true;

      try {
        const nextPage = this.journalLoadedPage + 1;
        const res = await this.call_api("get", `all-blogs/search?page=${nextPage}&per_page=18`);

        if (res?.data?.success) {
          const nextPosts = res.data.blogs?.data || [];
          this.totalPages = res.data.totalPage || this.totalPages;
          this.totalMaterials = res.data.total || this.totalMaterials;
          this.journalLoadedPage = res.data.currentPage || nextPage;
          this.syncJournalFeed(nextPosts, { append: true });
        }
      } finally {
        this.loadingMoreJournal = false;
        this.secondSectionVisibleCount = Math.min(nextVisibleCount, Math.max(this.journalFeedPosts.length - 6, 0));
      }
    },
    startHeroAutoRotate() {
      this.stopHeroAutoRotate();

      if (this.isFilteredView || this.heroPosts.length <= 1) {
        return;
      }

      this.heroAutoRotateTimer = window.setInterval(() => {
        this.heroIndex = (this.heroIndex + 1) % this.heroPosts.length;
      }, 5500);
    },
    stopHeroAutoRotate() {
      if (this.heroAutoRotateTimer) {
        window.clearInterval(this.heroAutoRotateTimer);
        this.heroAutoRotateTimer = null;
      }
    },
    restartHeroAutoRotate() {
      this.startHeroAutoRotate();
    },
    async openBlogModal(blog) {
      this.modalOpen = true;
      this.modalLoading = true;
      this.activeBlogSlug = blog.slug;
      this.syncBodyScroll();

      if (this.blogCache[blog.slug]) {
        this.activeBlogDetails = this.blogCache[blog.slug];
        this.modalLoading = false;
        return;
      }

      try {
        const res = await this.call_api("get", `blog/details/${blog.slug}`);
        if (res?.data?.success) {
          this.activeBlogDetails = res.data.data;
          this.blogCache = {
            ...this.blogCache,
            [blog.slug]: res.data.data,
          };
        }
      } finally {
        this.modalLoading = false;
      }
    },
    closeBlogModal() {
      this.modalOpen = false;
      this.modalLoading = false;
      this.activeBlogSlug = null;
      this.activeBlogDetails = null;
      this.syncBodyScroll();
      if (this.$route.name === "BlogDetails") {
        this.$router.replace({ name: "AllBlogs" }).catch(() => {});
      }
    },
    openVideoModal(video) {
      this.activeVideo = video;
      this.videoModalOpen = true;
      this.syncBodyScroll();
    },
    closeVideoModal() {
      this.videoModalOpen = false;
      this.activeVideo = null;
      this.syncBodyScroll();
    },
    syncBodyScroll() {
      document.body.classList.toggle("journal-modal-open", this.modalOpen || this.videoModalOpen);
    },
    handleKeydown(event) {
      if (event.key === "Escape") {
        if (this.videoModalOpen) {
          this.closeVideoModal();
          return;
        }

        if (this.modalOpen) {
          this.closeBlogModal();
        }
      }
    },
  },
  watch: {
    $route(to, from) {
      if (
        to.params.categorySlug !== from.params.categorySlug ||
        to.params.searchKeyword !== from.params.searchKeyword ||
        to.params.slug !== from.params.slug ||
        to.query.page !== from.query.page
      ) {
        this.queryParamBlog.categorySlug = to.params.categorySlug || null;
        this.queryParamBlog.searchKeyword = to.params.searchKeyword || "";
        this.queryParamBlog.page = Number(to.query.page || 1);
        this.getBlogList({
          page: this.queryParamBlog.page,
          categorySlug: this.queryParamBlog.categorySlug,
          searchKeyword: this.queryParamBlog.searchKeyword,
        });
      } else if (!to.params.slug && this.modalOpen) {
        this.closeBlogModal();
      }
    },
  },
  async created() {
    this.queryParamBlog.categorySlug = this.$route.params.categorySlug || null;
    this.queryParamBlog.searchKeyword = this.$route.params.searchKeyword || "";
    this.queryParamBlog.page = Number(this.$route.query.page || 1);
    await this.getBlogCategories();
    await this.getBlogList({
      page: this.queryParamBlog.page,
      categorySlug: this.queryParamBlog.categorySlug,
      searchKeyword: this.queryParamBlog.searchKeyword,
    });
  },
  mounted() {
    document.addEventListener("keydown", this.handleKeydown);
  },
  beforeUnmount() {
    this.stopHeroAutoRotate();
    document.removeEventListener("keydown", this.handleKeydown);
    document.body.classList.remove("journal-modal-open");
  },
};
</script>

<style scoped>
.journal-page {
  background: #f7f0e4;
  color: #171310;
  padding: 2.25rem 1.25rem 4rem;
}

.journal-hero-shell,
.journal-toolbar,
.journal-grid-section,
.journal-mixed-section,
.journal-video-section,
.journal-empty,
.journal-loading {
  max-width: 1220px;
  margin: 0 auto 3rem;
}

.journal-hero-shell {
  display: grid;
  grid-template-columns: 56px minmax(0, 1fr);
  gap: 2rem;
  align-items: stretch;
}

.journal-hero-index {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding-top: 1rem;
}

.journal-hero-index-item {
  border: 0;
  border-left: 1px solid rgba(23, 19, 16, 0.16);
  background: transparent;
  color: rgba(23, 19, 16, 0.5);
  font-size: 0.72rem;
  letter-spacing: 0.16em;
  padding: 0 0 0 0.65rem;
  text-align: left;
  transition: color 0.2s ease, border-color 0.2s ease;
}

.journal-hero-index-item.is-active {
  color: #171310;
  border-color: #171310;
}

.journal-hero {
  display: grid;
  grid-template-columns: 65fr 35fr;
  gap: 3rem;
  align-items: center;
  min-height: 430px;
}

.journal-hero-copy {
  padding: 2rem 0;
  max-width: 640px;
}

.journal-hero-fade-enter-active,
.journal-hero-fade-leave-active {
  transition: opacity 0.45s ease, transform 0.45s ease;
}

.journal-hero-fade-enter-from,
.journal-hero-fade-leave-to {
  opacity: 0;
  transform: translateY(18px);
}

.journal-eyebrow,
.journal-card-meta,
.journal-modal-meta {
  font-size: 0.75rem;
  letter-spacing: 0.03em;
  text-transform: none;
  color: rgba(23, 19, 16, 0.72);
  margin: 0 0 0.85rem;
}

.journal-dot {
  padding: 0 0.35rem;
}

.journal-hero-title,
.journal-modal-title {
  font-size: clamp(2.4rem, 4vw, 4rem);
  line-height: 0.98;
  letter-spacing: -0.04em;
  margin: 0 0 1rem;
  font-weight: 700;
}

.journal-hero-author,
.journal-card-author,
.journal-modal-author {
  margin: 0;
  font-size: 1rem;
  color: rgba(23, 19, 16, 0.72);
}

.journal-hero-actions {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-top: 2.5rem;
}

.journal-read-button {
  border: 1px solid #171310;
  background: #171310;
  color: #f7f0e4;
  border-radius: 999px;
  padding: 0.9rem 1.55rem;
  font-size: 0.9rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  transition: transform 0.2s ease, background 0.2s ease;
}

.journal-read-button:hover {
  transform: translateY(-1px);
  background: #2a211c;
}

.journal-hero-nav {
  display: flex;
  gap: 0.75rem;
}

.journal-arrow {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  border: 1px solid rgba(23, 19, 16, 0.2);
  background: rgba(255, 255, 255, 0.45);
  color: #171310;
}

.journal-hero-media {
  min-height: 430px;
}

.journal-hero-image-frame,
.journal-editorial-image-wrap,
.journal-card-image-wrap,
.journal-product-image-wrap {
  position: relative;
  overflow: hidden;
  background: rgba(23, 19, 16, 0.08);
}

.journal-hero-image-frame {
  height: 100%;
  min-height: 430px;
  border-radius: 0;
}

.journal-hero-image,
.journal-editorial-image,
.journal-card-image,
.journal-product-image,
.journal-modal-image,
.journal-video-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.journal-toolbar {
  border-top: 1px solid rgba(23, 19, 16, 0.12);
  padding-top: 0.85rem;
}

.journal-toolbar-head,
.journal-toolbar-controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.journal-toolbar-controls {
  margin-top: 1rem;
  align-items: flex-start;
}

.journal-section-label,
.journal-section-count {
  margin: 0;
  font-size: 0.88rem;
  color: rgba(23, 19, 16, 0.82);
}

.journal-search {
  display: flex;
  align-items: center;
  width: min(360px, 100%);
  border: 1px solid rgba(23, 19, 16, 0.14);
  background: rgba(255, 255, 255, 0.36);
}

.journal-search input {
  flex: 1;
  border: 0;
  background: transparent;
  padding: 0.95rem 1rem;
  color: #171310;
}

.journal-search button {
  border: 0;
  background: transparent;
  padding: 0 1rem;
  font-size: 1rem;
  color: #171310;
}

.journal-categories {
  display: flex;
  gap: 0.65rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.journal-category-link {
  color: rgba(23, 19, 16, 0.72);
  text-decoration: none;
  padding: 0.35rem 0.8rem;
  border-radius: 999px;
  border: 1px solid rgba(23, 19, 16, 0.12);
  font-size: 0.82rem;
}

.journal-category-link.is-active {
  color: #f7f0e4;
  background: #171310;
  border-color: #171310;
}

.journal-post-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.35rem;
}

.journal-card {
  cursor: pointer;
}

.journal-card-image-wrap {
  aspect-ratio: 0.92;
}

.journal-card-copy {
  padding-top: 0.7rem;
}

.journal-card-title,
.journal-editorial-title,
.journal-product-title {
  margin: 0;
  font-weight: 700;
  color: #171310;
}

.journal-card-title {
  font-size: 1.1rem;
  line-height: 1.2;
}

.journal-mixed-section {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
  gap: 1.5rem;
  align-items: start;
}

.journal-editorial-card {
  background: #dfc49b;
  padding: 1rem;
}

.journal-editorial-image-wrap {
  aspect-ratio: 1.28;
  margin-bottom: 1.25rem;
}

.journal-editorial-title {
  font-size: 1.35rem;
  line-height: 1.12;
  margin-bottom: 0.9rem;
}

.journal-editorial-content,
.journal-modal-body,
.journal-modal-summary {
  color: rgba(23, 19, 16, 0.8);
  line-height: 1.85;
  font-size: 0.98rem;
}

.journal-editorial-content :deep(p),
.journal-modal-body :deep(p),
.journal-modal-summary :deep(p) {
  margin-bottom: 1rem;
}

.journal-product-panel,
.journal-modal-product-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.journal-product-card {
  background: transparent;
}

.journal-product-link {
  color: inherit;
  text-decoration: none;
}

.journal-product-image-wrap {
  aspect-ratio: 0.86;
  margin-bottom: 0.7rem;
}

.journal-product-title {
  font-size: 1rem;
  line-height: 1.2;
  margin-bottom: 0.25rem;
}

.journal-product-description {
  margin: 0 0 0.45rem;
  color: rgba(23, 19, 16, 0.58);
  font-size: 0.88rem;
}

.journal-product-price {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
}

.journal-video-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  grid-template-rows: repeat(2, minmax(180px, 1fr));
  gap: 1rem;
}

.journal-video-card {
  position: relative;
  overflow: hidden;
  border: 0;
  padding: 0;
  background: #000;
  min-height: 180px;
}

.video-card-1 {
  grid-column: 1;
  grid-row: 1;
}

.video-card-2 {
  grid-column: 1;
  grid-row: 2;
}

.video-card-3 {
  grid-column: 2;
  grid-row: 1 / span 2;
}

.video-card-4 {
  grid-column: 3;
  grid-row: 1 / span 2;
}

.journal-video-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.48));
}

.journal-video-play {
  position: absolute;
  inset: 50% auto auto 50%;
  transform: translate(-50%, -50%);
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: rgba(196, 38, 24, 0.95);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.journal-video-title {
  position: absolute;
  left: 1rem;
  right: 1rem;
  bottom: 1rem;
  color: #fff;
  text-align: left;
  font-size: 0.95rem;
  line-height: 1.3;
}

.journal-pagination {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
}

.journal-load-more-wrap {
  display: flex;
  justify-content: center;
  margin-top: 2.25rem;
}

.journal-load-more-button {
  border: 1px solid rgba(23, 19, 16, 0.2);
  background: transparent;
  color: #171310;
  padding: 0.9rem 2rem;
  font-size: 0.78rem;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, opacity 0.2s ease;
}

.journal-load-more-button:hover:not(:disabled) {
  background: #171310;
  border-color: #171310;
  color: #f7f0e4;
}

.journal-load-more-button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.journal-empty {
  text-align: center;
  padding: 6rem 1rem;
}

.journal-loading-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.journal-loading-block {
  background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.45), rgba(255,255,255,0.2));
  background-size: 200% 100%;
  animation: shimmer 1.25s infinite linear;
}

.journal-loading-block--hero {
  min-height: 420px;
}

.journal-loading-block--card {
  min-height: 360px;
}

.journal-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(23, 19, 16, 0.52);
  font-size: 0.9rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.journal-modal-backdrop,
.journal-video-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(16, 12, 10, 0.34);
  z-index: 2400;
  display: flex;
  align-items: stretch;
  justify-content: center;
  padding: 0;
}

.journal-modal {
  position: relative;
  width: min(1400px, 100vw);
  height: 100vh;
  background: #f7f0e4;
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  overflow: hidden;
}

.journal-modal-close,
.journal-video-modal-close {
  position: absolute;
  top: 1.1rem;
  right: 1.2rem;
  z-index: 3;
  border: 0;
  background: transparent;
  color: #171310;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.journal-modal-media {
  min-height: 100vh;
}

.journal-modal-image {
  height: 100%;
}

.journal-modal-content {
  overflow-y: auto;
  padding: 5.5rem 4rem 3rem;
}

.journal-modal-header {
  text-align: center;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(23, 19, 16, 0.14);
}

.journal-modal-title {
  font-size: clamp(2.2rem, 3vw, 3.35rem);
  margin-bottom: 0.6rem;
}

.journal-modal-summary {
  margin-top: 2rem;
}

.journal-modal-body {
  margin-top: 1.5rem;
}

.journal-modal-body :deep(img) {
  max-width: 100%;
  height: auto;
}

.journal-modal-products {
  margin-top: 2rem;
}

.journal-modal-products h3 {
  font-size: 1.2rem;
  margin-bottom: 1rem;
}

.journal-modal-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.journal-video-modal {
  position: relative;
  width: min(980px, calc(100vw - 2rem));
  aspect-ratio: 16 / 9;
  background: #000;
  margin: auto;
}

.journal-video-modal iframe {
  width: 100%;
  height: 100%;
  border: 0;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 1024px) {
  .journal-post-grid,
  .journal-loading-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .journal-mixed-section,
  .journal-hero {
    grid-template-columns: 1fr;
  }

  .journal-hero-media,
  .journal-hero-image-frame {
    min-height: 360px;
  }

  .journal-modal {
    grid-template-columns: 1fr;
  }

  .journal-modal-media {
    min-height: 40vh;
    max-height: 40vh;
  }

  .journal-modal-content {
    padding: 4.5rem 1.5rem 2rem;
  }
}

@media (max-width: 768px) {
  .journal-page {
    padding: 1.5rem 0.95rem 3rem;
  }

  .journal-hero-shell {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .journal-hero-index {
    flex-direction: row;
    overflow-x: auto;
    padding-top: 0;
  }

  .journal-toolbar-head,
  .journal-toolbar-controls {
    flex-direction: column;
    align-items: flex-start;
  }

  .journal-search {
    width: 100%;
  }

  .journal-categories {
    justify-content: flex-start;
  }

  .journal-post-grid,
  .journal-product-panel,
  .journal-modal-product-grid,
  .journal-loading-grid {
    grid-template-columns: 1fr;
  }

  .journal-video-grid {
    grid-template-columns: 1fr;
    grid-template-rows: none;
  }

  .video-card-1,
  .video-card-2,
  .video-card-3,
  .video-card-4 {
    grid-column: auto;
    grid-row: auto;
    min-height: 220px;
  }

  .journal-modal {
    width: 100vw;
    height: 100vh;
  }

  .journal-modal-media {
    min-height: 32vh;
    max-height: 32vh;
  }
}

:global(body.journal-modal-open) {
  overflow: hidden;
}
</style>
