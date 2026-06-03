<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:100',
            'store_slug' => 'required|string|unique:tenants,slug|alpha_dash',
            'username'   => 'required|string|unique:users,username',
            'password'   => 'required|string|min:6',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $request->store_name,
                'slug' => $request->store_slug,
                'is_active' => true,
                'subscription_plan' => 'trial',
                'trial_ends_at' => now()->addDays(7), // 7-day trial
            ]);

            // 2. Create Admin User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'username'  => $request->username,
                'password'  => Hash::make($request->password),
                'role'      => 'admin',
            ]);

            // 3. Create Token for auto-login
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user,
                'tenant'       => [
                    'id'                        => $tenant->id,
                    'name'                      => $tenant->name,
                    'slug'                      => $tenant->slug,
                    'subscription_plan'         => $tenant->subscription_plan,
                    'subscription_status'       => $tenant->subscriptionStatus(),
                    'is_active'                 => $tenant->is_active,
                ],
            ], 201);
        });
    }

    public function login(Request $request)
    {
        // SuperAdmin login — no store_id required
        if ($request->store_id === 'superadmin' || $request->get('is_superadmin')) {
            $user = User::withoutGlobalScopes()
                ->where('username', $request->username)
                ->where('role', 'superadmin')
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Username atau Password salah'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $user,
                'tenant'       => null,
            ]);
        }

        // Regular tenant login
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'store_id' => 'required',
        ]);

        $tenant = Tenant::where('slug', $request->store_id)->first();
        if (!$tenant) {
            return response()->json(['message' => 'Store ID tidak ditemukan'], 404);
        }

        if (!$tenant->is_active) {
            return response()->json(['message' => 'Toko ini nonaktif. Hubungi administrator.'], 403);
        }

        if (!$tenant->is_active) {
            return response()->json(['message' => 'Toko ini nonaktif. Hubungi administrator.'], 403);
        }

        $user = User::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('username', $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Username atau Password salah'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
            'tenant'       => [
                'id'                        => $tenant->id,
                'name'                      => $tenant->name,
                'slug'                      => $tenant->slug,
                'subscription_plan'         => $tenant->subscription_plan,
                'subscription_active_until' => $tenant->subscription_active_until,
                'subscription_status'       => $tenant->subscriptionStatus(),
                'is_active'                 => $tenant->is_active,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
