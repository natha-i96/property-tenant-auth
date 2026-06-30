<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Http\Controllers\Api\Property;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use NathaI96\PropertyTenantAuth\Http\Controllers\Controller;
use NathaI96\PropertyTenantAuth\Http\Resources\TenantResource;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;

class TenantController extends Controller
{
    /**
     * List tenants belonging to the authenticated property.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Property $property */
        $property = $request->user();

        return TenantResource::collection(
            $property->tenants()->with('property')->paginate(
                config('property-tenant-auth.pagination.per_page', 25)
            )
        );
    }

    /**
     * Revoke all tokens for a tenant under this property.
     */
    public function revokeAll(Request $request, Tenant $tenant): JsonResponse
    {
        /** @var Property $property */
        $property = $request->user();

        if ($tenant->property_id !== $property->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant does not belong to this property.',
            ], 403);
        }

        $tenant->tokens()->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
