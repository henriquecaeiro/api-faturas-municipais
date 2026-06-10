<?php

return [
    'driver' => env('MAIL_DRIVER', 'log'),
    'host' => env('MAIL_HOST', 'localhost'),
    'port' => env('MAIL_PORT', 2525),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'showcase@example.test'),
        'name' => env('MAIL_FROM_NAME', 'Billing Showcase'),
    ],
    'encryption' => env('MAIL_ENCRYPTION'),
    'username' => env('MAIL_USERNAME'),
    'password' => env('MAIL_PASSWORD'),
];
