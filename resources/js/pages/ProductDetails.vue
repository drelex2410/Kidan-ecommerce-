<template>
  <v-container
    fluid
    class="pt-7 single-product-page"
  >
    <v-row
      align="start"
      class="product-layout"
    >
      <v-col
        lg="10"
        cols="12"
        class="main-bar product-main-column pa-0"
      >
        <v-row class="product-main-grid">
          <v-col
            cols="12"
            md="6"
            lg="5"
            class="product-gallery-column pa-0"
          >
            <ProductGallery
              :is-loading="detailsLoading"
              :gallery-imgaes="productDetails.photos"
              :selectedVariation="selectedVariation"
              :desktop-image-width="520"
              :desktop-image-height="680"
            />
          </v-col>
          <v-col
            md="6"
            lg="7"
            class="product-info-column pa-0"
          >
            <template v-if="!detailsLoading">
              <h1 class="fs-24 fw-700 mb-4 lh-1-3">{{ productDetails.name }}</h1>

              <div class="mb-3">
                <span class="opacity-60 fs-14">{{ shortDescription }}</span>
              </div>

              <div
                v-if="isVariantProduct"
                class="mb-4"
              >
                <div
                  v-for="(variationOption, optionIndex) in variationOptions"
                  :key="variationOption.id || optionIndex"
                  class="mb-4"
                >
                  <div class="fw-600 fs-14 mb-2">
                    Select {{ variationOption.name || "Option" }}
                  </div>
                  <div class="d-flex gap-2">
                    <label
                      v-for="(value, valueIndex) in safeVariationValues(variationOption)"
                      :key="value.id || valueIndex"
                      :class="[isColorVariation(variationOption) ? 'color-option' : 'size-option', 'me-2']"
                    >
                      <input
                        v-model="chooseOptions[optionIndex]"
                        type="radio"
                        :name="`option_${variationOption.id}`"
                        :value="variationOption.id + ':' + value.id"
                        @change="optionChosen"
                        class="d-none"
                      />
                      <span
                        v-if="isColorVariation(variationOption)"
                        class="color-swatch"
                        :style="{ backgroundColor: value.name || '#ddd' }"
                      ></span>
                      <span
                        v-else
                        class="size-label"
                      >{{ value.name }}</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <div class="d-flex align-center">
                  <template v-if="discount > 0">
                    <span class="primary-text fs-28 fw-700 me-3">{{ format_price(productDetails.base_discounted_price) }}</span>
                  </template>
                  <template v-else>
                    <span class="primary-text fs-28 fw-700 me-3">{{ format_price(productDetails.base_price) }}</span>
                  </template>
                </div>
              </div>

              <div class="mb-4">
                <v-btn
                  color="grey-darken-4 white-text"
                  elevation="0"
                  size="large"
                  block
                  class="text-uppercase fw-600"
                  :disabled="purchaseSelectionPending"
                  @click="addCart"
                >Add to Cart</v-btn>
              </div>

              <div class="mb-4">
                <v-btn
                  variant="outlined"
                  color="grey-darken-4"
                  size="large"
                  block
                  class="text-uppercase fw-600"
                  :disabled="purchaseSelectionPending"
                  @click="buyNow"
                >Purchase Now</v-btn>
              </div>

              <div class="details-section">
                <div class="details-item border-top pt-3 pb-3">
                  <div class="fw-600 fs-14 mb-2">Details</div>
                  <div class="fs-13 opacity-70" v-html="safeDescription"></div>
                </div>

                <div class="details-item border-top pt-3 pb-3">
                  <div class="fw-600 fs-14 mb-2">Shipping & Returns</div>
                  <div class="fs-13 opacity-70">
                    <div v-if="!productDetails.is_digital">
                      <div
                        v-if="Math.ceil(productDetails.express_delivery_time / 24) == Math.ceil(productDetails.standard_delivery_time / 24)"
                      >
                        Estimated delivery: {{ Math.ceil(productDetails.express_delivery_time / 24) }} days
                      </div>
                      <div v-else>
                        Estimated delivery: {{ Math.ceil(productDetails.express_delivery_time / 24) }} - {{ Math.ceil(productDetails.standard_delivery_time / 24) }} days
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </template>
          </v-col>
        </v-row>

        <div class="mt-8">
          <v-expansion-panels
            v-model="panel"
            class="product-details-expansion"
            flat
            multiple
          >
            <v-expansion-panel class="mb-3">
              <v-expansion-panel-title collapse-icon="las la-arrow-circle-right" expand-icon="las la-arrow-circle-right">
                <div class="d-flex align-center">
                  <span class="fs-16 fw-600">{{ $t("rating__reviews") }}</span>
                </div>
              </v-expansion-panel-title>
              <v-expansion-panel-text class="">
                <ProductReviews
                  :id="productDetails.id"
                  :is-loading="detailsLoading"
                  :review-summary="reviewSummary"
                />
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </div>

        <div
          v-if="boughtTogetherProducts.length > 0"
          class="mb-5 mt-8"
        >
          <h2 class="mb-3 fs-21 opacity-80">
            {{ $t("frequently_bought_together") }}
          </h2>
          <swiper
            :slides-per-view="carouselOption.slidesPerView"
            :space-between="carouselOption.spaceBetween"
            :breakpoints="carouselOption.breakpoints"
          >
            <swiper-slide
              v-for="(product, i) in boughtTogetherProducts"
              :key="i"
              class=""
            >
              <product-box
                :product-details="product"
                :is-loading="togetherLoading"
              />
            </swiper-slide>
          </swiper>
        </div>

        <div class="mb-5">
          <h2 class="mb-3 fs-21 opacity-80">
            {{ $t("more_items_to_explore") }}
          </h2>
          <swiper
            :slides-per-view="carouselOption.slidesPerView"
            :space-between="carouselOption.spaceBetween"
            :breakpoints="carouselOption.breakpoints"
          >
            <swiper-slide
              v-for="(product, i) in moreProducts"
              :key="i"
              class=""
            >
              <product-box
                :product-details="product"
                :is-loading="moreLoading"
              />
            </swiper-slide>
          </swiper>
        </div>
      </v-col>

      <v-col
        lg="2"
        cols="12"
        class="sticky-top right-bar product-sidebar-column pa-0"
      >
        <div class="mb-4 single-product-related-panel">
          <div class="mb-3 fw-600 fs-14">Related Items</div>
          <div class="single-product-related-list">
            <product-box
              v-for="(product, i) in relatedProducts"
              :key="i"
              class="single-product-related-card"
              :product-details="product"
              :is-loading="relatedLoading"
              box-style="two"
            />
          </div>
        </div>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { mapActions } from "vuex";
