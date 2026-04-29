<?php

namespace App\Addons\Refund\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class RefundPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_refund_policies'])->only('index');
        $this->middleware(['permission:add_refund_policies'])->only('create', 'store');
        $this->middleware(['permission:edit_refund_policies'])->only('edit', 'update', 'updateStatus');
    }

    public function index(Request $request)
    {
        $sortSearch = $request->input('search');
        $status = $request->input('status');

        $refundPolicies = RefundPolicy::query()
            ->when($sortSearch, function ($query, $sortSearch) {
                $query->where(function ($builder) use ($sortSearch) {
                    $builder
                        ->where('name', 'like', '%' . $sortSearch . '%')
                        ->orWhere('code', 'like', '%' . $sortSearch . '%')
                        ->orWhere('refund_method_type', 'like', '%' . $sortSearch . '%');
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', (int) $status);
            })
            ->latest()
            ->paginate(15);

        return view('addon:refund::admin.refund_policy.index', [
            'refundPolicies' => $refundPolicies,
            'sort_search' => $sortSearch,
            'status' => $status,
        ]);
    }

    public function create()
    {
        return view('addon:refund::admin.refund_policy.create', [
            'refundPolicy' => new RefundPolicy([
                'is_active' => true,
                'refund_window_days' => 30,
                'allowed_order_statuses' => ['delivered'],
                'allow_partial_refund' => true,
                'refund_shipping_fee' => false,
                'requires_admin_approval' => true,
                'requires_reason' => true,
                'requires_evidence' => false,
                'exclude_opened_items' => false,
                'exclude_digital_products' => true,
                'exclude_discounted_products' => false,
                'refund_method_type' => 'manual',
            ]),
            'orderStatusOptions' => $this->orderStatusOptions(),
            'refundMethodTypes' => $this->refundMethodTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        $validated['code'] = $this->generateUniqueCode($validated['code'] ?? null, $validated['name']);

        RefundPolicy::create($validated);

        flash(translate('Refund policy has been created successfully'))->success();

        return redirect()->route('admin.refund_policies.index');
    }

    public function edit($id)
    {
        return view('addon:refund::admin.refund_policy.edit', [
            'refundPolicy' => RefundPolicy::findOrFail($id),
            'orderStatusOptions' => $this->orderStatusOptions(),
            'refundMethodTypes' => $this->refundMethodTypes(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $refundPolicy = RefundPolicy::findOrFail($id);
        $validated = $this->validatedPayload($request, $refundPolicy->id);
        $validated['code'] = $this->generateUniqueCode(
            $validated['code'] ?? null,
            $validated['name'],
            $refundPolicy->id
        );

        $refundPolicy->update($validated);

        flash(translate('Refund policy has been updated successfully'))->success();

        return redirect()->route('admin.refund_policies.index');
    }

    public function updateStatus(Request $request)
    {
        $refundPolicy = RefundPolicy::findOrFail($request->id);
        $refundPolicy->is_active = $request->boolean('status');

        return $refundPolicy->save() ? 1 : 0;
    }

    protected function validatedPayload(Request $request, ?int $refundPolicyId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('refund_policies', 'code')->ignore($refundPolicyId),
            ],
            'description' => ['nullable', 'string'],
            'refund_window_days' => ['required', 'integer', 'min:0'],
            'allowed_order_statuses' => ['nullable', 'array'],
            'allowed_order_statuses.*' => ['string', Rule::in(array_keys($this->orderStatusOptions()))],
            'refund_method_type' => ['nullable', 'string', Rule::in(array_keys($this->refundMethodTypes()))],
            'internal_notes' => ['nullable', 'string'],
        ]);

        return array_merge($validated, [
            'allowed_order_statuses' => $this->normalizedOrderStatuses($request->input('allowed_order_statuses', [])),
            'is_active' => $request->boolean('is_active'),
            'allow_partial_refund' => $request->boolean('allow_partial_refund'),
            'refund_shipping_fee' => $request->boolean('refund_shipping_fee'),
            'requires_admin_approval' => $request->boolean('requires_admin_approval'),
            'requires_reason' => $request->boolean('requires_reason'),
            'requires_evidence' => $request->boolean('requires_evidence'),
            'exclude_opened_items' => $request->boolean('exclude_opened_items'),
            'exclude_digital_products' => $request->boolean('exclude_digital_products'),
            'exclude_discounted_products' => $request->boolean('exclude_discounted_products'),
        ]);
    }

    protected function generateUniqueCode(?string $submittedCode, string $name, ?int $ignoreId = null): string
    {
        $baseCode = Str::slug($submittedCode ?: $name, '-');
        $baseCode = $baseCode !== '' ? $baseCode : 'refund-policy';
        $code = $baseCode;
        $counter = 1;

        while (
            RefundPolicy::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('code', $code)
                ->exists()
        ) {
            $code = $baseCode . '-' . $counter;
            $counter++;
        }

        return $code;
    }

    protected function normalizedOrderStatuses(array $statuses): array
    {
        $validStatuses = array_keys($this->orderStatusOptions());

        return array_values(array_unique(array_values(array_filter(
            $statuses,
            fn ($status) => in_array($status, $validStatuses, true)
        ))));
    }

    protected function orderStatusOptions(): array
    {
        return [
            'order_placed' => translate('Order placed'),
            'confirmed' => translate('Confirmed'),
            'processed' => translate('Processed'),
            'shipped' => translate('Shipped'),
            'picked_up' => translate('Picked up'),
            'on_the_way' => translate('On the way'),
            'delivered' => translate('Delivered'),
            'cancelled' => translate('Cancelled'),
        ];
    }

    protected function refundMethodTypes(): array
    {
        return [
            'manual' => translate('Manual'),
            'original_method' => translate('Original payment method'),
            'store_credit' => translate('Store credit'),
            'wallet' => translate('Wallet'),
        ];
    }
}
