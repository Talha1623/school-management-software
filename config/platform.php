<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Base Domain
    |--------------------------------------------------------------------------
    |
    | All tenant domains are generated as: {subdomain}.{base_domain}
    | Examples:
    | - Local:  PLATFORM_BASE_DOMAIN=localhost
    | - Live:   PLATFORM_BASE_DOMAIN=educationmanagementsystem.com
    |
    */
    'base_domain' => env('PLATFORM_BASE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | Comma separated list in .env of non-tenant hosts that should always
    | use landlord database, for example:
    | PLATFORM_CENTRAL_DOMAINS=educationmanagementsystem.com,www.educationmanagementsystem.com
    |
    */
    'central_domains' => array_values(array_filter(array_map(
        static fn ($host) => strtolower(trim($host)),
        explode(',', (string) env('PLATFORM_CENTRAL_DOMAINS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Auto Provision (cPanel API)
    |--------------------------------------------------------------------------
    |
    | Enable this to auto-create tenant DB and subdomain through cPanel UAPI.
    | This should be enabled only when CPANEL_* variables are configured.
    |
    */
    'auto_provision' => (bool) env('PLATFORM_AUTO_PROVISION', false),
];
