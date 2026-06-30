<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NathaI96\PropertyTenantAuth\Http\Controllers\Controller;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;

class MeController extends Controller
{
    /**
     * Return the identity of the authenticated token holder.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Tenant) {
            return response()->json([
                'success' => true,
                'type' => 'tenant',
                'property_no' => $user->property_no,
                'tenant_no' => $user->tenant_no,
                'role' => $user->role,
                'abilities' => $user->currentAccessToken()?->abilities ?? [],
            ]);
        }

        if ($user instanceof Property) {
            return response()->json([
                'success' => true,
                'type' => 'property',
                'property_no' => $user->property_no,
                'abilities' => $user->currentAccessToken()?->abilities ?? [],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unrecognized tokenable type.',
        ], 401);
    }
}
