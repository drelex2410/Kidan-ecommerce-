<?php

namespace App\Addons\Refund\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionHistory;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderUpdate;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\RefundRequestItem;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payments\AlatPay\AlatPayService;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RefundRequestController extends Controller
{
    public function __construct(private readonly AlatPayService $alatPayService)
    {
        // $this->middleware(['permission:show_commission_log'])->only('commission_history');
    }

    public function refund_settings(){
        return view('addon:refund::admin.refund_settings');
    }

    public function refund_requests(Request $request){
        $sort_search = null;
        $shop_id = null;
        $status = null;
        $refund_requests = $this->refundRequestQuery()->latest();
        if(!addon_is_activated('multi_vendor')){
            $admin = User::where('user_type','admin')->first(); 
            $refund_requests = $refund_requests->where('shop_id',$admin->shop_id);
        }
        if ($request->shop_id) {
            $shop_id = $request->shop_id;
            $refund_requests = $refund_requests->where('shop_id', $shop_id);
        }
        if ($request->has('search') && $request->search) {
            $sort_search = $request->search;

            $refund_requests = $refund_requests->whereHas('order', function ($query) use ($sort_search) {
                $query->WhereHas('combined_order', function ($query) use ($sort_search) {
                    $query->where('code', 'like', '%' . $sort_search . '%');
                });
            });
        }

        if ($request->filled('status') && in_array($request->status, RefundRequest::WORKFLOW_STATUSES, true)) {
            $status = $request->status;
            $refund_requests = $refund_requests->where('status', $status);
        }

        $refund_requests = $refund_requests->paginate(10);
        
        return view('addon:refund::admin.refund_request.index',compact('refund_requests','shop_id','sort_search', 'status'));
    }

    public function refund_request_create($id){
        $order = Order::where('id',$id)->first();
        return view('addon:refund::admin.refund_request.create',compact('order'));
    }

    public function refund_request_store(Request $request){

        if($request->order_detail_ids != null){
            $refund_product_quantity = 0;
            $order = Order::findOrFail($request->order_id);
            $order_product_quantity = $order->orderDetails->sum('quantity');

            $refund_request =  new RefundRequest;
            $refund_request->order_id = $order->id;
            $refund_request->user_id = $order->user_id;
            $refund_request->shop_id = $order->shop_id;
            $refund_request->admin_approval = 1;
            $refund_request->amount = $request->refund_amount;
            $refund_request->reasons = $request->refund_reasons != null ? json_encode($request->refund_reasons) : '[]';
            $refund_request->refund_note = $request->refund_note;
            $refund_request->admin_notes = $request->refund_note;
            $refund_request->attachments = $request->attachments;
            $refund_request->payment_type = $request->payment_type;
            $refund_request->status = RefundRequest::STATUS_PROCESSED;
            $refund_request->requested_at = now();
            $refund_request->reviewed_at = now();
            $refund_request->reviewed_by = auth()->id();
            $refund_request->save();

            foreach($order->orderDetails as $key => $orderDetail){
               if(in_array($orderDetail->id,$request->order_detail_ids)){

                    $refund_request_item =  new RefundRequestItem;
                    $refund_request_item->refund_request_id = $refund_request->id;
                    $refund_request_item->order_detail_id = $orderDetail->id;
                    $refund_request_item->quantity = $request['quantity_for_'.$orderDetail->id];
                    $refund_request_item->product_id = $orderDetail->product_id;
                    $refund_request_item->applied_refund_policy_id = $orderDetail->product?->refund_policy_id;
                    $refund_request_item->quantity_requested = $request['quantity_for_'.$orderDetail->id];
                    $refund_request_item->quantity_approved = $request['quantity_for_'.$orderDetail->id];
                    $refund_request_item->item_status = RefundRequest::STATUS_PROCESSED;
                    $refund_request_item->save();

                    $refund_product_quantity += $request['quantity_for_'.$orderDetail->id];
               }
            }
            if($request->payment_type == 'wallet'){
                $user = $order->user;
                $user->balance += $request->refund_amount;
                $user->save();

                $wallet = new Wallet;
                $wallet->user_id = $user->id;
                $wallet->amount = $request->refund_amount;
                $wallet->details = 'Refund for Order'.$order->combined_order->code;
                $wallet->save();
            }
            

            $order->refund_status = $refund_product_quantity < $order_product_quantity ? "partially_refunded" : "fully_refunded";
            $order->refund_amount = $request->refund_amount;
            $order->save();

            OrderUpdate::create([
                'order_id' => $order->id,
                'user_id' => auth()->user()->id,
                'note' => 'Refund request created.',
            ]);
            
            flash(translate('Refunded Successfully.'))->success();
            return redirect()->route('orders.show', $order->id);
        }
        else{
            flash(translate('Please Select an item first.'))->warning();
            return back();
        }
    }

