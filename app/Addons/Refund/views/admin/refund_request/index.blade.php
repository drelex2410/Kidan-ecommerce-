@extends('backend.layouts.app')
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form id="sort_refund_requests" action="" method="GET">
                    <div class="card-header row gutters-5">
                        <div class="col text-center text-md-left">
                            <h5 class="mb-md-0 h6">{{ translate('Refund Requests') }}</h5>
                        </div>
                        @if (addon_is_activated('multi_vendor'))
                            <div class="col-md-2 ml-auto">
                                <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0"
                                    name="shop_id" onchange="sort_refund_requests()" data-selected="{{ $shop_id }}">
                                    <option value="">{{ translate('Choose Shop') }}</option>
                                    @foreach (\App\Models\Shop::with('user')->get() as $shop)
                                        <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-xl-2 col-md-3">
                            <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0"
                                name="status" onchange="sort_refund_requests()" data-selected="{{ $status }}">
                                <option value="">{{ translate('All statuses') }}</option>
                                @foreach (\App\Models\RefundRequest::WORKFLOW_STATUSES as $workflowStatus)
                                    <option value="{{ $workflowStatus }}">
                                        {{ ucwords(str_replace('_', ' ', $workflowStatus)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search"
                                    @isset($sort_search) value="{{ $sort_search }}" @endisset
                                    placeholder="{{ translate('Type Order code & hit Enter') }}">
                            </div>
                        </div>
                    </div>
                </form>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table aiz-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ translate('Customer') }}</th>
                                    <th>{{ translate('Order') }}</th>
                                    <th>{{ translate('Items') }}</th>
                                    <th>{{ translate('Policy') }}</th>
                                    <th>{{ translate('Requested Qty') }}</th>
                                    <th>{{ translate('Approved Qty') }}</th>
                                    <th>{{ translate('Amount') }}</th>
                                    @if (addon_is_activated('multi_vendor'))
                                        <th>{{ translate('Seller') }}</th>
                                    @endif
                                    <th>{{ translate('Workflow') }}</th>
                                    <th class="text-right">{{ translate('Options') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($refund_requests as $key => $refund_request)
                                    <tr>
                                        <td>{{ $key + 1 + ($refund_requests->currentPage() - 1) * $refund_requests->perPage() }}</td>
                                        <td>
                                            <div class="fw-600">{{ $refund_request->user->name ?? translate('Guest') }}</div>
                                            @if ($refund_request->user?->email)
                                                <small class="text-muted">{{ $refund_request->user->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('orders.show', $refund_request->order_id) }}">
                                                {{ $refund_request->order->combined_order->code ?? '#' . $refund_request->order_id }}
                                            </a>
                                            <div class="text-muted fs-12">
                                                {{ optional($refund_request->requested_at)->format('d M Y, h:i A') }}
                                            </div>
                                        </td>
                                        <td style="min-width: 300px;">
                                            @foreach ($refund_request->refundRequestItems as $refundRequestItem)
                                                @php
                                                    $orderDetail = $refundRequestItem->orderDetail;
                                                    $product = $refundRequestItem->product ?? $orderDetail?->product;
                                                @endphp
                                                <div class="media mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                                                    <img src="{{ uploaded_asset($product->thumbnail_img ?? '') }}"
                                                        class="size-60px mr-3">
                                                    <div class="media-body">
                                                        <div class="fw-600">
                                                            {{ $product->name ?? translate('Product unavailable') }}
                                                        </div>
                                                        <small class="text-muted">
                                                            {{ translate('Item Status') }}:
                                                            {{ $refundRequestItem->workflowStatusLabel() }}
                                                        </small>
                                                        @if ($orderDetail?->variation)
                                                            <div class="mt-1">
                                                                @foreach ($orderDetail->variation->combinations as $combination)
                                                                    <span class="mr-2">
                                                                        <span class="opacity-50">{{ $combination->attribute->name }}</span>:
                                                                        {{ $combination->attribute_value->name ?? '' }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @php
                                                $policyNames = $refund_request->refundRequestItems
                                                    ->map(fn ($item) => $item->appliedRefundPolicy?->name)
                                                    ->filter()
                                                    ->unique()
                                                    ->values();
                                            @endphp
                                            @forelse ($policyNames as $policyName)
                                                <div>{{ $policyName }}</div>
                                            @empty
                                                <span class="text-muted">{{ translate('No policy') }}</span>
                                            @endforelse
                                        </td>
                                        <td>{{ $refund_request->refundRequestItems->sum(fn ($item) => $item->requestedQuantity()) }}</td>
                                        <td>{{ $refund_request->refundRequestItems->sum(fn ($item) => $item->approvedQuantity()) }}</td>
                                        <td>
                                            <div>{{ format_price($refund_request->amount) }}</div>
                                            @if ($refund_request->payment_type)
                                                <small class="text-muted">{{ ucfirst($refund_request->payment_type) }}</small>
                                            @endif
                                        </td>
                                        @if (addon_is_activated('multi_vendor'))
                                            <td>
                                                @if ($refund_request->shop)
                                                    <div>{{ $refund_request->shop->name }}</div>
                                                @endif
                                                <span class="badge badge-inline {{ $refund_request->sellerStatusBadgeClass() }}">
                                                    {{ $refund_request->sellerStatusLabel() }}
                                                </span>
                                            </td>
                                        @endif
                                        <td>
                                            <span class="badge badge-inline {{ $refund_request->workflowStatusBadgeClass() }}">
                                                {{ $refund_request->workflowStatusLabel() }}
                                            </span>
                                            @if ($refund_request->reviewedBy)
                                                <div class="fs-12 text-muted mt-1">
                                                    {{ translate('By') }} {{ $refund_request->reviewedBy->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                                onclick="show_refund_request_info('{{ $refund_request->id }}');"
                                                title="{{ translate('Refund Request Info') }}" href="javascript:void(0)">
                                                <i class="las la-eye"></i>
                                            </a>
                                            @if (!$refund_request->isWorkflowFinal())
                                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                                    onclick="review_refund_request('{{ $refund_request->id }}');"
                                                    title="{{ translate('Review Refund Request') }}"
                                                    href="javascript:void(0)">
                                                    <i class="las la-clipboard-check"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ addon_is_activated('multi_vendor') ? 11 : 10 }}" class="text-center">
                                            {{ translate('No refund requests found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="aiz-pagination aiz-pagination-center">
                        {{ $refund_requests->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    <div class="modal fade" id="refund_request_info_modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" id="refund-request-info-modal-content"></div>
        </div>
    </div>

    <div class="modal fade" id="refund_request_review_modal">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" id="refund-request-review-modal-content"></div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function sort_refund_requests() {
            $('#sort_refund_requests').submit();
        }

        function show_refund_request_info(id) {
            $.post('{{ route('admin.refund_request.view_details') }}', {
                _token: '{{ @csrf_token() }}',
                id: id
            }, function(data) {
                $('#refund-request-info-modal-content').html(data);
                $('#refund_request_info_modal').modal('show', {
                    backdrop: 'static'
                });
            });
        }

        function review_refund_request(id) {
            $.post('{{ route('admin.refund_request.review') }}', {
                _token: '{{ @csrf_token() }}',
                id: id
            }, function(data) {
                $('#refund-request-review-modal-content').html(data);
                $('#refund_request_review_modal').modal('show', {
                    backdrop: 'static'
                });
            });
        }
    </script>
@endsection
