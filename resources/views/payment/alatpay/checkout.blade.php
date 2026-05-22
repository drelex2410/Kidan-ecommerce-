<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ALATPay Payment Instructions</title>
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
                    <h1 style="margin: 8px 0 0; font-size: 32px;">Complete your transfer</h1>
                </div>
                <span class="alatpay-brand__badge" id="gateway-status">Awaiting payment</span>
            </div>

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
        </div>
    </div>

    <script>
        const statusUrl = @json($status_url);
        const verifyUrl = @json($verify_url);
        const accountNumber = @json(data_get($instructions, 'account_number', ''));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const gatewayStatus = document.getElementById('gateway-status');
        const statusBox = document.getElementById('status-box');
        const verifyButton = document.getElementById('verify-button');
        const copyButton = document.getElementById('copy-button');

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

        async function verifyNow() {
            verifyButton.disabled = true;
            verifyButton.textContent = 'Verifying...';
            try {
                const response = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                });
                const payload = await response.json();
                paintStatus(payload.status, payload.message);
                if (payload.redirect_url) {
                    window.location.href = payload.redirect_url;
                }
            } finally {
                verifyButton.disabled = false;
                verifyButton.textContent = 'Verify payment now';
            }
        }

        copyButton.addEventListener('click', async () => {
            if (!accountNumber) return;
            await navigator.clipboard.writeText(accountNumber);
            copyButton.textContent = 'Copied';
            setTimeout(() => copyButton.textContent = 'Copy account number', 2000);
        });

        verifyButton.addEventListener('click', verifyNow);

        pollStatus(false);
        setInterval(() => pollStatus(true), 10000);
    </script>
</body>
</html>
