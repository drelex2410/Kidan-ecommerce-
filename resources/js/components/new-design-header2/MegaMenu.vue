<template>
    <div class="mega-menu-wrapper"  >
        <v-menu 
            offset-y 
            min-width="100%" 
            open-on-hover 
            class="mega-menu-trigger"
            transition="fade-transition"
           
            :elevation="0"
        >
            <template #activator="{ props }">
                <button
                    v-bind="props"
                    class="categories-btn"
                >
                    <i class="las la-bars"></i>
                    <span>{{ $t("all_categories") }}</span>
                </button>
            </template>

            <div class="mega-menu-panel">
                <div class="mega-menu-inner">
                    <div v-if="loading" class="loading-state">
                        <v-skeleton-loader
                            type="table"
                            class="w-100"
                        ></v-skeleton-loader>
                    </div>

                    <div v-else-if="categories.length" class="categories-grid">
                        <div
                            v-for="(category, i) in categories"
                            :key="`category-${i}`"
                            class="category-column"
                        >
                            <!-- Main Category -->
                            <router-link
                                :to="{
                                    name: 'Category',
                                    params: { categorySlug: category.slug },
                                }"
                                class="category-title"
                            >
                                <span>{{ category.name }}</span>
                                <i class="las la-arrow-right"></i>
                            </router-link>

                            <!-- Subcategories -->
                            <div v-if="category.children?.data?.length" class="subcategories">
                                <router-link
                                    v-for="(child, j) in category.children.data"
                                    :key="`child-${j}`"
                                    :to="{
                                        name: 'Category',
                                        params: { categorySlug: child.slug },
                                    }"
                                    class="subcategory-link"
                                >
                                    {{ child.name }}
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <div v-else class="no-categories">
                        <i class="las la-inbox"></i>
                        <p>{{ $t("no_categories_found") }}</p>
                    </div>
                </div>
            </div>
        </v-menu>
    </div>
</template>

<script>
export default {
    name: 'MegaMenu',
    data() {
        return {
            categories: [],
            loading: true,
        };
    },
    methods: {
        goToCategoryPage() {
            this.$router.push({ name: "AllCategories" });
        },
    },
    async created() {
        try {
            const res = await this.call_api("get", "all-categories");
            if (res.data.success) {
                this.categories = res.data.data;
            }
        } catch (error) {
            console.error("Error loading categories:", error);
        } finally {
            this.loading = false;
        }
    },
};
</script>

<style scoped>
/* ===================================
   MEGA MENU WRAPPER
   =================================== */
.mega-menu-wrapper {
    display: inline-block;
}

/* ===================================
   CATEGORIES BUTTON
   =================================== */
.categories-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #3d2222;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 0.9rem;
   
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: inherit;
    letter-spacing: 0.5px;
    text-transform: capitalize;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.categories-btn:hover {
    background: #4a2a2a;
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    transform: translateY(-2px);
}

.categories-btn:active {
    transform: translateY(0);
}

.categories-btn i {
    font-size: 1.1rem;
    opacity: 0.9;
}

/* ===================================
   MEGA MENU PANEL
   =================================== */
.mega-menu-panel {
    background: #2e1a1a;
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: slideDown 0.2s ease;
    margin-top: 8px;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mega-menu-inner {
    padding: 1.5rem;
    max-width: 1200px;
    background: #2e1a1a;
    border-radius: 12px;
}

/* ===================================
   CATEGORIES GRID
   =================================== */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 2rem;
}

.category-column {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* ===================================
   CATEGORY TITLE
   =================================== */
.category-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    color: #fff;
    text-decoration: none;
   
    font-size: 0.95rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.category-title:hover {
    background: rgba(255, 255, 255, 0.1);
    border-left-color: rgba(255, 255, 255, 0.6);
    border-color: rgba(255, 255, 255, 0.15);
    transform: translateX(4px);
}

.category-title i {
    font-size: 0.85rem;
    opacity: 0;
    transform: translateX(-5px);
    transition: all 0.2s ease;
    color: rgba(255, 255, 255, 0.7);
}

.category-title:hover i {
    opacity: 1;
    transform: translateX(0);
}

/* ===================================
   SUBCATEGORIES
   =================================== */
.subcategories {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.subcategory-link {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    position: relative;
    padding-left: 1.5rem;
    border: 1px solid transparent;
}

.subcategory-link::before {
    content: '•';
    position: absolute;
    left: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
   
    transition: color 0.2s ease;
}

.subcategory-link:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    padding-left: 1.75rem;
    border-color: rgba(255, 255, 255, 0.1);
    transform: translateX(4px);
}

.subcategory-link:hover::before {
    color: rgba(255, 255, 255, 0.8);
}

/* ===================================
   LOADING STATE
   =================================== */
.loading-state {
    padding: 2rem;
    min-height: 300px;
}

:deep(.v-skeleton-loader__table) {
    background: rgba(255, 255, 255, 0.05) !important;
}

:deep(.v-skeleton-loader__table-cell) {
    background: rgba(255, 255, 255, 0.1) !important;
}

/* ===================================
   EMPTY STATE
   =================================== */
.no-categories {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
}

.no-categories i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-categories p {
    margin: 0;
    font-size: 0.95rem;
}

/* ===================================
   RESPONSIVE
   =================================== */
@media (max-width: 1200px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .mega-menu-inner {
        padding: 1rem;
    }

    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .category-title {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }

    .subcategory-link {
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
        padding-left: 1.25rem;
    }

    .subcategory-link:hover {
        padding-left: 1.5rem;
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }

    .categories-btn {
        width: 100%;
        justify-content: center;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }
    
    .categories-btn i {
        font-size: 1rem;
    }
}

/* ===================================
   MENU CONTENT STYLING
   =================================== */
:deep(.v-menu__content) {
    border-radius: 12px !important;
    overflow: hidden;
    margin-top: 8px !important;
    background: transparent !important;
}

:deep(.v-menu__content .theme-light.menuable__content__active) {
    background: transparent !important;
}
</style>