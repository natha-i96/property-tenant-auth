<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenableActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Property) {
            if (! $user->is_active || $user->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property is inactive or expired.',
                ], 403);
            }
        }

        if ($user instanceof Tenant) {
            if (! $user->is_active || $user->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant is inactive or expired.',
                ], 403);
            }

            $property = $user->property;
            if ($property && (! $property->is_active || $property->isExpired())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property is inactive or expired.',
                ], 403);
            }
        }

        return $next($request);
    }
}
