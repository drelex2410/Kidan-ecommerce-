<template>
  <div class="orders-container">
    <div v-if="orders.length === 0 && !loading" class="empty-state">
      <div class="empty-icon">
        <i class="las la-box"></i>
      </div>
      <p class="empty-text">You have no active orders.</p>
      <v-btn class="shop-btn" color="#8B0000" dark @click="shopTodaysDeal">
        SHOP NEW ARRIVALS
      </v-btn>
    </div>

    <div v-else>
      <v-card v-for="(order, i) in orders" :key="i" class="order-card" :class="{ 'mt-4': i > 0 }" elevation="0">
        <div class="order-header">
          <div class="order-header-content">
            <div>
              <div class="order-number">#{{ order.code }}</div>
              <div class="order-date">Placed on {{ formatDate(order.date) }}</div>
            </div>
            <div class="order-actions">
              <v-btn text small class="action-btn" @click="invoiceDownload(order)">
                Download Receipt
              </v-btn>
              <v-btn text small class="action-btn" @click="openOrderDetails(order)">
                View Order Details
              </v-btn>
            </div>
          </div>
        </div>

        <v-divider></v-divider>

        <div class="order-body" v-for="(subOrder, idx) in order.orders" :key="idx">
          <div v-for="(product, pIdx) in subOrder.products.data" :key="pIdx" class="order-product"
            :class="{ 'mt-4': pIdx > 0 }">
            <div class="product-image-wrapper">
              <v-img :src="product.thumbnail || '/placeholder-product.jpg'" width="100" height="140"
                class="product-image" cover></v-img>
            </div>

            <div class="product-details">
              <h3 class="product-name">{{ product.name }}</h3>
              <p class="product-description" v-if="product.product_description">
                {{ product.product_description }}
              </p>
              <p class="product-variant">
                <span v-if="product.variation">{{ product.variation }}</span>
                <span v-if="product.variation && product.quantity"> • </span>
                <span>Quantity: {{ product.quantity }}</span>
              </p>
            </div>

            <div class="order-timeline">
              <div class="timeline-item" :class="getTimelineClass('placed', subOrder.delivery_status)">
                <div class="timeline-dot"></div>
                <span class="timeline-label">Order Placed</span>
              </div>
              <div class="timeline-item" :class="getTimelineClass('transit', subOrder.delivery_status)">
                <div class="timeline-dot"></div>
                <span class="timeline-label">In-Transit</span>
              </div>
              <div class="timeline-item" :class="getTimelineClass('delivered', subOrder.delivery_status)">
                <div class="timeline-dot"></div>
                <span class="timeline-label">Delivered</span>
              </div>
            </div>
          </div>

          <div class="delivery-alert" v-if="subOrder.expected_delivery_date">
            Expected Delivery on {{ formatDeliveryDate(subOrder.expected_delivery_date) }}
          </div>
        </div>
      </v-card>

      <div class="text-start" v-if="totalPages > 1">
        <v-pagination v-model="currentPage" @update:modelValue="getList" :length="totalPages"
          prev-icon="las la-angle-left" next-icon="las la-angle-right" :total-visible="7" elevation="0"
          class="my-4"></v-pagination>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions } from "vuex";

