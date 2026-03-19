.category-image {
  height: 220px;
  width: 100%;
  object-fit: cover;
  border-radius: 4px;
}

@media (min-width: 600px) {
  .category-image {
    height: 240px;
  }
}

@media (min-width: 960px) {
  h2 {
    font-size: 24px;
  }

  .category-image {
    height: 260px;
  }
}

@media (min-width: 1264px) {
  .category-image {
    height: 280px;
  }
}<template>
  <div class="mb-5">
    <v-container class="py-0 pe-0 pe-md-3 ps-3">
      <div class="d-flex justify-space-between align-center mb-4 pe-3 pe-md-0">
        <h2 class="">{{ $t('popular_categories') }}</h2>
        <router-link
          :to="{ name: 'AllCategories' }"
          class="view-all-btn py-2 lh-1 d-inline-block"
        >
          {{ $t('view_all') }}
          <i class="las la-angle-right"></i>
        </router-link>
      </div>
      <div v-if="loading">
        <swiper
          class=""
          :options="carouselOption"
        >
          <swiper-slide
            v-for="(i) in 8"
            :key="i"
            class=""
          >
            <v-skeleton-loader
              type="image"
              height="220"
            ></v-skeleton-loader>
          </swiper-slide>
        </swiper>
      </div>
      <div v-else>
        <swiper
          :slides-per-view="carouselOption.slidesPerView"
          :space-between="carouselOption.spaceBetween"
          :autoplay="carouselOption.autoplay"
          :modules="modules"
          :breakpoints="carouselOption.breakpoints"
        >
          <swiper-slide
            v-for="category in categories"
            :key="category.id"
            class=""
          >
            <router-link
              class="rounded pa-3 pa-md-5 border text-center d-block text-reset category-card"
              :to="{ name: 'Category', params: {categorySlug: category.slug}}"
            >
              <img
                :src="category.banner"
                :alt="category.name"
                @error="imageFallback($event)"
                class="img-fluid category-image"
              >
              <div class="fs-14 opacity-80 text-truncate d-none d-md-block mt-4">{{ category.name }}</div>
            </router-link>
          </swiper-slide>
        </swiper>
      </div>
    </v-container>
  </div>
</template>

<script>
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from "swiper/vue";

export default {

  components: {
    Swiper,
    SwiperSlide,
  },
  setup() {
      return {
        modules: [Autoplay, Pagination, Navigation],
      };
    },

  data: () => ({
    loading: true,
    categories: [],
    carouselOption: {
      slidesPerView: 8,
      spaceBetween: 20,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false,
      },
      breakpoints: {
        0: {
          slidesPerView: 3,
          spaceBetween: 12,
        },
        599: {
          slidesPerView: 3.5,
          spaceBetween: 16,
        },
        960: {
          slidesPerView: 4.5,
          spaceBetween: 20,
        },
        1264: {
          slidesPerView: 5.5,
          spaceBetween: 20,
        },
        1904: {
          slidesPerView: 6.5,
          spaceBetween: 20,
        },
      },
    },
  }),
  async created() {
    const res = await this.call_api("get", "setting/home/popular_categories");
    if (res.data.success) {
      const rawCategories = Array.isArray(res.data?.data?.data) ? res.data.data.data : [];
      const uniqueCategories = new Map();
      rawCategories.forEach((category) => {
        if (!category || !category.id || Number(category.featured) !== 1) {
          return;
        }
        if (!uniqueCategories.has(category.id)) {
          uniqueCategories.set(category.id, category);
        }
      });
      this.categories = Array.from(uniqueCategories.values());
      this.loading = false;
    }
  },
};
</script>
<style scoped>
.view-all-btn {
  color: #2e1a1a !important;
  font-weight: 500;
  text-decoration: none;
}

.view-all-btn:hover {
  color: #1b0e0e !important;
}

h2 {
  font-size: 16px;
}

.category-card {
  transition: all 0.3s ease;
}

.category-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.category-image {
  height: 180px;
  object-fit: cover;
  border-radius: 4px;
}

@media (min-width: 600px) {
  .category-image {
    height: 200px;
  }
}

@media (min-width: 960px) {
  h2 {
    font-size: 24px;
  }

  .category-image {
    height: 220px;
  }
}

@media (min-width: 1264px) {
  .category-image {
    height: 230px;
  }
}
</style>
