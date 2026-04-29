@php
    $selectedStatuses = old('allowed_order_statuses', $refundPolicy->allowed_order_statuses ?? []);
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ $submitLabel }}</h5>
    </div>
    <div class="card-body">
        <div class="form-group mb-3">
            <label class="form-label">{{ translate('Policy Name') }} <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $refundPolicy->name) }}"
                placeholder="{{ translate('Standard 30-Day Refund Policy') }}" required>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ translate('Code') }}</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $refundPolicy->code) }}"
                placeholder="{{ translate('standard-30-day-refund-policy') }}">
            <small class="text-muted">{{ translate('Leave blank to generate automatically from the name.') }}</small>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ translate('Description') }}</label>
            <textarea name="description" class="form-control" rows="4"
                placeholder="{{ translate('Explain what this refund policy covers.') }}">{{ old('description', $refundPolicy->description) }}</textarea>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ translate('Refund Window') }} <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" min="0" step="1" name="refund_window_days" class="form-control"
                    value="{{ old('refund_window_days', $refundPolicy->refund_window_days) }}" required>
                <div class="input-group-append">
                    <span class="input-group-text">{{ translate('Days') }}</span>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ translate('Allowed Order Statuses') }}</label>
            <select name="allowed_order_statuses[]" class="form-control aiz-selectpicker" multiple data-live-search="true"
                data-actions-box="true" data-selected-text-format="count" data-container="body">
                @foreach ($orderStatusOptions as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected(in_array($statusValue, $selectedStatuses, true))>
                        {{ $statusLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ translate('Refund Method Type') }}</label>
            <select name="refund_method_type" class="form-control aiz-selectpicker" data-live-search="true">
                @foreach ($refundMethodTypes as $methodValue => $methodLabel)
                    <option value="{{ $methodValue }}"
                        @selected(old('refund_method_type', $refundPolicy->refund_method_type) === $methodValue)>
                        {{ $methodLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">{{ translate('Internal Notes') }}</label>
            <textarea name="internal_notes" class="form-control" rows="4"
                placeholder="{{ translate('Private notes for admins reviewing refund requests.') }}">{{ old('internal_notes', $refundPolicy->internal_notes) }}</textarea>
        </div>

        <div class="border rounded p-3 bg-light">
            <h6 class="mb-3">{{ translate('Policy Rules') }}</h6>
            <div class="row">
                @php
                    $toggleFields = [
                        'is_active' => translate('Policy is active'),
                        'allow_partial_refund' => translate('Allow partial refunds'),
                        'refund_shipping_fee' => translate('Refund shipping fee'),
                        'requires_admin_approval' => translate('Requires admin approval'),
                        'requires_reason' => translate('Refund reason is required'),
                        'requires_evidence' => translate('Evidence/proof is required'),
                        'exclude_opened_items' => translate('Exclude opened or used items'),
                        'exclude_digital_products' => translate('Exclude digital products'),
                        'exclude_discounted_products' => translate('Exclude discounted products'),
                    ];
                @endphp

                @foreach ($toggleFields as $field => $label)
                    <div class="col-md-6">
                        <div class="form-group d-flex justify-content-between align-items-center border rounded px-3 py-2 bg-white">
                            <label class="mb-0" for="{{ $field }}">{{ $label }}</label>
                            <div>
                                <input type="hidden" name="{{ $field }}" value="0">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input id="{{ $field }}" type="checkbox" name="{{ $field }}" value="1"
                                        @checked((bool) old($field, $refundPolicy->{$field}))>
                                    <span></span>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="text-right mt-4">
            <button type="submit" class="btn btn-primary">{{ $buttonLabel }}</button>
        </div>
    </div>
</div>
