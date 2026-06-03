<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateFromSQLite extends Command
{
    protected $signature = 'pos:migrate-sqlite {db_path} {tenant_slug=toko-utama}';
    protected $description = 'Migrate data from SQLite to MySQL with tenant support';

    public function handle()
    {
        $dbPath = $this->argument('db_path');
        $slug = $this->argument('tenant_slug');

        if (!file_exists($dbPath)) {
            $this->error("SQLite file not found at: $dbPath");
            return;
        }

        $this->info("Starting migration for tenant: $slug");

        // 1. Create Tenant
        $tenant = Tenant::firstOrCreate(['slug' => $slug], [
            'name' => 'Toko Utama',
        ]);

        session()->put('tenant_id', $tenant->id);

        try {
            $sqlite = new \PDO("sqlite:$dbPath");
            $sqlite->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // 2. Migrate Users
            $this->info("Migrating Users...");
            $stmt = $sqlite->query("SELECT * FROM users");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                User::updateOrCreate(
                    ['username' => $row['username'], 'tenant_id' => $tenant->id],
                    ['password' => Hash::needsRehash($row['password']) ? Hash::make($row['password']) : $row['password'], 'role' => $row['role']]
                );
            }

            // 3. Migrate Categories
            $this->info("Migrating Categories...");
            $stmt = $sqlite->query("SELECT * FROM categories");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                Category::updateOrCreate(
                    ['id' => $row['id'], 'tenant_id' => $tenant->id],
                    ['name' => $row['name']]
                );
            }

            // 4. Migrate Products
            $this->info("Migrating Products...");
            $stmt = $sqlite->query("SELECT * FROM products");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                Product::updateOrCreate(
                    ['id' => $row['id'], 'tenant_id' => $tenant->id],
                    [
                        'name' => $row['name'],
                        'barcode' => $row['barcode'],
                        'cost_price' => $row['cost_price'] ?? 0,
                        'selling_price' => $row['selling_price'] ?? 0,
                        'shelf_stock' => $row['shelf_stock'] ?? 0,
                        'warehouse_stock' => $row['warehouse_stock'] ?? 0,
                    ]
                );
            }

            // 5. Migrate Settings
            $this->info("Migrating Settings...");
            $stmt = $sqlite->query("SELECT * FROM settings");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                Setting::updateOrCreate(
                    ['tenant_id' => $tenant->id],
                    [
                        'store_name' => $row['store_name'],
                        'store_address' => $row['store_address'],
                        'store_phone' => $row['store_phone'],
                        'tax_rate' => $row['tax_rate'],
                        'tax_type' => $row['tax_type'] ?? 'exclusive',
                        'receipt_text' => $row['receipt_text'],
                    ]
                );
            }

            $this->info("Migration completed successfully!");

        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
}
