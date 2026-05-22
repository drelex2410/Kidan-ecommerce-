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
            'alatpay_env' => ['required', 'in:sandbox,production'],
            'alatpay_base_url' => ['required', 'url', 'max:255'],
            'alatpay_merchant_id' => ['required', 'string', 'max:191'],
            'alatpay_client_id' => ['required', 'string', 'max:191'],
            'alatpay_client_secret' => ['nullable', 'string', 'max:1000'],
            'alatpay_subscription_key' => ['nullable', 'string', 'max:1000'],
            'alatpay_callback_url' => ['required', 'url', 'max:255'],
            'alatpay_webhook_secret' => ['nullable', 'string', 'max:1000'],
            'alatpay_supported_currencies' => ['required', 'string', 'max:255'],
            'alatpay_charge_type' => ['required', 'in:flat,percentage'],
            'alatpay_charge_flat' => ['required', 'numeric', 'min:0'],
            'alatpay_charge_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['alatpay_supported_currencies'] = collect(explode(',', $validated['alatpay_supported_currencies']))
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

        foreach (['alatpay_client_secret', 'alatpay_subscription_key', 'alatpay_webhook_secret'] as $secretField) {
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
