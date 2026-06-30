<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NathaI96\PropertyTenantAuth\Http\Controllers\Api\Auth\RevokeTokenController;
use NathaI96\PropertyTenantAuth\Http\Controllers\Api\Auth\TenantTokenController;
use NathaI96\PropertyTenantAuth\Http\Controllers\Api\MeController;
use NathaI96\PropertyTenantAuth\Http\Controllers\Api\Property\TenantController;

Route::middleware(['auth:sanctum', 'tokenable.active', 'ability:property,tenant', 'throttle:'.config('property-tenant-auth.throttle.general', 'api')])
    ->get('/v1/me', [MeController::class, 'show']);

Route::middleware(['auth:sanctum', 'tokenable.active', 'abilities:property,issue:tenant-token', 'throttle:'.config('property-tenant-auth.throttle.tenant_token', '5,1')])
    ->post('/v1/auth/tenant-token', [TenantTokenController::class, 'store']);

Route::middleware(['auth:sanctum', 'tokenable.active', 'throttle:'.config('property-tenant-auth.throttle.revoke', '5,1')])
    ->post('/v1/auth/revoke', [RevokeTokenController::class, 'destroy']);

Route::middleware(['auth:sanctum', 'tokenable.active', 'abilities:property', 'throttle:'.config('property-tenant-auth.throttle.general', 'api')])
    ->get('/v1/tenants', [TenantController::class, 'index']);

Route::middleware(['auth:sanctum', 'tokenable.active', 'abilities:property', 'throttle:'.config('property-tenant-auth.throttle.general', 'api')])
    ->delete('/v1/tenants/{tenant}/tokens', [TenantController::class, 'revokeAll']);
