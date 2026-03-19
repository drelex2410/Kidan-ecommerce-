# Frontend Compatibility Checklist

| Frontend Contract | Endpoint / Surface | Status | Notes |
| --- | --- | --- | --- |
| SPA bootstrap without `window.shopSetting` | `GET /api/v1/bootstrap` | Implemented | Phase 1 replacement for Blade-injected runtime config |
| Minimal SPA shell | Web catch-all shell | Implemented | Shell no longer exports runtime globals |
| Locale hydration | `GET /api/v1/locale/{lang}` | Implemented | Graceful fallback for inactive locales |
| Auth login | `POST /api/v1/auth/login` | Implemented | Sanctum bearer token flow with legacy-compatible top-level keys and contract-compliant `data` payload |
| Auth signup | `POST /api/v1/auth/signup` | Implemented | Issues token immediately only when verification is disabled |
| Auth verify | `POST /api/v1/auth/verify` | Implemented | OTP/code verification issues bearer token on success |
| Auth resend code | `POST /api/v1/auth/resend-code` | Implemented | Throttled public endpoint; preserves frontend route name |
| Password reset create | `POST /api/v1/auth/password/create` | Implemented | Persists reset codes through `auth_codes`; returns explicit channel flags |
| Password reset submit | `POST /api/v1/auth/password/reset` | Implemented | Resets password and invalidates existing bearer tokens |
| Logout | `POST /api/v1/auth/logout` | Implemented | Revokes current token only; temporary `GET` alias kept for current frontend continuity |
| Current user hydration | `GET /api/v1/user/info` | Implemented | Returns user, followed shops, permissions, profile state, and nested hydration payload |
| Product listing payload richness | `GET /api/v1/product/search` | Implemented | Returns products, filter attributes, brands, category context, totals, and SEO metadata in one payload |
| Product details payload richness | `GET /api/v1/product/details/{slug}` | Implemented | Returns variant-aware product data, variation options, limits, pricing, and review summary |
| Related products | `GET /api/v1/product/related/{id}` | Implemented | Public support endpoint kept for product page parity |
| Bought together products | `GET /api/v1/product/bought-together/{id}` | Implemented | Public support endpoint kept for product page parity |
| Random products | `GET /api/v1/product/random/{limit}/{id?}` | Implemented | Public support endpoint kept for product page parity |
| Latest products | `GET /api/v1/product/latest/{limit}` | Implemented | Public support endpoint for homepage/404 recommendations |
| All categories | `GET /api/v1/all-categories` | Implemented | Returns root categories plus flattened child category payloads for browse surfaces |
| First-level categories | `GET /api/v1/categories/first-level` | Implemented | Public category filter source for browse/shop views |
| All brands | `GET /api/v1/all-brands` | Implemented | Public brand browse/filter source |
| All offers | `GET /api/v1/all-offers` | Implemented | Public active-offer listing |
| Offer details | `GET /api/v1/offer/{slug}` | Implemented | Returns active offer plus included product cards |
| Ajax search suggestions | `GET /api/v1/search.ajax/{keyword}` | Implemented | Preserves keyword/category/brand/product/shop suggestion shape for existing header search |
| Cart guest/auth flow | `POST /api/v1/carts*` | Implemented | Phase 4 rebuild supports guest carts by `temp_user_id`, authenticated carts by Sanctum bearer token, and preserves the existing cart item/shop payload shape |
| Cart mutation endpoints | `POST /api/v1/carts/add`, `POST /api/v1/carts/change-quantity`, `POST /api/v1/carts/destroy` | Implemented | Variation-aware add/change/remove with quantity and stock validation plus stable summary totals |
| Temp cart merge | `POST /api/v1/temp-id-cart-update` | Implemented | Requires bearer auth and merges guest cart lines into the authenticated cart idempotently by variation |
| Coupon apply | `POST /api/v1/checkout/coupon/apply` | Implemented | Returns coupon details, explicit failure states, discount amount, and recalculated totals |
| Shipping quote | `GET /api/v1/checkout/get-shipping-cost/{addressId}` | Implemented | Zone-based shipping quote validated against the authenticated user’s address ownership |
| Order placement | `POST /api/v1/checkout/order/store` | Implemented | Creates combined/shop orders transactionally, snapshots pricing, clears selected cart lines, and returns payment-handoff metadata |
| CMS page by slug | `GET /api/v1/page/{slug}` | Implemented | Returns published page-builder payload with section data and explicit 404s for missing slugs |
| Header settings | `GET /api/v1/setting/header` | Implemented | Preserves direct component-ready header payload keys without wrapping under `data` |
| Footer settings | `GET /api/v1/setting/footer` | Implemented | Preserves direct component-ready footer payload keys without wrapping under `data` |
| Home content sections | `GET /api/v1/setting/home/{section}` | Implemented | Supports the active frontend’s called section set: sliders, popular categories, home-about text, product sections, banner sections, shop sections, and shop banner sections |
| Journal/blog categories | `GET /api/v1/all-blog-categories` | Implemented | Returns category list plus `recentBlogs` for homepage/journal bootstrap |
| Journal/blog feed | `GET /api/v1/all-blogs/search` | Implemented | Preserves paginated `blogs` payload plus richer `journal`, `currentCategory`, `currentPage`, `totalPage`, and `total` keys |
| Recent blogs | `GET /api/v1/recent-blogs` | Implemented | Returns recent published posts under the existing `blogs` collection key |
| Journal/blog details | `GET /api/v1/blog/details/{slug}` | Implemented | Returns blog detail, related products, video cards, recent posts, and explicit 404s for missing slugs |
| User dashboard summary | `GET /api/v1/user/dashboard` | Implemented | Returns `last_recharge`, `total_order_products`, and `recent_purchased_products.data` for the dashboard cards/widgets |
| User notifications menu | `GET /api/v1/user/notification` | Implemented | Preserves unread notification list under `notifications` plus paginated history under `data` |
| User notifications archive | `GET /api/v1/user/all-notification` | Implemented | Marks unread notifications as read and preserves the nested `data.data` pagination contract used by the SPA |
| Profile update | `POST /api/v1/user/info/update` | Implemented | Updates current user profile/password with a contract-compatible `user` payload for Vuex hydration |
| User coupons surface | `GET /api/v1/user/coupons` | Implemented | Preserves the existing nested `data.data` coupon list payload |
| Orders list | `GET /api/v1/user/orders` | Implemented | Returns only the authenticated user’s combined orders with `orders[].products.data` nested packages and pagination metadata |
| Order detail | `GET /api/v1/user/order/{orderCode}` | Implemented | Returns the combined-order detail payload used by order detail, order confirmation, and tracking views |
| Order cancel | `GET /api/v1/user/order/cancel/{orderId}` | Implemented | Keeps the legacy GET route for current SPA compatibility while enforcing ownership and cancellable-state checks |
| Invoice download handoff | `GET /api/v1/order/invoice-download/{orderId}` | Implemented | Requires bearer auth, enforces ownership, and returns `invoice_url` plus `invoice_name` |
| Digital product downloads | `GET /api/v1/user/orders/downloads` | Implemented | Returns the paginated downloadable-product payload used by the downloads page |
| Wishlist surface | `GET/POST/DELETE /api/v1/user/wishlists*` | Implemented | Supports list/add/remove, avoids duplicate rows, and preserves the product-card payload expected by Vuex |
| Followed shops surface | `GET/POST/DELETE /api/v1/user/follow*` | Implemented | Supports list/follow/unfollow and stays aligned with the followed-shop IDs returned by `user/info` |
| Address book surface | `GET /api/v1/user/addresses` and `POST/GET /api/v1/user/address/*` | Implemented | Preserves legacy create/update/delete/default-shipping/default-billing paths while enforcing ownership and checkout-compatible defaults |
| Refund flows | `GET/POST /api/v1/user/refund-*` | Implemented | `GET user/refund-requests`, `GET user/refund-request/create/{orderId}`, `POST user/refund-request/store` rebuilt in V1 benefits layer. Eligibility preserves paid/delivered/window rules; ownership failures return explicit contract messages. |
| Wallet / club points / affiliate | `GET/POST /api/v1/user/*` affiliate/wallet/earning | Implemented | `GET user/wallet/history`, `GET user/earning/history`, `POST user/convert-point-into-wallet`, and the active affiliate endpoints are rebuilt in V1 benefits. Club point conversion preserves the legacy raw `3` unpaid-order signal used by the SPA. Wallet recharge continues to hand off through `payment/{gateway}/pay`; the stale `user/wallet/recharge` route now returns an explicit non-success response instead of silently breaking. |
| Shop public endpoints | `GET /api/v1/shop/*`, `GET /api/v1/all-shops`, `POST /api/v1/shop/register` | Implemented | Rebuild covers `all-shops`, `shop/{slug}`, `shop/{slug}/home`, `shop/{slug}/coupons`, `shop/{slug}/products`, and the active SPA `shop/register` flow with storefront-compatible payloads and visibility rules (`published`, `approval`, `verification_status`) |
| Delivery endpoints | `GET/POST /api/v1/delivery-boy/*` | Implemented | Rebuild covers dashboard, assigned/pending/picked-up/on-the-way/completed/cancelled lists, collections/earnings ledgers, cancel request, and status transitions with bearer-token auth and delivery-boy role enforcement |
| Payment initialize | `POST /api/v1/payment/{gateway}/pay` | Implemented | Phase 9 rebuild now validates ownership/payable state, returns explicit JSON for API callers, and keeps online handoff metadata aligned with the existing SPA contract |
| Web payment handoff | `POST /payment/{gateway}/pay` | Implemented | Phase 9 rebuild preserves the browser-form gateway handoff used by checkout, wallet recharge, and re-payment dialogs while routing through a new payment initialization service |
| Payment callbacks | Web callback routes | Implemented | Existing gateway callback/return paths now reconcile through an idempotent Phase 9 callback service; Stripe/PayPal/Paystack/Flutterwave/etc. continue to use their legacy public route names |

