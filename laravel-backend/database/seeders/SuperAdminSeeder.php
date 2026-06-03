<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the default SuperAdmin account.
     */
    public function run(): void
    {
        $username = env('SUPERADMIN_USERNAME', 'superadmin');
        $password = env('SUPERADMIN_PASSWORD', 'superadmin123');

        User::withoutGlobalScopes()->updateOrCreate(
            [
                'username' => $username,
                'role' => 'superadmin',
            ],
            [
                'tenant_id' => null,
                'password' => Hash::make($password),
            ]
        );
    }
}
