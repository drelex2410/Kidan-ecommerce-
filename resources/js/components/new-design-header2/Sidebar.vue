<template>
  <div>
    <div class="sidebar" :class="{ 'sidebar-open': showSidebar }">
      <div class="sidebar-header">
        <button class="sidebar-close-btn" @click="toggleSidebar">
          <i class="las la-times"></i>
        </button>
      </div>
      <div class="sidebar-content">
        <div v-if="loadingCategories && !headerMenuItems.length" class="loading-state">
          <v-skeleton-loader type="table" class="w-100"></v-skeleton-loader>
        </div>
        <div v-else class="categories-list">
          <div v-if="headerMenuItems.length" class="menu-section">
            <div
              v-for="item in headerMenuItems"
              :key="item.label"
              class="category-item"
            >
              <router-link
                :to="item.link || '/'"
                class="category-link-sidebar"
                @click="toggleSidebar"
              >
                <span>{{ item.label }}</span>
              </router-link>
            </div>
          </div>

          <div class="category-item contact-section">
            <div class="contact-header">Contact Us</div>
            <div class="contact-content">
              <div class="contact-item">
                <p class="contact-label">Locate our store</p>
                <p class="contact-detail contact-detail--emphasis">No. 10 New Yidi Road Ilorin, Kwara State Nigeria.</p>
              </div>
              <div class="contact-item">
                <p class="contact-detail contact-detail--large">support@kidanstore.com</p>
              </div>
              <div class="contact-item">
                <p class="contact-detail contact-detail--large">07071827096</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="sidebar-overlay-bg" v-if="showSidebar" @click="toggleSidebar"></div>
  </div>
</template>

<script>
export default {
  props: {
    showSidebar: { type: Boolean, required: true },
    loadingCategories: { type: Boolean, required: true },
    categories: { type: Array, required: true },
    data: { type: Object, default: () => ({}) },
  },
  computed: {
    headerMenuItems() {
      return Object.entries(this.data?.header_menu || {}).map(([label, link]) => ({
        label,
        link,
      }));
    },
  },
  methods: {
    toggleSidebar() {
      this.$emit("toggle-sidebar");
    },
  },
};
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: -100%;
  width: 380px;
  max-width: 90vw;
  height: 100vh;
  background: #fff;
  z-index: 2001;
  transition: left 0.3s ease;
  overflow-y: auto;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar-open {
  left: 0;
}

.sidebar-overlay-bg {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 2000;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

.sidebar-open+.sidebar-overlay-bg {
  opacity: 1;
  visibility: visible;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 1rem;
  padding: 2rem 2rem 1.5rem 2rem;
  border-bottom: none;
  background: #FFFBF3;
  position: sticky;
  top: 0;
  z-index: 10;
}

.sidebar-close-btn {
  background: none;
  border: none;
  color: #000;
  font-size: 1.5rem;
  cursor: pointer;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  border-radius: 0;
  padding: 0;
}

.sidebar-close-btn:hover {
  background: transparent;
  transform: none;
  opacity: 0.7;
}

.sidebar-content {
  padding: 0;
  background: #FFFBF3;
}

.categories-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.menu-section {
  display: flex;
  flex-direction: column;
}

.category-item {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.category-link-sidebar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #000;
  text-decoration: none;
  font-size: 1.05rem;
  line-height: 1.2;
  padding: 1.35rem 2rem;
  border-radius: 0;
  transition: background 0.2s ease;
  background: transparent;
  border-left: none;
  text-transform: none;
}

.category-link-sidebar:hover {
  background: #f8f8f8;
  border-left-color: transparent;
  padding-left: 2rem;
}

/* Contact Section Styles */
.contact-section {
  margin-top: 1.5rem;
  padding: 0 2rem 2.5rem;
}

.contact-header {
  padding: 1.5rem 0 1.1rem;
  margin: 0;
  font-size: 2rem;
  line-height: 1.1;
  font-weight: 700;
  color: #000;
}

.contact-content {
  padding: 1.65rem 0 1.65rem;
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  border-top: 1px solid #1f1f1f;
  border-bottom: 1px solid #1f1f1f;
}

.contact-item {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.contact-label {
  font-size: 0.95rem;
  font-weight: 300;
  line-height: 1.3;
  color: #000;
  margin: 0;
}

.contact-detail {
  font-size: 1rem;
  color: #111;
  margin: 0;
  line-height: 1.35;
}

.contact-detail--emphasis {
  font-size: 1.1rem;
  font-weight: 700;
}

.contact-detail--large {
  font-size: 1.05rem;
  font-weight: 300;
}

.loading-state {
  padding: 2rem;
}

/* Responsive adjustments */
@media (max-width: 480px) {
  .sidebar {
    width: 100%;
    max-width: 100vw;
  }
  
  .category-link-sidebar {
    padding: 1.15rem 1.5rem;
    font-size: 0.95rem;
  }
  
  .contact-header,
  .contact-content {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }

  .contact-section {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
  
  .sidebar-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
  }
}

@media (min-width: 481px) and (max-width: 768px) {
  .sidebar {
    width: 320px;
  }
  
  .category-link-sidebar {
    padding: 1.125rem 1.75rem;
  }
}

/* Scrollbar styling */
.sidebar::-webkit-scrollbar {
  width: 5px;
}

.sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
  background: #ddd;
  border-radius: 0;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: #ccc;
}
</style>
