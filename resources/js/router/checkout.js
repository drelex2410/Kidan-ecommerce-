let Checkout = () => import("../pages/Checkout.vue");
let NewCheckout = () => import("../pages/NewCheckout.vue");
let OrderConfirmed = () => import("../pages/OrderConfirmed.vue");

export default [
    {
        path: "/checkout",
        component: NewCheckout,
        name: "Checkout",
        meta: { requiresAuth: true },
    },
    {
        path: "/order-confirmed",
        component: OrderConfirmed,
        name: "OrderConfirmed",
        meta: { requiresAuth: true },
    },
];