import ProductReviews from "../components/product/ProductReviews.vue";
import ProductGallery from "../components/product/ProductGallery.vue";
import { Swiper, SwiperSlide } from "swiper/vue";
import { useHead } from "@unhead/vue";
import { mapGetters, mapMutations } from "vuex";

export default {
  data: () => ({
    isBuyNow: false,
    cartQuantity: 1,
    chooseOptions: [],
    stock: 1,
    current_stock: 0,
    selectedVariation: {},
    minCartLimit: 1,
    maxCartLimit: Infinity,
    metaTitle: "",
    metaDescription: "",
    detailsLoading: true,
    productDetails: {},
    reviewSummary: { average: 0 },
    relatedLoading: true,
    relatedProducts: [{}, {}, {}, {}, {}],
    togetherLoading: true,
    boughtTogetherProducts: [{}, {}, {}, {}, {}],
    moreLoading: true,
    moreProducts: [{}, {}, {}, {}, {}],
    panel: [0],
    carouselOption: {
      slidesPerView: 5,
      spaceBetween: 20,
      breakpoints: {
        0: {
          slidesPerView: 2,
          spaceBetween: 12,
        },
        599: {
          slidesPerView: 3,
          spaceBetween: 16,
        },
        960: {
          slidesPerView: 4,
          spaceBetween: 20,
        },
        1264: {
          slidesPerView: 4,
          spaceBetween: 20,
        },
        1904: {
          slidesPerView: 5,
          spaceBetween: 20,
        },
      },
    },
  }),
  components: {
    ProductReviews,
    ProductGallery,
    Swiper,
    SwiperSlide,
  },
  computed: {
    ...mapGetters("app", ["generalSettings"]),
    ...mapGetters("auth", ["isAuthenticated", "currentUser"]),
    ...mapGetters("wishlist", ["isThisWishlisted"]),
    ...mapGetters("cart", ["isThisInCart", "findCartItemByVariationId"]),
    ...mapGetters("affiliate", [
      "getUserReferralCode",
      "isAffiliatedUser",
      "getAffiliateOption",
    ]),
    discount() {
      return this.discount_percent(
        this.productDetails.base_price,
        this.productDetails.base_discounted_price
      );
    },
    safeDescription() {
      return typeof this.productDetails.description === "string"
        ? this.productDetails.description
        : "";
    },
    shortDescription() {
      return this.safeDescription.replace(/<[^>]*>?/gm, "").substring(0, 100);
    },
    isVariantProduct() {
      return Number(this.productDetails.is_variant) === 1;
    },
    variationOptions() {
      return Array.isArray(this.productDetails.variation_options)
        ? this.productDetails.variation_options
        : [];
    },
    selectedVariationData() {
      if (
        this.selectedVariation &&
        typeof this.selectedVariation === "object" &&
        Object.keys(this.selectedVariation).length > 0
      ) {
        return this.selectedVariation;
      }

      return null;
    },
    purchaseSelectionPending() {
      return this.isVariantProduct && !this.selectedVariationData;
    },
  },
  watch: {
    metaTitle(newTitle) {
      this.updateHead(newTitle, this.metaDescription);
    },
    metaDescription(newDescription) {
      this.updateHead(this.metaTitle, newDescription);
    },
    productDetails: {
      immediate: true,
      handler(newVal, oldVal) {
        if (!this.is_empty_obj(newVal)) {
          this.cartQuantity = 1;
          this.stock = newVal.stock;
          this.current_stock = newVal.current_stock ?? newVal.stock ?? 0;
          this.maxCartLimit = newVal.max_qty > 0 ? newVal.max_qty : Infinity;
          this.minCartLimit = newVal.min_qty;
          this.selectedVariation =
            Number(newVal.is_variant) === 1 ? {} : (newVal.variations?.[0] || {});
          this.chooseOptions = [];
        }
      },
    },
  },
  methods: {
    ...mapActions("recentlyViewed", ["addNewRecentlyViewedProduct"]),
    ...mapActions("affiliate", ["fetchAffiliatedUser"]),
    ...mapActions("wishlist", ["addNewWishlist", "removeFromWishlist"]),
    ...mapActions("cart", ["addToCart", "updateQuantity"]),
    ...mapActions("auth", ["showConversationDialog"]),
    ...mapMutations("auth", ["updateChatWindow", "showAddToCartDialog"]),
    async getDetails() {
      const res = await this.call_api(
        "get",
        `product/details/${this.$route.params.slug}`
      );
      if (res.data.success) {
        this.metaTitle = res.data.data.metaTitle;
        this.metaDescription =
          typeof res.data.data.description === "string"
            ? res.data.data.description.replace(/<[^>]*>?/gm, "")
            : "";
        this.productDetails = res.data.data;
        this.reviewSummary = this.productDetails.review_summary;

        this.getRelatedProducts(this.productDetails.id);
        this.getBoughtTogetherProducts(this.productDetails.id);
        this.getMoreProducts(this.productDetails.id);
        this.addNewRecentlyViewedProduct(this.productDetails.id);
      } else {
        this.snack({
          message: res.data.message,
          color: "red",
        });
        this.$router.push({ name: "404" });
      }
      this.detailsLoading = false;
    },
    async getRelatedProducts(id) {
      const res = await this.call_api("get", `product/related/${id}`);
      if (res.data.success) {
        this.relatedProducts = res.data.data;
        this.relatedLoading = false;
      }
    },
    async getBoughtTogetherProducts(id) {
      const res = await this.call_api("get", `product/bought-together/${id}`);
      if (res.data.success) {
        this.boughtTogetherProducts = res.data.data;
        this.togetherLoading = false;
      }
    },
    async getMoreProducts(id) {
      const res = await this.call_api("get", `product/random/10/${id}`);
      if (res.data.success) {
        this.moreProducts = res.data.data;
        this.moreLoading = false;
      }
    },
    async productReferralCode(product_referral_code) {
      const res = await this.call_api("post", "product-refferal-code", {
        product_referral_code: product_referral_code,
        slug: this.$route.params.slug,
      });
    },
    updateHead(title, description) {
      useHead({
        title: title,
        meta: [{ name: "description", content: description }],
      });
    },
    addCart() {
      if (this.isAuthenticated && this.currentUser.user_type != "customer") {
        this.snack({
          message: this.$i18n.t(
            "please_login_as_a_customer_first_to_add_product_to_the_cart"
          ),
          color: "red",
        });
        return;
      }
      if (this.isVariantProduct) {
        let chooseOptions = this.chooseOptions.filter((el) => el != "");
        if (
          this.variationOptions.length > chooseOptions.length ||
          !this.selectedVariationData
        ) {
          this.snack({
            message: this.$i18n.t("please_select_all_options"),
            color: "red",
          });
          return;
        }
      }

      if (!this.stock) {
        this.snack({
          message: this.$i18n.t("this_product_is_out_of_stock"),
          color: "red",
        });
        return;
      }

      if (
        this.selectedVariationData?.current_stock != null &&
        this.selectedVariationData.current_stock < this.cartQuantity
      ) {
        this.snack({
          message: this.$i18n.t("this_product_is_out_of_stock"),
          color: "red",
        });
        return;
      }
      if (
        this.selectedVariationData?.current_stock != null &&
        this.selectedVariationData.current_stock < this.productDetails.min_qty
      ) {
        this.snack({
          message: this.$i18n.t("this_product_is_out_of_stock"),
          color: "red",
        });
        return;
      }

      let minMaxCheck = this.checkMinMaxLimit(this.selectedVariationData?.id);
      if (!minMaxCheck.success) {
        let message =
          minMaxCheck.type == "variation_required"
            ? this.$i18n.t("please_select_all_options")
            : minMaxCheck.type == "min_limit"
            ? `${this.$i18n.t("you_need_to_purchase_minimum_quantity")} ${
                this.minCartLimit
              }.`
            : `${this.$i18n.t("you_can_purchase_maximum_quantity")} ${
                this.maxCartLimit
              }.`;

        this.snack({
          message: message,
          color: "red",
        });
        return;
      }

      this.addToCart({
        variation_id: this.selectedVariationData.id,
        qty: this.cartQuantity,
      });
      this.isBuyNow = true;
      this.snack({
        message: this.$i18n.t("product_added_to_cart"),
        color: "green",
      });
      this.showAddToCartDialog({ status: false, slug: null });
    },
    buyNow() {
      this.addCart();
      if (this.isBuyNow) {
        this.$router.push({ name: "Checkout" });
      }
    },
    normalizeVariationName(name) {
      return typeof name === "string" ? name.toLowerCase() : "";
    },
    isColorVariation(option) {
      return this.normalizeVariationName(option?.name) === "color";
    },
    safeVariationValues(option) {
      return Array.isArray(option?.values) ? option.values : [];
    },
    optionChosen() {
      let chooseOptions = this.chooseOptions.filter((el) => el != "");
      if (
        this.variationOptions.length === chooseOptions.length
      ) {
        let filteredVariations = Array.isArray(this.productDetails.variations)
          ? this.productDetails.variations
          : [];

        chooseOptions.forEach((chosenOption) => {
          filteredVariations = filteredVariations.filter((variation) => {
            return variation?.code?.includes(chosenOption);
          });
        });

        if (filteredVariations.length == 1) {
          this.stock = filteredVariations[0].stock;
          this.current_stock = filteredVariations[0].current_stock;
          this.selectedVariation = filteredVariations[0];
        } else {
          this.selectedVariation = {};
        }
      } else {
        this.selectedVariation = {};
      }
    },
    checkMinMaxLimit(variation_id) {
      if (!variation_id) {
        return { success: false, type: "variation_required" };
      }

      if (this.isThisInCart(variation_id)) {
        if (
          this.findCartItemByVariationId(variation_id).qty + this.cartQuantity <
          this.minCartLimit
        ) {
          return { success: false, type: "min_limit" };
        } else if (
          this.findCartItemByVariationId(variation_id).qty + this.cartQuantity >
          this.maxCartLimit
        ) {
          return { success: false, type: "max_limit" };
        }

        return { success: true, type: "" };
      } else {
        if (this.cartQuantity < this.minCartLimit) {
          return { success: false, type: "min_limit" };
        } else if (this.cartQuantity > this.maxCartLimit) {
          return { success: false, type: "max_limit" };
        }

        return { success: true, type: "" };
      }
    },
  },
  async created() {
    this.getDetails();
    if (this.isAuthenticated) {
      this.fetchAffiliatedUser();
    }
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    const product_referral_code = urlParams.get("product_referral_code");
    if (product_referral_code != null) {
      this.productReferralCode(product_referral_code);
    }
  },
};
</script>

