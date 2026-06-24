<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api/v1/seller',
    'api_domain' => null,
    'export_path' => 'seller-api.json',
    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => <<<'MARKDOWN'
Seller API documentation for the Restro seller app.

This documentation includes the full seller onboarding and verification flow:

- Seller OTP request with device details
- Seller OTP verification with token issuance
- Restaurant details onboarding
- Restaurant weekly business hours management
- Seller language update
- Seller logout
- Seller profile
- Seller document upload
- Didit verification session creation
- Didit verification status lookup
- Didit webhook callback handling
- Cuisine and category lookup
- Product CRUD with images, add-ons, variants, availability, and menu assignments
- Menu CRUD with images, availability schedules, and assigned products

All authenticated seller endpoints use Sanctum bearer tokens.
MARKDOWN,
    ],
    'ui' => [
        'title' => 'Restro Seller API',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],
    'servers' => null,
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],
    'extensions' => [],
];
