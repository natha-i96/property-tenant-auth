<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Http\Controllers\Api\Auth;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NathaI96\PropertyTenantAuth\Http\Controllers\Controller;
use NathaI96\PropertyTenantAuth\Http\Requests\TenantTokenRequest;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;

class TenantTokenController extends Controller
{
    /**
     * Issue a tenant token using an authenticated property token.
     */
    public function store(TenantTokenRequest $request): JsonResponse
    {
        /** @var Property $property */
        $property = $request->user();

        if ($property->isExpired()) {
            return $this->errorResponse('Property subscription has expired.', 403);
        }

        $tenant = $this->resolveTenant($property, $request);

        if (! $tenant->is_active || $tenant->isExpired()) {
            return $this->errorResponse('Tenant is inactive or expired.', 403);
        }

        $expiresAt = $this->resolveExpiresAt($request);

        $token = $tenant->createToken(
            'tenant:'.$tenant->tenant_no,
            $this->tenantAbilities($property, $tenant),
            $expiresAt
        )->plainTextToken;

        return $this->tokenResponse($property, $tenant, $token, $expiresAt);
    }

    /**
     * Resolve or create the tenant for the property token request.
     */
    protected function resolveTenant(Property $property, Request $request): Tenant
    {
        $tenantClass = $this->tenantModelClass();

        return $tenantClass::firstOrCreate(
            [
                'property_id' => $property->id,
                'tenant_no' => $request->input('tenant_no'),
            ],
            [
                'role' => $request->input('role', 'tenant'),
                'is_active' => true,
            ]
        );
    }

    /**
     * Build the tenant token abilities.
     */
    protected function tenantAbilities(Property $property, Tenant $tenant): array
    {
        return array_merge(
            config('property-tenant-auth.tokens.tenant', ['tenant']),
            [
                'role:'.$tenant->role,
                'property:'.$property->property_no,
                'tenant:'.$tenant->tenant_no,
            ]
        );
    }

    /**
     * Build the successful token issue response.
     */
    protected function tokenResponse(Property $property, Tenant $tenant, string $token, ?Carbon $expiresAt): JsonResponse
    {
        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt ? (int) max(0, $expiresAt->diffInSeconds(Carbon::now())) : null,
            'property_no' => $property->property_no,
            'tenant_no' => $tenant->tenant_no,
            'role' => $tenant->role,
        ]);
    }

    /**
     * Build a standardized error response.
     */
    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Resolve the tenant model class from config.
     */
    protected function tenantModelClass(): string
    {
        return config('property-tenant-auth.models.tenant', Tenant::class);
    }

    /**
     * Resolve the optional token expiration from the request.
     */
    protected function resolveExpiresAt(Request $request): ?Carbon
    {
        $minutes = $request->input('expires_in_minutes');

        if ($minutes === null) {
            return null;
        }

        $minutes = (int) $minutes;

        return $minutes > 0 ? Carbon::now()->addMinutes($minutes) : null;
    }
}
