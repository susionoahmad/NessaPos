<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Use updateOrCreate to avoid foreign key issues with deletion
User::withoutGlobalScopes()->updateOrCreate(
    ['username' => 'superadmin'],
    [
        'tenant_id' => null,
        'password'  => Hash::make('super123'),
        'role'      => 'superadmin',
    ]
);

echo "SuperAdmin account created: superadmin / super123\n";
