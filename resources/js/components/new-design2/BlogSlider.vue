<template>
    <section class="stories-section" v-if="!loading && blogs.length > 0">
        <v-container class="stories-container">
            <div class="stories-header">
                <h2 class="stories-title">Exciting Stories on KIDAN</h2>
            </div>

            <div class="stories-grid">
                <article
                    v-for="(blog, index) in blogs"
                    :key="blog.id || blog.slug || index"
                    class="story-item"
                >
                    <router-link
                        class="story-card"
                        :to="{ name: 'BlogDetails', params: { slug: blog.slug } }"
                    >
                        <div class="story-image-wrap">
                            <img
                                class="story-image"
                                :src="getImage(blog)"
                                :alt="blog.title || 'KIDAN story'"
                                @error="onImageError"
                            />
                        </div>

                        <div class="story-body">
                            <div class="story-meta">
                                <template v-if="getTypeLabel(blog)">
                                    <span class="story-type">{{ getTypeLabel(blog) }}</span>
                                    <span class="story-separator" v-if="blog.created_at">&bull;</span>
                                </template>
                                <span class="story-date" v-if="blog.created_at">{{ blog.created_at }}</span>
                            </div>

                            <h3 class="story-title">{{ blog.title }}</h3>
                            <p class="story-author" :class="{ 'story-author--muted': !getAuthor(blog) }">
                                {{ getAuthorLine(blog) || "\u00A0" }}
                            </p>
                        </div>
                    </router-link>
                </article>
            </div>
        </v-container>
    </section>

    <section class="stories-section" v-else-if="loading">
        <v-container class="stories-container">
            <div class="stories-header">
                <v-skeleton-loader type="heading" width="280" />
            </div>

            <div class="stories-grid">
                <div v-for="item in 6" :key="item" class="story-item">
                    <div class="story-card">
                        <v-skeleton-loader type="image" height="260" />
                        <div class="story-body">
                            <v-skeleton-loader type="text" width="55%" />
                            <v-skeleton-loader type="heading" class="mt-2" />
                            <v-skeleton-loader type="text" width="45%" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>
        </v-container>
    </section>
</template>

<script>
const FALLBACK_IMAGE = "/public/assets/img/placeholder-rect.jpg";
const HOME_STORIES_LIMIT = 6;

export default {
    data: () => ({
        loading: true,
        blogs: [],
    }),
    async created() {
        try {
            this.blogs = await this.fetchHomepageStories();
        } catch (error) {
            console.error("Error fetching homepage stories:", error);
            this.blogs = [];
        } finally {
            this.loading = false;
        }
    },
    methods: {
        async fetchHomepageStories() {
            const requests = [
                `recent-blogs?limit=${HOME_STORIES_LIMIT}`,
                `all-blogs/search?page=1&per_page=${HOME_STORIES_LIMIT}`,
                "all-blog-categories",
            ];

            for (const url of requests) {
                try {
                    const res = await this.call_api("get", url);
                    const blogs = this.extractBlogsFromResponse(res?.data);

                    if (blogs.length > 0) {
                        return blogs.slice(0, HOME_STORIES_LIMIT);
                    }
                } catch (error) {
                    console.warn(`Homepage stories request failed for ${url}`, error);
                }
            }

            return [];
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
        getAuthor(blog) {
            return blog.author || blog.source || blog.source_handle || "";
        },
        getAuthorLine(blog) {
            const author = this.getAuthor(blog);

            if (!author) {
                return "";
            }

            return author.startsWith("@") ? author : `By ${author}`;
        },
        getImage(blog) {
            return blog.banner || FALLBACK_IMAGE;
        },
        getTypeLabel(blog) {
            return blog.type || blog.post_type || "";
        },
        onImageError(event) {
            event.target.src = FALLBACK_IMAGE;
        },
    },
};
</script>

<style scoped>
.stories-section {
    background: #f4efe6;
    padding: 32px 0 22px;
}

.stories-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
}

.stories-header {
    margin-bottom: 20px;
}

.stories-title {
    margin: 0;
    color: #13110f;
    font-size: 19px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.stories-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    align-items: start;
}

.story-item {
    min-width: 0;
}

.story-card {
    display: flex;
    flex-direction: column;
    color: inherit;
    text-decoration: none;
}

.story-image-wrap {
    width: 100%;
    aspect-ratio: 1 / 1.18;
    overflow: hidden;
    border-radius: 3px;
    background: #ded6ca;
}

.story-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.story-card:hover .story-image {
    transform: scale(1.015);
}

.story-body {
    padding-top: 11px;
}

.story-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 20px;
    margin-bottom: 7px;
    color: #60574e;
    font-size: 11px;
    line-height: 1.3;
}

.story-type,
.story-date {
    white-space: normal;
}

.story-separator {
    line-height: 1;
}

.story-title {
    margin: 0;
    color: #13110f;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.32;
    letter-spacing: -0.02em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 40px;
}

.story-author {
    margin: 8px 0 0;
    color: #71685f;
    font-size: 12px;
    line-height: 1.35;
    min-height: 18px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.story-author--muted {
    opacity: 0;
}

@media (max-width: 1320px) {
    .stories-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 24px;
    }
}

@media (max-width: 959px) {
    .stories-section {
        padding: 28px 0 18px;
    }

    .stories-container {
        padding: 0 28px;
    }

    .stories-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 599px) {
    .stories-container {
        padding: 0 16px;
    }

    .stories-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .story-title {
        font-size: 18px;
        min-height: 0;
    }
}
</style>
