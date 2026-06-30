<?php

declare(strict_types=1);

use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;

return [
    /*
    |--------------------------------------------------------------------------
    | Model Classes
    |--------------------------------------------------------------------------
    |
    | The package resolves Property and Tenant models through these classes.
    | Your host application can extend the package models and override them
    | here. The default models work out of the box.
    |
    */

    'models' => [
        'property' => env('PTA_PROPERTY_MODEL') ?: Property::class,
        'tenant' => env('PTA_TENANT_MODEL') ?: Tenant::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Set the prefix and middleware applied to the package routes.
    | Disable routes entirely if you want to define them yourself.
    |
    */

    'routes' => [
        'enabled' => env('PTA_ROUTES_ENABLED', true),
        'prefix' => env('PTA_ROUTES_PREFIX', 'api'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Abilities
    |--------------------------------------------------------------------------
    |
    | These abilities are attached when tokens are created. The property token
    | can issue tenant tokens; tenant tokens carry role/property/tenant scopes.
    |
    */

    'tokens' => [
        'property' => ['property', 'issue:tenant-token'],
        'tenant' => ['tenant'],
    ],

    'pagination' => [
        'per_page' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttle Limits
    |--------------------------------------------------------------------------
    |
    | Route-specific throttle middleware. Uses Laravel's throttle syntax:
    | "{maxAttempts},{decayMinutes}". Set to null to disable per-route limits.
    |
    */

    'throttle' => [
        'tenant_token' => env('PTA_TENANT_TOKEN_THROTTLE', '5,1'),
        'revoke' => env('PTA_REVOKE_THROTTLE', '5,1'),
        'general' => env('PTA_GENERAL_THROTTLE', 'api'),
    ],

];
