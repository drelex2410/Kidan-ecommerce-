<template>
    <div>
        <v-container>
            <v-row>
                <v-col xl="10" class="mx-auto my-5 my-lg-16">
                    <div class="verification-container">
                        <v-row no-gutters class="fill-height">
                            <!-- Left Side - Image -->
                            <v-col cols="12" lg="6" class="image-section d-none d-lg-block">
                                <banner :loading="false" :banner="$store.getters['app/banners'].verification_page"
                                    class="fill-height" />
                            </v-col>

                            <!-- Right Side - Verification Form -->
                            <v-col cols="12" lg="6" class="form-section">
                                <div class="form-wrapper">
                                    <!-- Close Button -->
                                    <div class="text-end mb-4">
                                        <v-btn icon variant="text" size="small" @click="$router.push({ name: 'Home' })">
                                            <v-icon>mdi-close</v-icon>
                                            <span class="ml-1 text-caption">Close</span>
                                        </v-btn>
                                    </div>

                                    <!-- Logo -->
                                    <div class="text-center mb-8">
                                        <div class="logo-circle mx-auto mb-4">
                                            <span class="logo-text">{{ getLogoInitials }}</span>
                                        </div>
                                        <h2 class="welcome-text mb-2">Verification</h2>
                                        <p class="subtitle-text">
                                            <span
                                                v-if="authSettings.customer_login_with == 'email' || (authSettings.customer_login_with == 'email_phone' && authSettings.customer_otp_with == 'email')">
                                                {{ $t('a_verification_code_has_been_sent_to_your_email') }}
                                            </span>
                                            <span
                                                v-else-if="authSettings.customer_login_with == 'phone' || (authSettings.customer_login_with == 'email_phone' && authSettings.customer_otp_with == 'phone')">
                                                {{ $t('a_verification_code_has_been_sent_to_your_phone_number') }}
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Verification Form -->
                                    <v-form ref="loginForm" lazy-validation @submit.prevent="verifyAccount()">
                                        <!-- Email Field -->
                                        <div v-if="authSettings.customer_login_with == 'email' || (authSettings.customer_login_with == 'email_phone' && authSettings.customer_otp_with == 'email')"
                                            class="mb-4">
                                            <div class="field-label mb-2">{{ $t('email') }}</div>
                                            <v-text-field variant="outlined" v-model="form.email"
                                                :placeholder="$t('email_address')" type="email" hide-details="auto"
                                                required density="comfortable" class="custom-text-field"></v-text-field>
                                            <p v-for="error of v$.form.email.$errors" :key="error.$uid"
                                                class="text-red mt-1 text-caption">
                                                {{ error.$message }}
                                            </p>
                                        </div>

                                        <!-- Phone Field -->
                                        <div v-if="authSettings.customer_login_with == 'phone' || (authSettings.customer_login_with == 'email_phone' && authSettings.customer_otp_with == 'phone')"
                                            class="mb-4">
                                            <div class="field-label mb-2">
                                                {{ $t("phone_number") }}
                                            </div>
                                            <vue-tel-input v-model="form.phone" v-bind="mobileInputProps"
                                                :only-countries="availableCountries" @validate="phoneValidate">
                                                <template #arrow-icon><span
                                                        class="vti__dropdown-arrow">&nbsp;▼</span></template>
                                            </vue-tel-input>
                                            <div v-if="v$.form.phone.$error" class="mt-1">
                                                <div class="text-red text-caption">
                                                    {{ $t("this_field_is_required") }}
                                                </div>
                                            </div>
                                            <div v-if="!v$.form.phone.$error && form.showInvalidPhone" class="mt-1">
                                                <div class="text-red text-caption">
                                                    {{ $t("phone_number_must_be_valid") }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Verification Code -->
                                        <div class="mb-6">
                                            <div class="field-label mb-2 text-center">
                                                <span
                                                    v-if="authSettings.customer_login_with == 'email' || (authSettings.customer_login_with == 'email_phone' && authSettings.customer_otp_with == 'email')">
                                                    {{ $t('enter_your_email_address_verification_code') }}
                                                </span>
                                                <span
                                                    v-else-if="authSettings.customer_login_with == 'phone' || (authSettings.customer_login_with == 'email_phone' && authSettings.customer_otp_with == 'phone')">
                                                    {{ $t('enter_your_phone_number_verification_code') }}
                                                </span>
                                            </div>
                                            <v-otp-input v-model="form.code" length="6" type="number"
                                                hide-details="auto" :disabled="loading" required
                                                class="custom-otp-input"></v-otp-input>
                                        </div>

                                        <!-- Verify Button -->
                                        <v-btn block size="large" elevation="0" type="submit" color="primary"
                                            class="verify-btn text-white mb-3" :loading="loading" :disabled="loading"
                                            @click="verifyAccount">{{ $t('verify') }}</v-btn>

                                        <!-- Resend Code -->
                                        <div class="text-center mb-6">
                                            <span class="text-caption">{{ $t('did_not_receive_code') }}? </span>
                                            <a href="#"
                                                class="text-primary text-decoration-none text-caption resend-link"
                                                @click.prevent="resendCode">
                                                {{ $t('resend_code') }}
                                            </a>
                                        </div>
                                    </v-form>
                                </div>
                            </v-col>
                        </v-row>
                    </div>
                </v-col>
            </v-row> 
        </v-container>
    </div>

</template>

<script>
import { useVuelidate } from "@vuelidate/core";
import { email, required, requiredIf } from "@vuelidate/validators";
import { VueTelInput } from "vue-tel-input";
import { mapActions, mapGetters, mapMutations } from "vuex";

export default {
    data: () => ({
        mobileInputProps: {
            inputOptions: {
                type: "tel",
                placeholder: "phone number",
            },
            dropdownOptions: {
                showDialCodeInSelection: false,
                showFlags: true,
                showDialCodeInList: true,
            },
            autoDefaultCountry: false,
            validCharactersOnly: true,
            mode: "international",
        },
        v$: useVuelidate(),
        form: {
            email: "",
            phone: "",
            code: "",
            invalidPhone: true,
            showInvalidPhone: false,
        },
        loading: false,
        resendLoading: false,
    }),
    components: {
        VueTelInput,
    },
    validations: {
        form: {
            email: {
                requiredIf: requiredIf(function () {
                    return this.authSettings.customer_login_with == 'email' || (this.authSettings.customer_login_with == 'email_phone' && this.authSettings.customer_otp_with == 'email')
                }),
                email
            },
            phone: {
                requiredIf: requiredIf(function () {
                    return this.authSettings.customer_login_with == 'phone' || (this.authSettings.customer_login_with == 'email_phone' && this.authSettings.customer_otp_with == 'phone')
                }),
            },
            code: {
                required,
            },
        }
    },
    computed: {

        ...mapGetters("auth", ["authSettings"]),
        ...mapGetters("app", ["availableCountries", "banners",]),
        getLogoInitials() {
            const appName = this.$store.getters["app/appName"];
            return appName ? appName.substring(0, 2).toUpperCase() : "AV";
        },
    },
    methods: {
        ...mapActions("auth", {
            actionLogin: "login",
        }),
        ...mapMutations('auth', [
            'updateChatWindow',
            'showLoginDialog'
        ]),
        ...mapActions("app", ["fetchProductQuerries"]),
        ...mapActions("wishlist", [
            'fetchWislistProducts'
        ]),
        ...mapActions("cart", [
            "fetchCartProducts",
        ]),
        phoneValidate(phone) {
            this.form.invalidPhone = phone.valid ? false : true;
            if (phone.valid) this.form.showInvalidPhone = false;
        },
        async verifyAccount() {
            const isFormCorrect = await this.v$.$validate();
            if (!isFormCorrect) return;

            if ((this.authSettings.customer_login_with == 'phone' || (this.authSettings.customer_login_with == 'email_phone' && this.authSettings.customer_otp_with == 'phone')) && this.form.invalidPhone) {
                this.form.showInvalidPhone = true;
                return;
            }
            this.form.phone = this.form.phone.replace(/\s/g, "");

            this.loading = true;
            const res = await this.call_api("post", "auth/verify", this.form);
            if (res.data.success) {
                this.actionLogin(res.data);
                this.showLoginDialog(false);
                this.updateChatWindow(false);

                this.fetchWislistProducts();
                this.fetchProductQuerries();
                this.fetchCartProducts();

                this.$router.push(this.$route.query.redirect || { name: "DashBoard" });
            } else {
                this.snack({
                    message: res.data.message,
                    color: "red"
                });
            }
            this.loading = false;
        },
        async resendCode() {
            this.v$.form.email.$touch()
            if (this.v$.form.email.$anyError) {
                return;
            }
            if ((this.authSettings.customer_login_with == 'phone' || (this.authSettings.customer_login_with == 'email_phone' && this.authSettings.customer_otp_with == 'phone')) && this.form.invalidPhone) {
                this.form.showInvalidPhone = true;
                return;
            }
            this.form.phone = this.form.phone.replace(/\s/g, "");

            this.resendLoading = true;
            const res = await this.call_api("post", "auth/resend-code", this.form);

            if (res.data.success) {
                this.snack({
                    message: res.data.message,
                });
            } else {
                this.snack({
                    message: res.data.message,
                    color: "red"
                });
            }
            this.resendLoading = false;
        }
    },
    created() {
        if (this.$route.params.email) {
            this.form.email = this.$route.params.email
        }
        if (this.$route.params.phone) {
            this.form.phone = this.$route.params.phone
        }
    }
}
</script>

<style scoped>
.verification-container {
    min-height: 100vh;
    background: white;
}

.image-section {
    position: relative;
    overflow: hidden;
}

.image-section :deep(img) {
    width: 100%;
    height: 100vh;
    object-fit: cover;
}

.form-section {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: white;
}

.form-wrapper {
    width: 100%;
    max-width: 440px;
    padding: 0 20px;
}

.logo-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-text {
    color: white;
    font-size: 28px;
    font-weight: 700;
}

.welcome-text {
    font-size: 24px;
    font-weight: 600;
    color: #000;
}

.subtitle-text {
    font-size: 14px;
    color: #666;
    margin: 0;
    line-height: 1.5;
}

.field-label {
    font-size: 13px;
    font-weight: 500;
    color: #333;
}

.custom-text-field :deep(.v-field) {
    border-radius: 8px;
}

/* OTP Input Styling */
.custom-otp-input {
    margin: 0 auto;
}

.custom-otp-input :deep(.v-otp-input) {
    gap: 12px;
    justify-content: center;
}

.custom-otp-input :deep(.v-field) {
    border-radius: 8px;
    font-size: 20px;
    font-weight: 600;
    width: 48px;
    height: 56px;
}

.custom-otp-input :deep(.v-field__input) {
    text-align: center;
}

.verify-btn {
    border-radius: 8px;
    text-transform: none;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0;
}

.resend-link {
    cursor: pointer;
    font-weight: 500;
}

.resend-link:hover {
    text-decoration: underline !important;
}

/* Phone input styling */
:deep(.vue-tel-input) {
    border: 1px solid rgba(0, 0, 0, 0.23);
    border-radius: 8px;
}

:deep(.vue-tel-input:focus-within) {
    border-color: rgb(var(--v-theme-primary));
    box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
}

@media (max-width: 1279px) {
    .form-section {
        padding: 60px 20px;
    }
}
</style>