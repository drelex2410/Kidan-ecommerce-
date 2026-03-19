import VueNumberInput from "@chenfengyuan/vue-number-input";
import { createApp } from "vue";
import VueSocialSharing from "vue-social-sharing";
import VueTelInput from "vue-tel-input";
import { VueHeadMixin, createHead } from "@unhead/vue";

import "vue-tel-input/vue-tel-input.css";
import "../sass/app.scss";
import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";
import "line-awesome/dist/line-awesome/css/line-awesome.css";

import "./bootstrap";

import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";

import { i18n } from "./plugins/i18n";

const bootstrapDefaults = {
    appName: "Storefront",
    appMetaTitle: "Storefront",
    appMetaDescription: "",
    appLogo: null,
    appUrl: window.location.origin + "/",
    cacheVersion: "1",
    demoMode: false,
    appLanguage: "en",
    allLanguages: [{ name: "English", code: "en", flag: "en", rtl: 0 }],
    allCurrencies: [],
    availableCountries: [],
    paymentMethods: [],
    offlinePaymentMethods: [],
    general_settings: {
        product_comparison: 0,
        wallet_system: 0,
        club_point: 0,
        club_point_convert_rate: null,
        conversation_system: 0,
        sticky_header: 0,
        affiliate_system: 0,
        delivery_boy: 0,
        support_chat: false,
        pickup_point: false,
        chat: {
            customer_chat_logo: null,
            customer_chat_name: null,
        },
        social_login: {
            google: 0,
            facebook: 0,
            twitter: 0,
        },
        currency: {
            code: "$",
            decimal_separator: "1",
            symbol_format: "1",
            no_of_decimals: "2",
            truncate_price: "0",
        },
    },
    addons: [],
    banners: {},
    refundSettings: {
        refund_request_time_period: 0,
        refund_request_order_status: [],
        refund_reason_types: [],
    },
    shop_registration_message: {
        shop_registration_message_title: "",
        shop_registration_message_content: "",
    },
    cookie_message: {
        cookie_title: "",
        cookie_description: "",
    },
    authSettings: {
        customer_login_with: "email",
        customer_otp_with: "disabled",
    },
    primaryColor: "#000000",
};

function mergeBootstrap(payload = {}) {
    return {
        ...bootstrapDefaults,
        ...payload,
        general_settings: {
            ...bootstrapDefaults.general_settings,
            ...(payload.general_settings || {}),
            chat: {
                ...bootstrapDefaults.general_settings.chat,
                ...(payload.general_settings?.chat || {}),
            },
            social_login: {
                ...bootstrapDefaults.general_settings.social_login,
                ...(payload.general_settings?.social_login || {}),
            },
            currency: {
                ...bootstrapDefaults.general_settings.currency,
                ...(payload.general_settings?.currency || {}),
            },
        },
        refundSettings: {
            ...bootstrapDefaults.refundSettings,
            ...(payload.refundSettings || {}),
        },
        shop_registration_message: {
            ...bootstrapDefaults.shop_registration_message,
            ...(payload.shop_registration_message || {}),
        },
        cookie_message: {
            ...bootstrapDefaults.cookie_message,
            ...(payload.cookie_message || {}),
        },
        authSettings: {
            ...bootstrapDefaults.authSettings,
            ...(payload.authSettings || {}),
        },
    };
}

async function hydrateBootstrap() {
    window.shopSetting = mergeBootstrap(window.shopSetting);

    try {
        const response = await window.axios.get("/api/v1/bootstrap", {
            headers: {
                Accept: "application/json",
            },
        });

        if (response?.data?.success && response.data.data) {
            window.shopSetting = mergeBootstrap(response.data.data);
        }
    } catch (error) {
        console.warn("Bootstrap hydration failed, using local defaults.", error);
    }

    return window.shopSetting;
}

async function mountStorefront() {
    const shopSetting = await hydrateBootstrap();

    const [
        { default: init },
        { default: plugins },
        { default: router },
        { default: store },
        { default: Mixin },
        { default: App },
        { default: Banner },
        { default: DynamicLink },
        { default: ProductBox },
        { default: HelperClass },
    ] = await Promise.all([
        import("./plugins/init"),
        import("./plugins/plugins"),
        import("./router/router.js"),
        import("./store/store"),
        import("./utils/mixin"),
        import("./components/App.vue"),
        import("./components/inc/Banner.vue"),
        import("./components/inc/DynamicLink.vue"),
        import("./components/product/ProductBox.vue"),
        import("./utils/helpers"),
    ]);

    const app = createApp(App);
    const customDarkTheme = {
        colors: {
            primary: shopSetting?.primaryColor || "#000",
        },
    };

    const shopSelectedLanguage =
        localStorage.getItem("shopSelectedLanguage") || shopSetting.appLanguage || "en";

    const vuetify = createVuetify({
        components,
        directives,
        theme: {
            defaultTheme: "customDarkTheme",
            themes: { customDarkTheme },
        },
        locale: {
            locale: shopSelectedLanguage,
            fallback: shopSelectedLanguage,
        },
    });

    const head = createHead();

    app.component("dynamic-link", DynamicLink);
    app.component("banner", Banner);
    app.component("product-box", ProductBox);
    app.component("vue-number-input", VueNumberInput);

    init(store, router);
    plugins(app);

    app.mixin(VueHeadMixin);
    app.use(head);
    app.use(vuetify);
    app.use(VueSocialSharing);
    app.use(VueTelInput, { mode: "auto" });
    app.use(store);
    app.use(router);
    app.use(i18n);
    app.mixin(Mixin);
    app.provide("HelperClass", HelperClass);

    router.isReady().then(() => {
        app.mount("#app");
    });
}

mountStorefront();
