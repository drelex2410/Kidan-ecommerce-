<template>
  <v-container class="checkout-view">
    <div class="checkout-layout">
      <div class="checkout-layout__main">
        <div class="checkout-page">
      <div class="checkout-page__header">
        <h1>Checkout</h1>
      </div>

      <CheckoutStepper :current-step="currentStep" />

      <div class="checkout-page__intro">
        <span class="checkout-page__intro-bar"></span>
        <p>
          To ensure a smooth, timely, and accurate delivery of your order,
          please take a moment to carefully review and confirm that all the
          information provided below is correct.
        </p>
      </div>

      <div
        class="checkout-page__delivery-mode"
        v-if="generalSettings.pickup_point"
      >
        <button
          type="button"
          class="checkout-toggle"
          :class="{ 'is-active': selectedDeliveryType === 'home_delivery' }"
          @click="ChooseDeleviryType('home_delivery')"
        >
          Home Delivery
        </button>
        <button
          type="button"
          class="checkout-toggle"
          :class="{ 'is-active': selectedDeliveryType === 'pickup' }"
          @click="checkForPickUp('pickup')"
        >
          Pickup
        </button>
      </div>

      <template v-if="currentStep === 1">
        <CheckoutSectionCard title="Shipping Address">
          <div
            v-if="isAuthenticated && selectedDeliveryType === 'home_delivery'"
            class="checkout-form-grid checkout-form-grid--saved"
          >
            <div class="checkout-saved-note checkout-form-field--full">
              <span class="checkout-saved-note__label">Saved Address</span>
              <p>
                Using your account address book for delivery. Select a saved
                address below or update it from the change action.
              </p>
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Address Book</label>
              <div class="checkout-select-wrap">
                <select
                  v-model="selectedShippingAddressId"
                  class="checkout-input"
                  @change="shippingAddressSelected(selectedShippingAddressId)"
                >
                  <option
                    v-for="address in getAddresses"
                    :key="address.id"
                    :value="address.id"
                  >
                    {{ address.address }}, {{ address.city }}
                  </option>
                </select>
                <i class="las la-angle-down"></i>
              </div>
            </div>

            <div class="checkout-form-field checkout-form-field--full">
              <label>Location</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="selectedShippingAddressPreview.country || ''"
                readonly
              />
            </div>
            <div class="checkout-form-field">
              <label>First Name</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="shippingNameParts.firstName"
                readonly
              />
            </div>
            <div class="checkout-form-field">
              <label>Last Name</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="shippingNameParts.lastName"
                readonly
              />
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Address</label>
              <textarea
                class="checkout-input checkout-input--textarea checkout-input--readonly"
                :value="selectedShippingAddressPreview.address || ''"
                readonly
              ></textarea>
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Nearest Landmark (Optional)</label>
              <input
                class="checkout-input checkout-input--readonly"
                value=""
                readonly
                placeholder="Not specified"
              />
            </div>
            <div class="checkout-form-field">
              <label>State</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="selectedShippingAddressPreview.state || ''"
                readonly
              />
            </div>
            <div class="checkout-form-field">
              <label>City</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="selectedShippingAddressPreview.city || ''"
                readonly
              />
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Phone Number</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="selectedShippingAddressPreview.phone || currentUser?.phone || ''"
                readonly
              />
            </div>
          </div>

          <div
            v-else
            class="checkout-form-grid"
          >
            <div class="checkout-form-field checkout-form-field--full">
              <label>Location</label>
              <div class="checkout-select-wrap">
                <select
                  v-model="guestForm.country_id"
                  class="checkout-input"
                  @change="guestCountryChanged"
                >
                  <option :value="null">Select country</option>
                  <option
                    v-for="country in countries"
                    :key="country.id"
                    :value="country.id"
                  >
                    {{ country.name }}
                  </option>
                </select>
                <i class="las la-angle-down"></i>
              </div>
            </div>
            <div class="checkout-form-field">
              <label>First Name</label>
              <input
                v-model="guestForm.first_name"
                class="checkout-input"
                type="text"
              />
            </div>
            <div class="checkout-form-field">
              <label>Last Name</label>
              <input
                v-model="guestForm.last_name"
                class="checkout-input"
                type="text"
              />
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Address</label>
              <textarea
                v-model="guestForm.address"
                class="checkout-input checkout-input--textarea"
              ></textarea>
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Nearest Landmark (Optional)</label>
              <input
                v-model="guestForm.landmark"
                class="checkout-input"
                type="text"
              />
            </div>
            <div class="checkout-form-field">
              <label>State</label>
              <div class="checkout-select-wrap">
                <select
                  v-model="guestForm.state_id"
                  class="checkout-input"
                  @change="guestStateChanged"
                >
                  <option :value="null">Select state</option>
                  <option
                    v-for="state in filteredStates"
                    :key="state.id"
                    :value="state.id"
                  >
                    {{ state.name }}
                  </option>
                </select>
                <i class="las la-angle-down"></i>
              </div>
            </div>
            <div class="checkout-form-field">
              <label>City</label>
              <div class="checkout-select-wrap">
                <select
                  v-model="guestForm.city_id"
                  class="checkout-input"
                  @change="guestShippingChanged"
                >
                  <option :value="null">Select city</option>
                  <option
                    v-for="city in filteredCities"
                    :key="city.id"
                    :value="city.id"
                  >
                    {{ city.name }}
                  </option>
                </select>
                <i class="las la-angle-down"></i>
              </div>
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Phone Number</label>
              <input
                v-model="guestForm.phone"
                class="checkout-input"
                type="tel"
                @input="syncGuestContactPhone"
              />
            </div>
          </div>

          <template #action>
            <button
              v-if="isAuthenticated && selectedDeliveryType === 'home_delivery'"
              type="button"
              class="checkout-inline-link"
              @click="addDialogShow = true"
            >
              Change
            </button>
          </template>
        </CheckoutSectionCard>

        <CheckoutSectionCard title="Contact Information">
          <div
            v-if="isAuthenticated"
            class="checkout-form-grid checkout-form-grid--saved"
          >
            <div class="checkout-saved-note checkout-form-field--full">
              <span class="checkout-saved-note__label">Account Contact</span>
              <p>
                These details come from your account and selected delivery
                address so your order confirmation stays consistent.
              </p>
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Email</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="currentUser?.email || ''"
                readonly
              />
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Phone Number</label>
              <input
                class="checkout-input checkout-input--readonly"
                :value="selectedShippingAddressPreview.phone || currentUser?.phone || ''"
                readonly
              />
            </div>
          </div>

          <div
            v-else
            class="checkout-form-grid"
          >
            <div class="checkout-form-field checkout-form-field--full">
              <label>Email</label>
              <input
                v-model="guestForm.email"
                class="checkout-input"
                type="email"
              />
            </div>
            <div class="checkout-form-field checkout-form-field--full">
              <label>Phone Number</label>
              <input
                v-model="guestForm.contact_phone"
                class="checkout-input"
                type="tel"
                :disabled="samePhoneAsShipping"
              />
            </div>
            <label class="checkout-checkbox">
              <input
                v-model="samePhoneAsShipping"
                type="checkbox"
                @change="syncGuestContactPhone"
              />
              <span>Use the same phone number as shipping address</span>
            </label>
          </div>
        </CheckoutSectionCard>

        <CheckoutSectionCard :title="selectedDeliveryType === 'pickup' ? 'Pickup Option' : 'Shipping Option'">
          <div
            v-if="selectedDeliveryType === 'pickup'"
            class="checkout-pickup-grid"
          >
            <button
              v-for="pickupPoint in getPickupPoints"
              :key="pickupPoint.id"
              type="button"
              class="checkout-pickup-card"
              :class="{ 'is-selected': selectedPickupPoint === pickupPoint.id }"
              @click="selectedPickupPoint = pickupPoint.id"
            >
              <strong>{{ pickupPoint.name }}</strong>
              <span>{{ pickupPoint.location }}</span>
              <span>{{ pickupPoint.phone }}</span>
            </button>
          </div>
          <div
            v-else-if="selectedDeliveryOption !== ''"
            class="checkout-option-grid"
          >
            <button
              type="button"
              class="checkout-option-card"
              :class="{ 'is-selected': selectedDeliveryOption === 'standard' }"
              @click="selectedDeliveryOption = 'standard'"
            >
              <div>
                <strong>Est. Delivery {{ standardDeliveryLabel }}</strong>
                <span>Standard Delivery</span>
              </div>
              <strong>{{ format_price(standardDeliveryCost, false) }}</strong>
            </button>
            <button
              type="button"
              class="checkout-option-card"
              :class="{ 'is-selected': selectedDeliveryOption === 'express' }"
              @click="selectedDeliveryOption = 'express'"
            >
              <div>
                <strong>Est. Delivery {{ expressDeliveryLabel }}</strong>
                <span>Express Delivery</span>
              </div>
              <strong>{{ format_price(expressDeliveryCost, false) }}</strong>
            </button>
          </div>
          <div
            v-else
            class="checkout-unavailable"
          >
            Sorry, delivery is not available in this shipping address.
          </div>
        </CheckoutSectionCard>

        <div class="checkout-page__actions">
          <button
            type="button"
            class="checkout-primary-button"
            @click="goToPaymentStep"
          >
            Proceed to Payment
          </button>
        </div>
      </template>

      <template v-else>
        <CheckoutSectionCard title="Shipping Details">
          <div class="checkout-shipping-summary">
            <div>
              <h4>Ship to:</h4>
              <p>{{ shippingSummaryName }}</p>
              <p>{{ shippingSummaryAddress }}</p>
              <p>{{ shippingSummaryLocation }}</p>
              <button
                type="button"
                class="checkout-inline-link"
                @click="currentStep = 1"
              >
                Change
              </button>
            </div>
            <div>
              <h4>{{ selectedDeliveryType === "pickup" ? "Pickup Option:" : "Shipping Option:" }}</h4>
              <p>{{ paymentStepDeliverySummary }}</p>
              <p v-if="selectedDeliveryType === 'pickup' && selectedPickupPointObject">
                {{ selectedPickupPointObject.location }}
              </p>
              <p v-if="contactPhoneDisplay">{{ contactPhoneDisplay }}</p>
            </div>
          </div>
        </CheckoutSectionCard>

        <CheckoutSectionCard title="Payment">
          <div class="checkout-payment-grid">
            <button
              v-for="paymentMethod in visiblePaymentMethods"
              :key="paymentMethod.code"
              type="button"
              class="checkout-payment-card"
              :class="{ 'is-selected': selectedPaymentMethod?.code === paymentMethod.code }"
              @click="paymentSelected(null, paymentMethod)"
            >
              <div class="checkout-payment-card__meta">
                <img
                  :src="paymentMethod.img"
                  :alt="paymentMethod.name"
                />
                <div>
                  <strong>{{ paymentMethod.name }}</strong>
                  <span>{{ paymentMethodDescription(paymentMethod) }}</span>
                </div>
              </div>
            </button>

            <button
              v-if="generalSettings.wallet_system == 1 && isAuthenticated"
              type="button"
              class="checkout-payment-card"
              :class="{ 'is-selected': selectedPaymentMethod?.code === 'wallet' }"
              @click="walletSelected"
            >
              <div class="checkout-payment-card__meta">
                <div class="checkout-wallet-badge">W</div>
                <div>
                  <strong>Wallet</strong>
                  <span>Your balance: {{ format_price(currentUser.balance, false) }}</span>
                </div>
              </div>
            </button>
          </div>

          <div
            v-if="selectedPaymentMethod?.code === 'authorizenet'"
            class="checkout-payment-extra"
          >
            <div class="checkout-form-grid">
              <div class="checkout-form-field checkout-form-field--full">
                <label>Card Number</label>
                <input
                  v-model="authorizeNet.card_number"
                  class="checkout-input"
                  type="text"
                />
              </div>
              <div class="checkout-form-field">
                <label>CVV</label>
                <input
                  v-model="authorizeNet.cvv"
                  class="checkout-input"
                  type="text"
                />
              </div>
              <div class="checkout-form-field">
                <label>Expiration</label>
                <div class="checkout-card-expiry">
                  <select
                    v-model="authorizeNet.expiration_month"
                    class="checkout-input"
                  >
                    <option value="">Month</option>
                    <option
                      v-for="month in months"
                      :key="month"
                      :value="month"
                    >
                      {{ month }}
                    </option>
                  </select>
                  <select
                    v-model="authorizeNet.expiration_year"
                    class="checkout-input"
                  >
                    <option value="">Year</option>
                    <option
                      v-for="year in dateLoop"
                      :key="year"
                      :value="year"
                    >
                      {{ year }}
                    </option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="selectedPaymentMethod?.code?.includes('offline_payment')"
            class="checkout-payment-extra"
          >
            <div class="checkout-form-grid">
              <div class="checkout-form-field checkout-form-field--full">
                <label>Transaction ID</label>
                <input
                  v-model="transactionId"
                  class="checkout-input"
                  type="text"
                />
              </div>
              <div class="checkout-form-field checkout-form-field--full">
                <label>Add Receipt</label>
                <input
                  class="checkout-input"
                  type="file"
                  accept="image/*"
                  @change="receipt = $event.target.files?.[0] || null"
                />
              </div>
            </div>
          </div>

          <label class="checkout-checkbox checkout-checkbox--payment">
            <input
              v-model="checkbox"
              type="checkbox"
            />
            <span>
              By clicking proceed, I agree to {{ $store.getters["app/appName"] }}'s
              <router-link
                :to="{ name: 'CustomPage', params: { pageSlug: 'terms-and-conditions' } }"
              >
                terms and conditions
              </router-link>
              and
              <router-link
                :to="{ name: 'CustomPage', params: { pageSlug: 'privacy-policy' } }"
              >
                privacy policy
              </router-link>
            </span>
          </label>

          <div class="checkout-page__actions checkout-page__actions--payment">
            <button
              type="button"
              class="checkout-primary-button"
              :disabled="checkoutLoading"
              @click="proceedCheckout"
            >
              {{ paymentButtonLabel }}
            </button>
          </div>
        </CheckoutSectionCard>
      </template>

      <Payment ref="makePayment" />
      <FailedDialog ref="failedPayment" />
      <RechargeDialog
        :show="rechargeDialogShow"
        from="/checkout"
        @close="rechargeDialogClosed"
      />

      <address-dialog
        v-if="isAuthenticated && selectedDeliveryType === 'home_delivery'"
        :show="addDialogShow"
        @close="addressDialogClosed"
        :old-address="addressSelectedForEdit"
      />
        </div>
      </div>

      <aside class="checkout-layout__summary">
      <CheckoutSummaryPanel
        mode="checkout"
        :item-count="selectedCartItems.length"
        :subtotal="getCartPrice"
        :discount="getTotalCouponDiscount"
        :points="getCartClubPoints"
        :shipping-label="summaryShippingLabel"
        :total="totalPrice"
      />
      </aside>
    </div>
  </v-container>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import { useHead } from "@unhead/vue";