## Final Phase 10 Audit

### Fully covered by rebuilt paths

- SPA bootstrap and locale
- Auth and current-user hydration
- Catalog, product detail, and browse surfaces
- Checkout, order placement, and payment continuation contract
- CMS pages, home sections, and journal/blog
- Account area, orders, invoices, downloads, wishlist, follows, addresses, notifications
- Refunds, wallet history, club points, affiliate user flows
- Public shops and delivery-boy surfaces
- Payment initialization, callbacks, and idempotent reconciliation

### Intentionally legacy-bridged

- `GET /api/v1/auth/logout` remains as a temporary alias while the SPA still calls it in a few places
- order cancel and several address actions still preserve legacy GET path shapes because the SPA depends on them directly
- public payment callback route names remain legacy because external gateways and the current frontend handoff already depend on those exact paths
- provider-specific payment controllers still exist as gateway bridges, but shared initialization and reconciliation now go through the rebuilt Phase 9 services

### Unused or not rebuilt because not required by the active SPA

- seller package payment flows
- customer package payment flows
- broad legacy admin surfaces
- legacy API controller namespace endpoints that the active SPA no longer calls directly

### Known compatibility risks still worth monitoring

- several online gateways remain configuration-sensitive and will fail explicitly when disabled or missing credentials
- admin and addon route files still contain legacy code, but optional bridges are now guarded where safe to keep tooling from failing unnecessarily
- the repository still contains legacy controller namespaces for historical reference and provider compatibility, even though the SPA-facing contract is rebuilt under the V1/API-first layer

