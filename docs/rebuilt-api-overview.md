# Rebuilt API Overview

## SPA Bootstrap

- `GET /api/v1/bootstrap`
- `GET /api/v1/locale/{lang}`

## Auth

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/signup`
- `POST /api/v1/auth/verify`
- `POST /api/v1/auth/resend-code`
- `POST /api/v1/auth/password/create`
- `POST /api/v1/auth/password/reset`
- `POST /api/v1/auth/logout`
- `GET /api/v1/user/info`

## Catalog

- `GET /api/v1/product/search`
- `GET /api/v1/product/details/{slug}`
- `GET /api/v1/product/related/{id}`
- `GET /api/v1/product/bought-together/{id}`
- `GET /api/v1/product/random/{limit}/{id?}`
- `GET /api/v1/product/latest/{limit}`
- `GET /api/v1/all-categories`
- `GET /api/v1/categories/first-level`
- `GET /api/v1/all-brands`
- `GET /api/v1/all-offers`
- `GET /api/v1/offer/{slug}`
- `GET /api/v1/search.ajax/{keyword}`

## Checkout

- `POST /api/v1/carts`
- `POST /api/v1/carts/add`
- `POST /api/v1/carts/change-quantity`
- `POST /api/v1/carts/destroy`
- `POST /api/v1/checkout/coupon/apply`
- `GET /api/v1/checkout/get-shipping-cost/{addressId}`
- `POST /api/v1/checkout/order/store`
- `POST /api/v1/temp-id-cart-update`

## Content

- `GET /api/v1/page/{slug}`
- `GET /api/v1/setting/header`
- `GET /api/v1/setting/footer`
- `GET /api/v1/setting/home/{section}`
- `GET /api/v1/all-blog-categories`
- `GET /api/v1/all-blogs/search`
- `GET /api/v1/recent-blogs`
- `GET /api/v1/blog/details/{slug}`

## Account

- `GET /api/v1/user/dashboard`
- `GET /api/v1/user/notification`
- `GET /api/v1/user/all-notification`
- `POST /api/v1/user/info/update`
- `GET /api/v1/user/coupons`
- `GET /api/v1/user/orders`
- `GET /api/v1/user/order/{orderCode}`
- `GET /api/v1/user/order/cancel/{orderId}`
- `GET /api/v1/order/invoice-download/{orderId}`
- `GET /api/v1/user/orders/downloads`
- `GET/POST/DELETE /api/v1/user/wishlists*`
- `GET/POST/DELETE /api/v1/user/follow*`
- `GET /api/v1/user/addresses`
- `POST /api/v1/user/address/create`
- `POST /api/v1/user/address/update`
- `GET /api/v1/user/address/delete/{id}`
- `GET /api/v1/user/address/default-shipping/{id}`
- `GET /api/v1/user/address/default-billing/{id}`

## Benefits

- refund endpoints under `/api/v1/user/refund-*`
- wallet, club point, and affiliate user flows under `/api/v1/user/*`

## Shops / Delivery

- `POST /api/v1/shop/register`
- `GET /api/v1/all-shops`
- `GET /api/v1/shop/{slug}`
- `GET /api/v1/shop/{slug}/home`
- `GET /api/v1/shop/{slug}/coupons`
- `GET /api/v1/shop/{slug}/products`
- delivery-boy endpoints under `/api/v1/delivery-boy/*`

## Payments

- `POST /api/v1/payment/{gateway}/pay`
- `POST /payment/{gateway}/pay`
- provider callback and return routes remain under `/payment/*`

## Auth Expectations

- bearer-token API auth
- token stored by the frontend as `shopAccessToken`
- `GET /api/v1/user/info` is the authoritative hydration endpoint

## Payment Expectations

- checkout returns payment continuation metadata like `go_to_payment`, `payment_method`, `order_code`, and `grand_total`
- online payment handoff remains browser-form based
- callbacks reconcile through persisted `payments` and `payment_transactions`
- duplicate payment success callbacks are idempotent
