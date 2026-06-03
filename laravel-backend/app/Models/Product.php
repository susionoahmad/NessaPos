<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'barcode', 'cost_price', 
        'selling_price', 'shelf_stock', 'warehouse_stock'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
