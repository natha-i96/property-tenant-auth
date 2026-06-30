<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Http\Controllers\Api\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NathaI96\PropertyTenantAuth\Http\Controllers\Controller;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;

class RevokeTokenController extends Controller
{
    /**
     * Revoke the current access token.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var Property|Tenant $user */
        $user = $request->user();

        $user->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
