<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$u = User::where('username', 'admin')->first();
if ($u) {
    $u->password = Hash::make('admin123');
    $u->save();
    echo "Password reset successfully for user: admin\n";
} else {
    echo "User admin not found.\n";
}
