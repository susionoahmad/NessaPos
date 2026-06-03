<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        // Returns only users for the current tenant (scoped via BelongsToTenant)
        return response()->json(User::select('id', 'username', 'role', 'tenant_id')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,kasir',
        ]);

        $user = User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->only(['username', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting the last admin
        $adminCount = User::withoutGlobalScopes()
            ->where('tenant_id', $user->tenant_id)
            ->where('role', 'admin')
            ->count();

        if ($user->role === 'admin' && $adminCount <= 1) {
            return response()->json(['message' => 'Tidak bisa menghapus satu-satunya admin.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User dihapus'], 204);
    }
}