import AddressDialog from "../components/address/AddressDialog.vue";
import RechargeDialog from "../components/wallet/RechargeDialog.vue";
import FailedDialog from "./../components/payment/FailedDialog.vue";
import Payment from "./../components/payment/Payment.vue";
import CheckoutStepper from "../components/checkout/CheckoutStepper.vue";
import CheckoutSummaryPanel from "../components/checkout/CheckoutSummaryPanel.vue";
import CheckoutSectionCard from "../components/checkout/CheckoutSectionCard.vue";

export default {
  name: "AizShopCheckout",
  components: {
    AddressDialog,
    RechargeDialog,
    Payment,
    FailedDialog,
    CheckoutStepper,
    CheckoutSummaryPanel,
    CheckoutSectionCard,
  },
  data() {
    return {
      currentStep: 1,
      for_pickup: true,
      selectedPickupPoint: null,
      checkbox: false,
      checkoutLoading: false,
      countriesLoaded: false,
      countries: [],
      filteredStates: [],
      filteredCities: [],
      selectedShippingAddressId: null,
      selectedBillingAddressId: null,
      selectedPaymentMethod: null,
      selectedDeliveryOption: "",
      selectedDeliveryType: "home_delivery",
      standardDeliveryCost: 0,
      expressDeliveryCost: 0,
      addDialogShow: false,
      addressSelectedForEdit: {},
      rechargeDialogShow: false,
      transactionId: null,
      receipt: null,
      samePhoneAsShipping: true,
      guestForm: {
        first_name: "",
        last_name: "",
        email: "",
        phone: "",
        contact_phone: "",
        address: "",
        landmark: "",
        postal_code: "",
        country_id: null,
        state_id: null,
        city_id: null,
      },
      authorizeNet: {
        card_number: "",
        cvv: "",
        expiration_month: "",
        expiration_year: "",
      },
      months: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ],
      dateLoop: [],
    };
  },
  computed: {
    ...mapGetters("app", [
      "generalSettings",
      "paymentMethods",
      "offlinePaymentMethods",
      "appMetaTitle",
      "appMetaDescription",
    ]),
    ...mapGetters("address", [
      "getAddresses",
      "getDefaultShippingAddress",
      "getDefaultBillingAddress",
    ]),
    ...mapGetters("cart", [
      "getCartPrice",
      "getTotalCouponDiscount",
      "getCartClubPoints",
      "getCartTax",
      "getCartShops",
      "getStandardTime",
      "getExpressTime",
      "getAllCouponCodes",
      "getSelectedCartIds",
      "checkShopMinOrder",
      "getIsDigital",
      "getPickupPoints",
      "getCartProducts",
      "getTempUserId",
    ]),
    ...mapGetters("auth", ["currentUser", "isAuthenticated"]),
    selectedCartItems() {
      return this.getCartProducts.filter((item) => item.selected);
    },
    guestShopCount() {
      return Math.max(1, this.getCartShops.length);
    },
    selectedShippingAddressPreview() {
      if (!this.isAuthenticated) return {};
      return (
        this.getAddresses.find(
          (address) => Number(address.id) === Number(this.selectedShippingAddressId)
        ) || this.getDefaultShippingAddress || {}
      );
    },
    shippingNameParts() {
      const rawName = this.isAuthenticated
        ? this.currentUser?.name || ""
        : `${this.guestForm.first_name} ${this.guestForm.last_name}`.trim();
      const [firstName = "", ...rest] = rawName.trim().split(" ");
      return {
        firstName,
        lastName: rest.join(" "),
      };
    },
    shippingSummaryName() {
      if (this.isAuthenticated) {
        return this.currentUser?.name || "Customer";
      }

      return `${this.guestForm.first_name} ${this.guestForm.last_name}`.trim();
    },
    shippingSummaryAddress() {
      if (this.isAuthenticated) {
        return this.selectedShippingAddressPreview.address || "";
      }

      return [this.guestForm.address, this.guestForm.landmark]
        .filter(Boolean)
        .join(", ");
    },
    shippingSummaryLocation() {
      if (this.isAuthenticated) {
        return [
          this.selectedShippingAddressPreview.city,
          this.selectedShippingAddressPreview.state,
          this.selectedShippingAddressPreview.country,
        ]
          .filter(Boolean)
          .join(", ");
      }

      return [
        this.selectedGuestCity?.name,
        this.selectedGuestState?.name,
        this.selectedGuestCountry?.name,
      ]
        .filter(Boolean)
        .join(", ");
    },
    selectedGuestCountry() {
      return this.countries.find(
        (country) => Number(country.id) === Number(this.guestForm.country_id)
      );
    },
    selectedGuestState() {
      return this.filteredStates.find(
        (state) => Number(state.id) === Number(this.guestForm.state_id)
      );
    },
    selectedGuestCity() {
      return this.filteredCities.find(
        (city) => Number(city.id) === Number(this.guestForm.city_id)
      );
    },
    effectiveGuestPhone() {
      return this.samePhoneAsShipping
        ? this.guestForm.phone
        : this.guestForm.contact_phone || this.guestForm.phone;
    },
    contactPhoneDisplay() {
      if (this.isAuthenticated) {
        return this.selectedShippingAddressPreview.phone || this.currentUser?.phone || "";
      }

      return this.effectiveGuestPhone;
    },
    paymentButtonLabel() {
      if (!this.selectedPaymentMethod) {
        return "Select Payment Method";
      }

      return ["cash_on_delivery", "wallet"].includes(this.selectedPaymentMethod.code) ||
        this.selectedPaymentMethod.code.includes("offline_payment")
        ? "Complete Order"
        : "Make Payment";
    },
    visiblePaymentMethods() {
      const online = this.paymentMethods.filter((method) => {
        if (method.status != 1) return false;
        if (this.getIsDigital && method.code === "cash_on_delivery") return false;
        return true;
      });

      return [...online, ...this.offlinePaymentMethods];
    },
    summaryShippingLabel() {
      if (this.selectedDeliveryType === "pickup") {
        return this.selectedPickupPointObject?.name || "Awaiting Selection";
      }

      if (this.selectedDeliveryType !== "home_delivery" || !this.selectedDeliveryOption) {
        return "Awaiting Selection";
      }

      const label = this.selectedDeliveryOption === "express" ? "Express" : "Standard";
      return `${label} ${this.format_price(this.selectedShippingCost, false)}`;
    },
    selectedShippingCost() {
      if (this.selectedDeliveryType !== "home_delivery") {
        return 0;
      }

      return this.selectedDeliveryOption === "express"
        ? this.expressDeliveryCost
        : this.standardDeliveryCost;
    },
    totalPrice() {
      return this.selectedDeliveryType == "home_delivery"
        ? this.getCartPrice -
            this.getTotalCouponDiscount +
            this.selectedShippingCost * this.getCartShops.length
        : this.getCartPrice - this.getTotalCouponDiscount;
    },
    standardDeliveryLabel() {
      return this.getStandardTime ? `within ${this.getStandardTime} day(s)` : "within standard window";
    },
    expressDeliveryLabel() {
      return this.getExpressTime ? `within ${this.getExpressTime} day(s)` : "within express window";
    },
    paymentStepDeliverySummary() {
      if (this.selectedDeliveryType === "pickup") {
        return this.selectedPickupPointObject?.name || "Pickup";
      }

      const speed = this.selectedDeliveryOption === "express" ? "Express" : "Standard";
      return `${speed} - ${this.format_price(this.selectedShippingCost, false)}`;
    },
    selectedPickupPointObject() {
      return (
        this.getPickupPoints.find(
          (pickupPoint) => Number(pickupPoint.id) === Number(this.selectedPickupPoint)
        ) || null
      );
    },
  },
  watch: {
    appMetaTitle: {
      immediate: true,
      handler(title) {
        useHead({
          title: title || "Checkout",
          meta: [{ name: "description", content: this.appMetaDescription || "" }],
        });
      },
    },
  },
  methods: {
    ...mapActions("cart", [
      "resetCoupon",
      "removeMultipleFromCart",
      "fetchCartProducts",
      "fetchPickupPoints",
    ]),
    ...mapActions("address", ["fetchAddresses"]),
    ...mapActions("auth", ["rechargeWallet", "deductFromWallet"]),
    async checkForPickUp(type) {
      this.getCartProducts.map((product) => {
        if (product.for_pickup == 0) {
          this.selectedPickupPoint = null;
          this.for_pickup = false;
          this.snack({
            message: `One or more items in the cart are not available for pickup`,
            color: "red",
          });
          return;
        } else {
          this.for_pickup = true;
        }
      });
      this.selectedDeliveryType = type;
      this.selectedDeliveryOption = "";
    },
    ChooseDeleviryType(deliveryType) {
      this.selectedDeliveryType = deliveryType;
      if (deliveryType === "home_delivery") {
        if (this.isAuthenticated && this.selectedShippingAddressId) {
          this.getShippingCost(this.selectedShippingAddressId);
        } else if (!this.isAuthenticated) {
          this.getShippingCost(null);
        }
      }
    },
    addressDialogClosed() {
      this.addressSelectedForEdit = {};
      this.addDialogShow = false;
    },
    rechargeDialogClosed() {
      this.rechargeDialogShow = false;
    },
    paymentSelected(event, paymentMethod) {
      this.selectedPaymentMethod = paymentMethod;
    },
    walletSelected() {
      if (this.currentUser.balance >= this.totalPrice) {
        this.selectedPaymentMethod = { code: "wallet", name: "Wallet" };
      } else {
        this.snack({
          message: `You don't have enough wallet balance. Please recharge.`,
          color: "red",
        });
      }
    },
    shippingAddressSelected(addressId) {
      this.selectedBillingAddressId = addressId;
      this.getShippingCost(addressId);
    },
    async getShippingCost(addressId) {
      let res;

      if (this.isAuthenticated) {
        if (!addressId) {
          this.selectedDeliveryOption = "";
          this.standardDeliveryCost = 0;
          this.expressDeliveryCost = 0;
          return;
        }

        res = await this.call_api("get", `checkout/get-shipping-cost/${addressId}`);
      } else {
        if (!this.guestForm.city_id) {
          this.selectedDeliveryOption = "";
          this.standardDeliveryCost = 0;
          this.expressDeliveryCost = 0;
          return;
        }

        res = await this.call_api("post", "checkout/shipping-quote", {
          guest_city_id: this.guestForm.city_id,
          shop_count: this.guestShopCount,
        });
      }

      this.selectedDeliveryOption = res.data.success ? "standard" : "";
      this.standardDeliveryCost = parseFloat(res.data.standard_delivery_cost || 0);
      this.expressDeliveryCost = parseFloat(res.data.express_delivery_cost || 0);
    },
    async fetchCountries() {
      if (this.countriesLoaded) return;
      const res = await this.call_api("get", "all-countries");
      if (res.data.success) {
        this.countriesLoaded = true;
        this.countries = res.data.data;
      }
    },
    async guestCountryChanged() {
      this.filteredStates = [];
      this.filteredCities = [];
      this.guestForm.state_id = null;
      this.guestForm.city_id = null;

      if (!this.guestForm.country_id) return;

      const res = await this.call_api("get", `states/${this.guestForm.country_id}`);
      if (res.data.success) {
        this.filteredStates = res.data.data;
      }
      this.guestShippingChanged();
    },
    async guestStateChanged() {
      this.filteredCities = [];
      this.guestForm.city_id = null;

      if (!this.guestForm.state_id) return;

      const res = await this.call_api("get", `cities/${this.guestForm.state_id}`);
      if (res.data.success) {
        this.filteredCities = res.data.data;
      }
      this.guestShippingChanged();
    },
    guestShippingChanged() {
      if (this.selectedDeliveryType === "home_delivery") {
        this.getShippingCost(null);
      }
    },
    syncGuestContactPhone() {
      if (this.samePhoneAsShipping) {
        this.guestForm.contact_phone = this.guestForm.phone;
      }
    },
    validateShippingStep() {
      if (this.getSelectedCartIds.length === 0) {
        this.snack({
          message: `Please select a cart product`,
          color: "red",
        });
        return false;
      }

      if (this.selectedDeliveryType === "") {
        this.snack({
          message: `Please select delivery type at first`,
          color: "red",
        });
        return false;
      }

      if (this.selectedDeliveryType === "pickup" && this.for_pickup == false) {
        this.snack({
          message: `One or more items in the cart are not available for pickup`,
          color: "red",
        });
        return false;
      }

      if (this.selectedDeliveryType === "pickup" && this.selectedPickupPoint == null) {
        this.snack({
          message: `Please select a pick up point`,
          color: "red",
        });
        return false;
      }

      if (this.isAuthenticated) {
        if (this.selectedDeliveryType === "home_delivery" && this.getAddresses.length === 0) {
          this.snack({
            message: `Please add a delivery address`,
            color: "red",
          });
          return false;
        }

        if (this.selectedDeliveryType === "home_delivery" && !this.selectedShippingAddressId) {
          this.snack({
            message: `Please select a delivery address`,
            color: "red",
          });
          return false;
        }

        this.selectedBillingAddressId = this.selectedShippingAddressId;
      } else {
        const validGuest =
          this.guestForm.first_name &&
          this.guestForm.last_name &&
          this.guestForm.email &&
          this.guestForm.phone &&
          this.effectiveGuestPhone &&
          this.guestForm.address &&
          this.guestForm.country_id &&
          this.guestForm.state_id &&
          this.guestForm.city_id;

        if (!validGuest) {
          this.snack({
            message: `Please complete your contact and shipping details`,
            color: "red",
          });
          return false;
        }
      }

      if (this.selectedDeliveryType === "home_delivery" && this.selectedDeliveryOption === "") {
        this.snack({
          message: `Sorry, delivery is not available in this shipping address.`,
          color: "red",
        });
        return false;
      }

      if (!this.checkShopMinOrder.success) {
        this.snack({
          message: this.checkShopMinOrder.message,
          color: "red",
        });
        return false;
      }

      return true;
    },
    goToPaymentStep() {
      if (!this.validateShippingStep()) {
        return;
      }

      this.currentStep = 2;
      window.scrollTo({ top: 0, behavior: "smooth" });
    },
    paymentMethodDescription(paymentMethod) {
      if (paymentMethod.code.includes("offline_payment")) {
        return "Submit your proof of payment";
      }

      return "Complete your payment";
    },
    async proceedCheckout() {
      if (!this.validateShippingStep()) {
        this.currentStep = 1;
        return;
      }

      if (!this.checkbox) {
        this.snack({
          message: `You need to agree with our policies`,
          color: "red",
        });
        return;
      }

      if (!this.selectedPaymentMethod) {
        this.snack({
          message: `Please select a payment method`,
          color: "red",
        });
        return;
      }

      if (
        this.selectedPaymentMethod.code.includes("offline_payment") &&
        this.transactionId === null
      ) {
        this.snack({
          message: this.$i18n.t("please_input_transaction_id"),
          color: "red",
        });
        return;
      }

      const formData = new FormData();
      formData.append("shipping_address_id", this.selectedShippingAddressId ?? "");
      formData.append("billing_address_id", this.selectedBillingAddressId ?? "");
      formData.append("payment_type", this.selectedPaymentMethod.code);
      formData.append("delivery_type", this.selectedDeliveryOption);
      formData.append("type_of_delivery", this.selectedDeliveryType);
      formData.append("pickup_point_id", this.selectedPickupPoint ?? "");
      formData.append("temp_user_id", this.getTempUserId ?? "");

      if (!this.isAuthenticated) {
        formData.append(
          "guest_name",
          `${this.guestForm.first_name} ${this.guestForm.last_name}`.trim()
        );
        formData.append("guest_email", this.guestForm.email);
        formData.append("guest_phone", this.effectiveGuestPhone);
        formData.append(
          "guest_address",
          [this.guestForm.address, this.guestForm.landmark]
            .filter(Boolean)
            .join(", ")
        );
        formData.append("guest_postal_code", this.guestForm.postal_code);
        formData.append("guest_country_id", this.guestForm.country_id);
        formData.append("guest_state_id", this.guestForm.state_id);
        formData.append("guest_city_id", this.guestForm.city_id);
      }

      this.getSelectedCartIds.forEach((item) => {
        formData.append("cart_item_ids[]", item);
      });

      this.getAllCouponCodes.forEach((couponItem) => {
        formData.append("coupon_codes[]", couponItem);
      });

      formData.append("transactionId", this.transactionId);
      if (this.receipt) {
        formData.append("receipt", this.receipt);
      }

      if (this.getCartPrice > 0) {
        this.checkoutLoading = true;
        const res = await this.call_api("post", "checkout/order/store", formData);
        if (res.data.success) {
          if (res.data.payment_method == "wallet") {
            this.deductFromWallet(res.data.grand_total);
          }

          if (res.data.go_to_payment) {
            this.$refs.makePayment.pay({
              requestedFrom: "/checkout",
              paymentAmount: 0,
              paymentMethod: res.data.payment_method,
              paymentType: "cart_payment",
              userId: this.currentUser?.id || null,
              oderCode: res.data.order_code,
              card_number: this.authorizeNet.card_number,
              cvv: this.authorizeNet.cvv,
              expiration_month: this.authorizeNet.expiration_month,
              expiration_year: this.authorizeNet.expiration_year,
            });
          } else {
            this.$router
              .push({
                name: "OrderConfirmed",
                query: { orderCode: res.data.order_code },
              })
              .catch(() => {});
          }
          setTimeout(() => {
            this.resetCoupon();
            this.removeMultipleFromCart(this.getSelectedCartIds);
          }, 2000);
        } else {
          this.snack({
            message: res.data.message,
            color: "red",
          });
        }
        this.checkoutLoading = false;
      }
    },
  },
  async created() {
    if (this.generalSettings.pickup_point) {
      try {
        await this.fetchPickupPoints();
      } catch (error) {
        console.error("Unable to load pickup points for checkout.", error);
      }
    }

    if (this.isAuthenticated) {
      try {
        await this.fetchAddresses();
        this.selectedShippingAddressId = this.getDefaultShippingAddress?.id ?? null;
        this.selectedBillingAddressId =
          this.getDefaultBillingAddress?.id ?? this.selectedShippingAddressId;
        if (this.selectedShippingAddressId) {
          this.getShippingCost(this.selectedShippingAddressId);
        }
      } catch (error) {
        console.error("Unable to load saved checkout addresses.", error);
      }
    } else {
      try {
        await this.fetchCountries();
        this.syncGuestContactPhone();
      } catch (error) {
        console.error("Unable to load checkout countries.", error);
      }
    }

    const dateArray = [];
    let year = new Date().getFullYear();
    for (year; year <= new Date().getFullYear() + 15; year++) {
      dateArray.push(year);
    }
    this.dateLoop = dateArray;
  },
  mounted() {
    if (this.$route.query.cart_payment && this.$route.query.order_code) {
      if (this.$route.query.cart_payment == "success") {
        this.$router
          .push({
            name: "OrderConfirmed",
            query: {
              orderCode: this.$route.query.order_code,
            },
          })
          .catch(() => {});
        this.snack({ message: "Payment successful!" });
      } else if (this.$route.query.cart_payment == "failed") {
        this.$refs.failedPayment.open({
          orderCode: this.$route.query.order_code,
          paymentMethod: this.$route.query.payment_method,
        });
      }
    }
    this.rechargeWallet(this.$route.query.wallet_payment);
    this.fetchCartProducts();

    if (!this.isAuthenticated && this.countries.length === 0) {
      this.fetchCountries().catch((error) => {
        console.error("Retry failed while loading checkout countries.", error);
      });
    }
  },
};
</script>

