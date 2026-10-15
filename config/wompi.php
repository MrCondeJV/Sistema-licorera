<?php

return [
    'public_key' => env('WOMPI_PUBLIC_KEY', 'pub_test_demo_mambacode_wompi_public'),
    'private_key' => env('WOMPI_PRIVATE_KEY', 'prv_test_demo_mambacode_wompi_private'),
    'integrity_secret' => env('WOMPI_INTEGRITY_SECRET', 'test_integrity_demo_mambacode_secret'),
    'events_secret' => env('WOMPI_EVENTS_SECRET', 'test_events_demo_mambacode_secret'),
    'sandbox' => env('WOMPI_SANDBOX', true),
    'currency' => env('WOMPI_CURRENCY', 'COP'),
    'base_url' => env('WOMPI_SANDBOX', true) 
        ? 'https://sandbox.wompi.co/v1' 
        : 'https://production.wompi.co/v1',
    'widget_url' => 'https://checkout.wompi.co/p/',

    // Wompi Fee / Surcharge configuration (2.65% + $700 COP + 19% IVA on fee)
    'apply_surcharge' => env('WOMPI_APPLY_SURCHARGE', true),
    'fee_percentage' => (float) env('WOMPI_FEE_PERCENTAGE', 2.65),
    'fee_fixed' => (int) env('WOMPI_FEE_FIXED', 700),
    'fee_vat' => (float) env('WOMPI_FEE_VAT', 0.19),
];
