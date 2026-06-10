<?php

$erpBaseUrl = env('ERP_BASE_URL');

return [
    'erp' => [
        'driver' => env('ERP_DRIVER', 'mock'),
        'base_url' => $erpBaseUrl ?: 'http://localhost/',
        'company' => env('ERP_COMPANY'),
        'username' => env('ERP_USERNAME'),
        'password' => env('ERP_PASSWORD'),
        'timeout' => env('ERP_TIMEOUT', 15),
        'verify_ssl' => env('ERP_VERIFY_SSL', true),
        'login_path' => env('ERP_LOGIN_PATH', '/Login'),
        'endpoints' => [
            'municipalities' => env('ERP_MUNICIPALITIES_ENDPOINT', '/billing/municipalities'),
            'installments' => env('ERP_INSTALLMENTS_ENDPOINT', '/billing/installments'),
            'bank_slips' => env('ERP_BANK_SLIPS_ENDPOINT', '/billing/bank-slips'),
        ],
    ],
];
