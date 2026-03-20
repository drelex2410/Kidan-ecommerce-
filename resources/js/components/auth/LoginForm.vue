<template>
    <div class="login-container">
        <v-row no-gutters class="fill-height">
            <!-- Left Side - Image -->
            <v-col cols="12" lg="6" class="image-section d-none d-lg-block">
                <banner :loading="false" :banner="$store.getters['app/banners'].login_page" class="fill-height" />
            </v-col>

            <!-- Right Side - Login Form -->
            <v-col cols="12" lg="6" class="form-section">
                <div class="form-wrapper">
                    <!-- Close Button -->
                    <div class="text-end mb-4">
                        <v-btn icon variant="text" size="small" @click="$router.back()">
                            <v-icon>mdi-close</v-icon>
                            <span class="ml-1 text-caption">Close</span>
                        </v-btn>
                    </div>

                    <!-- Logo -->
                    <div class="text-center mb-8">
                        <div class="logo-circle mx-auto mb-4">
                            <span class="logo-text">{{ getLogoInitials }}</span>
                        </div>
                        <h2 class="welcome-text mb-2">Welcome Back Love !</h2>
                        <p class="subtitle-text">Enter your email to login</p>
                    </div>

                    <!-- Login Form -->
                    <v-form ref="loginForm" @submit.prevent="login()">
                        <!-- Email/Phone Field -->
                        <div v-if="
                            authSettings.customer_login_with == 'email' ||
                            (!showPhoneField && authSettings.customer_login_with == 'email_phone')
                        " class="mb-4">
                            <div class="field-label mb-2">Email Account</div>
                            <v-text-field variant="outlined" v-model="form.email" placeholder="Enter your email"
                                type="text" hide-details="auto" required density="comfortable"
                                class="custom-text-field"></v-text-field>
                            <p v-for="error of v$.form.email.$errors" :key="error.$uid"
                                class="text-red mt-1 text-caption">
                                {{ error.$message }}
                            </p>
                            <div v-if="authSettings.customer_login_with == 'email_phone'" class="text-end mt-1">
                                <span class="text-primary text-caption c-pointer"
                                    @click="showPhoneField = !showPhoneField">{{ $t("use_phone_instead") }}</span>
                            </div>
                        </div>

                        <!-- Phone Field -->
                        <div v-if="
                            authSettings.customer_login_with == 'phone' ||
                            (showPhoneField && authSettings.customer_login_with == 'email_phone')
                        " class="mb-4">
                            <div class="field-label mb-2">
                                {{ $t("phone_number") }}
                            </div>
                            <vue-tel-input v-model="form.phone" v-bind="mobileInputProps"
                                :only-countries="availableCountries" @validate="phoneValidate">
                                <template #arrow-icon><span class="vti__dropdown-arrow">&nbsp;▼</span></template>
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
                            <div v-if="authSettings.customer_login_with == 'email_phone'" class="text-end mt-1">
                                <span class="text-primary text-caption c-pointer"
                                    @click="showPhoneField = !showPhoneField">{{
                                    $t("use_email_instead") }}</span>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="mb-3">
                            <div class="field-label mb-2">Enter Password</div>
                            <v-text-field v-model="form.password" placeholder="* * * * * * * *"
                                :type="passwordShow ? 'text' : 'password'"
                                :append-inner-icon="passwordShow ? 'mdi-eye-off' : 'mdi-eye'" variant="outlined"
                                hide-details="auto" required density="comfortable" class="custom-text-field"
                                @click:append-inner="passwordShow = !passwordShow"></v-text-field>
                            <p v-for="error of v$.form.password.$errors" :key="error.$uid"
                                class="text-red mt-1 text-caption">
                                {{ error.$message }}
                            </p>
                        </div>

                        <!-- Forgot Password Link -->
                        <div class="text-end mb-6">
                            <router-link :to="{ name: 'ForgotPassword' }"
                                class="text-primary text-decoration-none text-caption forgot-link">Forgot
                                Password</router-link>
                        </div>

                        <!-- Login Button -->
                        <v-btn block size="large" elevation="0" type="submit" color="primary"
                            class="login-btn text-white mb-4" :loading="loading" :disabled="loading"
                            @click="login">Login</v-btn>

                        <!-- Divider -->
                        <div class="d-flex align-center mb-4">
                            <v-divider class="flex-grow-1"></v-divider>
                            <span class="px-3 text-caption text-medium-emphasis">Or</span>
                            <v-divider class="flex-grow-1"></v-divider>
                        </div>

                        <!-- Social Login -->
                        <div v-if="
                            generalSettings.social_login.google == 1
                        " class="mb-6">
                            <SocialLogin />
                        </div>

                        <!-- Sign Up Link -->
                        <div class="text-center">
                            <span class="text-caption">{{ $t("dont_have_an_account") }}, </span>
                            <router-link :to="{ name: 'Registration' }"
                                class="text-primary text-decoration-none text-caption">{{ $t("signup")
                                }}</router-link>
                        </div>
                    </v-form>
                </div>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { useVuelidate } from "@vuelidate/core";
