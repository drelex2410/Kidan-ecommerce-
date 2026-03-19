<?php

use App\Http\Controllers\Api\V1\BootstrapController as V1BootstrapController;
use App\Http\Controllers\Api\V1\LocaleController as V1LocaleController;
use App\Http\Controllers\Api\V1\Auth\LoginController as V1LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController as V1LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordCreateController as V1PasswordCreateController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController as V1PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\ResendCodeController as V1ResendCodeController;
use App\Http\Controllers\Api\V1\Auth\SignupController as V1SignupController;
use App\Http\Controllers\Api\V1\Auth\VerifyController as V1VerifyController;
use App\Http\Controllers\Api\V1\Catalog\AjaxSearchController as V1AjaxSearchController;
use App\Http\Controllers\Api\V1\Catalog\BrandController as V1BrandController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController as V1CategoryController;
use App\Http\Controllers\Api\V1\Catalog\OfferController as V1OfferController;
use App\Http\Controllers\Api\V1\Catalog\ProductDetailsController as V1ProductDetailsController;
use App\Http\Controllers\Api\V1\Catalog\ProductSearchController as V1ProductSearchController;
use App\Http\Controllers\Api\V1\Content\BlogCategoryController as V1BlogCategoryController;
use App\Http\Controllers\Api\V1\Content\BlogDetailsController as V1BlogDetailsController;
use App\Http\Controllers\Api\V1\Content\BlogSearchController as V1BlogSearchController;
use App\Http\Controllers\Api\V1\Content\FooterSettingsController as V1FooterSettingsController;
use App\Http\Controllers\Api\V1\Content\HeaderSettingsController as V1HeaderSettingsController;
use App\Http\Controllers\Api\V1\Content\HomeSectionController as V1HomeSectionController;
use App\Http\Controllers\Api\V1\Content\PageController as V1PageController;
use App\Http\Controllers\Api\V1\Content\RecentBlogsController as V1RecentBlogsController;
use App\Http\Controllers\Api\V1\Delivery\DeliveryDashboardController as V1DeliveryDashboardController;
use App\Http\Controllers\Api\V1\Delivery\DeliveryOrdersController as V1DeliveryOrdersController;
use App\Http\Controllers\Api\V1\Checkout\CartController as V1CartController;
use App\Http\Controllers\Api\V1\Checkout\CouponController as V1CheckoutCouponController;
use App\Http\Controllers\Api\V1\Checkout\OrderController as V1CheckoutOrderController;
use App\Http\Controllers\Api\V1\Checkout\ShippingQuoteController as V1ShippingQuoteController;
use App\Http\Controllers\Api\V1\Checkout\TempCartMergeController as V1TempCartMergeController;
use App\Http\Controllers\Api\V1\Account\AddressController as V1AccountAddressController;
use App\Http\Controllers\Api\V1\Account\CouponController as V1AccountCouponController;
use App\Http\Controllers\Api\V1\Account\DashboardController as V1AccountDashboardController;
use App\Http\Controllers\Api\V1\Account\FollowController as V1AccountFollowController;
use App\Http\Controllers\Api\V1\Account\InvoiceDownloadController as V1InvoiceDownloadController;
use App\Http\Controllers\Api\V1\Account\NotificationController as V1AccountNotificationController;
use App\Http\Controllers\Api\V1\Account\OrderController as V1AccountOrderController;
use App\Http\Controllers\Api\V1\Account\ProfileController as V1AccountProfileController;
use App\Http\Controllers\Api\V1\Account\WishlistController as V1AccountWishlistController;
use App\Http\Controllers\Api\V1\Benefits\AffiliateController as V1AffiliateController;
use App\Http\Controllers\Api\V1\Benefits\ClubPointController as V1ClubPointController;
use App\Http\Controllers\Api\V1\Benefits\RefundController as V1RefundController;
use App\Http\Controllers\Api\V1\Benefits\WalletController as V1WalletController;
use App\Http\Controllers\Api\V1\Payments\PaymentInitializationController as V1PaymentInitializationController;
use App\Http\Controllers\Api\V1\Shops\ShopController as V1ShopController;
use App\Http\Controllers\Api\V1\Shops\ShopRegistrationController as V1ShopRegistrationController;
use App\Http\Controllers\Api\V1\User\UserInfoController as V1UserInfoController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AffiliateController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ClubPointController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DeliveryBoyController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RefundRequestController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SubscribeController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WishlistController;