<style scoped>
.checkout-view {
  max-width: 1460px;
  padding-top: 28px;
  padding-bottom: 56px;
}

.checkout-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.65fr) minmax(320px, 0.75fr);
  gap: 52px;
  align-items: start;
}

.checkout-layout__main,
.checkout-layout__summary {
  min-width: 0;
}

.checkout-page {
  padding: 0;
}

.checkout-page__header {
  border-bottom: 1px solid #ddd4c8;
  padding-bottom: 16px;
}

.checkout-page__header h1 {
  margin: 0;
  font-size: 28px;
  line-height: 1.1;
  color: #111;
  font-weight: 700;
}

.checkout-page__intro {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  margin: 8px 0 16px;
}

.checkout-page__intro-bar {
  width: 3px;
  min-width: 3px;
  height: 52px;
  background: #921b12;
}

.checkout-page__intro p {
  margin: 0;
  color: #4a433a;
  font-size: 17px;
  line-height: 1.55;
  max-width: 860px;
}

.checkout-page__delivery-mode {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 16px 0 10px;
}

.checkout-toggle {
  border: 1px solid #cec3b4;
  background: transparent;
  color: #3b342d;
  padding: 11px 18px;
  font-size: 13px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
}

.checkout-toggle.is-active {
  border-color: #921b12;
  color: #921b12;
  background: rgba(146, 27, 18, 0.04);
}

