@extends('backend.layouts.app')

@section('content')

    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Cash Payment Activation') }}</h5>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('Activation') }}</label>
                        </div>
                        <div class="col-md-4">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" onchange="updateSettings(this, 'cash_payment')"
                                    @if (get_setting('cash_payment') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    {{-- <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div> --}}
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Paypal --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Paypal Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'paypal_payment')"
                                        @if (get_setting('paypal_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYPAL_CLIENT_ID">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Paypal Client Id') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYPAL_CLIENT_ID"
                                    value="{{ env('PAYPAL_CLIENT_ID') }}"
                                    placeholder="{{ translate('Paypal Client ID') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYPAL_CLIENT_SECRET">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Paypal Client Secret') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYPAL_CLIENT_SECRET"
                                    value="{{ env('PAYPAL_CLIENT_SECRET') }}"
                                    placeholder="{{ translate('Paypal Client Secret') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Paypal Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="paypal_sandbox" type="checkbox"
                                        onchange="updateSettings(this, 'paypal_sandbox')" @if (get_setting('paypal_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sslcommerz --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header ">
                    <h5 class="mb-0 h6">{{ translate('Sslcommerz Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'sslcommerz_payment')"
                                        @if (get_setting('sslcommerz_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="SSLCZ_STORE_ID">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Sslcz Store Id') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="SSLCZ_STORE_ID"
                                    value="{{ env('SSLCZ_STORE_ID') }}"
                                    placeholder="{{ translate('Sslcz Store Id') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="SSLCZ_STORE_PASSWD">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Sslcz store password') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="SSLCZ_STORE_PASSWD"
                                    value="{{ env('SSLCZ_STORE_PASSWD') }}"
                                    placeholder="{{ translate('Sslcz store password') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Sslcommerz Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="sslcommerz_sandbox" type="checkbox"
                                        onchange="updateSettings(this, 'sslcommerz_sandbox')" @if (get_setting('sslcommerz_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Stripe --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Stripe Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'stripe_payment')"
                                        @if (get_setting('stripe_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="STRIPE_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Stripe Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="STRIPE_KEY"
                                    value="{{ env('STRIPE_KEY') }}" placeholder="{{ translate('STRIPE KEY') }}"
                                    required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="STRIPE_SECRET">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Stripe Secret') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="STRIPE_SECRET"
                                    value="{{ env('STRIPE_SECRET') }}" placeholder="{{ translate('STRIPE SECRET') }}"
                                    required>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Razorpay --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Razorpay Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'razorpay_payment')" @if (get_setting('razorpay_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="RAZOR_KEY">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Razorpay Key') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="RAZOR_KEY" value="{{ env('RAZOR_KEY') }}" placeholder="Key">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="RAZOR_SECRET">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Razorpay Secret') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="RAZOR_SECRET" value="{{ env('RAZOR_SECRET') }}" placeholder="secret">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Flutterwave --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Flutterwave Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'flutterwave_payment')"
                                        @if (get_setting('flutterwave_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="FLW_PUBLIC_KEY">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('FLW_PUBLIC_KEY') }}</label>
                            </div>
                            <div class="col-lg-8">
                                <input type="text" class="form-control" name="FLW_PUBLIC_KEY"
                                    value="{{ env('FLW_PUBLIC_KEY') }}"
                                    placeholder="{{ translate('FLW_PUBLIC_KEY') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="FLW_SECRET_KEY">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('FLW_SECRET_KEY') }}</label>
                            </div>
                            <div class="col-lg-8">
                                <input type="text" class="form-control" name="FLW_SECRET_KEY"
                                    value="{{ env('FLW_SECRET_KEY') }}"
                                    placeholder="{{ translate('FLW_SECRET_KEY') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="FLW_SECRET_HASH">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('FLW_ENCRYPTION_KEY') }}</label>
                            </div>
                            <div class="col-lg-8">
                                <input type="text" class="form-control" name="FLW_SECRET_HASH"
                                    value="{{ env('FLW_SECRET_HASH') }}"
                                    placeholder="{{ translate('FLW_ENCRYPTION_KEY') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="FLW_PAYMENT_CURRENCY_CODE">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('FLW_PAYMENT_CURRENCY_CODE') }}</label>
                            </div>
                            <div class="col-lg-8">
                                <input type="text" class="form-control" name="FLW_PAYMENT_CURRENCY_CODE"
                                    value="{{ env('FLW_PAYMENT_CURRENCY_CODE') }}"
                                    placeholder="{{ translate('FLW_PAYMENT_CURRENCY_CODE') }}" required>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- PayStack --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('PayStack Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'paystack_payment')"
                                        @if (get_setting('paystack_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYSTACK_PUBLIC_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('PUBLIC KEY') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYSTACK_PUBLIC_KEY"
                                    value="{{ env('PAYSTACK_PUBLIC_KEY') }}"
                                    placeholder="{{ translate('PUBLIC KEY') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYSTACK_SECRET_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('SECRET KEY') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYSTACK_SECRET_KEY"
                                    value="{{ env('PAYSTACK_SECRET_KEY') }}"
                                    placeholder="{{ translate('SECRET KEY') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="MERCHANT_EMAIL">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('MERCHANT EMAIL') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="MERCHANT_EMAIL"
                                    value="{{ env('MERCHANT_EMAIL') }}"
                                    placeholder="{{ translate('MERCHANT EMAIL') }}" required>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ALATPay --}}
        <div class="col-md-6">
            <div class="card border-primary shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="{{ static_asset('assets/img/cards/alatpay.svg') }}" alt="ALATPay" class="mr-2" style="height: 24px;">
                        <h5 class="mb-0 h6">{{ translate('ALATPay') }}</h5>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-{{ ($alatpaySummary['gateway_status'] ?? 'incomplete') === 'ready' ? 'success' : 'warning' }} mr-2">
                            {{ strtoupper($alatpaySummary['gateway_status'] ?? 'incomplete') }}
                        </span>
                        <span class="badge badge-info">
                            {{ strtoupper($alatpaySummary['environment'] ?? 'sandbox') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('alatpay.settings.update') }}" method="POST">
                        @csrf

                        <div class="alert alert-soft-secondary mb-3">
                            <div><strong>{{ translate('Gateway Status') }}:</strong> {{ translate(($alatpaySummary['configured'] ?? false) ? 'Configured' : 'Credentials incomplete') }}</div>
                            <div><strong>{{ translate('Checkout Flow') }}:</strong>
                                {{ ($alatpaySummary['checkout_flow'] ?? 'virtual_account') === 'web_plugin' ? translate('Web Plugin') : translate('Virtual Account Fallback') }}
                            </div>
                            <div><strong>{{ translate('Last Webhook Received') }}:</strong>
                                {{ !empty($alatpaySummary['last_webhook_received_at']) ? \Carbon\Carbon::parse($alatpaySummary['last_webhook_received_at'])->toDayDateTimeString() : translate('Never') }}
                            </div>
                            <div><strong>{{ translate('Last Successful Transaction') }}:</strong>
                                {{ !empty($alatpaySummary['last_successful_transaction_at']) ? \Carbon\Carbon::parse($alatpaySummary['last_successful_transaction_at'])->toDayDateTimeString() : translate('None yet') }}
                            </div>
                            <div><strong>{{ translate('Web Plugin Ready') }}:</strong>
                                {{ ($alatpaySummary['web_plugin_ready'] ?? false) ? translate('Yes') : translate('No - falls back to virtual account checkout') }}
                            </div>
                        </div>

                        <div class="alert alert-soft-primary mb-3">
                            <strong>{{ translate('Core Web Plugin Setup') }}:</strong>
                            {{ translate('Per the ALATPay web plugin docs, the popup flow mainly needs your Business ID and Public Key. KIDAN now uses the official ALATPay web plugin script automatically, and only keeps the ALATPay Secret Key and Webhook Secret for secure backend confirmation.') }}
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="hidden" name="alatpay_payment" value="0">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" name="alatpay_payment" value="1" onchange="updateSettings(this, 'alatpay_payment')"
                                        @if (get_setting('alatpay_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Environment') }}</label>
                            </div>
                            <div class="col-md-8">
                                <select class="form-control aiz-selectpicker" name="alatpay_env" data-live-search="false">
                                    <option value="sandbox" @selected(($alatpaySettings['env'] ?? 'sandbox') === 'sandbox')>{{ translate('Sandbox') }}</option>
                                    <option value="production" @selected(($alatpaySettings['env'] ?? '') === 'production')>{{ translate('Production') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Business ID') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="alatpay_merchant_id"
                                    value="{{ old('alatpay_merchant_id', $alatpaySettings['merchant_id'] ?? '') }}"
                                    placeholder="{{ translate('Business ID') }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Public Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="alatpay_public_key"
                                    value="{{ old('alatpay_public_key', $alatpaySettings['public_key'] ?? '') }}"
                                    placeholder="{{ translate('ALATPay public key for the web plugin') }}">
                                <small class="text-muted">
                                    {{ translate('Required for the popup checkout flow shown in the ALATPay web plugin docs.') }}
                                </small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Secret Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="alatpay_client_secret"
                                    value="{{ old('alatpay_client_secret', '') }}"
                                    placeholder="{{ ($alatpaySettings['has_client_secret'] ?? false) ? translate('Leave blank to keep existing secret key') : translate('Paste ALATPay Secret Key') }}">
                                <small class="text-muted">
                                    {{ translate('Used by KIDAN to verify ALATPay transactions securely after the popup closes.') }}
                                </small>
                            </div>
                        </div>

                        <details class="mb-3 border rounded p-3"
                            @if (
                                $errors->has('alatpay_client_secret') ||
                                $errors->has('alatpay_base_url') ||
                                $errors->has('alatpay_webhook_secret')
                            ) open @endif>
                            <summary class="font-weight-bold text-dark" style="cursor: pointer;">
                                {{ translate('Optional Advanced Settings') }}
                            </summary>
                            <small class="d-block text-muted mb-3 mt-2">
                                {{ translate('ALATPay\'s dashboard mainly gives you Business ID, Public Key, Secret Key, and Webhook Secret Key. The fields below are optional KIDAN-side overrides, not required popup parameters.') }}
                            </small>

                            <div class="form-group row">
                                <div class="col-md-4">
                                    <label class="col-from-label">{{ translate('Base URL') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="url" class="form-control" name="alatpay_base_url"
                                        value="{{ old('alatpay_base_url', $alatpaySettings['base_url'] ?? '') }}"
                                        placeholder="https://wema-alatdev-apimgt.developer.azure-api.net">
                                    <small class="text-muted">{{ translate('Defaults from the selected environment if you leave this untouched.') }}</small>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-4">
                                    <label class="col-from-label">{{ translate('Webhook Secret') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="alatpay_webhook_secret"
                                        value="{{ old('alatpay_webhook_secret', '') }}"
                                        placeholder="{{ ($alatpaySettings['has_webhook_secret'] ?? false) ? translate('Leave blank to keep existing webhook secret key') : translate('Paste ALATPay Webhook Secret Key') }}">
                                </div>
                            </div>
                        </details>

                        <details class="mb-3 border rounded p-3"
                            @if (
                                $errors->has('alatpay_supported_currencies') ||
                                $errors->has('alatpay_charge_type') ||
                                $errors->has('alatpay_charge_flat') ||
                                $errors->has('alatpay_charge_percent')
                            ) open @endif>
                            <summary class="font-weight-bold text-dark" style="cursor: pointer;">
                                {{ translate('Optional Settlement Settings') }}
                            </summary>
                            <small class="d-block text-muted mb-3 mt-2">
                                {{ translate('These are optional KIDAN-side payment settings. They are not part of the minimum ALATPay web plugin parameters.') }}
                            </small>

                            <div class="form-group row">
                                <div class="col-md-4">
                                    <label class="col-from-label">{{ translate('Currency Support') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="alatpay_supported_currencies"
                                        value="{{ old('alatpay_supported_currencies', $alatpaySettings['supported_currencies'] ?? 'NGN') }}"
                                        placeholder="NGN, USD">
                                    <small class="text-muted">{{ translate('Comma-separated ISO currency codes.') }}</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-4">
                                    <label class="col-from-label">{{ translate('Charge Type') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <select class="form-control aiz-selectpicker" name="alatpay_charge_type" data-live-search="false">
                                        <option value="percentage" @selected(($alatpaySettings['charge_type'] ?? 'percentage') === 'percentage')>{{ translate('Percentage') }}</option>
                                        <option value="flat" @selected(($alatpaySettings['charge_type'] ?? '') === 'flat')>{{ translate('Flat') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-4">
                                    <label class="col-from-label">{{ translate('Flat Charge') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="number" step="0.01" min="0" class="form-control" name="alatpay_charge_flat"
                                        value="{{ old('alatpay_charge_flat', $alatpaySettings['charge_flat'] ?? 0) }}">
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-4">
                                    <label class="col-from-label">{{ translate('Charge Percent') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" name="alatpay_charge_percent"
                                        value="{{ old('alatpay_charge_percent', $alatpaySettings['charge_percent'] ?? 0) }}">
                                </div>
                            </div>
                        </details>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Plugin Script') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" value="https://web.alatpay.ng/js/alatpay.js"
                                    readonly>
                                <small class="text-muted">{{ translate('Built in from the official ALATPay web plugin script you provided.') }}</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Callback URL') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="url" class="form-control" name="alatpay_callback_url"
                                    value="{{ old('alatpay_callback_url', $alatpaySettings['callback_url'] ?? '') }}"
                                    readonly>
                                <small class="text-muted">{{ translate('Copy this into the Webhook URL / callback area inside ALATPay if they ask for it on the dashboard.') }}</small>
                            </div>
                        </div>

                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Authorize Net --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Authorize Net') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'authorizenet_payment')"
                                        @if (get_setting('authorizenet_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="AUTHORIZE_NET_MERCHANT_LOGIN_ID">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Merchant Login ID') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="AUTHORIZE_NET_MERCHANT_LOGIN_ID"
                                    value="{{ env('AUTHORIZE_NET_MERCHANT_LOGIN_ID') }}"
                                    placeholder="{{ translate('Merchant Login ID') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="AUTHORIZE_NET_MERCHANT_TRANSACTION_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Merchant Transaction Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="AUTHORIZE_NET_MERCHANT_TRANSACTION_KEY"
                                    value="{{ env('AUTHORIZE_NET_MERCHANT_TRANSACTION_KEY') }}"
                                    placeholder="{{ translate('Merchant Transaction Key') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Authorize Net Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="authorizenet_sandbox" type="checkbox"
                                        onchange="updateSettings(this, 'authorizenet_sandbox')" @if (get_setting('authorizenet_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Payfast --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Payfast Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'payfast_payment')"
                                        @if (get_setting('payfast_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYFAST_MERCHANT_ID">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Payfast Merchant ID') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYFAST_MERCHANT_ID"
                                    value="{{ env('PAYFAST_MERCHANT_ID') }}"
                                    placeholder="{{ translate('Payfast Merchant ID') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYFAST_MERCHANT_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Payfast Merchant Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYFAST_MERCHANT_KEY"
                                    value="{{ env('PAYFAST_MERCHANT_KEY') }}"
                                    placeholder="{{ translate('Payfast Merchant Key') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Payfast Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="payfast_sandbox" type="checkbox"
                                        onchange="updateSettings(this, 'payfast_sandbox')" @if (get_setting('payfast_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- mercadopago --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Mercadopago Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'mercadopago_payment')"
                                        @if (get_setting('mercadopago_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="MERCADOPAGO_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Mercadopago Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="MERCADOPAGO_KEY"
                                    value="{{ env('MERCADOPAGO_KEY') }}"
                                    placeholder="{{ translate('Mercadopago Key') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="MERCADOPAGO_ACCESS">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Mercadopago Access') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="MERCADOPAGO_ACCESS"
                                    value="{{ env('MERCADOPAGO_ACCESS') }}"
                                    placeholder="{{ translate('Mercadopago Access') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="MERCADOPAGO_CURRENCY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Mercadopago Currency') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="MERCADOPAGO_CURRENCY"
                                    value="{{ env('MERCADOPAGO_CURRENCY') }}"
                                    placeholder="{{ translate('Mercadopago Currency') }}" required>
                                <br>
                                <div class="alert alert-primary" role="alert">
                                    Currency must be <b>es-AR</b> or <b>es-CL</b> or <b>es-CO</b> or <b>es-MX</b> or <b>es-VE</b> or <b>es-UY</b> or <b>es-PE</b> or <b>pt-BR</b><br>
                                    If kept empty, <b>en-US</b> will be used automatically
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Paytm --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Paytm Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'paytm_payment')"
                                        @if (get_setting('paytm_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_ENVIRONMENT">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('PAYTM ENVIRONMENT') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_ENVIRONMENT"
                                    value="{{ env('PAYTM_ENVIRONMENT') }}" placeholder="local or production" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_MERCHANT_ID">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('PAYTM MERCHANT ID') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_MERCHANT_ID"
                                    value="{{ env('PAYTM_MERCHANT_ID') }}" placeholder="PAYTM MERCHANT ID" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_MERCHANT_KEY">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('PAYTM MERCHANT KEY') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_MERCHANT_KEY"
                                    value="{{ env('PAYTM_MERCHANT_KEY') }}" placeholder="PAYTM MERCHANT KEY">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_MERCHANT_WEBSITE">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('PAYTM MERCHANT WEBSITE') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_MERCHANT_WEBSITE"
                                    value="{{ env('PAYTM_MERCHANT_WEBSITE') }}" placeholder="PAYTM MERCHANT WEBSITE">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_CHANNEL">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('PAYTM CHANNEL') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_CHANNEL"
                                    value="{{ env('PAYTM_CHANNEL') }}" placeholder="PAYTM CHANNEL">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_INDUSTRY_TYPE">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('PAYTM INDUSTRY TYPE') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_INDUSTRY_TYPE"
                                    value="{{ env('PAYTM_INDUSTRY_TYPE') }}" placeholder="PAYTM INDUSTRY TYPE">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- Iyzico --}}
    
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Iyzico Credential')}}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" onchange="updateSettings(this, 'iyzico_payment')"
                                        @if (get_setting('iyzico_payment') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="IYZICO_API_KEY">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{translate('IYZICO_API_KEY')}}</label>
                            </div>
                            <div class="col-lg-8">
                                <input type="text" class="form-control" name="IYZICO_API_KEY" value="{{  env('IYZICO_API_KEY') }}" placeholder="{{ translate('IYZICO API KEY') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="IYZICO_SECRET_KEY">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{translate('IYZICO_SECRET_KEY')}}</label>
                            </div>
                            <div class="col-lg-8">
                                <input type="text" class="form-control" name="IYZICO_SECRET_KEY" value="{{  env('IYZICO_SECRET_KEY') }}" placeholder="{{ translate('IYZICO SECRET KEY') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="IYZICO_CURRENCY_CODE">
                            <div class="col-md-4">
                                <label class="col-from-label">{{translate('IYZICO CURRENCY CODE')}}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="IYZICO_CURRENCY_CODE" value="{{  env('IYZICO_CURRENCY_CODE') }}" placeholder="{{ translate('IYZICO CURRENCY CODE') }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{translate('IYZICO Sandbox Mode')}}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="iyzico_sandbox" type="checkbox" @if (get_setting('iyzico_sandbox') == 1)
                                        checked
                                    @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

     {{-- MyFatoorah --}}

     <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6 ">{{ translate('MyFatoorah Credential') }}</h5>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                    @csrf
                    
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('Activation') }}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" onchange="updateSettings(this, 'myfatoorah_payment')"
                                    @if (get_setting('myfatoorah_payment') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="payment_method" value="myfatoorah">
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="MYFATOORAH_TOKEN">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('MYFATOORAH TOKEN') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="MYFATOORAH_TOKEN"
                                value="{{ env('MYFATOORAH_TOKEN') }}"
                                placeholder="{{ translate('MYFATOORAH TOKEN') }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="MYFATOORAH_COUNTRY_ISO">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('MYFATOORAH COUNTRY ISO') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="MYFATOORAH_COUNTRY_ISO"
                                value="{{ env('MYFATOORAH_COUNTRY_ISO') }}"
                                placeholder="{{ translate('MYFATOORAH COUNTRY ISO') }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('Sandbox Mode') }}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input value="1" name="myfatoorah_sandbox" type="checkbox"
                                    @if (get_setting('myfatoorah_sandbox') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
     {{-- Phonepe --}}

     <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6 ">{{ translate('Phonepe Credential') }}</h5>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                    @csrf
                    
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('Activation') }}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" onchange="updateSettings(this, 'phonepe_payment')"
                                    @if (get_setting('phonepe_payment') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="payment_method" value="phonepe">
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="PHONEPE_MERCHANT_ID">
                        <div class="col-lg-4">
                            <label class="col-from-label">{{ translate('Merchant Id') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <input type="text" class="form-control" name="PHONEPE_MERCHANT_ID"
                                value="{{ env('PHONEPE_MERCHANT_ID') }}"
                                placeholder="{{ translate('PHONEPE MERCHANT ID') }}" required>
                        </div>
                    </div>
                
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="PHONEPE_SALT_KEY">
                        <div class="col-lg-4">
                            <label class="col-from-label">{{ translate('PHONEPE_SALT KEY') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <input type="text" class="form-control" name="PHONEPE_SALT_KEY"
                                value="{{ env('PHONEPE_SALT_KEY') }}"
                                placeholder="{{ translate('PHONEPESALT KEY') }}" required>
                        </div>
                    </div>
                
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="PHONEPE_SALT_INDEX">
                        <div class="col-lg-4">
                            <label class="col-from-label">{{ translate('PHONEPE SALT INDEX') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <input type="text" class="form-control" name="PHONEPE_SALT_INDEX"
                                value="{{ env('PHONEPE_SALT_INDEX') }}"
                                placeholder="{{ translate('PHONEPE SALT INDEX') }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('Sandbox Mode') }}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input value="1" name="phonepe_sandbox" type="checkbox"
                                    @if (get_setting('phonepe_sandbox') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
     {{-- Payhere --}}

     <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6 ">{{ translate('Payhere Credential') }}</h5>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                    @csrf
                    
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('Activation') }}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" onchange="updateSettings(this, 'payhere_payment')"
                                    @if (get_setting('payhere_payment') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="payment_method" value="payhere">
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="PAYHERE_MERCHANT_ID">
                        <div class="col-lg-4">
                            <label class="col-from-label">{{ translate('PAYHERE MERCHANT ID') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <input type="text" class="form-control" name="PAYHERE_MERCHANT_ID"
                                value="{{ env('PAYHERE_MERCHANT_ID') }}"
                                placeholder="{{ translate('PAYHERE MERCHANT ID') }}" required>
                        </div>
                    </div>
                
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="PAYHERE_SECRET">
                        <div class="col-lg-4">
                            <label class="col-from-label">{{ translate('PAYHERE SECRET') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <input type="text" class="form-control" name="PAYHERE_SECRET"
                                value="{{ env('PAYHERE_SECRET') }}"
                                placeholder="{{ translate('PAYHERE SECRET') }}" required>
                        </div>
                    </div>
                
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="PAYHERE_CURRENCY">
                        <div class="col-lg-4">
                            <label class="col-from-label">{{ translate('PAYHERE CURRENCY') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <input type="text" class="form-control" name="PAYHERE_CURRENCY"
                                value="{{ env('PAYHERE_CURRENCY') }}"
                                placeholder="{{ translate('PAYHERE CURRENCY') }}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="col-from-label">{{ translate('Sandbox Mode') }}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input value="1" name="payhere_sandbox" type="checkbox"
                                    @if (get_setting('payhere_sandbox') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    </div>

@endsection

@section('script')
    <script type="text/javascript">
        function updateSettings(el, type) {
            if ($(el).is(':checked')) {
                var value = 1;
            } else {
                var value = 0;
            }
            $.post('{{ route('settings.update.activation') }}', {
                _token: '{{ csrf_token() }}',
                type: type,
                value: value
            }, function(data) {
                if (data == '1') {
                    AIZ.plugins.notify('success', '{{ translate('Settings updated successfully') }}');
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }
    </script>
@endsection