<style scoped>
.single-product-page {
  overflow-x: clip;
  width: 100%;
  max-width: 1480px;
  margin: 0 auto;
  padding: 32px 28px 0;
}

.product-layout {
  margin: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 28px;
}

.product-main-column,
.product-gallery-column,
.product-info-column,
.product-sidebar-column {
  min-width: 0;
  width: auto;
  max-width: none;
  flex: none;
}

.product-main-grid {
  margin: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 24px;
}

.product-info-column {
  align-self: start;
  width: 100%;
}

.product-info-column :deep(.d-flex.gap-2) {
  flex-wrap: wrap;
  row-gap: 12px;
}

.product-info-column label.size-option,
.product-info-column label.color-option {
  margin-right: 0 !important;
}

.product-info-column .mb-3,
.product-info-column .mb-4 {
  min-width: 0;
}

.product-info-column h1 {
  max-width: 720px;
  margin-bottom: 18px !important;
}

.product-info-column .opacity-60.fs-14 {
  display: block;
  max-width: 680px;
  line-height: 1.7;
}

.product-info-column .details-section {
  margin-top: 8px;
}

.product-info-column .v-btn {
  min-height: 54px;
}

.single-product-related-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.single-product-related-panel {
  width: 100%;
}

.single-product-related-panel .fw-600 {
  margin-bottom: 18px !important;
}

