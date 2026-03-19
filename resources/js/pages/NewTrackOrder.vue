<template>
    <div class="track-order-page">
        <!-- Hero Banner Section -->
        <div class="hero-banner">
            <div class="banner-content">
                <div class="text-section">
                    <h1 class="main-heading">Keep Tabs on<br>Your Order.</h1>
                    <p class="subheading">We believe in full transparency and seamless shopping.</p>
                </div>

                <!-- Track Order Form Card -->
                <div class="track-form-card">
                    <h2 class="form-title">Track Your Order</h2>
                    <p class="form-subtitle">Please fill the fields below with the correct information</p>

                    <v-form lazy-validation v-on:submit.prevent="trackOrder()">
                        <div class="form-field">
                            <label class="field-label">Order ID</label>
                            <v-text-field :placeholder="$t('order_code')" type="text" v-model="form.orderCode"
                                :error-messages="orderCodeErrors" hide-details="auto" required variant="outlined"
                                density="comfortable"></v-text-field>
                            <p v-for="error of v$.form.orderCode.$errors" :key="error.$uid" class="error-text">
                                {{ error.$message }}
                            </p>
                        </div>

                        <v-btn class="track-button" elevation="0" type="submit" color="primary" block size="large"
                            @click="trackOrder" :loading="loading" :disabled="loading">Track Order</v-btn>

                        <div class="info-notice">
                            <v-icon size="small" class="info-icon">mdi-information-outline</v-icon>
                            <span>Please check your order confirmation mail for your <strong>Order ID.</strong></span>
                        </div>
                    </v-form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useVuelidate } from "@vuelidate/core";
import { required } from "@vuelidate/validators";

export default {
    head: {
        title: 'Order Tracking Page',
    },
    data: () => ({
        loading: false,
        v$: useVuelidate(),
        form: {
            orderCode: "",
        },
    }),
    validations: {
        form: {
            orderCode: {
                required,
            },
        },
    },
    computed: {
        orderCodeErrors() {
            const errors = [];
            if (!this.v$.form.orderCode.$dirty) return errors;
            !this.v$.form.orderCode.required &&
                errors.push(this.$i18n.t("this_field_is_required"));
            return errors;
        },
    },
    methods: {
        async trackOrder() {
            const isFormCorrect = await this.v$.$validate();
            if (!isFormCorrect) return;

            this.loading = true;

            try {
                // Check if order exists first
                const res = await this.call_api(
                    "get",
                    `user/order/${this.form.orderCode}`
                );

                if (res.data.success) {
                    this.$router.push(`/user/order/${this.form.orderCode}`);
                } else {
                    this.snack({
                        message: res.data.message,
                        color: "red",
                    });
                }
            } catch (error) {
                this.snack({
                    message: this.$i18n.t("an_error_occurred"),
                    color: "red",
                });
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.track-order-page {
    width: 100%;
}

.hero-banner {
    height: 600px;
    background-image: url('/public/assets/img/trackorderbanner.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.banner-content {
    width: 100%;
    max-width: 1200px;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 60px;
}

.text-section {
    flex: 1;
    color: white;
}

.main-heading {
    font-size: 56px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 16px;
    color: white;
}

.subheading {
    font-size: 18px;
    font-weight: 400;
    color: white;
    opacity: 0.95;
}

.track-form-card {
    width: 500px;
    height: 480px;
    background: white;
    border-radius: 8px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
}

.form-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-subtitle {
    font-size: 14px;
    color: #666;
    margin-bottom: 32px;
}

.form-field {
    margin-bottom: 20px;
}

.field-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
}

.error-text {
    color: #d32f2f;
    font-size: 12px;
    margin-top: 4px;
}

.track-button {
    margin-top: 12px;
    margin-bottom: 20px;
    text-transform: none;
    font-size: 16px;
    font-weight: 500;
    letter-spacing: 0;
    color: white !important;
}

.info-notice {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    color: #666;
    line-height: 1.5;
}

.info-icon {
    color: #666;
    margin-top: 2px;
}

/* Responsive Design */
@media (max-width: 960px) {
    .banner-content {
        flex-direction: column;
        justify-content: center;
        padding: 40px 24px;
    }

    .text-section {
        text-align: center;
    }

    .main-heading {
        font-size: 42px;
    }

    .track-form-card {
        width: 100%;
        max-width: 500px;
        height: auto;
    }
}

@media (max-width: 600px) {
    .hero-banner {
        height: auto;
        min-height: 600px;
        padding: 40px 0;
    }

    .main-heading {
        font-size: 36px;
    }

    .track-form-card {
        padding: 32px 24px;
    }
}
</style>