    public function refund_request_view_detials(Request $request){
        $refund_request = $this->refundRequestQuery()->findOrFail($request->id);
        return view('addon:refund::refund_request_detail_modal', compact('refund_request'));
    }

    public function refund_request_review(Request $request)
    {
        $refund_request = $this->refundRequestQuery()->findOrFail($request->id);

        return view('addon:refund::admin.refund_request.review_modal', compact('refund_request'));
    }

    public function refund_request_update(Request $request)
    {
        $validated = $request->validate([
            'refund_request_id' => ['required', 'integer', 'exists:refund_requests,id'],
            'status' => ['required', Rule::in(RefundRequest::WORKFLOW_STATUSES)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['nullable', Rule::in(['manual', 'wallet', 'alatpay'])],
            'admin_notes' => ['nullable', 'string'],
            'approved_quantities' => ['nullable', 'array'],
            'approved_quantities.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $refund_request = $this->refundRequestQuery()->findOrFail($validated['refund_request_id']);
        $targetStatus = $validated['status'];

        $this->assertWorkflowTransitionAllowed($refund_request, $targetStatus);

        if ($targetStatus === RefundRequest::STATUS_PROCESSED && empty($validated['payment_type'])) {
            return back()->withErrors([
                'payment_type' => translate('Choose how this refund will be processed.'),
            ]);
        }

        $reviewResult = $this->prepareWorkflowItems($refund_request, $targetStatus, (array) ($validated['approved_quantities'] ?? []));

        if ($reviewResult['approved_quantity'] < 1 && in_array($targetStatus, [
            RefundRequest::STATUS_APPROVED,
            RefundRequest::STATUS_PROCESSED,
        ], true)) {
            return back()->withErrors([
                'approved_quantities' => translate('Approve at least one item quantity before continuing.'),
            ]);
        }

        $amount = $this->resolveRefundAmount(
            $refund_request,
            $reviewResult['approved_items'],
            $validated['amount'] ?? null
        );

        $refund_request->status = $targetStatus;
        $refund_request->admin_notes = $validated['admin_notes'] ?? $refund_request->admin_notes;
        $refund_request->reviewed_at = now();
        $refund_request->reviewed_by = auth()->id();

        if ($targetStatus === RefundRequest::STATUS_REJECTED) {
            $refund_request->admin_approval = 2;
            $refund_request->amount = 0;
        } elseif (in_array($targetStatus, [RefundRequest::STATUS_APPROVED, RefundRequest::STATUS_PROCESSED], true)) {
            $refund_request->admin_approval = 1;
            $refund_request->amount = $amount;
        } else {
            $refund_request->admin_approval = 0;
            $refund_request->amount = $amount;
        }

        if (!empty($validated['payment_type'])) {
            $refund_request->payment_type = $validated['payment_type'];
        }

        $refund_request->save();

        foreach ($reviewResult['approved_items'] as $approvedItem) {
            $approvedItem['item']->quantity_approved = $approvedItem['approved_quantity'];
            $approvedItem['item']->item_status = $this->resolveItemStatusForWorkflow($targetStatus, $approvedItem['approved_quantity']);
            $approvedItem['item']->rejection_reason = in_array($targetStatus, [
                RefundRequest::STATUS_REJECTED,
                RefundRequest::STATUS_CANCELLED,
            ], true)
                ? ($validated['admin_notes'] ?? null)
                : null;
            $approvedItem['item']->save();
        }

        if ($targetStatus === RefundRequest::STATUS_PROCESSED) {
            $this->processRefundPayout($refund_request, $amount, $refund_request->payment_type);
        }

        $this->syncOrderRefundSummary($refund_request->order);
        $this->recordOrderUpdate($refund_request, $targetStatus);

        flash($this->workflowFlashMessage($targetStatus))->success();

        return back();
    }

    public function refund_request_accept(Request $request){
        $request->merge([
            'status' => RefundRequest::STATUS_PROCESSED,
            'payment_type' => $request->button,
            'approved_quantities' => $this->legacyApprovedQuantities($request->refund_request_id),
        ]);

        return $this->refund_request_update($request);
    }
    
    public function refund_request_reject($id){
        $request = request();
        $request->merge([
            'refund_request_id' => $id,
            'status' => RefundRequest::STATUS_REJECTED,
        ]);

        return $this->refund_request_update($request);
    }

    private function refundRequestQuery()
    {
        return RefundRequest::query()->with([
            'user',
            'shop.user',
            'reviewedBy',
            'order.combined_order',
            'refundRequestItems.orderDetail.variation.combinations.attribute.attribute_translations',
            'refundRequestItems.orderDetail.variation.combinations.attribute_value.attribute_value_translations',
            'refundRequestItems.orderDetail.product.product_translations',
            'refundRequestItems.orderDetail.product.taxes',
            'refundRequestItems.orderDetail.product.refundPolicy',
            'refundRequestItems.appliedRefundPolicy',
            'refundRequestItems.product.product_translations',
            'refundRequestItems.product.taxes',
        ]);
    }

    private function assertWorkflowTransitionAllowed(RefundRequest $refundRequest, string $targetStatus): void
    {
        $currentStatus = $refundRequest->workflowStatus();

        if ($currentStatus === $targetStatus) {
            return;
        }

        $allowedTransitions = [
            RefundRequest::STATUS_PENDING => [
                RefundRequest::STATUS_UNDER_REVIEW,
                RefundRequest::STATUS_APPROVED,
                RefundRequest::STATUS_PROCESSED,
                RefundRequest::STATUS_REJECTED,
                RefundRequest::STATUS_CANCELLED,
            ],
            RefundRequest::STATUS_UNDER_REVIEW => [
                RefundRequest::STATUS_APPROVED,
                RefundRequest::STATUS_PROCESSED,
                RefundRequest::STATUS_REJECTED,
                RefundRequest::STATUS_CANCELLED,
            ],
            RefundRequest::STATUS_APPROVED => [
                RefundRequest::STATUS_PROCESSED,
                RefundRequest::STATUS_CANCELLED,
            ],
            RefundRequest::STATUS_REJECTED => [],
            RefundRequest::STATUS_PROCESSED => [],
            RefundRequest::STATUS_CANCELLED => [],
        ];

        if (!in_array($targetStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => translate('This refund request can no longer move to the selected status.'),
            ]);
        }
    }

    private function prepareWorkflowItems(RefundRequest $refundRequest, string $targetStatus, array $approvedQuantities): array
    {
        $approvedItems = [];
        $approvedQuantity = 0;

        foreach ($refundRequest->refundRequestItems as $refundRequestItem) {
            $requestedQuantity = $refundRequestItem->requestedQuantity();
            $approvedQuantityForItem = (int) ($approvedQuantities[$refundRequestItem->id] ?? $requestedQuantity);

            if (in_array($targetStatus, [RefundRequest::STATUS_REJECTED, RefundRequest::STATUS_CANCELLED], true)) {
                $approvedQuantityForItem = 0;
            }

            if ($approvedQuantityForItem < 0 || $approvedQuantityForItem > $requestedQuantity) {
                throw ValidationException::withMessages([
                    'approved_quantities' => translate('Approved quantity cannot exceed requested quantity.'),
                ]);
            }

            $approvedItems[] = [
                'item' => $refundRequestItem,
                'requested_quantity' => $requestedQuantity,
                'approved_quantity' => $approvedQuantityForItem,
            ];

            $approvedQuantity += $approvedQuantityForItem;
        }

        return [
            'approved_items' => $approvedItems,
            'approved_quantity' => $approvedQuantity,
        ];
    }

    private function resolveRefundAmount(RefundRequest $refundRequest, array $approvedItems, $amount): float
    {
        if ($amount !== null && $amount !== '') {
            return (float) $amount;
        }

        $computedAmount = collect($approvedItems)->sum(function (array $approvedItem) {
            /** @var RefundRequestItem $item */
            $item = $approvedItem['item'];
            $orderDetail = $item->orderDetail;

            if (!$orderDetail) {
                return 0;
            }

            return ((float) $orderDetail->price + (float) $orderDetail->tax) * (int) $approvedItem['approved_quantity'];
        });

        return $computedAmount > 0 ? (float) $computedAmount : (float) $refundRequest->amount;
    }

    private function resolveItemStatusForWorkflow(string $workflowStatus, int $approvedQuantity): string
    {
        if (in_array($workflowStatus, [RefundRequest::STATUS_REJECTED, RefundRequest::STATUS_CANCELLED], true)) {
            return $workflowStatus;
        }

        if ($workflowStatus === RefundRequest::STATUS_UNDER_REVIEW) {
            return RefundRequest::STATUS_UNDER_REVIEW;
        }

        if ($workflowStatus === RefundRequest::STATUS_PENDING) {
            return RefundRequest::STATUS_PENDING;
        }

        return $approvedQuantity > 0 ? $workflowStatus : RefundRequest::STATUS_REJECTED;
    }

    private function processRefundPayout(RefundRequest $refundRequest, float $amount, ?string $paymentType): void
    {
        $order = $refundRequest->order;

        if ($order && (int) $refundRequest->seller_approval === 1) {
            $seller = $order->shop;
            if ($seller != null) {
                $seller->current_balance -= $amount;
                $seller->save();

                $commission = new CommissionHistory;
                $commission->order_id = $order->id;
                $commission->shop_id = $order->shop->id;
                $commission->seller_earning = $amount;
                $commission->type = 'Deducted';
                $commission->details = format_price($amount).' is Deducted for Order Refund.';
                $commission->save();
            }
        }

        if ($paymentType === 'wallet' && $refundRequest->user) {
            $user = $refundRequest->user;
            $user->balance += $amount;
            $user->save();

            $wallet = new Wallet;
            $wallet->user_id = $user->id;
            $wallet->amount = $amount;
            $wallet->details = 'Refund for Order '.optional($order?->combined_order)->code;
            $wallet->save();
        }

        if ($paymentType === 'alatpay') {
            $this->processAlatPayRefund($refundRequest, $amount);
        }
    }

    private function syncOrderRefundSummary(?Order $order): void
    {
        if (!$order) {
            return;
        }

        $approvedStatuses = [
            RefundRequest::STATUS_APPROVED,
            RefundRequest::STATUS_PROCESSED,
        ];

        $processedRequests = $order->refundRequests()
            ->with('refundRequestItems')
            ->get()
            ->filter(fn (RefundRequest $refundRequest) => in_array($refundRequest->workflowStatus(), $approvedStatuses, true));

        $refundedQuantity = $processedRequests->sum(function (RefundRequest $refundRequest) {
            return $refundRequest->refundRequestItems->sum(function (RefundRequestItem $refundRequestItem) {
                return $refundRequestItem->approvedQuantity();
            });
        });

        $approvedAmount = (float) $processedRequests->sum('amount');
        $orderQuantity = (int) $order->orderDetails()->sum('quantity');

        if ($refundedQuantity < 1) {
            $order->refund_status = null;
            $order->refund_amount = 0;
        } else {
            $order->refund_status = $refundedQuantity < $orderQuantity ? 'partially_refunded' : 'fully_refunded';
            $order->refund_amount = $approvedAmount;
        }

        $order->save();
    }

    private function recordOrderUpdate(RefundRequest $refundRequest, string $status): void
    {
        if (!$refundRequest->order) {
            return;
        }

        $notes = [
            RefundRequest::STATUS_PENDING => 'Refund request moved back to pending.',
            RefundRequest::STATUS_UNDER_REVIEW => 'Refund request marked under review.',
            RefundRequest::STATUS_APPROVED => 'Refund request approved.',
            RefundRequest::STATUS_REJECTED => 'Refund request rejected.',
            RefundRequest::STATUS_PROCESSED => 'Refund request processed.',
            RefundRequest::STATUS_CANCELLED => 'Refund request cancelled.',
        ];

        OrderUpdate::create([
            'order_id' => $refundRequest->order->id,
            'user_id' => auth()->user()->id,
            'note' => $notes[$status] ?? 'Refund request updated.',
        ]);
    }

    private function workflowFlashMessage(string $status): string
    {
        return match ($status) {
            RefundRequest::STATUS_UNDER_REVIEW => translate('Refund request moved to under review.'),
            RefundRequest::STATUS_APPROVED => translate('Refund request approved successfully.'),
            RefundRequest::STATUS_REJECTED => translate('Refund request rejected successfully.'),
            RefundRequest::STATUS_PROCESSED => translate('Refund request processed successfully.'),
            RefundRequest::STATUS_CANCELLED => translate('Refund request cancelled successfully.'),
            default => translate('Refund request updated successfully.'),
        };
    }

    private function legacyApprovedQuantities($refundRequestId): array
    {
        $refundRequest = RefundRequest::query()
            ->with('refundRequestItems')
            ->findOrFail($refundRequestId);

        $quantities = [];

        foreach ($refundRequest->refundRequestItems as $refundRequestItem) {
            $quantities[$refundRequestItem->id] = $refundRequestItem->requestedQuantity();
        }

        return $quantities;
    }

    private function processAlatPayRefund(RefundRequest $refundRequest, float $amount): void
    {
        $order = $refundRequest->order;
        $combinedOrderId = $order?->combined_order?->id ?? $order?->combined_order_id;

        $payment = Payment::query()
            ->where('gateway', 'alatpay')
            ->where('status', 'paid')
            ->when($combinedOrderId, fn ($query) => $query->where('combined_order_id', $combinedOrderId))
            ->latest('id')
            ->first();

        if (!$payment) {
            throw ValidationException::withMessages([
                'payment_type' => translate('No successful ALATPay payment was found for this order.'),
            ]);
        }

        $transaction = $payment->alatPayTransactions()
            ->where('status', 'successful')
            ->latest('id')
            ->first();

        if (!$transaction) {
            throw ValidationException::withMessages([
                'payment_type' => translate('The ALATPay transaction history for this order is incomplete.'),
            ]);
        }

        $this->alatPayService->requestRefund(
            $transaction,
            $amount,
            $refundRequest->refund_note,
            $refundRequest->id,
            auth()->id(),
            [
                'refund_request_id' => $refundRequest->id,
                'reviewed_by' => auth()->id(),
                'order_id' => $refundRequest->order_id,
            ]
        );
    }
}
