<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== BEFORE: All Packages ===\n";
$all = App\Models\SubscriptionPackage::all(['id','slug','name','is_active']);
foreach ($all as $p) {
    echo $p->id . ' | ' . str_pad($p->slug, 20) . ' | is_active=' . ($p->is_active ? '1' : '0') . "\n";
}

echo "\n=== FIXING: Force-activate all Cloud packages ===\n";
$updated = App\Models\SubscriptionPackage::where('slug', 'not like', 'local_%')
    ->update(['is_active' => 1]);
echo "Updated: $updated rows\n";

echo "\n=== AFTER: Cloud packages (what /packages returns) ===\n";
$cloud = App\Models\SubscriptionPackage::where('is_active', true)
    ->where('slug', 'not like', 'local_%')
    ->orderBy('price')
    ->get(['id','slug','name','price','is_active']);
echo "Count: " . $cloud->count() . "\n";
foreach ($cloud as $p) {
    echo $p->id . ' | ' . str_pad($p->slug, 20) . ' | ' . $p->name . ' | Rp ' . number_format($p->price) . "\n";
}
