<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Bypass check for SuperAdmin
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        // 2. Allow renewal-related routes even if expired
        $allowedRoutes = [
            'api/tenant/info',
            'api/subscription/renew',
            'api/packages',
            'api/logout'
        ];

        if ($request->is(...$allowedRoutes)) {
            return $next($request);
        }

        // 3. Check if user is associated with a tenant
        if (!$user || !$user->tenant) {
            return $next($request); // Fallback to other middleware or allow if no tenant needed
        }

        // 3. Verify subscription status
        if (!$user->tenant->isSubscriptionActive()) {
            return response()->json([
                'message' => 'Masa aktif toko sudah habis (Expired). Silakan perpanjang layanan.',
                'subscription_status' => 'expired'
            ], 403);
        }

        // 4. Verify manual active flag
        if (!$user->tenant->is_active) {
            return response()->json([
                'message' => 'Toko ini nonaktif. Hubungi administrator.',
                'is_active' => false
            ], 403);
        }

        return $next($request);
    }
}
