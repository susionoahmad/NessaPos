<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

echo "=== ALL USERS IN DATABASE ===\n";
$users = User::withoutGlobalScopes()->get(['id', 'tenant_id', 'username', 'role']);
foreach ($users as $u) {
    echo "  ID:{$u->id} | tenant:{$u->tenant_id} | username:{$u->username} | role:{$u->role}\n";
}

echo "\n=== ALL TENANTS ===\n";
$tenants = Tenant::withoutGlobalScopes()->get(['id', 'name', 'slug', 'is_active', 'subscription_plan']);
foreach ($tenants as $t) {
    echo "  ID:{$t->id} | slug:{$t->slug} | active:{$t->is_active} | plan:{$t->subscription_plan}\n";
}

echo "\n=== VERIFY PASSWORDS ===\n";
$superadmin = User::withoutGlobalScopes()->where('username', 'superadmin')->first();
if ($superadmin) {
    $ok = Hash::check('super123', $superadmin->password) ? 'OK' : 'FAIL';
    echo "  superadmin / super123 → {$ok}\n";
} else {
    echo "  superadmin user NOT FOUND\n";
}

$admin = User::withoutGlobalScopes()->where('username', 'admin')->where('role', 'admin')->first();
if ($admin) {
    $ok = Hash::check('admin123', $admin->password) ? 'OK' : 'FAIL';
    echo "  admin / admin123 → {$ok}\n";
} else {
    echo "  admin user NOT FOUND\n";
}
