# ALATPay Gateway Integration

## What Was Added

ALATPay is now integrated as a modular gateway inside the rebuilt Kidan payment stack.

It plugs into:

- admin payment settings
- browser checkout handoff
- wallet/top-up compatible payment initialization
- shared payment verification and idempotent callback handling
- queued webhook processing
- provider-side reconciliation
- provider-linked refund bookkeeping

## Admin Setup

Open:

- `Admin -> Settings -> Payment Method -> ALATPay`

Configure:

- activation toggle
- environment
- merchant ID
- client ID
- client secret
- subscription key
- base URL
- callback URL
- webhook secret
- supported currencies
- charge type
- flat charge
- charge percent

Notes:

- secrets are encrypted at rest
- secret inputs are intentionally blank on load; leave them empty to keep the stored values
- production uses production-safe env overrides when present, so deployed credentials can stay in environment variables without relying only on DB values

## Environment Variables

Add these to `.env` or your hosting environment:

```env
ALATPAY_ENABLED=0
ALATPAY_ENV=sandbox
ALATPAY_BASE_URL=https://wema-alatdev-apimgt.developer.azure-api.net
ALATPAY_CLIENT_ID=
ALATPAY_CLIENT_SECRET=
ALATPAY_SUBSCRIPTION_KEY=
ALATPAY_MERCHANT_ID=
ALATPAY_CALLBACK_URL=
ALATPAY_WEBHOOK_SECRET=
ALATPAY_SANDBOX_BASE_URL=https://wema-alatdev-apimgt.developer.azure-api.net
ALATPAY_PRODUCTION_BASE_URL=
ALATPAY_VIRTUAL_ACCOUNT_PATH=/api/v1/bankTransfer/virtualAccount
ALATPAY_ACCOUNT_LOOKUP_PATH=/api/v1/bankTransfer/nip/accountLookup
ALATPAY_SETTLEMENTS_PATH=/api/v1/settlements
ALATPAY_REFUND_PATH=/api/v1/refunds
ALATPAY_STATUS_PATHS=/api/v1/transaction/check-transaction-status,/api/v1/transactions/status,/api/v1/transaction/status
ALATPAY_SIGNATURE_TOLERANCE_SECONDS=600
```

## Webhook Configuration

Set the ALATPay webhook/callback target to:

- `/api/v1/payment/alatpay/webhook`

In production, always set:

- `ALATPAY_WEBHOOK_SECRET`

Webhook handling behavior:

- signature validation
- timestamp freshness validation
- duplicate fingerprint protection
- raw payload persistence
- queue-based async processing
- correlation ID storage

## Queue / Worker Requirements

ALATPay webhook and reconciliation handling depends on Laravel queues.

Make sure workers stay online after deploy:

```bash
php artisan queue:restart
```

If you supervise workers, restart them after deploy through your process manager.

## cPanel / Deployment Notes

After deployment:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci
npm run build
php artisan queue:restart
```

If queue workers are not available, webhooks will still be accepted and logged, but downstream payment state updates will wait until the queue resumes.

## Checkout Behavior

ALATPay appears in checkout only when:

- `alatpay_payment = 1`
- credentials are configured well enough for initialization

The current integration uses a transfer/virtual-account style flow:

1. Kidan initializes a shared payment record
2. ALATPay initialization creates a provider transaction
3. the shopper is redirected to an internal Kidan instruction page
4. Kidan verifies status server-side through polling, webhooks, and reconciliation
5. the order/wallet is marked paid only after server-side confirmation

## Refund Behavior

Admin refund review can now choose:

- manual
- wallet
- ALATPay

When the original order was paid with ALATPay, the refund workflow can create a provider refund record and persist the provider response for reconciliation/audit.

## Troubleshooting

### ALATPay not showing in checkout

Check:

- `alatpay_payment` is enabled
- supported currency includes the current order currency
- merchant/client/subscription credentials are saved

### Webhook accepted but order not updating

Check:

- queue worker is running
- webhook secret matches ALATPay
- logs in `alatpay_webhook_logs`
- reconciliation attempts in `alatpay_reconciliation_logs`

### Payment reaches ALATPay but stays pending

Check:

- status endpoints in `ALATPAY_STATUS_PATHS`
- settlement feed path
- provider amount/currency matches the local payment

### Secrets disappear from admin form

This is expected. Secrets are stored encrypted and intentionally not echoed back into the form.

## Sandbox Credential Shape

Use the structure below as a placeholder shape only:

```text
Merchant ID: your-merchant-id
Client ID: your-client-id
Client Secret: your-client-secret
Subscription Key: your-subscription-key
Base URL: https://wema-alatdev-apimgt.developer.azure-api.net
Callback URL: https://your-domain.com/api/v1/payment/alatpay/webhook
Webhook Secret: your-webhook-secret
```

Replace those values with the real sandbox credentials from ALATPay/Wema.
