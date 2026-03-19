let Home = () => import("../pages/Home.vue");
let ProductDetails = () => import("../pages/ProductDetails.vue");
let ProductListing = () => import("../pages/ProductListing.vue");
let TodayDealListing = () => import("../pages/TodayDealListing.vue");
let TrackOrder = () => import("../pages/TrackOrder.vue");
let NewTrackOrder = () => import("../pages/NewTrackOrder.vue");
let AllCategories = () => import("../pages/AllCategories.vue");
let AllBrands = () => import("../pages/AllBrands.vue");
let AllOffers = () => import("../pages/AllOffers.vue");
let OfferDetails = () => import("../pages/OfferDetails.vue");
let AllBlogs = () => import("../pages/AllBlogs.vue");
let Cart = () => import("../../js/components/cart/CartPage.vue");
let SearchOverLay =()=> import("../components/new-design-header2/SearchOverlay.vue")

export default [
    {
        path: "/",
        component: Home,
        name: "Home",
        meta: { requiresAuth: false },
    },
    {
        path: '/cart',
        component: Cart,
        name: 'Cart',
        meta: { requiresAuth: false }
    },
    {
        path: "/all-categories",
        component: AllCategories,
        name: "AllCategories",
        meta: { requiresAuth: false },
    },
    {
        path: "/all-brands",
        component: AllBrands,
        name: "AllBrands",
        meta: { requiresAuth: false },
    },
    {
        path: "/all-offers",
        component: AllOffers,
        name: "AllOffers",
        meta: { requiresAuth: false },
    },
    {
        path: "/offer/:offerSlug?",
        component: OfferDetails,
        name: "OfferDetails",
        meta: { requiresAuth: false },
    },
    {
        path: "/search",
        component: ProductListing,
        name: "Shop",
        meta: { requiresAuth: false },
    },
    {
        path: "/todays-deal",
        component: TodayDealListing,
        name: "TodayDeal",
        meta: { requiresAuth: false },
    },
    {
        path: "/category/:categorySlug?",
        component: ProductListing,
        name: "Category",
        meta: { requiresAuth: false },
    },
    {
        path: "/brand/:brandId?",
        component: ProductListing,
        name: "Brand",
        meta: { requiresAuth: false },
    },
    {
        path: "/search/:keyword?",
        component: SearchOverLay,
        name: "Search",
        meta: { requiresAuth: false },
    },
    {
        path: "/product/:slug",
        component: ProductDetails,
        name: "ProductDetails",
        meta: { requiresAuth: false },
    },
    {
        path: "/track-order",
        component: NewTrackOrder,
        name: "TrackOrder",
        meta: { requiresAuth: true },
    },
    {
        path: "/all-blogs",
        component: AllBlogs,
        name: "AllBlogs",
        meta: { requiresAuth: false },
    },
    {
        path: "/journal",
        component: AllBlogs,
        name: "Journal",
        meta: { requiresAuth: false },
    },
    {
        path: "/journal/category/:categorySlug?",
        component: AllBlogs,
        name: "JournalFilter",
        meta: { requiresAuth: false },
    },
    {
        path: "/journal/search/:searchKeyword?",
        component: AllBlogs,
        name: "JournalSearch",
        meta: { requiresAuth: false },
    },
    {
        path: "/all-blogs/category/:categorySlug?",
        component: AllBlogs,
        name: "AllBlogsFilter",
        meta: { requiresAuth: false },
    },
    {
        path: "/all-blogs/search/:searchKeyword?",
        component: AllBlogs,
        name: "SearchBlogs",
        meta: { requiresAuth: false },
    },
    {
        path: "/blog-details/:slug",
        component: AllBlogs,
        name: "BlogDetails",
        meta: { requiresAuth: false },
    },
];
