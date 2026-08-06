<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lease API configuration
    |--------------------------------------------------------------------------
    | Until the client provides the real endpoint, the app runs against a
    | built-in mock (LEASE_API_MOCK=true). To go live, set the three values
    | below in your .env file and flip the mock off.
    */

    'mock'     => env('LEASE_API_MOCK', true),
    'base_url' => env('LEASE_API_BASE_URL', ''),
    'api_key'  => env('LEASE_API_KEY'),
];
