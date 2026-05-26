<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\AlatPay\AlatPayConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlatPaySettingsController extends Controller
{
    public function __construct(private readonly AlatPayConfig $config)
    {
        $this->middleware(['permission:payment_method']);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alatpay_payment' => ['nullable', 'boolean'],
            'alatpay_env' => ['required', 'in:sandbox,production'],
            'alatpay_base_url' => ['nullable', 'url', 'max:255'],
            'alatpay_merchant_id' => ['required', 'string', 'max:191'],
            'alatpay_public_key' => ['nullable', 'string', 'max:255'],
            'alatpay_client_secret' => ['nullable', 'string', 'max:1000'],
            'alatpay_callback_url' => ['nullable', 'url', 'max:255'],
            'alatpay_webhook_secret' => ['nullable', 'string', 'max:1000'],
            'alatpay_supported_currencies' => ['nullable', 'string', 'max:255'],
            'alatpay_charge_type' => ['nullable', 'in:flat,percentage'],
            'alatpay_charge_flat' => ['nullable', 'numeric', 'min:0'],
            'alatpay_charge_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['alatpay_payment'] = $request->boolean('alatpay_payment');

        $validated['alatpay_base_url'] = $validated['alatpay_base_url']
            ?: $this->config->defaultBaseUrlForEnvironment($validated['alatpay_env']);
        $validated['alatpay_callback_url'] = $validated['alatpay_callback_url']
            ?: $this->config->callbackUrl();
        $validated['alatpay_charge_type'] = $validated['alatpay_charge_type']
            ?: $this->config->chargeType();
        $validated['alatpay_charge_flat'] = $validated['alatpay_charge_flat']
            ?? $this->config->chargeFlat();
        $validated['alatpay_charge_percent'] = $validated['alatpay_charge_percent']
            ?? $this->config->chargePercent();

        $currencySource = $validated['alatpay_supported_currencies']
            ?: implode(', ', $this->config->supportedCurrencies());

        $validated['alatpay_supported_currencies'] = collect(explode(',', $currencySource))
            ->map(static fn (string $currency): string => strtoupper(trim($currency)))
            ->filter()
            ->values()
            ->all();

        if (empty($validated['alatpay_supported_currencies'])) {
            return back()
                ->withErrors(['alatpay_supported_currencies' => translate('Provide at least one supported currency code.')])
                ->withInput();
        }

        foreach ($validated['alatpay_supported_currencies'] as $currency) {
            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                return back()
                    ->withErrors(['alatpay_supported_currencies' => translate('Currency support must use 3-letter ISO codes like NGN or USD.')])
                    ->withInput();
            }
        }

        if (!preg_match('/^[A-Za-z0-9._-]{3,}$/', $validated['alatpay_merchant_id'])) {
            return back()
                ->withErrors(['alatpay_merchant_id' => translate('Merchant ID format looks invalid.')])
                ->withInput();
        }

        $existingClientSecret = $this->config->clientSecret();

        $verificationCredentialsMissing = blank(
            ($validated['alatpay_client_secret'] ?? '') !== ''
                ? $validated['alatpay_client_secret']
                : $existingClientSecret
        );

        if ($validated['alatpay_payment'] && $verificationCredentialsMissing) {
            return back()
                ->withErrors([
                    'alatpay_client_secret' => translate('ALATPay Secret Key is required so KIDAN can verify payments securely after the popup closes.'),
                ])
                ->withInput();
        }

        foreach (['alatpay_client_secret', 'alatpay_webhook_secret'] as $secretField) {
            if (($validated[$secretField] ?? '') === '') {
                unset($validated[$secretField]);
            }
        }

        $this->config->save($validated);
        cache_clear();

        flash(translate('ALATPay settings updated successfully'))->success();

        return back();
    }
}