.checkout-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 16px;
  max-width: 900px;
}

.checkout-form-field {
  display: grid;
  gap: 6px;
}

.checkout-form-field--full {
  grid-column: 1 / -1;
}

.checkout-form-field label {
  font-size: 13px;
  color: #5b5349;
}

.checkout-saved-note {
  border: 1px solid #ece4da;
  background: rgba(255, 255, 255, 0.58);
  padding: 14px 16px;
  display: grid;
  gap: 6px;
}

.checkout-saved-note__label {
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #8f8478;
  font-weight: 700;
}

.checkout-saved-note p {
  margin: 0;
  color: #5b5349;
  font-size: 14px;
  line-height: 1.5;
}

.checkout-input {
  width: 100%;
  min-height: 56px;
  border: 1px solid #ece4da;
  background: rgba(255, 255, 255, 0.68);
  padding: 0 15px;
  color: #17130f;
  font-size: 16px;
  outline: none;
}

.checkout-input--readonly {
  background: rgba(249, 245, 239, 0.9);
  color: #2d2721;
}

.checkout-input--textarea {
  min-height: 82px;
  padding-top: 14px;
  resize: vertical;
}

.checkout-input:focus {
  border-color: #921b12;
  box-shadow: 0 0 0 3px rgba(146, 27, 18, 0.08);
}

