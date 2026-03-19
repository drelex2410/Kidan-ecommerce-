<template>
  <footer class="kidan-footer">
    <v-container class="main-footer">
      <div class="logo-section">
        <img :src="data.footer_logo" class="footer-logo" />
      </div>
      <v-row class="footer-content d-none d-lg-flex">
        <v-col lg="3">
          <h4 class="column-title">{{ data.footer_link_one?.title }}</h4>
          <ul class="footer-links">
            <li v-for="(link, label, i) in data.footer_link_one?.menu" :key="i">
              <dynamic-link :to="link" class="footer-link">{{ label }}</dynamic-link>
            </li>
          </ul>
        </v-col>
        <v-col lg="3">
          <h4 class="column-title">{{ data.footer_link_two?.title }}</h4>
          <ul class="footer-links">
            <li v-for="(link, label, i) in data.footer_link_two?.menu" :key="i">
              <dynamic-link :to="link" class="footer-link">{{ label }}</dynamic-link>
            </li>
          </ul>
        </v-col>
        <v-col lg="3">
          <h4 class="column-title">{{ $t("terms_policies") || "Terms & Policies" }}</h4>
          <ul class="footer-links">
            <li v-for="(link, label, i) in data.footer_menu" :key="i">
              <dynamic-link :to="link" class="footer-link">{{ label }}</dynamic-link>
            </li>
          </ul>
        </v-col>
        <v-col lg="3">
          <div class="right-section">
            <v-btn class="find-store-btn" href="#" target="_blank">
              {{ $t("find_our_store") || "FIND OUR STORE" }}
            </v-btn>
            <div class="signup-box">
              <h4 class="signup-title">
                {{ $t("sign_up_newsletter") || "SIGN UP SO YOU DON'T MISS ANYTHING" }}
              </h4>
            </div>
            <ul class="social-list">
              <li v-for="(link, label, i) in data.social_link" :key="i">
                <a :href="link" target="_blank" class="social-icon">
                  <i :class="['lab', 'la-' + label]"></i>
                </a>
              </li>
            </ul>
          </div>
        </v-col>
      </v-row>
      <v-row class="footer-content d-lg-none">
        <v-col cols="12">
          <div class="signup-box mobile">
            <h4 class="signup-title">
              {{ $t("sign_up_newsletter") || "SIGN UP SO YOU DON'T MISS ANYTHING" }}
            </h4>
          </div>
          <ul class="social-list mobile">
            <li v-for="(link, label, i) in data.social_link" :key="i">
              <a :href="link" target="_blank" class="social-icon">
                <i :class="['lab', 'la-' + label]"></i>
              </a>
            </li>
          </ul>
        </v-col>
        <v-col cols="12">
          <v-expansion-panels flat accordion class="mobile-panels">
            <v-expansion-panel>
              <v-expansion-panel-title>
                <h4 class="column-title mb-0">{{ data.footer_link_one?.title }}</h4>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <ul class="footer-links">
                  <li v-for="(link, label, i) in data.footer_link_one?.menu" :key="i">
                    <dynamic-link :to="link" class="footer-link">{{ label }}</dynamic-link>
                  </li>
                </ul>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel>
              <v-expansion-panel-title>
                <h4 class="column-title mb-0">{{ data.footer_link_two?.title }}</h4>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <ul class="footer-links">
                  <li v-for="(link, label, i) in data.footer_link_two?.menu" :key="i">
                    <dynamic-link :to="link" class="footer-link">{{ label }}</dynamic-link>
                  </li>
                </ul>
              </v-expansion-panel-text>
            </v-expansion-panel>
            <v-expansion-panel>
              <v-expansion-panel-title>
                <h4 class="column-title mb-0">{{ $t("terms_policies") || "Terms & Policies" }}</h4>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <ul class="footer-links">
                  <li v-for="(link, label, i) in data.footer_menu" :key="i">
                    <dynamic-link :to="link" class="footer-link">{{ label }}</dynamic-link>
                  </li>
                </ul>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-col>
      </v-row>
      <div class="copyright-section">
        <div v-html="data.copyright_text" class="copyright-text"></div>
      </div>
    </v-container>
  </footer>
  <div v-if="getCookieDescription" class="cookie-dialog-container">
    <v-dialog v-model="showCookie" max-width="400" persistent>
      <v-card class="cookie-card">
        <v-card-title class="cookie-title">{{ getCookieTitle }}</v-card-title>
        <v-card-text class="cookie-text" v-html="getCookieDescription"></v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn class="accept-btn" @click="setCookie(true)">Accept Cookies</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
