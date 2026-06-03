<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\CashierTransaction;
use App\Models\CashierSession;
use App\Models\Setting;
use App\Models\StockMutation;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function isCashPayment(string $method): bool
    {
        return strtolower(trim($method)) === 'cash';
    }

    public function index()
    {
        return response()->json(Order::with(['items', 'user', 'payment'])->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'amount_paid' => 'required|numeric|min:0',
            'change_amount' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();

        return DB::transaction(function () use ($validated, $user) {
            $order = Order::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'total_amount' => $validated['total_amount'],
                'tax_amount' => $validated['tax_amount'],
                'discount' => $validated['discount'],
                'final_amount' => $validated['final_amount'],
                'status' => 'Paid'
            ]);

            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['total']
                ]);

                // Update Stock & Record Mutation
                if ($item['product_id']) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $product->decrement('shelf_stock', $item['quantity']);
                        
                        StockMutation::create([
                            'product_id'    => $product->id,
                            'type'          => 'SALE',
                            'from_location' => 'SHELF',
                            'to_location'   => 'CUSTOMER',
                            'quantity'      => $item['quantity'],
                            'reference'     => "Order #{$order->id}",
                            'created_by'    => $user->id
                        ]);
                    }
                }
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'amount_paid' => $validated['amount_paid'],
                'change_amount' => $validated['change_amount'],
                'status' => 'Success'
            ]);

            // Tetap catat transaksi kasir jika pembayaran tunai dan sesi aktif
            if ($this->isCashPayment($validated['payment_method'])) {
                $session = CashierSession::where('user_id', $user->id)
                    ->where('status', 'open')
                    ->latest('id')
                    ->first();
                
                if ($session) {
                    // Get latest balance in this session
                    $latest = CashierTransaction::where('session_id', $session->id)->latest('id')->first();
                    $currentBalance = $latest ? $latest->balance_after : $session->start_amount;

                    CashierTransaction::create([
                        'session_id' => $session->id,
                        'user_id' => $user->id,
                        'type' => 'sale',
                        'amount' => $validated['final_amount'],
                        'balance_after' => $currentBalance + $validated['final_amount'],
                        'description' => "Penjualan Order #{$order->id}"
                    ]);
                }
            }

            return response()->json($order->load('items', 'payment'), 201);
        });
    }

    public function dailyStats(Request $request)
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $userId = $request->integer('user_id') ?: null;
        $sessionId = $request->integer('session_id') ?: null;
        $session = null;

        if ($sessionId) {
            $session = CashierSession::find($sessionId);
            if (!$session) {
                return response()->json(['message' => 'Sesi tidak ditemukan'], 404);
            }
            $userId = $session->user_id;
        }
        
        $salesQuery = Order::where('created_at', '>=', $today);

        $cashQuery = Order::where('created_at', '>=', $today)
            ->whereHas('payment', fn($q) => $q->whereRaw('LOWER(payment_method) = ?', ['cash']));
            
        $nonCashQuery = Order::where('created_at', '>=', $today)
            ->whereHas('payment', fn($q) => $q->whereRaw('LOWER(payment_method) != ?', ['cash']));

        if ($userId) {
            $salesQuery->where('user_id', $userId);
            $cashQuery->where('user_id', $userId);
            $nonCashQuery->where('user_id', $userId);
        }

        if ($session) {
            $salesQuery->where('created_at', '>=', $session->start_time);
            $cashQuery->where('created_at', '>=', $session->start_time);
            $nonCashQuery->where('created_at', '>=', $session->start_time);

            if ($session->end_time) {
                $salesQuery->where('created_at', '<=', $session->end_time);
                $cashQuery->where('created_at', '<=', $session->end_time);
                $nonCashQuery->where('created_at', '<=', $session->end_time);
            }
        }

        $cashSales = $cashQuery->sum('final_amount');
        $cashCount = $cashQuery->count();

        if ($session) {
            $sessionCashQuery = CashierTransaction::where('session_id', $session->id)
                ->whereRaw('LOWER(type) = ?', ['sale']);
            $cashSales = $sessionCashQuery->sum('amount');
            $cashCount = $sessionCashQuery->count();
        }

        return response()->json([
            'sales' => $salesQuery->sum('final_amount'),
            'cash_sales' => $cashSales,
            'cash_count' => $cashCount,
            'non_cash_count' => $nonCashQuery->count(),
            'monthly_sales' => Order::where('created_at', '>=', $monthStart)->sum('final_amount'),
        ]);
    }
}
