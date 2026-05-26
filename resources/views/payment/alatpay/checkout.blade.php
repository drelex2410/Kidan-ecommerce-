<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ALATPay Checkout</title>
    <style>
        body {
            margin: 0;
            font-family: "Helvetica Neue", Arial, sans-serif;
            background: #f7f7fb;
            color: #1f2937;
        }
        .alatpay-shell {
            max-width: 860px;
            margin: 0 auto;
            padding: 48px 20px 72px;
        }
        .alatpay-card {
            background: #fff;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        }
        .alatpay-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 28px;
        }
        .alatpay-brand__badge {
            background: #ecfdf5;
            color: #065f46;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .alatpay-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
        .alatpay-panel {
            background: #faf7f2;
            border-radius: 18px;
            padding: 20px;
        }
        .alatpay-label {
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }
        .alatpay-value {
            font-size: 24px;
            font-weight: 700;
            word-break: break-word;
        }
        .alatpay-meta {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .alatpay-status {
            margin-top: 24px;
            padding: 18px 20px;
            border-radius: 16px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
        }
        .alatpay-actions {
            margin-top: 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .alatpay-button {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            cursor: pointer;
            font-weight: 700;
        }
        .alatpay-button:disabled {
            opacity: 0.7;
            cursor: wait;
        }
        .alatpay-button--primary {
            background: #0f172a;
            color: #fff;
        }
        .alatpay-button--ghost {
            background: transparent;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }
        .alatpay-note {
            margin-top: 16px;
            font-size: 14px;
            line-height: 1.7;
            color: #4b5563;
        }
        .alatpay-warning {
            margin-top: 18px;
            border-radius: 16px;
            background: #fff7ed;
            color: #9a3412;
            padding: 16px 18px;
            line-height: 1.6;
        }
        @media (max-width: 720px) {
            .alatpay-card {
                padding: 24px;
            }
            .alatpay-grid {
                grid-template-columns: 1fr;
            }
            .alatpay-brand {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="alatpay-shell">
        <div class="alatpay-card">
            <div class="alatpay-brand">
                <div>
                    <div style="font-size: 13px; letter-spacing: 0.1em; text-transform: uppercase; color: #6b7280;">Kidan x ALATPay</div>
                    <h1 style="margin: 8px 0 0; font-size: 32px;">
                        {{ $checkout_mode === 'web_plugin' ? 'Complete your payment securely' : 'Complete your transfer' }}
                    </h1>
                </div>
                <span class="alatpay-brand__badge" id="gateway-status">
                    {{ $checkout_mode === 'web_plugin' ? 'Launching checkout' : 'Awaiting payment' }}
                </span>
            </div>

            @if ($checkout_mode === 'web_plugin')
                <div class="alatpay-grid">
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Amount</div>
                        <div class="alatpay-value">{{ data_get($plugin_payload, 'currency', 'NGN') }} {{ number_format((float) data_get($plugin_payload, 'amount', 0), 2) }}</div>
                    </div>
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Customer</div>
                        <div class="alatpay-value" style="font-size: 20px;">
                            {{ trim(implode(' ', array_filter([data_get($plugin_payload, 'firstName'), data_get($plugin_payload, 'lastName')]))) ?: 'Kidan Customer' }}
                        </div>
                    </div>
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Email</div>
                        <div class="alatpay-value" style="font-size: 20px;">{{ data_get($plugin_payload, 'email', 'Not provided') }}</div>
                    </div>
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Order Reference</div>
                        <div class="alatpay-value" style="font-size: 20px;">{{ $transaction->reference }}</div>
                    </div>
                </div>

                <div class="alatpay-meta">
                    <div><strong>Order Code:</strong> {{ $transaction->order_code ?? 'Wallet Funding' }}</div>
                    <div style="margin-top: 6px;"><strong>Gateway Mode:</strong> Web Plugin</div>
                </div>

                <div class="alatpay-status" id="status-box">
                    We are preparing the ALATPay checkout popup. If it does not open automatically, use the button below.
                </div>

                <div class="alatpay-actions">
                    <button type="button" class="alatpay-button alatpay-button--primary" id="launch-button">Launch ALATPay checkout</button>
                    <button type="button" class="alatpay-button alatpay-button--ghost" id="verify-button">Verify payment now</button>
                </div>

                <div class="alatpay-warning" id="plugin-warning" style="display: none;"></div>

                <p class="alatpay-note">
                    The ALATPay web plugin is opened from this secure bridge page. We capture the returned transaction identifiers here, then verify the payment server-side before taking the customer back to the normal Kidan flow.
                </p>
            @else
                <div class="alatpay-grid">
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Virtual Account Number</div>
                        <div class="alatpay-value">{{ data_get($instructions, 'account_number', 'Pending') }}</div>
                    </div>
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Bank Code</div>
                        <div class="alatpay-value">{{ data_get($instructions, 'bank_code', '035') }}</div>
                    </div>
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Business Name</div>
                        <div class="alatpay-value" style="font-size: 20px;">{{ data_get($instructions, 'business_name', config('app.name')) }}</div>
                    </div>
                    <div class="alatpay-panel">
                        <div class="alatpay-label">Amount</div>
                        <div class="alatpay-value">{{ data_get($instructions, 'currency', 'NGN') }} {{ number_format((float) data_get($instructions, 'amount', 0), 2) }}</div>
                    </div>
                </div>

                <div class="alatpay-meta">
                    <div><strong>Reference:</strong> {{ $transaction->reference }}</div>
                    <div style="margin-top: 6px;"><strong>Expires:</strong> {{ optional($transaction->expires_at)->toDayDateTimeString() ?? 'Not provided by gateway' }}</div>
                    <div style="margin-top: 6px;"><strong>Order Code:</strong> {{ $transaction->order_code ?? 'Wallet Funding' }}</div>
                </div>

                <div class="alatpay-status" id="status-box">
                    We are waiting for ALATPay to confirm the transfer. Keep this page open after you send the payment.
                </div>

                <div class="alatpay-actions">
                    <button type="button" class="alatpay-button alatpay-button--primary" id="verify-button">Verify payment now</button>
                    <button type="button" class="alatpay-button alatpay-button--ghost" id="copy-button">Copy account number</button>
                </div>

                <p class="alatpay-note">
                    Once the transfer reaches ALATPay, we will verify it server-side and return you to the normal Kidan checkout flow automatically.
                </p>
            @endif
        </div>
    </div>

    <script>
        const checkoutMode = @json($checkout_mode);
        const statusUrl = @json($status_url);
        const verifyUrl = @json($verify_url);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const gatewayStatus = document.getElementById('gateway-status');
        const statusBox = document.getElementById('status-box');
        const verifyButton = document.getElementById('verify-button');
        const pluginPayload = @json($plugin_payload);
        const pluginScriptUrl = @json($plugin_script_url);
        const launchButton = document.getElementById('launch-button');
        const pluginWarning = document.getElementById('plugin-warning');
        const copyButton = document.getElementById('copy-button');
        const accountNumber = @json(data_get($instructions, 'account_number', ''));

        const statusStyles = {
            pending: { text: 'Awaiting payment', bg: '#eff6ff', color: '#1d4ed8' },
            processing: { text: 'Processing', bg: '#fff7ed', color: '#c2410c' },
            successful: { text: 'Payment confirmed', bg: '#ecfdf5', color: '#047857' },
            failed: { text: 'Payment failed', bg: '#fef2f2', color: '#b91c1c' },
            cancelled: { text: 'Payment cancelled', bg: '#fef2f2', color: '#b91c1c' },
            reversed: { text: 'Payment reversed', bg: '#fef2f2', color: '#b91c1c' },
        };

        function paintStatus(status, message) {
            const styles = statusStyles[status] || statusStyles.pending;
            gatewayStatus.textContent = styles.text;
            gatewayStatus.style.background = styles.bg;
            gatewayStatus.style.color = styles.color;
            statusBox.style.background = styles.bg;
            statusBox.style.color = styles.color;
            statusBox.textContent = message || styles.text;
        }

        async function pollStatus(force = true) {
            const response = await fetch(statusUrl + '?refresh=' + (force ? '1' : '0'), {
                headers: { 'Accept': 'application/json' }
            });
            const payload = await response.json();
            paintStatus(payload.status, payload.message);

            if (payload.redirect_url) {
                window.location.href = payload.redirect_url;
            }
        }

        async function verifyNow(pluginResponse = null) {
            verifyButton.disabled = true;
            verifyButton.textContent = 'Verifying...';

            if (launchButton) {
                launchButton.disabled = true;
            }

            try {
                const response = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(pluginResponse ? { plugin_response: pluginResponse } : {}),
                });

                const payload = await response.json();
                paintStatus(payload.status, payload.message);

                if (payload.redirect_url) {
                    window.location.href = payload.redirect_url;
                }
            } finally {
                verifyButton.disabled = false;
                verifyButton.textContent = 'Verify payment now';

                if (launchButton) {
                    launchButton.disabled = false;
                }
            }
        }

        function showPluginWarning(message) {
            if (!pluginWarning) {
                return;
            }

            pluginWarning.style.display = 'block';
            pluginWarning.textContent = message;
        }

        function loadPluginScript() {
            return new Promise((resolve, reject) => {
                if (window.Alatpay && typeof window.Alatpay.setup === 'function') {
                    resolve(window.Alatpay);
                    return;
                }

                if (!pluginScriptUrl) {
                    reject(new Error('The ALATPay plugin script URL is not configured.'));
                    return;
                }

                const existing = document.querySelector('script[data-alatpay-plugin="1"]');
                if (existing) {
                    existing.addEventListener('load', () => resolve(window.Alatpay));
                    existing.addEventListener('error', () => reject(new Error('ALATPay plugin script failed to load.')));
                    return;
                }

                const script = document.createElement('script');
                script.src = pluginScriptUrl;
                script.async = true;
                script.dataset.alatpayPlugin = '1';
                script.onload = () => {
                    if (window.Alatpay && typeof window.Alatpay.setup === 'function') {
                        resolve(window.Alatpay);
                        return;
                    }

                    reject(new Error('ALATPay plugin loaded but did not expose the expected setup API.'));
                };
                script.onerror = () => reject(new Error('ALATPay plugin script failed to load.'));
                document.head.appendChild(script);
            });
        }

        async function launchPluginCheckout() {
            if (checkoutMode !== 'web_plugin') {
                return;
            }

            launchButton.disabled = true;
            launchButton.textContent = 'Opening checkout...';
            paintStatus('processing', 'Opening the ALATPay payment popup...');

            try {
                const alatpay = await loadPluginScript();
                const popup = alatpay.setup({
                    apiKey: pluginPayload.apiKey,
                    businessId: pluginPayload.businessId,
                    email: pluginPayload.email,
                    phone: pluginPayload.phone || '',
                    firstName: pluginPayload.firstName,
                    lastName: pluginPayload.lastName,
                    color: '#01070e',
                    metadata: pluginPayload.metadata || null,
                    currency: pluginPayload.currency,
                    amount: pluginPayload.amount,
                    orderId: pluginPayload.orderId,
                    description: pluginPayload.description,
                    channel: pluginPayload.channel,
                    onTransaction: async function (response) {
                        paintStatus('processing', 'ALATPay returned a transaction response. Verifying now...');
                        await verifyNow(response || {});
                    },
                    onClose: function () {
                        if (!statusBox.textContent.includes('confirmed')) {
                            paintStatus('pending', 'The ALATPay popup was closed. You can launch it again to continue checkout.');
                        }
                    }
                });

                popup.show();
                launchButton.textContent = 'Launch ALATPay checkout';
            } catch (error) {
                paintStatus('failed', error.message);
                showPluginWarning(error.message);
                launchButton.textContent = 'Launch ALATPay checkout';
            } finally {
                launchButton.disabled = false;
            }
        }

        if (copyButton) {
            copyButton.addEventListener('click', async () => {
                if (!accountNumber) return;
                await navigator.clipboard.writeText(accountNumber);
                copyButton.textContent = 'Copied';
                setTimeout(() => copyButton.textContent = 'Copy account number', 2000);
            });
        }

        verifyButton.addEventListener('click', () => verifyNow());

        if (launchButton) {
            launchButton.addEventListener('click', launchPluginCheckout);
        }

        pollStatus(false);
        setInterval(() => pollStatus(true), 10000);

        if (checkoutMode === 'web_plugin') {
            window.addEventListener('load', () => {
                window.setTimeout(() => {
                    launchPluginCheckout();
                }, 300);
            });
        }
    </script>
</body>
</html>