.checkout-select-wrap {
  position: relative;
}

.checkout-select-wrap i {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #17130f;
  pointer-events: none;
  font-size: 22px;
}

.checkout-select-wrap select {
  appearance: none;
  padding-right: 46px;
}

.checkout-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  margin-top: 8px;
  color: #5b5349;
  font-size: 15px;
}

.checkout-checkbox input {
  width: 22px;
  height: 22px;
  accent-color: #921b12;
}

.checkout-checkbox a {
  color: #921b12;
  text-decoration: none;
}

.checkout-option-grid,
.checkout-pickup-grid,
.checkout-payment-grid {
  display: grid;
  gap: 12px;
}

.checkout-option-card,
.checkout-pickup-card,
.checkout-payment-card {
  width: 100%;
  border: 1px solid #e7ddd0;
  background: rgba(255, 255, 255, 0.72);
  padding: 20px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.checkout-option-card strong,
.checkout-pickup-card strong,
.checkout-payment-card strong {
  display: block;
  font-size: 17px;
  color: #17130f;
}

.checkout-option-card span,
.checkout-pickup-card span,
.checkout-payment-card span {
  display: block;
  margin-top: 6px;
  color: #776d62;
  font-size: 14px;
}

.checkout-option-card.is-selected,
.checkout-pickup-card.is-selected,
.checkout-payment-card.is-selected {
  border-color: #921b12;
  box-shadow: 0 0 0 1px rgba(146, 27, 18, 0.08);
}

.checkout-payment-card__meta {
  display: flex;
  align-items: center;
  gap: 16px;
}

.checkout-payment-card img {
  width: 42px;
  height: 42px;
  object-fit: contain;
}

.checkout-wallet-badge {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #17130f;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.checkout-payment-extra {
  margin-top: 16px;
  padding: 20px;
  border: 1px solid #e6ddcf;
  background: rgba(255, 255, 255, 0.62);
}

.checkout-card-expiry {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.checkout-unavailable {
  border: 1px solid rgba(146, 27, 18, 0.2);
  color: #921b12;
  background: rgba(146, 27, 18, 0.05);
  padding: 18px;
}

.checkout-page__actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 10px;
}

.checkout-page__actions--payment {
  padding-top: 16px;
}

.checkout-primary-button {
  min-width: 226px;
  min-height: 60px;
  border: none;
  background: #921b12;
  color: #fff;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  padding: 0 28px;
  transition: background 0.2s ease, transform 0.2s ease;
}

.checkout-primary-button:hover:not(:disabled) {
  background: #7f130c;
  transform: translateY(-1px);
}

.checkout-primary-button:disabled {
  opacity: 0.65;
  cursor: wait;
}

.checkout-inline-link {
  border: none;
  background: transparent;
  color: #17130f;
  font-size: 15px;
  font-weight: 700;
  padding: 0;
  cursor: pointer;
}

.checkout-shipping-summary {
  border: 1px solid #ece3d8;
  background: rgba(255, 255, 255, 0.72);
  padding: 30px 32px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
}

.checkout-shipping-summary h4 {
  margin: 0 0 14px;
  font-size: 18px;
  color: #17130f;
}

.checkout-shipping-summary p {
  margin: 0 0 10px;
  color: #4a433a;
  font-size: 16px;
  line-height: 1.5;
}

@media (max-width: 960px) {
  .checkout-view {
    padding-top: 16px;
    padding-bottom: 32px;
  }

  .checkout-layout {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .checkout-form-grid,
  .checkout-shipping-summary {
    grid-template-columns: 1fr;
  }

  .checkout-page__intro {
    margin-bottom: 12px;
  }

  .checkout-page__delivery-mode {
    margin-bottom: 4px;
  }

  .checkout-page__actions {
    justify-content: stretch;
  }

  .checkout-primary-button {
    width: 100%;
  }
}

@media (max-width: 640px) {
  .checkout-page {
    padding-top: 4px;
  }

  .checkout-view {
    padding-left: 16px;
    padding-right: 16px;
  }

  .checkout-page__intro p {
    font-size: 15px;
  }

  .checkout-page__intro {
    gap: 10px;
  }

  .checkout-page__header h1 {
    font-size: 24px;
  }

  .checkout-input {
    min-height: 52px;
    font-size: 15px;
  }

  .checkout-option-card,
  .checkout-pickup-card,
  .checkout-payment-card {
    padding: 16px;
    gap: 14px;
  }

  .checkout-shipping-summary {
    padding: 22px 18px;
  }

  .checkout-toggle {
    width: 100%;
    justify-content: center;
  }

  .checkout-saved-note {
    padding: 12px 14px;
  }

  .checkout-saved-note p {
    font-size: 13px;
  }

  .checkout-primary-button {
    min-height: 54px;
    font-size: 16px;
  }
}
</style>
