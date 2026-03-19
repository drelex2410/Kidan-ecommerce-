# Backend Rebuild Plan

## Goal

Rebuild the ecommerce backend as a clean, API-first Laravel application that preserves the active frontend contract while removing legacy coupling, vendor-controlled logic, and Blade-injected SPA runtime state.

## Core Principles

- The frontend contract is the source of truth for external behavior.
- The legacy backend is reference material, not a structural template.
- Controllers orchestrate; services own business logic.
- API Resources shape payloads.
- Form Requests validate input.
- Policies and dedicated actions guard user-owned resources.
- Payment flows are isolated behind gateway contracts and auditable transactions.
- SPA boot depends on `GET /api/v1/bootstrap`, not Blade globals.

## Proposed Module Structure

```text
app/
  Http/
    Controllers/
      Api/
        V1/
          Auth/
          Bootstrap/
          Catalog/
          Checkout/
          Cms/
          Delivery/
          Payments/
          User/
      SpaController.php
    Requests/
      Api/
        V1/
          ...
    Resources/
      Api/
        V1/
          ...
  Models/
    ...
  Policies/
    ...
  Services/
    Auth/
    Bootstrap/
    Catalog/
    Checkout/
    Cms/
    Delivery/
    Payments/
    User/
  Support/
    Payments/
    Settings/
```

## Core Domain Model

- `User`
- `CustomerProfile`
- `Address`
- `Country`
- `Language`
- `Currency`
- `Product`
- `ProductCategory`
- `Brand`
- `ProductAttribute`
- `ProductVariation`
- `Offer`
- `Cart`
- `CartItem`
- `Coupon`
- `Order`
- `OrderItem`
- `Payment`
- `PaymentTransaction`
- `RefundRequest`
- `Shop`
- `ShopFollower`
- `Wishlist`
- `Conversation`
- `ConversationMessage`
- `ChatThread`
- `ChatMessage`
- `Wallet`
- `WalletTransaction`
- `ClubPointTransaction`
- `AffiliateProfile`
- `AffiliateWithdrawal`
- `Notification`
- `BlogCategory`
- `BlogPost`
- `CmsPage`
- `CmsSection`
- `HomeSection`
- `DeliveryAgent`
- `DeliveryAssignment`
- `FeatureFlag`
- `PaymentMethod`
- `OfflinePaymentMethod`
- `Banner`
- `ApplicationSetting`

## Service Inventory

### Phase 1

- `BootstrapService`
- `LocaleService`
- `SettingRepository`

### Phase 2

- `AuthenticationService`
- `RegistrationService`
- `VerificationService`
- `PasswordResetService`
- `CurrentUserService`

### Phase 3

- `CatalogSearchService`
- `ProductDetailsService`
- `OfferService`
- `CategoryTreeService`
- `BrandService`

### Phase 4

- `GuestCartService`
- `CartService`
- `CouponService`
- `ShippingQuoteService`
- `CheckoutService`
- `OrderPlacementService`

### Phase 5

- `HeaderSettingsService`
- `FooterSettingsService`
- `HomeSectionService`
- `CmsPageService`
- `JournalService`

### Phase 6

- `AddressBookService`
- `OrderQueryService`
- `InvoiceService`
- `WishlistService`
- `FollowService`
- `NotificationService`
- `ProfileService`

### Phase 7

- `RefundService`
- `WalletService`
- `ClubPointService`
- `AffiliateService`

### Phase 8

- `ShopRegistrationService`
- `ShopPublicService`
- `DeliveryDashboardService`
- `DeliveryStatusService`

### Phase 9

- `PaymentGatewayManager`
- `PaymentInitializationService`
- `PaymentCallbackService`
- `PaymentAuditService`

## Policies

- `AddressPolicy`
- `OrderPolicy`
- `RefundRequestPolicy`
- `WishlistPolicy`
- `ShopFollowerPolicy`
- `ConversationPolicy`
- `ChatThreadPolicy`
- `WalletPolicy`
- `AffiliateProfilePolicy`
- `DeliveryAssignmentPolicy`

## Events / Jobs / Listeners

- `UserRegistered`
- `VerificationCodeRequested`
- `OrderPlaced`
- `OrderPaid`
- `PaymentCallbackReceived`
- `RefundRequested`
- `WalletRecharged`
- `AffiliateWithdrawalRequested`
- `ChatMessageSent`

Jobs:

- `SendVerificationCodeJob`
- `SendPasswordResetCodeJob`
- `GenerateInvoiceJob`
- `ProcessPaymentCallbackJob`
- `DispatchOrderNotificationsJob`

## API Route Map

### Phase 1

- `GET /api/v1/bootstrap`
- `GET /api/v1/locale/{lang}`

### Phase 2

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/signup`
- `POST /api/v1/auth/verify`
- `POST /api/v1/auth/resend-code`
- `POST /api/v1/auth/password/create`
- `POST /api/v1/auth/password/reset`
- `POST /api/v1/auth/logout`
- `GET /api/v1/user/info`

### Phase 3

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

### Phase 4

- `POST /api/v1/carts`
- `POST /api/v1/carts/add`
- `POST /api/v1/carts/change-quantity`
- `POST /api/v1/carts/destroy`
- `POST /api/v1/checkout/coupon/apply`
- `GET /api/v1/checkout/get-shipping-cost/{addressId}`
- `POST /api/v1/checkout/order/store`
- `POST /api/v1/temp-id-cart-update`

### Phase 5

- `GET /api/v1/page/{slug}`
- `GET /api/v1/setting/header`
- `GET /api/v1/setting/footer`
- `GET /api/v1/setting/home/{section}`
- `GET /api/v1/all-blog-categories`
- `GET /api/v1/all-blogs/search`
- `GET /api/v1/recent-blogs`
- `GET /api/v1/blog/details/{slug}`

## Web Route Map

- SPA shell catch-all for client routes
- Payment redirect and callback routes
- Social login callback routes if preserved
- File serving routes that must remain web-facing

## Response Resource Plan

- `BootstrapResource`
- `LocaleResource`
- `AuthenticatedUserResource`
- `ProductListingResource`
- `ProductDetailsResource`
- `CartResource`
- `CheckoutQuoteResource`
- `OrderPlacementResource`
- `CmsPageResource`
- `JournalFeedResource`

## Phases

1. Bootstrap, SPA shell, locale, feature flags
2. Auth and current user hydration
3. Catalog and product detail parity
4. Cart, coupon, shipping, checkout, order placement
5. CMS and journal/blog
6. User account area
7. Refund, wallet, club points, affiliate
8. Shops and delivery flows
9. Payments and callback hardening
10. Tests, docs, seeders, cleanup
