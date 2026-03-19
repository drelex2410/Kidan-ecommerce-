<template>
  <div>
    <v-container>
      <v-row>
        <v-col xl="10" class="mx-auto my-5 my-lg-16">
          <div class="registration-container">
            <v-row no-gutters class="fill-height">
              <v-col cols="12" lg="6" class="image-section d-none d-lg-block">
                <banner :loading="false" :banner="$store.getters['app/banners'].registration_page"
                  class="fill-height" />
              </v-col>

              <v-col cols="12" lg="6" class="form-section">
                <div class="form-wrapper">
                  <div class="text-end mb-4">
                    <v-btn icon variant="text" size="small" @click="$router.back()">
                      <v-icon>mdi-close</v-icon>
                      <span class="ml-1 text-caption">Close</span>
                    </v-btn>
                  </div>

                  <div class="text-center mb-8">
                    <div class="logo-circle mx-auto mb-4">
                      <span class="logo-text">{{ getLogoInitials }}</span>
                    </div>
                    <h2 class="welcome-text mb-2">Hello Beautiful Person !</h2>
                    <p class="subtitle-text">Enter your email to create account</p>
                  </div>

                  <v-form ref="loginForm" @submit.prevent="register()">
                    <div class="mb-4">
                      <div class="field-label mb-2">Name</div>
                      <v-text-field variant="outlined" v-model="form.name" placeholder="Enter your name" type="text"
                        hide-details="auto" required density="comfortable" class="custom-text-field"
                        @blur="v$.form.name.$touch()"></v-text-field>
                      <p v-for="error of v$.form.name.$errors" :key="error.$uid" class="text-red mt-1 text-caption">
                        {{ error.$message }}
                      </p>
                    </div>

                    <div v-if="
                      authSettings.customer_login_with == 'email' ||
                      authSettings.customer_login_with == 'email_phone'
                    " class="mb-4">
                      <div class="field-label mb-2">Email Account</div>
                      <v-text-field variant="outlined" v-model="form.email" placeholder="Enter your email" type="email"
                        hide-details="auto" required density="comfortable" class="custom-text-field"
                        @blur="v$.form.email.$touch()"></v-text-field>
                      <p v-for="error of v$.form.email.$errors" :key="error.$uid" class="text-red mt-1 text-caption">
                        {{ error.$message }}
                      </p>
                    </div>

                    <div v-if="
                      authSettings.customer_login_with == 'phone' ||
                      authSettings.customer_login_with == 'email_phone'
                    " class="mb-4">
                      <div class="field-label mb-2">
                        {{ $t("phone_number") }}
                      </div>
                      <vue-tel-input v-model="form.phone" v-bind="mobileInputProps" :only-countries="availableCountries"
                        @validate="phoneValidate">
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
                    </div>

                    <div class="mb-3">
                      <div class="field-label mb-2">Create Password</div>
                      <v-text-field v-model="form.password" placeholder="* * * * * * * *"
                        :type="passwordShow ? 'text' : 'password'"
                        :append-inner-icon="passwordShow ? 'mdi-eye-off' : 'mdi-eye'" variant="outlined"
                        hide-details="auto" required density="comfortable" class="custom-text-field"
                        @blur="v$.form.password.$touch()"
                        @click:append-inner="passwordShow = !passwordShow"></v-text-field>
                      <p v-for="error of v$.form.password.$errors" :key="error.$uid" class="text-red mt-1 text-caption">
                        {{ error.$message }}
                      </p>
                    </div>

                    <div class="text-end mb-6">
                      <span class="text-caption">{{ $t("by_signing_up_you_agree_to_our") }} </span>
                      <router-link :to="{ name: 'CustomPage', params: { pageSlug: 'terms-and-conditions' } }"
                        class="text-primary text-decoration-none text-caption">{{ $t("terms_and_conditions")
                        }}</router-link>
                    </div>

                    <v-btn block size="large" elevation="0" type="submit" color="primary"
                      class="registration-btn text-white mb-4" :loading="loading" :disabled="loading"
                      >Create Account</v-btn>

                    <div class="d-flex align-center mb-4">
                      <v-divider class="flex-grow-1"></v-divider>
                      <span class="px-3 text-caption text-medium-emphasis">Or</span>
                      <v-divider class="flex-grow-1"></v-divider>
                    </div>

                    <div v-if="
                      generalSettings.social_login.google == 1
                    " class="mb-6">
                      <SocialLogin />
                    </div>

                    <div class="text-center">
                      <span class="text-caption">{{ $t("already_have_an_account") }}, </span>
                      <router-link :to="{ name: 'Login' }" class="text-primary text-decoration-none text-caption">{{
                        $t("login")
                        }}</router-link>
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
import {
  email,
  minLength,
  required,
  requiredIf,
} from "@vuelidate/validators";
import { VueTelInput } from "vue-tel-input";
import { mapActions, mapGetters, mapMutations } from "vuex";
import SocialLogin from "../../components/auth/SocialLogin.vue";

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
      name: "",
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
      name: { required },
      email: {
        requiredIf: requiredIf(function () {
          return (
            this.authSettings.customer_login_with == "email" ||
            this.authSettings.customer_login_with == "email_phone"
          );
        }),
        email,
      },
      phone: {
        requiredIf: requiredIf(function () {
          return (
            this.authSettings.customer_login_with == "phone" ||
            this.authSettings.customer_login_with == "email_phone"
          );
        }),
      },
      password: { required, minLength: minLength(6) },
    },
  },
  computed: {
    ...mapGetters("app", ["generalSettings", "availableCountries"]),
    ...mapGetters("auth", ["authSettings"]),
    ...mapGetters("cart", ["getTempUserId"]),
    getLogoInitials() {
      const appName = this.$store.getters["app/appName"];
      return appName ? appName.substring(0, 2).toUpperCase() : "AV";
    },
  },
  methods: {
    ...mapActions("auth", ["login"]),
    ...mapMutations("cart", ["removeTempUserId"]),
    ...mapMutations("auth", ["updateChatWindow", "showLoginDialog"]),
    phoneValidate(phone) {
      this.form.invalidPhone = phone.valid ? false : true;
      if (phone.valid) this.form.showInvalidPhone = false;
    },

    async register() {
      const isFormCorrect = await this.v$.$validate();
      if (!isFormCorrect) return;

      if (
        (this.authSettings.customer_login_with == "phone" ||
          this.authSettings.customer_login_with == "email_phone") &&
        this.form.invalidPhone
      ) {
        this.form.showInvalidPhone = true;
        return;
      }

      this.form.phone = this.form.phone.replace(/\s/g, "");
      if (this.getTempUserId) {
        this.form.temp_user_id = this.getTempUserId;
      }
      this.loading = true;
      const res = await this.call_api("post", "auth/signup", this.form);
      if (res.data.success) {
        if (this.getTempUserId) {
          this.removeTempUserId();
        }
        if (this.authSettings.customer_otp_with == "disabled") {
          this.login(res.data);
          this.showLoginDialog(false);
          this.updateChatWindow(false);
          this.$router.push(
            this.$route.query.redirect || { name: "DashBoard" }
          );
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

          this.snack({
            message: res.data.message,
          });
        }
      } else {
        this.snack({
          message: res.data.message,
          color: "red",
        });
      }
      this.loading = false;
    },
    async registrationReferralCode(referralCode) {
      const res = await this.call_api(
        "post",
        "affiliate/registration-refferal-code",
        { referralCode: referralCode }
      );
    },
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    const referralCode = urlParams.get("referral_code");
    if (referralCode != null) {
      this.registrationReferralCode(referralCode);
    }
  },
};
</script>

<style scoped>
.registration-container {
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

.registration-btn {
  border-radius: 8px;
  text-transform: none;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0;
}

.google-btn {
  border-radius: 8px;
  font-weight: 500;
}

.c-pointer {
  cursor: pointer;
}

.c-pointer:hover {
  text-decoration: underline;
}

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