## Assumptions

- The rebuild is being introduced incrementally inside the current repository before the final codebase is split or promoted.
- The active frontend will be updated to fetch bootstrap from `/api/v1/bootstrap`.
- Legacy frontend architecture tests will be replaced by rebuild-focused API feature tests as modules are completed.
- `/api/v1/auth/logout` now has an official `POST` contract, while a guarded `GET` alias remains temporarily to avoid breaking the current frontend before it is switched over.
- Catalog resources now avoid blindly resolving null media through the uploads table; missing media is returned as `null` instead of triggering storage lookups.
- Guest cart identity is still driven by the frontend-owned `temp_user_id`, while authenticated cart access and cart merge both use the Sanctum-backed `api` guard.
- Shipping remains zone-based off the selected shipping address’s city, with checkout quoting a per-shop rate and pickup orders forcing shipping cost to zero.
- Public shop visibility continues to mirror the active storefront rule: only shops with `published = 1`, `approval = 1`, and `verification_status = 1` are exposed.
- Shop registration remains implemented because the current SPA still posts to `/api/v1/shop/register`; new shops are created pending verification rather than silently self-approving.
- Delivery-boy endpoints resolve the acting user from the bearer token and require `user_type = delivery_boy`; state transitions are limited to `pending|confirmed -> picked_up -> on_the_way -> delivered`.
- Coupon validation preserves the frontend-facing `coupon_details` contract while normalizing expiry, prior-usage, and applicability checks inside the new service layer.
- Stock is deducted at successful order placement time, matching the current checkout contract; payment callbacks remain a later phase concern.
- Phase 9 preserves the storefront’s current split payment contract: browser form POSTs to `/payment/{gateway}/pay` for online handoff, while the API alias at `/api/v1/payment/{gateway}/pay` returns explicit JSON and is primarily used by offline wallet/re-payment flows.
- Offline manual payment handling remains pending/approval-based: cart checkout stays non-redirecting from Phase 4, while Phase 9 now also records pending wallet recharge and repayment submissions without falsely marking them paid.
- Callback idempotency is enforced through persisted `payments` and `payment_transactions` records, so duplicate success callbacks will not double-credit wallets or re-mark orders.
- The current rebuild keeps the legacy gateway public route names for frontend/provider continuity, but unsupported or disabled gateways now fail explicitly at initialization instead of falling through hidden legacy behavior.
- Page-builder payloads continue to use the existing section-based contract, but Phase 5 now resolves media defensively so null upload IDs return `null` instead of forcing upload lookups.
- Home content remains section-sliced at `setting/home/{section}` because that is the exact surface the active SPA calls today; the rebuild normalizes the internals without collapsing those endpoints into one generic CMS blob.
- Journal/blog payloads preserve the richer frontend journal structure, including hero posts, mixed editorial sections, and video cards when the request is not filtered by category or search.
- Invoice download remains a JSON handoff endpoint returning `invoice_url` and `invoice_name`; the SPA continues to initiate the browser download itself after receiving that payload.
- Digital downloads are implemented because the active SPA does call `GET /api/v1/user/orders/downloads`; the rebuild returns only paid digital purchases owned by the authenticated user.
- Notification payloads preserve the current split between a top-menu unread slice (`notifications`) and a paginated archive (`data.data`), even though the internals now route through a dedicated Phase 6 notification service.
- Follow and wishlist paths keep their existing legacy route names because the SPA store modules already depend on them directly.
- Address defaults remain explicit `default_shipping` and `default_billing` flags so Phase 4 checkout can continue to rely on user-owned address selection without additional hydration calls.
