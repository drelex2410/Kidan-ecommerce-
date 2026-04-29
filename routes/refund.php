<?php
use App\Addons\Refund\Http\Controllers\Admin\RefundRequestController;
use App\Addons\Refund\Http\Controllers\Admin\RefundPolicyController;
use App\Addons\Refund\Http\Controllers\Seller\RefundRequestController as SellerRefundRequestController;


/*
|--------------------------------------------------------------------------
| Refund Routes
|--------------------------------------------------------------------------
|
| Here is where you can register refund routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth', 'admin'],
    'as' => 'admin.'
],function () {
    Route::get('/refund-settings', [RefundRequestController::class, 'refund_settings'])->name('refund_settings');

    Route::get('/refund-requests', [RefundRequestController::class, 'refund_requests'])->name('refund_requests');
    Route::get('/refund-request/{id}', [RefundRequestController::class, 'refund_request_create'])->name('refund_request.create');
    Route::post('/refund-request-store', [RefundRequestController::class, 'refund_request_store'])->name('refund_request.store');
    Route::post('/refund-request-view_details', [RefundRequestController::class, 'refund_request_view_detials'])->name('refund_request.view_details');
    Route::post('/refund-request-review', [RefundRequestController::class, 'refund_request_review'])->name('refund_request.review');
    Route::post('/refund-request-update', [RefundRequestController::class, 'refund_request_update'])->name('refund_request.update');
    Route::post('/refund-request-accept', [RefundRequestController::class, 'refund_request_accept'])->name('refund_request.accept');
    Route::get('/refund-request-reject/{id}', [RefundRequestController::class, 'refund_request_reject'])->name('refund_request.reject');

    Route::get('/refund-policies', [RefundPolicyController::class, 'index'])->name('refund_policies.index');
    Route::get('/refund-policies/create', [RefundPolicyController::class, 'create'])->name('refund_policies.create');
    Route::post('/refund-policies', [RefundPolicyController::class, 'store'])->name('refund_policies.store');
    Route::get('/refund-policies/{id}/edit', [RefundPolicyController::class, 'edit'])->name('refund_policies.edit');
    Route::put('/refund-policies/{id}', [RefundPolicyController::class, 'update'])->name('refund_policies.update');
    Route::post('/refund-policies/update-status', [RefundPolicyController::class, 'updateStatus'])->name('refund_policies.update_status');
});

Route::group([
    'prefix' => 'seller',
    'middleware' => ['auth', 'seller'],
    'as' => 'seller.'
], function () {

    Route::get('/refund-requests', [SellerRefundRequestController::class, 'refund_requests'])->name('refund_requests');
    Route::get('/refund-request/{id}', [SellerRefundRequestController::class, 'refund_request_create'])->name('refund_request.create');
    Route::post('/refund-request-store', [SellerRefundRequestController::class, 'refund_request_store'])->name('refund_request.store');
    Route::post('/refund-request-view_details', [SellerRefundRequestController::class, 'refund_request_view_detials'])->name('refund_request.view_details');
    Route::get('/refund-request-accept/{id}', [SellerRefundRequestController::class, 'refund_request_accept'])->name('refund_request.accept');
    Route::get('/refund-request-reject/{id}', [SellerRefundRequestController::class, 'refund_request_reject'])->name('refund_request.reject');
});