<script>
import { useVuelidate } from "@vuelidate/core";
import { email, required } from "@vuelidate/validators";
import { mapActions, mapGetters } from "vuex";
export default {
  data: () => ({
    loading: true,
    data: {},
    v$: useVuelidate(),
    subscribeForm: { email: "" },
    subscribeFormLoading: false,
    app_store: null,
    play_store: null,
    showCookie: false,
  }),
  validations() {
    return {
      subscribeForm: {
        email: { required, email },
      },
    };
  },
  computed: {
    ...mapGetters("app", ["appName", "generalSettings", "getCookieStatus", "getCookieTitle", "getCookieDescription"]),
    ...mapGetters("auth", ["isAuthenticated"]),
    ...mapGetters("affiliate", ["isAffiliatedUser"]),
  },
  methods: {
    ...mapActions("affiliate", ["fetchAffiliatedUser"]),
    ...mapActions("app", ["setCookie"]),
    async setCookie(status) {
      document.cookie = `${this.appName}-cookie=${this.getCookieDescription}`;
      localStorage.setItem("cookieStatus", status);
      this.showCookie = false;
    },
    async getDetails() {
      const res = await this.call_api("get", `setting/footer`);
      if (res.status === 200) {
        this.data = res.data;
        this.app_store = res.data.mobile_app_links?.app_store;
        this.play_store = res.data.mobile_app_links?.play_store;
        this.loading = false;
      }
    },
    async subscribe() {
      const isValid = await this.v$.$validate();
      if (!isValid) return;
      this.subscribeFormLoading = true;
      const res = await this.call_api("post", "subscribe", this.subscribeForm);
      this.snack({ message: res.data.message });
      this.subscribeFormLoading = false;
    },
  },
  created() {
    if (this.getCookieStatus == null) this.showCookie = true;
    this.getDetails();
    if (this.isAuthenticated) this.fetchAffiliatedUser();
  },
};
</script>
<style scoped>
.kidan-footer {
  background-color: #FFFBF3;
  color: #000000;
  font-family: 'Helvetica Neue', Arial, sans-serif;
  padding: 10px 0 20px;
  height: 428px;
}
.main-footer {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 40px;
  height: 100%;
}
.logo-section {
  margin-bottom: 40px;
}
.footer-logo {
  height: 50px;
  width: auto;
}
.footer-content {
  margin-bottom: 30px;
}
.column-title {
  font-size: 14px;
  font-weight: 600;
  color: #000 !important;
  margin-bottom: 16px;
  letter-spacing: 0;
  text-transform: none;
}
.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}
.footer-links li {
  margin-bottom: 8px;
}
.footer-link {
  color: #666666 !important;
  
}
.footer-link:hover {
  opacity: 0.6;
}
.right-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
  align-items: stretch;
  width: 100%;
}
.find-store-btn {
  background-color: #8b0000 !important;
  color: #fff !important;
  height: 50px !important;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  border-radius: 0 !important;
  box-shadow: none !important;
  width: 100%;
}
.find-store-btn:hover {
  background-color: #a00000 !important;
}
.signup-box {
  border: 1px solid #e0e0e0;
  padding: 30px 20px;
  background: #FFFBF3;
  width: 100%;
}
.signup-box.mobile {
  margin-bottom: 16px;
}
.signup-title {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0;
  color: #000 !important;
  text-align: center;
  line-height: 1.4;
}
.social-list {
  display: flex;
  justify-content: center;
  gap: 12px;
  list-style: none;
  padding: 0;
  margin: 0;
  flex-wrap: wrap;
  width: 100%;
}
.social-list.mobile {
  margin-bottom: 24px;
}
.social-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  background-color: #000;
  color: #fff !important;
  border-radius: 50%;
  font-size: 18px;
  transition: all 0.3s;
  text-decoration: none;
}
.social-icon:hover {
  background-color: #333;
  transform: scale(1.08);
}
.mobile-panels {
  background: transparent;
}
.mobile-panels ::v-deep(.v-expansion-panel) {
  background: transparent;
  box-shadow: none;
  border-bottom: 1px solid #e0e0e0;
}
.mobile-panels ::v-deep(.v-expansion-panel-title) {
  padding: 16px 0;
  min-height: auto;
}
.mobile-panels ::v-deep(.v-expansion-panel-title__overlay) {
  display: none;
}
.mobile-panels ::v-deep(.v-expansion-panel-text__wrapper) {
  padding: 0 0 16px 0;
}
.copyright-section {
  border-top: 1px solid #e0e0e0;
  padding-top: 20px;
  margin-top: 20px;
}
.copyright-text {
  color: #000 !important;
  font-size: 12px;
  line-height: 1.6;
  text-align: left;
}
.cookie-dialog-container {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
}
.cookie-card {
  border: 1px solid #000;
  border-radius: 0;
}
.cookie-title {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 16px 24px;
  background: #000;
  color: #fff !important;
}
.cookie-text {
  font-size: 13px;
  padding: 20px 24px;
  color: #000 !important;
}
.accept-btn {
  background: #000 !important;
  color: #fff !important;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 1px;
  height: 40px !important;
  border-radius: 0;
}
@media (max-width: 960px) {
  .kidan-footer {
    padding: 30px 0 20px;
    height: auto;
  }
  .main-footer {
    padding: 0 20px;
    height: auto;
  }
  .logo-section {
    margin-bottom: 30px;
  }
  .footer-logo {
    height: 40px;
  }
  .column-title {
    font-size: 13px;
  }
  .footer-link {
    font-size: 14px;
  }
  .signup-box {
    padding: 24px 16px;
  }
}
</style>