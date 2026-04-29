<?php

namespace Tests\Feature\Api\V1\Benefits;

use App\Models\Order;
use App\Models\Product;
use App\Models\RefundPolicy;
use App\Models\User;
use App\Addons\Refund\Http\Controllers\Admin\RefundPolicyController;
use App\Services\Benefits\RefundEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseSevenBenefitsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->app['view']->addNamespace('addon:refund', base_path('app/Addons/Refund/views'));

        $this->createBenefitsSchema();
        $this->seedBenefitSettings();
    }

    public function test_refund_requests_list_returns_current_users_requests(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other@example.com']);
        $refundId = $this->seedRefundRequestGraph($user);
        $this->seedRefundRequestGraph($other, 'OTHER-COMBINED');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/refund-requests?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $refundId)
            ->assertJsonPath('data.0.status_key', 'pending')
            ->assertJsonPath('data.0.refunditems.0.quantity_requested', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_refund_create_context_enforces_ownership(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other@example.com']);
        [, $orderId] = $this->seedEligibleOrder($other, 'OTHER-COMBINED');

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/refund-request/create/' . $orderId)
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    public function test_refund_store_creates_request_for_eligible_order(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => 'Damaged',
                'refund_note' => 'Item arrived damaged.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Your request has been submitted successfully');

        $this->assertDatabaseHas('refund_requests', [
            'order_id' => $orderId,
            'user_id' => $user->id,
        ]);
    }

    public function test_refund_create_context_returns_item_level_policy_eligibility(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/refund-request/create/' . $orderId);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.refund_summary.has_eligible_items', true)
            ->assertJsonPath('order.refund_summary.eligible_item_count', 1)
            ->assertJsonPath('order.products.data.0.order_detail_id', $orderDetailId)
            ->assertJsonPath('order.products.data.0.refund_eligibility.is_eligible', true)
            ->assertJsonPath('order.products.data.0.refund_eligibility.max_requestable_quantity', 1);
    }

    public function test_refund_store_rejects_when_policy_window_has_expired(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user, 'EXPIRED-COMBINED', [
            'policy' => ['refund_window_days' => 1],
            'order' => [
                'completed_at' => now()->subDays(3),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(3),
            ],
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => 'Window expired',
                'refund_note' => 'Trying after the allowed period.',
            ]);

        $order = Order::query()
            ->with([
                'refundRequests.refundRequestItems',
                'orderDetails.product.refundPolicy',
            ])
            ->findOrFail($orderId);

        $decoratedOrder = app(RefundEligibilityService::class)->decorateOrder($order);

        $this->assertFalse((bool) data_get($decoratedOrder->getAttribute('refund_summary'), 'has_eligible_items'));
        $this->assertSame(
            'The refund window for this item has expired.',
            data_get($decoratedOrder->orderDetails->first()?->getAttribute('refund_eligibility'), 'message')
        );

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('message', "You can't send refund request for this order");
    }

    public function test_refund_store_rejects_when_pending_request_already_consumed_item_quantity(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId, $productId, $policyId, $shopId] = $this->seedEligibleOrder($user, 'PENDING-COMBINED');

        $refundId = (int) DB::table('refund_requests')->insertGetId([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'amount' => 45,
            'reasons' => json_encode(['Damaged']),
            'refund_note' => 'Pending original request.',
            'admin_approval' => 0,
            'seller_approval' => 0,
            'status' => 'pending',
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('refund_request_items')->insert([
            'refund_request_id' => $refundId,
            'order_detail_id' => $orderDetailId,
            'quantity' => 1,
            'product_id' => $productId,
            'applied_refund_policy_id' => $policyId,
            'quantity_requested' => 1,
            'item_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => 'Duplicate attempt',
                'refund_note' => 'Second request for same unit.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('message', "You can't send refund request for this order");
    }

    public function test_refund_store_requires_reason_and_details_when_policy_demands_them(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user, 'REASON-COMBINED', [
            'policy' => [
                'requires_reason' => 1,
            ],
        ]);

        $missingReason = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => '',
                'refund_note' => '',
            ]);

        $missingReason->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('message', 'Refund reason is required for the selected item(s).');

        $missingDetails = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => 'Damaged',
                'refund_note' => '',
            ]);

        $missingDetails->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('message', 'Refund details are required for the selected item(s).');
    }

    public function test_refund_store_requires_evidence_when_policy_demands_it(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user, 'EVIDENCE-COMBINED', [
            'policy' => [
                'requires_evidence' => 1,
            ],
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => 'Damaged',
                'refund_note' => 'See attached evidence.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('message', 'Evidence is required for the selected item(s).');
    }

    public function test_refund_create_context_marks_digital_items_ineligible_when_policy_excludes_them(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [, $orderId] = $this->seedEligibleOrder($user, 'DIGITAL-EXCLUDED', [
            'policy' => [
                'exclude_digital_products' => 1,
            ],
            'product' => [
                'digital' => 1,
            ],
        ]);

        $order = Order::query()
            ->with([
                'refundRequests.refundRequestItems',
                'orderDetails.product.refundPolicy',
            ])
            ->findOrFail($orderId);

        $decoratedOrder = app(RefundEligibilityService::class)->decorateOrder($order);

        $this->assertSame(
            'Digital products are excluded from refunds.',
            data_get($decoratedOrder->orderDetails->first()?->getAttribute('refund_eligibility'), 'message')
        );

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/refund-request/create/' . $orderId)
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "You can't send refund request for this order");
    }

    public function test_refund_create_context_marks_discounted_items_ineligible_when_policy_excludes_them(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [, $orderId] = $this->seedEligibleOrder($user, 'DISCOUNT-EXCLUDED', [
            'policy' => [
                'exclude_discounted_products' => 1,
            ],
            'product' => [
                'discount' => 5,
            ],
        ]);

        $order = Order::query()
            ->with([
                'refundRequests.refundRequestItems',
                'orderDetails.product.refundPolicy',
            ])
            ->findOrFail($orderId);

        $decoratedOrder = app(RefundEligibilityService::class)->decorateOrder($order);

        $this->assertSame(
            'Discounted items are excluded from refunds.',
            data_get($decoratedOrder->orderDetails->first()?->getAttribute('refund_eligibility'), 'message')
        );

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/refund-request/create/' . $orderId)
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "You can't send refund request for this order");
    }

    public function test_refund_store_rejects_when_quantity_exceeds_refundable_quantity(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($user, 'QTY-BOUNDARY', [
            'order_detail' => [
                'quantity' => 1,
            ],
        ]);

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 2,
                    ],
                ]),
                'refund_reasons' => 'Too many units',
                'refund_note' => 'Trying to refund more than purchased.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonPath('message', "You can't request more than refundable quantity");
    }

    public function test_refund_store_enforces_ownership_for_other_users_order(): void
    {
        $owner = $this->createUser(['email_verified_at' => now()]);
        $other = $this->createUser(['email' => 'other-owner@example.com', 'email_verified_at' => now()]);
        [$orderDetailId, $orderId] = $this->seedEligibleOrder($owner, 'FOREIGN-ORDER');

        $response = $this->withToken($other->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/refund-request/store', [
                'order_id' => $orderId,
                'refund_items' => json_encode([
                    [
                        'status' => true,
                        'order_detail_id' => $orderDetailId,
                        'quantity' => 1,
                    ],
                ]),
                'refund_reasons' => 'Not mine',
                'refund_note' => 'Should be blocked.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 403);
    }

    public function test_product_refund_policy_assignment_persists_and_relations_resolve(): void
    {
        $owner = $this->createUser();
        $shopId = $this->seedShop($owner);
        $initialPolicyId = $this->seedRefundPolicy(['name' => 'Initial Policy', 'code' => 'initial-policy']);
        $replacementPolicyId = $this->seedRefundPolicy(['name' => 'Replacement Policy', 'code' => 'replacement-policy']);
        $productId = $this->seedProductWithPolicy($shopId, $initialPolicyId);

        $product = Product::query()->findOrFail($productId);
        $this->assertSame($initialPolicyId, (int) $product->refund_policy_id);
        $this->assertSame('Initial Policy', $product->refundPolicy?->name);

        $product->refund_policy_id = $replacementPolicyId;
        $product->save();

        $freshProduct = Product::query()->findOrFail($productId);
        $this->assertSame($replacementPolicyId, (int) $freshProduct->refund_policy_id);
        $this->assertSame('Replacement Policy', $freshProduct->refundPolicy?->name);
    }

    public function test_admin_refund_policy_routes_support_create_update_status_toggle_and_listing(): void
    {
        $this->withoutMiddleware();

        $admin = $this->createUser([
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession([])
            ->post(route('admin.refund_policies.store'), [
                'name' => 'Thirty Day Policy',
                'code' => 'thirty-day-policy',
                'description' => 'Primary store policy',
                'is_active' => 1,
                'refund_window_days' => 30,
                'allowed_order_statuses' => ['delivered'],
                'allow_partial_refund' => 1,
                'refund_shipping_fee' => 0,
                'requires_admin_approval' => 1,
                'requires_reason' => 1,
                'requires_evidence' => 0,
                'exclude_opened_items' => 0,
                'exclude_digital_products' => 1,
                'exclude_discounted_products' => 0,
                'refund_method_type' => 'manual',
                'internal_notes' => 'Created in test',
            ])
            ->assertRedirect(route('admin.refund_policies.index'));

        $policy = RefundPolicy::query()->where('code', 'thirty-day-policy')->firstOrFail();
        $this->assertSame(['delivered'], $policy->allowed_order_statuses);

        $this->actingAs($admin)
            ->withSession([])
            ->put(route('admin.refund_policies.update', $policy->id), [
                'name' => 'Updated Thirty Day Policy',
                'code' => 'updated-thirty-day-policy',
                'description' => 'Updated store policy',
                'is_active' => 1,
                'refund_window_days' => 21,
                'allowed_order_statuses' => ['delivered', 'shipped'],
                'allow_partial_refund' => 0,
                'refund_shipping_fee' => 1,
                'requires_admin_approval' => 1,
                'requires_reason' => 1,
                'requires_evidence' => 1,
                'exclude_opened_items' => 0,
                'exclude_digital_products' => 1,
                'exclude_discounted_products' => 1,
                'refund_method_type' => 'wallet',
                'internal_notes' => 'Updated in test',
            ])
            ->assertRedirect(route('admin.refund_policies.index'));

        $policy->refresh();
        $this->assertSame('Updated Thirty Day Policy', $policy->name);
        $this->assertSame(['delivered', 'shipped'], $policy->allowed_order_statuses);
        $this->assertTrue((bool) $policy->requires_evidence);

        $toggleResponse = $this->actingAs($admin)
            ->withSession([])
            ->post(route('admin.refund_policies.update_status'), [
                'id' => $policy->id,
                'status' => 0,
            ]);

        $toggleResponse->assertOk();
        $this->assertSame('1', trim($toggleResponse->getContent()));
        $this->assertFalse((bool) $policy->fresh()->is_active);

        $view = app(RefundPolicyController::class)->index(Request::create(
            route('admin.refund_policies.index'),
            'GET',
            ['status' => 0]
        ));

        $refundPolicies = $view->getData()['refundPolicies'];
        $this->assertSame(1, $refundPolicies->count());
        $this->assertSame('Updated Thirty Day Policy', $refundPolicies->first()->name);
    }

    public function test_admin_can_approve_refund_request_with_notes_and_quantities(): void
    {
        $this->withoutMiddleware();

        $admin = $this->createUser([
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);
        $customer = $this->createUser(['email' => 'refund-customer@example.com', 'email_verified_at' => now()]);
        [$orderDetailId, $orderId, $productId, $policyId, $shopId] = $this->seedEligibleOrder($customer, 'ADMIN-APPROVE');
        [$refundId, $refundItemId] = $this->seedPendingRefundRequestForOrder(
            $customer,
            $orderId,
            $orderDetailId,
            $productId,
            $policyId,
            $shopId
        );

        $response = $this->actingAs($admin)
            ->from('/admin/refund-requests')
            ->withSession([])
            ->post(route('admin.refund_request.update'), [
                'refund_request_id' => $refundId,
                'status' => 'approved',
                'amount' => 45,
                'admin_notes' => 'Approved after evidence review.',
                'approved_quantities' => [
                    $refundItemId => 1,
                ],
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundId,
            'status' => 'approved',
            'admin_notes' => 'Approved after evidence review.',
            'reviewed_by' => $admin->id,
            'admin_approval' => 1,
            'amount' => 45,
        ]);

        $this->assertDatabaseHas('refund_request_items', [
            'id' => $refundItemId,
            'quantity_approved' => 1,
            'item_status' => 'approved',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'refund_status' => 'fully_refunded',
            'refund_amount' => 45,
        ]);
    }

    public function test_admin_can_reject_refund_request_with_notes(): void
    {
        $this->withoutMiddleware();

        $admin = $this->createUser([
            'user_type' => 'admin',
            'email_verified_at' => now(),
        ]);
        $customer = $this->createUser(['email' => 'refund-reject@example.com', 'email_verified_at' => now()]);
        [$orderDetailId, $orderId, $productId, $policyId, $shopId] = $this->seedEligibleOrder($customer, 'ADMIN-REJECT');
        [$refundId, $refundItemId] = $this->seedPendingRefundRequestForOrder(
            $customer,
            $orderId,
            $orderDetailId,
            $productId,
            $policyId,
            $shopId
        );

        $response = $this->actingAs($admin)
            ->from('/admin/refund-requests')
            ->withSession([])
            ->post(route('admin.refund_request.update'), [
                'refund_request_id' => $refundId,
                'status' => 'rejected',
                'admin_notes' => 'Evidence was insufficient.',
                'approved_quantities' => [
                    $refundItemId => 0,
                ],
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundId,
            'status' => 'rejected',
            'admin_notes' => 'Evidence was insufficient.',
            'reviewed_by' => $admin->id,
            'admin_approval' => 2,
            'amount' => 0,
        ]);

        $this->assertDatabaseHas('refund_request_items', [
            'id' => $refundItemId,
            'quantity_approved' => 0,
            'item_status' => 'rejected',
            'rejection_reason' => 'Evidence was insufficient.',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'refund_amount' => 0,
        ]);
    }

    public function test_wallet_history_returns_current_users_entries(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $this->seedWalletEntry($user, 5000, 'Recharge', 'Added');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->getJson('/api/v1/user/wallet/history?page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.amount', 5000)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_wallet_recharge_route_returns_explicit_handoff_failure(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/wallet/recharge', ['amount' => 100])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_club_point_history_and_conversion_work(): void
    {
        $user = $this->createUser(['email_verified_at' => now(), 'balance' => 0]);
        $clubPointId = $this->seedClubPoint($user, 20);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user/earning/history?page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $clubPointId);

        $response = $this->withToken($token)
            ->postJson('/api/v1/user/convert-point-into-wallet', ['id' => $clubPointId]);

        $response->assertOk();
        $this->assertSame(1, $response->json());
        $this->assertDatabaseHas('club_points', [
            'id' => $clubPointId,
            'convert_status' => 1,
        ]);
        $this->assertSame('10.00', number_format((float) $user->fresh()->balance, 2, '.', ''));
    }

    public function test_club_point_conversion_returns_legacy_unpaid_signal_for_unpaid_orders(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $clubPointId = $this->seedClubPoint($user, 20, 'unpaid');

        $response = $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/convert-point-into-wallet', ['id' => $clubPointId]);

        $response->assertOk();
        $this->assertSame(3, $response->json());
    }

    public function test_affiliate_registration_balance_and_referral_contracts_work(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/user/affiliate/store', [
                'name' => 'Affiliate User',
                'email' => 'user@example.com',
                'phone' => '+2348000000000',
                'address' => '12 Allen Avenue',
                'description' => 'Content creator',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/user-check')
            ->assertOk()
            ->assertJsonPath('affiliate_option', true)
            ->assertJsonPath('user_referral_code', $user->fresh()->referral_code);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/balance')
            ->assertOk()
            ->assertJsonStructure(['affiliate_balance', 'status']);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/referral-code')
            ->assertOk()
            ->assertJsonPath('status', 200);
    }

    public function test_affiliate_stats_and_histories_return_paginated_contracts(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $profileId = $this->seedAffiliateProfile($user, 150);
        $orderDetailId = $this->seedAffiliateEarningOrder($user);
        DB::table('affiliate_stats')->insert([
            'affiliate_user_id' => $profileId,
            'no_of_click' => 10,
            'no_of_order_item' => 2,
            'no_of_delivered' => 1,
            'no_of_cancel' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('affiliate_payments')->insert([
            'affiliate_user_id' => $profileId,
            'amount' => 25,
            'payment_method' => 'Converted To Wallet',
            'payment_details' => 'Converted To Wallet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('affiliate_withdraw_requests')->insert([
            'user_id' => $user->id,
            'amount' => 20,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('affiliate_logs')->insert([
            'user_id' => $user->id,
            'referred_by_user_id' => $user->id,
            'order_detail_id' => $orderDetailId,
            'affiliate_type' => 'product_sharing',
            'amount' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/stats')
            ->assertOk()
            ->assertJsonPath('data.click', 10)
            ->assertJsonPath('data.item', 2);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/payment-history?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.payment_method', 'Converted To Wallet')
            ->assertJsonPath('meta.current_page', 1);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/earning-history?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.referrel_type', 'product_sharing')
            ->assertJsonPath('meta.current_page', 1);

        $this->withToken($token)
            ->getJson('/api/v1/user/affiliate/withdraw-request?page=1')
            ->assertOk()
            ->assertJsonPath('data.0.amount', 20)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_affiliate_payment_settings_and_convert_request_work(): void
    {
        $user = $this->createUser(['email_verified_at' => now(), 'balance' => 0]);
        $this->seedAffiliateProfile($user, 150);
        $token = $user->createToken('frontend-web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/user/affiliate/payment-settings', [
                'paypalEmail' => 'paypal@example.com',
                'bankInformations' => 'Bank details',
            ])
            ->assertOk()
            ->assertJsonPath('status', 200);

        $this->withToken($token)
            ->postJson('/api/v1/user/affiliate/convert-request', [
                'amount' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('affiliate_payments', [
            'payment_method' => 'Converted To Wallet',
            'amount' => 50,
        ]);
        $this->assertSame('50.00', number_format((float) $user->fresh()->balance, 2, '.', ''));
    }

    public function test_affiliate_withdraw_request_rejects_insufficient_balance(): void
    {
        $user = $this->createUser(['email_verified_at' => now()]);
        $this->seedAffiliateProfile($user, 20);

        $this->withToken($user->createToken('frontend-web')->plainTextToken)
            ->postJson('/api/v1/user/affiliate/withdraw-request', [
                'amount' => 25,
            ])
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    private function createBenefitsSchema(): void
    {
        foreach ([
            'affiliate_logs',
            'affiliate_stats',
            'affiliate_options',
            'affiliate_payments',
            'affiliate_withdraw_requests',
            'affiliate_users',
            'club_points',
            'wallets',
            'refund_request_items',
            'refund_requests',
            'refund_policies',
            'order_updates',
            'order_details',
            'orders',
            'combined_orders',
            'product_variation_combinations',
            'product_variations',
            'attribute_value_translations',
            'attribute_values',
            'attribute_translations',
            'attributes',
            'product_taxes',
            'product_translations',
            'products',
            'shops',
            'translations',
            'currencies',
            'settings',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'personal_access_tokens',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            $table->string('referral_code')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->string('user_type')->default('customer');
            $table->boolean('banned')->default(false);
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedInteger('club_points')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('symbol')->default('$');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('lang', 10);
            $table->string('lang_key');
            $table->text('lang_value')->nullable();
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->boolean('published')->default(true);
            $table->boolean('approval')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('refund_policy_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->string('thumbnail_img')->nullable();
            $table->decimal('lowest_price', 12, 2)->default(0);
            $table->decimal('highest_price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(10);
            $table->decimal('earn_point', 8, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->boolean('digital')->default(false);
            $table->boolean('published')->default(true);
            $table->boolean('approved')->default(true);
            $table->timestamps();
        });

        Schema::create('refund_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('refund_window_days')->default(7);
            $table->text('allowed_order_statuses')->nullable();
            $table->boolean('allow_partial_refund')->default(false);
            $table->boolean('refund_shipping_fee')->default(false);
            $table->boolean('requires_admin_approval')->default(true);
            $table->boolean('requires_reason')->default(false);
            $table->boolean('requires_evidence')->default(false);
            $table->boolean('exclude_opened_items')->default(false);
            $table->boolean('exclude_digital_products')->default(false);
            $table->boolean('exclude_discounted_products')->default(false);
            $table->string('refund_method_type')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('product_taxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('tax_type')->default('flat');
            $table->decimal('tax', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_value_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attribute_value_id');
            $table->string('lang', 10);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('product_variations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('variation_key')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('current_stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variation_combinations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_variation_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();
        });

        Schema::create('combined_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('combined_order_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('code')->nullable();
            $table->string('payment_status')->default('paid');
            $table->string('delivery_status')->default('delivered');
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->string('refund_status')->nullable();
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('payment_type')->nullable();
            $table->boolean('manual_payment')->default(false);
            $table->text('manual_payment_data')->nullable();
            $table->string('delivery_type')->nullable();
            $table->string('type_of_delivery')->nullable();
            $table->unsignedBigInteger('pickup_point_id')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('delivery_history_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_updates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('reasons')->nullable();
            $table->text('refund_note')->nullable();
            $table->text('attachments')->nullable();
            $table->unsignedTinyInteger('admin_approval')->default(0);
            $table->unsignedTinyInteger('seller_approval')->default(0);
            $table->string('status')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('policy_snapshot')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_request_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('refund_request_id');
            $table->unsignedBigInteger('order_detail_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('applied_refund_policy_id')->nullable();
            $table->unsignedInteger('quantity_requested')->nullable();
            $table->unsignedInteger('quantity_approved')->nullable();
            $table->string('item_status')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_details')->nullable();
            $table->string('details')->nullable();
            $table->string('type')->nullable();
            $table->string('reciept')->nullable();
            $table->boolean('approval')->default(true);
            $table->timestamps();
        });

        Schema::create('club_points', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('points')->default(0);
            $table->unsignedBigInteger('combined_order_id')->nullable();
            $table->unsignedTinyInteger('convert_status')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->text('informations')->nullable();
            $table->string('paypal_email')->nullable();
            $table->text('bank_information')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('affiliate_user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_details')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_withdraw_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_options', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_stats', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('affiliate_user_id');
            $table->unsignedInteger('no_of_click')->default(0);
            $table->unsignedInteger('no_of_order_item')->default(0);
            $table->unsignedInteger('no_of_delivered')->default(0);
            $table->unsignedInteger('no_of_cancel')->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('referred_by_user_id');
            $table->unsignedBigInteger('order_detail_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('affiliate_type')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    private function seedBenefitSettings(): void
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            ['type' => 'system_default_currency', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'no_of_decimals', 'value' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'symbol_format', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'wallet_system', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'club_point', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'club_point_convert_rate', 'value' => '2', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'affiliate_system', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'refund_request_order_status', 'value' => json_encode(['delivered']), 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'refund_request_time_period', 'value' => '7', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('affiliate_options')->insert([
            ['type' => 'product_sharing', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category_wise_affiliate', 'status' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Cache::flush();
    }

    private function createUser(array $attributes = []): User
    {
        static $emailCounter = 1;
        static $phoneCounter = 1;

        $email = $attributes['email'] ?? 'user' . $emailCounter++ . '@example.com';
        $phone = $attributes['phone'] ?? '+2348000000' . str_pad((string) $phoneCounter++, 3, '0', STR_PAD_LEFT);

        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('secret123'),
            'user_type' => 'customer',
            'banned' => false,
            'balance' => 0,
            'club_points' => 0,
        ], $attributes));
    }

    private function seedShop(User $user): int
    {
        return (int) DB::table('shops')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Benefit Shop',
            'slug' => 'benefit-shop-' . $user->id,
            'published' => 1,
            'approval' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedProduct(int $shopId): int
    {
        return $this->seedProductWithPolicy($shopId);
    }

    private function seedProductWithPolicy(int $shopId, ?int $refundPolicyId = null, array $attributes = []): int
    {
        $productId = (int) DB::table('products')->insertGetId([
            'shop_id' => $shopId,
            'refund_policy_id' => $refundPolicyId,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt-' . $shopId,
            'lowest_price' => 40,
            'highest_price' => 40,
            'stock' => 20,
            'min_qty' => 1,
            'max_qty' => 5,
            'discount' => 0,
            'digital' => 0,
            'published' => 1,
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ] + []);

        if ($attributes !== []) {
            DB::table('products')
                ->where('id', $productId)
                ->update($attributes);
        }

        DB::table('product_translations')->insert([
            'product_id' => $productId,
            'lang' => 'en',
            'name' => 'Linen Shirt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_taxes')->insert([
            'product_id' => $productId,
            'tax_type' => 'flat',
            'tax' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $productId;
    }

    private function seedRefundPolicy(array $attributes = []): int
    {
        $name = $attributes['name'] ?? 'Standard Refund Policy';
        $code = $attributes['code'] ?? 'standard-refund-' . str_replace('.', '', (string) microtime(true));

        return (int) DB::table('refund_policies')->insertGetId([
            'name' => $name,
            'code' => $code,
            'description' => $attributes['description'] ?? 'Default product refund policy',
            'is_active' => $attributes['is_active'] ?? 1,
            'refund_window_days' => $attributes['refund_window_days'] ?? 7,
            'allowed_order_statuses' => json_encode($attributes['allowed_order_statuses'] ?? ['delivered']),
            'allow_partial_refund' => $attributes['allow_partial_refund'] ?? 1,
            'refund_shipping_fee' => $attributes['refund_shipping_fee'] ?? 0,
            'requires_admin_approval' => $attributes['requires_admin_approval'] ?? 1,
            'requires_reason' => $attributes['requires_reason'] ?? 0,
            'requires_evidence' => $attributes['requires_evidence'] ?? 0,
            'exclude_opened_items' => $attributes['exclude_opened_items'] ?? 0,
            'exclude_digital_products' => $attributes['exclude_digital_products'] ?? 0,
            'exclude_discounted_products' => $attributes['exclude_discounted_products'] ?? 0,
            'refund_method_type' => $attributes['refund_method_type'] ?? 'original_method',
            'internal_notes' => $attributes['internal_notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedEligibleOrder(User $user, string $combinedCode = 'COMBINED-REFUND', array $options = []): array
    {
        $shopId = $this->seedShop($user);
        $refundPolicyId = array_key_exists('refund_policy_id', $options)
            ? $options['refund_policy_id']
            : $this->seedRefundPolicy($options['policy'] ?? []);
        $productId = $this->seedProductWithPolicy($shopId, $refundPolicyId, $options['product'] ?? []);

        $combinedOrderId = (int) DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => $combinedCode,
            'grand_total' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = (int) DB::table('orders')->insertGetId([
            'combined_order_id' => $combinedOrderId,
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'code' => 'ORDER-' . $combinedCode,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'grand_total' => 45,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'delivery_history_date' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        if (!empty($options['order'])) {
            DB::table('orders')
                ->where('id', $orderId)
                ->update($options['order']);
        }

        $orderDetailId = (int) DB::table('order_details')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => 1,
            'price' => 40,
            'tax' => 5,
            'total' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($options['order_detail'])) {
            DB::table('order_details')
                ->where('id', $orderDetailId)
                ->update($options['order_detail']);
        }

        return [$orderDetailId, $orderId, $productId, $refundPolicyId, $shopId];
    }

    private function seedRefundRequestGraph(User $user, string $combinedCode = 'COMBINED-REFUND'): int
    {
        [$orderDetailId, $orderId, $productId, $policyId, $shopId] = $this->seedEligibleOrder($user, $combinedCode);

        $refundId = (int) DB::table('refund_requests')->insertGetId([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'amount' => 45,
            'reasons' => json_encode(['Damaged']),
            'refund_note' => 'Damaged item',
            'admin_approval' => 0,
            'seller_approval' => 0,
            'status' => 'pending',
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('refund_request_items')->insert([
            'refund_request_id' => $refundId,
            'order_detail_id' => $orderDetailId,
            'quantity' => 1,
            'product_id' => $productId,
            'applied_refund_policy_id' => $policyId,
            'quantity_requested' => 1,
            'item_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $refundId;
    }

    private function seedPendingRefundRequestForOrder(
        User $user,
        int $orderId,
        int $orderDetailId,
        int $productId,
        int $policyId,
        int $shopId,
        array $attributes = []
    ): array {
        $refundId = (int) DB::table('refund_requests')->insertGetId([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'shop_id' => $shopId,
            'amount' => 45,
            'reasons' => json_encode(['Damaged']),
            'refund_note' => 'Pending admin review.',
            'admin_approval' => 0,
            'seller_approval' => 0,
            'status' => 'pending',
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($attributes['request'])) {
            DB::table('refund_requests')
                ->where('id', $refundId)
                ->update($attributes['request']);
        }

        $refundItemId = (int) DB::table('refund_request_items')->insertGetId([
            'refund_request_id' => $refundId,
            'order_detail_id' => $orderDetailId,
            'quantity' => 1,
            'product_id' => $productId,
            'applied_refund_policy_id' => $policyId,
            'quantity_requested' => 1,
            'item_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($attributes['item'])) {
            DB::table('refund_request_items')
                ->where('id', $refundItemId)
                ->update($attributes['item']);
        }

        return [$refundId, $refundItemId];
    }

    private function seedWalletEntry(User $user, float $amount, string $details, string $type): void
    {
        DB::table('wallets')->insert([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => 'manual',
            'payment_details' => $details,
            'details' => $details,
            'type' => $type,
            'approval' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedClubPoint(User $user, int $points, string $paymentStatus = 'paid'): int
    {
        $combinedOrderId = (int) DB::table('combined_orders')->insertGetId([
            'user_id' => $user->id,
            'code' => 'CLUB-' . $user->id . '-' . $points,
            'grand_total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'combined_order_id' => $combinedOrderId,
            'user_id' => $user->id,
            'shop_id' => $this->seedShop($user),
            'code' => 'CLUB-ORDER-' . $points,
            'payment_status' => $paymentStatus,
            'delivery_status' => 'delivered',
            'grand_total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('club_points')->insertGetId([
            'user_id' => $user->id,
            'points' => $points,
            'combined_order_id' => $combinedOrderId,
            'convert_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAffiliateProfile(User $user, float $balance): int
    {
        return (int) DB::table('affiliate_users')->insertGetId([
            'user_id' => $user->id,
            'balance' => $balance,
            'status' => 1,
            'informations' => json_encode(['name' => $user->name]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAffiliateEarningOrder(User $user): int
    {
        [$orderDetailId] = $this->seedEligibleOrder($user, 'AFFILIATE-COMBINED');

        return $orderDetailId;
    }
}
