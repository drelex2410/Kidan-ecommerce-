<?php

return [
    'sandbox_base_url' => env('ALATPAY_SANDBOX_BASE_URL', 'https://wema-alatdev-apimgt.developer.azure-api.net'),
    'production_base_url' => env('ALATPAY_PRODUCTION_BASE_URL', env('ALATPAY_BASE_URL', 'https://wema-alatdev-apimgt.developer.azure-api.net')),
    'signature_tolerance_seconds' => (int) env('ALATPAY_SIGNATURE_TOLERANCE_SECONDS', 600),
    'paths' => [
        'virtual_account' => env('ALATPAY_VIRTUAL_ACCOUNT_PATH', '/api/v1/bankTransfer/virtualAccount'),
        'account_lookup' => env('ALATPAY_ACCOUNT_LOOKUP_PATH', '/api/v1/bankTransfer/nip/accountLookup'),
        'settlements' => env('ALATPAY_SETTLEMENTS_PATH', '/api/v1/settlements'),
        'refund' => env('ALATPAY_REFUND_PATH', '/api/v1/refunds'),
        'status_candidates' => array_values(array_filter(array_map(
            static fn (string $path): string => trim($path),
            explode(',', (string) env('ALATPAY_STATUS_PATHS', '/api/v1/transaction/check-transaction-status,/api/v1/transactions/status,/api/v1/transaction/status'))
        ))),
    ],
];
