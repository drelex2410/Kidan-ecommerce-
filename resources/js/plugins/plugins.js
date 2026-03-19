// resources/js/plugins/plugins.js
import helpers from "../utils/helpers";

export default function (app) {
    // Make helpers available globally
    app.config.globalProperties.$helpers = helpers;

    // Global image fallback directive/method
    app.config.globalProperties.imageFallback = function (e, size = "square") {
        e.target.src = helpers.imagePlaceholder(size);
    };

    // Optional: also expose as app.helpers (some old code might use it)
    app.helpers = helpers;
}