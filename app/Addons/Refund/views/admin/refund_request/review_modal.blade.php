<div class="modal-header">
    <h5 class="modal-title h6">
        {{ translate('Review Refund Request') }}
        <span class="ml-2 badge badge-inline {{ $refund_request->workflowStatusBadgeClass() }}">
            {{ $refund_request->workflowStatusLabel() }}
        </span>
    </h5>
    <button type="button" class="close" data-dismiss="modal"></button>
</div>
<form action="{{ route('admin.refund_request.update') }}" method="POST">
    @csrf
    <input type="hidden" name="refund_request_id" value="{{ $refund_request->id }}">

    <div class="modal-body">
        @php
            $refundReasons = json_decode($refund_request->reasons ?? '[]', true) ?: [];
        @endphp

        <div class="row gutters-10 mb-3">
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-3">{{ translate('Customer & Order') }}</h6>
                    <div class="mb-2"><strong>{{ translate('Customer') }}:</strong> {{ $refund_request->user->name ?? translate('Guest') }}</div>
                    <div class="mb-2"><strong>{{ translate('Email') }}:</strong> {{ $refund_request->user->email ?? '-' }}</div>
                    <div class="mb-2"><strong>{{ translate('Order') }}:</strong> {{ $refund_request->order->combined_order->code ?? '#' . $refund_request->order_id }}</div>
                    <div class="mb-0"><strong>{{ translate('Seller Review') }}:</strong> {{ $refund_request->sellerStatusLabel() }}</div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-3">{{ translate('Customer Request') }}</h6>
                    <div class="mb-2"><strong>{{ translate('Reasons') }}:</strong>
                        @if ($refundReasons !== [])
                            <div class="mt-1">
                                @foreach ($refundReasons as $refundReason)
                                    <span class="badge badge-inline badge-soft-secondary mr-1 mb-1">{{ $refundReason }}</span>
                                @endforeach
                            </div>
                        @else
                            <span>-</span>
                        @endif
                    </div>
                    <div class="mb-2"><strong>{{ translate('Details') }}:</strong> {{ $refund_request->refund_note ?: '-' }}</div>
                    <div class="mb-0"><strong>{{ translate('Evidence') }}:</strong>
                        {{ $refund_request->attachments ? translate('Attached below') : translate('No evidence uploaded') }}
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-3">{{ translate('Admin Review') }}</h6>
                    <div class="form-group mb-3">
                        <label class="mb-1">{{ translate('Refund Amount') }}</label>
                        <input type="number" lang="en" min="0" step="0.01" name="amount"
                            value="{{ old('amount', $refund_request->amount) }}" class="form-control">
                        <small class="text-muted">
                            {{ translate('Leave as-is to use the current request amount, or change it if shipping/manual adjustments are needed.') }}
                        </small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="mb-1">{{ translate('Refund Method') }}</label>
                        <select name="payment_type" class="form-control aiz-selectpicker">
                            <option value="">{{ translate('Choose method when processing') }}</option>
                            <option value="manual" @selected(old('payment_type', $refund_request->payment_type) === 'manual')>
                                {{ translate('Pay Manually') }}
                            </option>
                            <option value="wallet" @selected(old('payment_type', $refund_request->payment_type) === 'wallet')>
                                {{ translate('Pay in Wallet') }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="mb-1">{{ translate('Admin Notes') }}</label>
                        <textarea name="admin_notes" class="form-control" rows="5"
                            placeholder="{{ translate('Add review notes for the customer, seller, or finance team.') }}">{{ old('admin_notes', $refund_request->admin_notes) }}</textarea>
                    </div>
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
                        <th>{{ translate('Current Item Status') }}</th>
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
                            <td width="140">
                                <input type="number" min="0" step="1" class="form-control"
                                    name="approved_quantities[{{ $refundRequestItem->id }}]"
                                    value="{{ old('approved_quantities.' . $refundRequestItem->id, $refundRequestItem->approvedQuantity() ?: $refundRequestItem->requestedQuantity()) }}">
                            </td>
                            <td>{{ $refundRequestItem->workflowStatusLabel() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php
            $attachmentIds = array_filter(explode(',', (string) $refund_request->attachments));
        @endphp
        @if ($attachmentIds !== [])
            <div class="border rounded p-3">
                <h6 class="mb-3">{{ translate('Evidence') }}</h6>
                <div class="row">
                    @foreach ($attachmentIds as $attachment_id)
                        @php $attachment = \App\Models\Upload::find($attachment_id); @endphp
                        @if ($attachment)
                            <div class="col-lg-6">
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
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="modal-footer flex-wrap justify-content-between">
        <div class="mb-2 mb-md-0">
            @if (in_array($refund_request->workflowStatus(), [
                \App\Models\RefundRequest::STATUS_PENDING,
                \App\Models\RefundRequest::STATUS_UNDER_REVIEW,
            ], true))
                <button type="submit" name="status" value="{{ \App\Models\RefundRequest::STATUS_UNDER_REVIEW }}"
                    class="btn btn-warning">
                    {{ translate('Mark Under Review') }}
                </button>
                <button type="submit" name="status" value="{{ \App\Models\RefundRequest::STATUS_APPROVED }}"
                    class="btn btn-success">
                    {{ translate('Approve Only') }}
                </button>
                <button type="submit" name="status" value="{{ \App\Models\RefundRequest::STATUS_REJECTED }}"
                    class="btn btn-danger">
                    {{ translate('Reject') }}
                </button>
                <button type="submit" name="status" value="{{ \App\Models\RefundRequest::STATUS_CANCELLED }}"
                    class="btn btn-dark">
                    {{ translate('Cancel Request') }}
                </button>
            @elseif ($refund_request->workflowStatus() === \App\Models\RefundRequest::STATUS_APPROVED)
                <button type="submit" name="status" value="{{ \App\Models\RefundRequest::STATUS_CANCELLED }}"
                    class="btn btn-dark">
                    {{ translate('Cancel Request') }}
                </button>
            @endif
        </div>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">{{ translate('Close') }}</button>
            @if (in_array($refund_request->workflowStatus(), [
                \App\Models\RefundRequest::STATUS_PENDING,
                \App\Models\RefundRequest::STATUS_UNDER_REVIEW,
                \App\Models\RefundRequest::STATUS_APPROVED,
            ], true))
                <button type="submit" name="status" value="{{ \App\Models\RefundRequest::STATUS_PROCESSED }}"
                    class="btn btn-primary">
                    {{ translate('Process Refund') }}
                </button>
            @endif
        </div>
    </div>
</form>
