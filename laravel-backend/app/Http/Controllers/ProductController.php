<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::with('category')->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'barcode' => 'nullable',
            'cost_price' => 'numeric',
            'selling_price' => 'numeric',
            'shelf_stock' => 'integer',
            'warehouse_stock' => 'integer',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        $product = Product::create($validated);

        if ($product->shelf_stock > 0 || $product->warehouse_stock > 0) {
            StockMutation::create([
                'product_id' => $product->id,
                'type' => 'ADJUSTMENT',
                'from_location' => 'INITIAL',
                'to_location' => $product->shelf_stock > 0 ? 'SHELF' : 'WAREHOUSE',
                'quantity' => $product->shelf_stock + $product->warehouse_stock,
                'reference' => 'Initial Stock',
                'created_by' => Auth::id()
            ]);
        }

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required',
            'barcode' => 'nullable',
            'cost_price' => 'numeric',
            'selling_price' => 'numeric',
            'shelf_stock' => 'integer',
            'warehouse_stock' => 'integer',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        $oldShelf = $product->shelf_stock;
        $oldWh = $product->warehouse_stock;

        $product->update($validated);

        // Record shelf change
        if ($oldShelf != $product->shelf_stock) {
            StockMutation::create([
                'product_id' => $product->id,
                'type' => 'ADJUSTMENT',
                'from_location' => $oldShelf < $product->shelf_stock ? 'EXTERNAL' : 'SHELF',
                'to_location' => $oldShelf < $product->shelf_stock ? 'SHELF' : 'ADJUSTMENT',
                'quantity' => abs($product->shelf_stock - $oldShelf),
                'reference' => 'Manual Adjustment (Shelf)',
                'created_by' => Auth::id()
            ]);
        }

        // Record warehouse change
        if ($oldWh != $product->warehouse_stock) {
            StockMutation::create([
                'product_id' => $product->id,
                'type' => 'ADJUSTMENT',
                'from_location' => $oldWh < $product->warehouse_stock ? 'EXTERNAL' : 'WAREHOUSE',
                'to_location' => $oldWh < $product->warehouse_stock ? 'WAREHOUSE' : 'ADJUSTMENT',
                'quantity' => abs($product->warehouse_stock - $oldWh),
                'reference' => 'Manual Adjustment (Warehouse)',
                'created_by' => Auth::id()
            ]);
        }

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(null, 204);
    }

    public function stockMutations()
    {
        return response()->json(\App\Models\StockMutation::with(['product', 'user'])->latest()->get());
    }
}
