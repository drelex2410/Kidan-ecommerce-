let Layout = () => import("../components/static/Layout.vue");
let Career = () => import("../components/static/Career.vue");
let Tribe = () => import("../components/static/Tribe.vue");
let PartnerShip = () => import("../components/static/PartnerShip.vue");
let Youth = () => import("../components/static/Youth.vue");
let Press = () => import("../components/static/Press.vue");
let Privacy = () => import("../components/static/Privacy.vue");
let Terms = () => import("../components/static/Terms.vue");
let Return = () => import("../components/static/Return.vue");
let Shipping = () => import("../components/static/Shipping.vue");
let CustomPage = () => import("../pages/CustomPage.vue");

export default [
    {
        path: "/about",
        component: CustomPage,
        name: "About",
        meta: { requiresAuth: false, pageSlugOverride: "about-us" },
    },
    {
        path: "/privacy",
        component: Privacy,
        name: "Privacy",
        meta: { requiresAuth: false },
    },
    {
        path: "/terms",
        component: Terms,
        name: "Terms",
        meta: { requiresAuth: false },
    },
    {
        path: "/return",
        component: Return,
        name: "Return",
        meta: { requiresAuth: false },
    },
    {
        path: "/shipping",
        component: Shipping,
        name: "Shipping",
        meta: { requiresAuth: false },
    },
    {
        path: "/faq",
        component: CustomPage,
        name: "FAQ",
        meta: { requiresAuth: false, pageSlugOverride: "faq" },
    },
    {
        path: "/contact-us",
        component: CustomPage,
        name: "ContactUs",
        meta: { requiresAuth: false, pageSlugOverride: "contact-us" },
    },
    {
        path: "/",
        component: Layout,
        children: [
            {
                path: "career",
                component: Career,
                name: "Career",
                meta: { requiresAuth: false },
            },
            {
                path: "tribe",
                component: Tribe,
                name: "Tribe",
                meta: { requiresAuth: false },
            },
            {
                path: "partnership",
                component: PartnerShip,
                name: "PartnerShip",
                meta: { requiresAuth: false },
            },
            {
                path: "youth",
                component: Youth,
                name: "Youth",
                meta: { requiresAuth: false },
            },
            {
                path: "press",
                component: Press,
                name: "Press",
                meta: { requiresAuth: false },
            },
        ]
    },
];
