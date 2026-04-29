<div class="modal-header">
    <h5 class="modal-title h6">{{ translate('Refund Request Information') }}</h5>
    <button type="button" class="close" data-dismiss="modal"></button>
</div>
<div class="modal-body">
    @php
        $refund_reasons = json_decode($refund_request->reasons ?? '[]', true) ?: [];
    @endphp

    <div class="row gutters-10 mb-3">
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3">{{ translate('Request Summary') }}</h6>
                <div class="mb-2"><strong>{{ translate('Customer') }}:</strong> {{ $refund_request->user->name ?? translate('Guest') }}</div>
                <div class="mb-2"><strong>{{ translate('Email') }}:</strong> {{ $refund_request->user->email ?? '-' }}</div>
                <div class="mb-2"><strong>{{ translate('Order') }}:</strong> {{ $refund_request->order->combined_order->code ?? '#' . $refund_request->order_id }}</div>
                <div class="mb-2"><strong>{{ translate('Workflow') }}:</strong> {{ $refund_request->workflowStatusLabel() }}</div>
                <div class="mb-2"><strong>{{ translate('Seller Review') }}:</strong> {{ $refund_request->sellerStatusLabel() }}</div>
                <div class="mb-2"><strong>{{ translate('Requested At') }}:</strong> {{ optional($refund_request->requested_at)->format('d M Y, h:i A') }}</div>
                <div class="mb-0"><strong>{{ translate('Reviewed At') }}:</strong> {{ optional($refund_request->reviewed_at)->format('d M Y, h:i A') ?? '-' }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3">{{ translate('Decision Notes') }}</h6>
                <div class="mb-2"><strong>{{ translate('Requested Amount') }}:</strong> {{ format_price($refund_request->amount) }}</div>
                <div class="mb-2"><strong>{{ translate('Refund Method') }}:</strong> {{ $refund_request->payment_type ? ucfirst($refund_request->payment_type) : '-' }}</div>
                <div class="mb-2"><strong>{{ translate('Reasons') }}:</strong>
                    @if ($refund_reasons !== [])
                        <div class="mt-1">
                            @foreach ($refund_reasons as $refund_reason)
                                <span class="badge badge-inline badge-soft-secondary mr-1 mb-1">{{ $refund_reason }}</span>
                            @endforeach
                        </div>
                    @else
                        <span>-</span>
                    @endif
                </div>
                <div class="mb-2"><strong>{{ translate('Customer Details') }}:</strong> {{ $refund_request->refund_note ?: '-' }}</div>
                <div class="mb-0"><strong>{{ translate('Admin Notes') }}:</strong> {{ $refund_request->admin_notes ?: '-' }}</div>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ translate('Product') }}</th>
                    <th>{{ translate('Policy') }}</th>
                    <th>{{ translate('Requested Qty') }}</th>
                    <th>{{ translate('Approved Qty') }}</th>
                    <th>{{ translate('Item Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($refund_request->refundRequestItems as $refundRequestItem)
                    @php
                        $orderDetail = $refundRequestItem->orderDetail;
                        $product = $refundRequestItem->product ?? $orderDetail?->product;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-600">{{ $product->name ?? translate('Product unavailable') }}</div>
                            @if ($orderDetail?->variation)
                                <small class="text-muted">
                                    @foreach ($orderDetail->variation->combinations as $combination)
                                        <span class="mr-2">
                                            {{ $combination->attribute->name }}:
                                            {{ $combination->attribute_value->name ?? '' }}
                                        </span>
                                    @endforeach
                                </small>
                            @endif
                        </td>
                        <td>{{ $refundRequestItem->appliedRefundPolicy->name ?? translate('No policy') }}</td>
                        <td>{{ $refundRequestItem->requestedQuantity() }}</td>
                        <td>{{ $refundRequestItem->approvedQuantity() }}</td>
                        <td>{{ $refundRequestItem->workflowStatusLabel() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>
        <h6 class="mb-3">{{ translate('Evidence') }}</h6>
        @php
            $attachmentIds = array_filter(explode(',', (string) $refund_request->attachments));
        @endphp
        @if ($attachmentIds === [])
            <div class="alert alert-secondary mb-0">{{ translate('No evidence uploaded.') }}</div>
        @else
            @foreach ($attachmentIds as $attachment_id)
                @php $attachment = \App\Models\Upload::find($attachment_id); @endphp
                @if ($attachment)
                    <div class="d-flex justify-content-between align-items-center mt-2 file-preview-item"
                        title="{{ $attachment->file_name }}">
                        <a href="{{ route('download_attachment', $attachment->id) }}" target="_blank"
                            class="d-block text-reset">
                            <div class="align-items-center align-self-stretch d-flex justify-content-center thumb">
                                @if ($attachment->type == 'image')
                                    <img src="{{ my_asset($attachment->file_name) }}" class="img-fit">
                                @else
                                    <i class="la la-file-text"></i>
                                @endif
                            </div>
                            <div class="col body">
                                <h6 class="d-flex">
                                    <span class="text-truncate title">{{ $attachment->file_original_name }}</span>
                                    <span class="ext flex-shrink-0">.{{ $attachment->extension }}</span>
                                </h6>
                                <p>{{ formatBytes($attachment->file_size) }}</p>
                            </div>
                        </a>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>