Route::group(['prefix' => 'v1', 'as' => 'api.'], function () {

    Route::get('bootstrap', V1BootstrapController::class);
    Route::get('locale/{lang}', V1LocaleController::class);

    Route::group(['prefix' => 'payment', 'middleware' => 'auth:api'], function () {
        Route::any('/{gateway}/pay', V1PaymentInitializationController::class);
    });

    Route::group(['prefix' => 'auth'], function () {
        // banned user
        Route::group(['middleware' => 'unbanned'], function () {
            Route::post('login', V1LoginController::class)->middleware('throttle:10,1');
            Route::post('signup', V1SignupController::class)->middleware('throttle:10,1');
            Route::post('verify', V1VerifyController::class)->middleware('throttle:6,1');
            Route::post('resend-code', V1ResendCodeController::class)->middleware('throttle:6,1');

            Route::post('password/create', V1PasswordCreateController::class)->middleware('throttle:6,1');
            Route::post('password/reset', V1PasswordResetController::class)->middleware('throttle:6,1');
        });
        Route::group(['middleware' => 'auth:api'], function () {
            Route::post('logout', V1LogoutController::class);
            // Temporary compatibility alias for the current frontend until it switches to POST.
            Route::get('logout', V1LogoutController::class);
        });
    });
    Route::post('temp-id-cart', [AuthController::class, 'tempIdCart']);
    Route::post('temp-id-cart-update', V1TempCartMergeController::class)->middleware(['auth:api', 'unbanned']);


    Route::get('setting/home/{section}', [V1HomeSectionController::class, 'show']);
    Route::get('setting/footer', V1FooterSettingsController::class);
    Route::get('setting/header', V1HeaderSettingsController::class);
    Route::post('subscribe', [SubscribeController::class, 'subscribe']);

    Route::get('all-categories', [V1CategoryController::class, 'index']);
    Route::get('categories/first-level', [V1CategoryController::class, 'firstLevel']);
    Route::get('all-brands', V1BrandController::class);
    Route::get('all-offers', [V1OfferController::class, 'index']);
    Route::get('offer/{slug}', [V1OfferController::class, 'show']);
    Route::get('page/{slug}', [V1PageController::class, 'show']);

    // Blogs
    Route::get('all-blog-categories', V1BlogCategoryController::class);
    Route::get('all-blogs/search', V1BlogSearchController::class);
    Route::get('recent-blogs', V1RecentBlogsController::class);
    Route::get('blog/details/{slug}', [V1BlogDetailsController::class, 'show']);

    Route::group(['prefix' => 'product'], function () {
        Route::get('/details/{product_slug}', [V1ProductDetailsController::class, 'show']);
        Route::post('get-by-ids', [ProductController::class, 'get_by_ids']);
        Route::get('search', V1ProductSearchController::class);
        Route::get('todays-deal', [ProductController::class, 'todays_deal']);
        Route::get('related/{product_id}', [V1ProductDetailsController::class, 'related']);
        Route::get('bought-together/{product_id}', [V1ProductDetailsController::class, 'boughtTogether']);
        Route::get('random/{limit}/{product_id?}', [V1ProductDetailsController::class, 'random']);
        Route::get('latest/{limit}', [V1ProductDetailsController::class, 'latest']);
        Route::get('reviews/{product_id}', [ReviewController::class, 'index']);
    });
    Route::post('compared-list', [ProductController::class, 'productComparedList']);
    Route::get('search.ajax/{keyword}', V1AjaxSearchController::class);

    Route::get('all-countries', [AddressController::class, 'get_all_countries']);
    Route::get('states/{country_id}', [AddressController::class, 'get_states_by_country_id']);
    Route::get('cities/{state_id}', [AddressController::class, 'get_cities_by_state_id']);

    Route::post('carts', [V1CartController::class, 'index']);
    Route::post('carts/add', [V1CartController::class, 'add']);
    Route::post('carts/change-quantity', [V1CartController::class, 'changeQuantity']);
    Route::post('carts/destroy', [V1CartController::class, 'destroy']);
    Route::get('order/invoice-download/{order_id}', V1InvoiceDownloadController::class)
        ->middleware(['auth:api', 'unbanned']);


    Route::group(['middleware' => ['auth:api', 'unbanned']], function () {

        Route::group(['prefix' => 'checkout'], function () {
            Route::get('get-shipping-cost/{address_id}', V1ShippingQuoteController::class);
            Route::post('order/store', V1CheckoutOrderController::class);
            Route::post('coupon/apply', V1CheckoutCouponController::class);
        });

        Route::group(['prefix' => 'user'], function () {

            Route::get('notification', [V1AccountNotificationController::class, 'index']);
            Route::get('all-notification', [V1AccountNotificationController::class, 'all']);
            Route::get('dashboard', V1AccountDashboardController::class);

            Route::get('chats', [ChatController::class, 'index']);
            Route::post('chats/send', [ChatController::class, 'send']);
            Route::get('chats/new-messages', [ChatController::class, 'new_messages']);

            Route::get('info', V1UserInfoController::class);
            Route::post('info/update', [V1AccountProfileController::class, 'update']);

            Route::get('coupons', V1AccountCouponController::class);

            Route::get('orders', [V1AccountOrderController::class, 'index']);
            
            Route::get('re-order/{orderCode}', [OrderController::class, 'reOrder']);

            Route::get('orders/downloads', [V1AccountOrderController::class, 'downloads']);
            Route::get('orders/product/download/{id}', [OrderController::class, 'download']);
            Route::get('order/{order_code}', [V1AccountOrderController::class, 'show']);
            Route::get('order/cancel/{order_id}', [V1AccountOrderController::class, 'cancel']);
            // Route::get('order/invoice-download/{order_code}', [OrderController::class, 'invoice_download']);

            Route::get('review/check/{product_id}', [ReviewController::class, 'check_review_status']);
            Route::post('review/submit', [ReviewController::class, 'submit_review']);

            Route::apiResource('wishlists', V1AccountWishlistController::class)->except(['update', 'show']);
            Route::apiResource('follow', V1AccountFollowController::class)->except(['update', 'show']);

            Route::get('addresses', [V1AccountAddressController::class, 'index']);
            Route::post('address/create', [V1AccountAddressController::class, 'store']);
            Route::post('address/update', [V1AccountAddressController::class, 'update']);
            Route::get('address/delete/{id}', [V1AccountAddressController::class, 'destroy']);
            Route::get('address/default-shipping/{id}', [V1AccountAddressController::class, 'defaultShipping']);
            Route::get('address/default-billing/{id}', [V1AccountAddressController::class, 'defaultBilling']);
            Route::get('pickup-points', [AddressController::class, 'get_pickup_points']);

            # conversation
            Route::get('querries', [ConversationController::class, 'index']);
            Route::post('new-query', [ConversationController::class, 'store']);
            Route::get('querries/{id}', [ConversationController::class, 'show']);
            Route::post('new-message-query', [ConversationController::class, 'storeMessage']);

            # wallet
            Route::post('wallet/recharge', [V1WalletController::class, 'recharge']);
            Route::get('wallet/history', [V1WalletController::class, 'history']);

            # club points
            Route::get('earning/history', [V1ClubPointController::class, 'history']);
            Route::post('convert-point-into-wallet', [V1ClubPointController::class, 'convert']);

            // Refund Addon
            Route::get('refund-requests', [V1RefundController::class, 'index']);
            Route::get('refund-request/create/{order_id}', [V1RefundController::class, 'create']);
            Route::post('refund-request/store', [V1RefundController::class, 'store']);

            // affiliate
            Route::controller(V1AffiliateController::class)->group(function () {
                Route::post('affiliate/convert-request', 'affiliateAmountConvertToWallet');
                Route::post('affiliate/withdraw-request', 'withdrawRequestStore');
                Route::get('affiliate/withdraw-request',  'withdrawRequestList');
                Route::get('affiliate/payment-history',  'paymentHistory');
                Route::get('affiliate/earning-history',  'earningHistory');
                Route::post('affiliate/payment-settings',  'paymentSettings');
                Route::get('affiliate/balance',  'affiliateBalance');
                Route::get('affiliate/referral-code',  'referralCode');
                Route::get('affiliate/stats',  'affiliateStats');
                Route::get('affiliate/user-check',  'affiliateUserCheck');
                Route::post('affiliate/store',  'store');
            });
        });

        Route::group(['prefix' => 'delivery-boy'], function () {
            Route::get('dashboard', V1DeliveryDashboardController::class);
            Route::get('/collections-list', [V1DeliveryOrdersController::class, 'collections']);
            Route::get('/earnings-list', [V1DeliveryOrdersController::class, 'earnings']);
            Route::get('/assigned-deliveries', [V1DeliveryOrdersController::class, 'assigned']);
            Route::get('/cancel-deliveries', [V1DeliveryOrdersController::class, 'cancelled']);
            Route::get('/completed-deliveries', [V1DeliveryOrdersController::class, 'completed']);
            Route::get('/pending-deliveries', [V1DeliveryOrdersController::class, 'pending']);
            Route::get('/picked-up-deliveries', [V1DeliveryOrdersController::class, 'pickedUp']);
            Route::get('/on-the-way-deliveries', [V1DeliveryOrdersController::class, 'onTheWay']);
            Route::post('/update-delivery-status', [V1DeliveryOrdersController::class, 'updateStatus']);
            Route::post('/cancel-request', [V1DeliveryOrdersController::class, 'cancelRequest']);
        });
    });


    //for shops
    Route::post('shop/register', V1ShopRegistrationController::class);
    Route::get('all-shops', [V1ShopController::class, 'index']);
    Route::get('shop/{slug}', [V1ShopController::class, 'show']);
    Route::get('shop/{slug}/home', [V1ShopController::class, 'home']);
    Route::get('shop/{slug}/coupons', [V1ShopController::class, 'coupons']);
    Route::get('shop/{slug}/products', [V1ShopController::class, 'products']);
    // affiliate
    // Route::post('affiliate/store', [AffiliateController::class, 'store']);
    Route::post('affiliate/registration-refferal-code', [AffiliateController::class, 'registration_refferal_code']);
    Route::get('affiliate/balance-check',  [AffiliateController::class, 'affiliate_balance_check']);

    Route::post('product-refferal-code', [AffiliateController::class, 'product_refferal_code']);
});

Route::fallback(function () {
    return response()->json([
        'data' => [],
        'success' => false,
        'status' => 404,
        'message' => 'Invalid Route'
    ]);
});