export default {
  data: () => ({
    loading: true,
    currentPage: 1,
    totalPages: 1,
    orders: [],
  }),
  watch: {
    currentPage() {
      this.$router
        .push({
          query: {
            ...this.$route.query,
            page: this.currentPage,
          },
        })
        .catch(() => { });
    },
  },
  methods: {
    ...mapActions("cart", ["addToCart"]),

    shopTodaysDeal() {
      this.$router.push({ name: "Home" });
    },

    async getList() {
      this.loading = true;
      const res = await this.call_api(
        "get",
        `user/orders?page=${this.currentPage}`
      );

      if (res.data.success) {
        this.orders = res.data.data;
        this.totalPages = res.data.meta.last_page;
        this.currentPage = res.data.meta.current_page;
      } else {
        this.snack({
          message: this.$i18n.t("something_went_wrong"),
          color: "red",
        });
      }
      this.loading = false;
    },

    openOrderDetails(order) {
      this.$router.push({
        name: "OrderDetails",
        params: { code: order.code },
      });
    },

    async invoiceDownload(order) {
      order.orders.forEach(async (subOrder) => {
        const res = await this.call_api(
          "get",
          `order/invoice-download/${subOrder.id}`
        );
        if (res.data.success) {
          const fileUrl = res.data.invoice_url;
          const link = document.createElement("a");
          link.href = fileUrl;
          link.download = res.data.invoice_name;
          link.click();
        } else {
          this.snack({
            message: this.$i18n.t("something_went_wrong"),
            color: "red",
          });
        }
      });
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      const day = date.getDate();
      const month = date.toLocaleString('en', { month: 'long' });
      const year = date.getFullYear();
      const suffix = this.getDaySuffix(day);
      return `${day}${suffix} ${month} ${year}`;
    },

    formatDeliveryDate(dateString) {
      const date = new Date(dateString);
      const weekday = date.toLocaleString('en', { weekday: 'long' });
      const day = date.getDate();
      const month = date.toLocaleString('en', { month: 'long' });
      const year = date.getFullYear();
      const suffix = this.getDaySuffix(day);
      return `${weekday} ${day}${suffix} ${month} ${year}`;
    },

    getDaySuffix(day) {
      if (day >= 11 && day <= 13) return 'th';
      switch (day % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
      }
    },

    getTimelineClass(stage, status) {
      const statusLower = (status || '').toLowerCase();

      if (stage === 'placed') {
        return 'completed';
      }

      if (stage === 'transit') {
        if (statusLower.includes('transit') || statusLower.includes('shipping') || statusLower.includes('shipped')) {
          return 'active';
        }
        if (statusLower.includes('delivered')) {
          return 'completed';
        }
      }

      if (stage === 'delivered') {
        if (statusLower.includes('delivered')) {
          return 'completed';
        }
      }

      return '';
    },
  },

  created() {
    let page = this.$route.query.page || this.currentPage;
    this.getList(page);
  },
};
</script>

<style lang="scss" scoped>
.orders-container {
  width: 100%;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-icon {
  margin-bottom: 24px;

  i {
    font-size: 80px;
    color: #ccc;
  }
}

.empty-text {
  color: #666;
  font-size: 16px;
  margin-bottom: 32px;
}

.shop-btn {
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0.5px;
  padding: 12px 32px !important;
  height: 48px !important;
}

.order-card {
  background: white;
  border: 1px solid #e8e8e8;
  border-radius: 12px;
  overflow: hidden;
}

.order-header {
  background: #FAFAFA;
  padding: 20px 24px;
}

.order-header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.order-number {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 4px;
}

.order-date {
  font-size: 13px;
  color: #999;
}

.order-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  text-transform: none;
  font-size: 13px;
  color: #666;
  padding: 0 8px !important;
  height: 32px !important;
  min-width: auto !important;

  &:hover {
    color: #1a1a1a;
  }
}

.order-body {
  padding: 24px;
}

.order-product {
  display: flex;
  gap: 24px;
  margin-bottom: 20px;
}

.product-image-wrapper {
  flex-shrink: 0;
}

.product-image {
  border-radius: 8px;
  border: 1px solid #f0f0f0;
}

.product-details {
  flex: 1;
}

.product-name {
  font-size: 17px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 6px 0;
}

.product-description {
  font-size: 13px;
  color: #999;
  margin: 0 0 8px 0;
}

.product-variant {
  font-size: 13px;
  color: #666;
  margin: 0;
}

.order-timeline {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding-top: 4px;
}

.timeline-item {
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;

  &::after {
    content: "";
    position: absolute;
    left: 7px;
    top: 24px;
    width: 2px;
    height: 24px;
    background: #e8e8e8;
  }

  &:last-child::after {
    display: none;
  }

  &.completed {
    .timeline-dot {
      background: #10B981;
      border-color: #10B981;
    }

    &::after {
      background: #10B981;
    }
  }

  &.active {
    .timeline-dot {
      background: #F59E0B;
      border-color: #F59E0B;
    }
  }
}

.timeline-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #e8e8e8;
  background: white;
  flex-shrink: 0;
}

.timeline-label {
  font-size: 13px;
  font-weight: 500;
  color: #1a1a1a;
}

.delivery-alert {
  background: #8B0000;
  color: white;
  padding: 14px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  text-align: center;
}

@media (max-width: 960px) {
  .order-header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .order-product {
    flex-direction: column;
  }

  .order-timeline {
    margin-top: 24px;
  }
}
</style>