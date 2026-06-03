<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Reset kasir passwords so they are known
$kasirs = User::withoutGlobalScopes()->where('role', 'kasir')->get();

foreach ($kasirs as $u) {
    $u->password = Hash::make('kasir123');
    $u->save();
    echo "Reset password for kasir: {$u->username} → kasir123\n";
}