.size-option input[type="radio"]:checked + .size-label {
  background-color: #000;
  color: #fff;
  border-color: #000;
}

.size-label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 48px;
  display: inline-block;
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.size-label:hover {
  border-color: #000;
}

.color-option input[type="radio"]:checked + .color-swatch {
  outline: 2px solid #000;
  outline-offset: 2px;
}

.color-swatch {
  display: inline-block;
  flex: 0 0 auto;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px #ddd;
  cursor: pointer;
  transition: all 0.2s;
}

.color-swatch:hover {
  box-shadow: 0 0 0 1px #000;
}

.details-section {
  border-top: 1px solid #eee;
}

.details-item {
  border-bottom: 1px solid #eee;
}

@media (max-width: 767px) {
  .single-product-page {
    padding: 20px 16px 0;
  }

  .single-product-related-list {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (min-width: 768px) and (max-width: 1263px) {
  .single-product-page {
    max-width: 1100px;
    padding: 28px 24px 0;
  }

  .product-layout > .product-main-column,
  .product-layout > .product-sidebar-column,
  .product-main-grid > .product-gallery-column,
  .product-main-grid > .product-info-column {
    width: auto;
    max-width: none;
    flex: none;
  }

  .product-main-grid {
    grid-template-columns: minmax(320px, 440px) minmax(0, 1fr);
    column-gap: 28px;
    align-items: start;
  }

  .single-product-related-list {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
  }
}

@media (min-width: 1264px) {
  .product-layout > .product-main-column,
  .product-layout > .product-sidebar-column,
  .product-main-grid > .product-gallery-column,
  .product-main-grid > .product-info-column {
    width: auto;
    max-width: none;
    flex: none;
  }

  .product-layout {
    grid-template-columns: minmax(0, 1fr) 268px;
    column-gap: 40px;
    align-items: start;
  }

  .product-main-grid {
    grid-template-columns: 520px minmax(420px, 1fr);
    column-gap: 40px;
    align-items: start;
  }

  .product-info-column {
    padding-top: 8px;
    padding-right: 16px;
  }

  .main-bar,
  .right-bar {
    max-width: none;
  }

  .right-bar {
    width: 268px;
  }

  .product-sidebar-column {
    align-self: start;
  }

  .single-product-related-list {
    grid-template-columns: minmax(0, 1fr);
    gap: 24px;
  }

  :deep(.single-product-related-card) {
    width: 268px;
    max-width: 268px;
  }

  :deep(.single-product-related-card .v-row) {
    display: block;
  }

  :deep(.single-product-related-card .v-col) {
    flex: 0 0 auto;
    max-width: 100%;
  }

  :deep(.single-product-related-card .lv-product-card) {
    width: 268px;
    min-height: 332px;
  }

  :deep(.single-product-related-card .size-70px) {
    width: 100%;
    height: 220px;
  }

  :deep(.single-product-related-card .lv-product-details) {
    padding: 16px 0 0;
  }

  :deep(.single-product-related-card .product-box-two .lv-product-details) {
    padding: 16px 0 0;
  }
}

@media (min-width: 1440px) {
  .single-product-page {
    padding: 40px 32px 0;
  }

  .product-layout {
    column-gap: 44px;
  }

  .product-main-grid {
    column-gap: 44px;
  }
}
</style>