import { email, required, requiredIf } from "@vuelidate/validators";
import { VueTelInput } from "vue-tel-input";
import { mapActions, mapGetters, mapMutations } from "vuex";
import SocialLogin from "../auth/SocialLogin.vue";

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
        showPhoneField: false,
        v$: useVuelidate(),
        form: {
            phone: "",
            email: "",
            password: "",
            invalidPhone: true,
            showInvalidPhone: false,
        },
        passwordShow: false,
        loading: false,
    }),
    components: {
        SocialLogin,
        VueTelInput,
    },
    validations: {
        form: {
            email: {
                requiredIf: requiredIf(function () {
                    return (
                        this.authSettings.customer_login_with == "email" ||
                        (this.authSettings.customer_login_with == "email_phone" &&
                            !this.showPhoneField)
                    );
                }),
                email,
            },
            phone: {
                requiredIf: requiredIf(function () {
                    return (
                        this.authSettings.customer_login_with == "phone" ||
                        (this.authSettings.customer_login_with == "email_phone" &&
                            this.showPhoneField)
                    );
                }),
            },
            password: {
                required,
            },
        },
    },
    computed: {
        ...mapGetters("app", [
            "generalSettings",
            "availableCountries",
            "demoMode",
            "banners",
        ]),
        ...mapGetters("cart", ["getTempUserId"]),
        ...mapGetters("auth", ["authSettings", "currentUser"]),
        getLogoInitials() {
            const appName = this.$store.getters["app/appName"];
            return appName ? appName.substring(0, 2).toUpperCase() : "AV";
        },
    },
    methods: {
        ...mapActions("auth", {
            actionLogin: "login",
        }),
        ...mapActions("app", ["fetchProductQuerries"]),
        ...mapActions("wishlist", ["fetchWislistProducts"]),
        ...mapActions("cart", ["fetchCartProducts"]),
        ...mapMutations("cart", ["removeTempUserId"]),
        ...mapMutations("auth", ["updateChatWindow", "showLoginDialog"]),
        phoneValidate(phone) {
            this.form.invalidPhone = phone.valid ? false : true;
            if (phone.valid) this.form.showInvalidPhone = false;
        },
        async login() {
            const isFormCorrect = await this.v$.$validate();
            if (!isFormCorrect) return;
            if (
                (this.authSettings.customer_login_with == "phone" ||
                    (this.authSettings.customer_login_with == "email_phone" &&
                        this.showPhoneField)) &&
                this.form.invalidPhone
            ) {
                this.form.showInvalidPhone = true;
                return;
            }
            if (this.getTempUserId) {
                this.form.temp_user_id = this.getTempUserId;
            }
            this.form.phone = this.form.phone.replace(/\s/g, "");
            this.form.form_type = "customer";
            this.loading = true;
            const res = await this.call_api("post", "auth/login", this.form);
            if (res.data.success) {
                if (
                    res.data.verified == true ||
                    this.authSettings.customer_otp_with == "disabled"
                ) {
                    if (this.getTempUserId) {
                        this.removeTempUserId();
                    }
                    this.actionLogin(res.data);
                    this.showLoginDialog(false);
                    this.updateChatWindow(false);
                    this.fetchWislistProducts();
                    this.fetchProductQuerries();
                    this.fetchCartProducts();
                    this.$router.push(this.$route.query.redirect || { name: "DashBoard" });
                } else {
                    if (
                        this.authSettings.customer_login_with == "email" ||
                        (this.authSettings.customer_login_with == "email_phone" &&
                            this.authSettings.customer_otp_with == "email")
                    ) {
                        this.$router.push({
                            name: "VerifyAccount",
                            params: { email: this.form.email },
                        });
                    } else {
                        this.$router.push({
                            name: "VerifyAccount",
                            params: { phone: this.form.phone },
                        });
                    }
                }
                this.snack({
                    message: res.data.message,
                });
            } else {
                this.snack({
                    message: res.data.message,
                    color: "red",
                });
            }
            this.loading = false;
        },
    },
    created() {
        if (this.demoMode) {
            this.form.email = "customer@example.com";
            this.form.password = "123456";
        }
    },
};
</script>

<style scoped>
.login-container {
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
}

.field-label {
    font-size: 13px;
    font-weight: 500;
    color: #333;
}

.custom-text-field :deep(.v-field) {
    border-radius: 8px;
}

.login-btn {
    background: #17120d !important;
    color: #fbf8f1 !important;
    border-radius: 8px;
    text-transform: none;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0;
}

.forgot-link:hover {
    text-decoration: underline !important;
}

.c-pointer {
    cursor: pointer;
}

.c-pointer:hover {
    text-decoration: underline;
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
