<?php

return [
    'sync_cache_minutes' => env('BILLING_SYNC_CACHE_MINUTES', 1440),

    /*
     * Mock data keeps the showcase runnable without access to the corporate
     * ERP. All names, codes, documents and amounts below are fictional.
     */
    'mock' => [
        'municipalities' => [
            [
                'code' => '1000001',
                'name' => 'Municipality Alpha',
                'state' => 'AA',
                'contributor' => true,
                'payment_method' => 'Bank slip',
            ],
            [
                'code' => '1000002',
                'name' => 'Municipality Beta',
                'state' => 'BB',
                'contributor' => true,
                'payment_method' => 'Bank transfer',
            ],
            [
                'code' => '1000003',
                'name' => 'Municipality Gamma',
                'state' => 'CC',
                'contributor' => false,
                'payment_method' => 'Bank slip',
            ],
        ],
        'installments' => [
            [
                'municipality_code' => '1000001',
                'document_id' => 'INV-DEMO-001',
                'reference' => '2026-05-01',
                'due_date' => '2026-05-20',
                'payment_date' => '2026-05-18',
                'amount' => 1250.50,
                'contributor' => true,
                'legal_action' => false,
                'canceled' => false,
                'payment_method' => 'Bank slip',
            ],
            [
                'municipality_code' => '1000001',
                'document_id' => 'INV-DEMO-002',
                'reference' => '2026-06-01',
                'due_date' => '2026-06-25',
                'payment_date' => null,
                'amount' => 1250.50,
                'contributor' => true,
                'legal_action' => false,
                'canceled' => false,
                'payment_method' => 'Bank slip',
            ],
            [
                'municipality_code' => '1000002',
                'document_id' => null,
                'reference' => '2026-04-01',
                'due_date' => '2026-04-20',
                'payment_date' => null,
                'amount' => 980.00,
                'contributor' => true,
                'legal_action' => false,
                'canceled' => false,
                'payment_method' => 'Bank transfer',
            ],
            [
                'municipality_code' => '1000003',
                'document_id' => 'INV-DEMO-004',
                'reference' => '2026-06-01',
                'due_date' => '2026-06-20',
                'payment_date' => null,
                'amount' => 750.00,
                'contributor' => false,
                'legal_action' => false,
                'canceled' => false,
                'payment_method' => 'Bank slip',
            ],
        ],
        'bank_slips' => [
            [
                'municipality_code' => '1000001',
                'document_id' => 'INV-DEMO-001',
                'due_date' => '2026-05-20',
                'amount' => 1250.50,
                'status' => 'Pago',
                'digital_line' => '00000.00000 00000.000000 00000.000000 0 00000000125050',
                'annual' => false,
            ],
            [
                'municipality_code' => '1000001',
                'document_id' => 'INV-DEMO-002',
                'due_date' => '2026-06-25',
                'amount' => 1250.50,
                'status' => 'Confirmado',
                'digital_line' => '11111.11111 11111.111111 11111.111111 1 00000000125050',
                'annual' => false,
            ],
            [
                'municipality_code' => '1000001',
                'document_id' => 'ANNUAL-DEMO-2026',
                'due_date' => '2026-12-20',
                'amount' => 13755.50,
                'status' => 'Confirmado',
                'digital_line' => '22222.22222 22222.222222 22222.222222 2 00000001375550',
                'annual' => true,
            ],
        ],
    ],
];
