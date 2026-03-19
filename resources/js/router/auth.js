let DeliveryBoyLogin = () => import("../pages/auth/DeliveryBoyLogin.vue");
let Login = () => import("../pages/auth/Login.vue");
let Registration = () => import("../pages/auth/Registration.vue");
let ForgotPassword = () => import("../pages/auth/ForgotPassword.vue");
let NewPassword = () => import("../pages/auth/NewPassword.vue");
let VerifyAccount = () => import("../pages/auth/VerifyAccount.vue");

export default [
    {
        path: "/user/login",
        component: Login,
        name: "Login",
        meta: { requiresAuth: false, hideLayout: true },
    },
    {
        path: "/delivery-boy/login",
        component: DeliveryBoyLogin,
        name: "DeliveryBoyLogin",
        meta: { requiresAuth: false, hideLayout: true },
    },
    {
        path: "/user/registration",
        component: Registration,
        name: "Registration",
        meta: { requiresAuth: false, hideLayout: true },
    },
    {
        path: "/user/forgot-password",
        component: ForgotPassword,
        name: "ForgotPassword",
        meta: { requiresAuth: false, hideLayout: true },
    },
    {
        path: "/user/new-password",
        component: NewPassword,
        name: "NewPassword",
        meta: { requiresAuth: false, hideLayout: true },
    },
    {
        path: "/user/verify-account",
        component: VerifyAccount,
        name: "VerifyAccount",
        meta: { requiresAuth: false, hideLayout: true },
    },

];
