<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CashierSession;
use App\Models\Vault;
use App\Models\VaultTransaction;
use App\Models\CashierTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    private function openSessionQuery($userId)
    {
        return CashierSession::where('user_id', $userId)
            ->where('status', 'open')
            ->latest('id');
    }

    private function sessionCashSales(CashierSession $session)
    {
        return CashierTransaction::where('session_id', $session->id)
            ->whereRaw('LOWER(type) = ?', ['sale'])
            ->sum('amount');
    }

    private function sessionDrawerBalance(CashierSession $session)
    {
        $latest = CashierTransaction::where('session_id', $session->id)
            ->latest('id')
            ->first();

        return $latest ? $latest->balance_after : $session->start_amount + $this->sessionCashSales($session);
    }

    public function index()
    {
        return response()->json(CashierSession::with('user')->latest()->get());
    }

    public function transactions()
    {
        return response()->json(\App\Models\CashierTransaction::with('user')->latest()->get());
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'start_amount' => 'required|numeric',
            'start_denoms' => 'nullable'
        ]);

        $userId = auth()->id();
        $tenantId = auth()->user()->tenant_id;

        return DB::transaction(function () use ($validated, $userId, $tenantId) {
            // Check if user already has an open session
            $existing = $this->openSessionQuery($userId)->first();

            if ($existing) {
                return response()->json(['message' => 'Sesi sudah terbuka'], 400);
            }

            // Withdraw from Vault
            $vault = Vault::where('tenant_id', $tenantId)->first();
            
            // Auto-initialize vault for the tenant if missing (Internal Store logic)
            if (!$vault) {
                $vault = Vault::create(['tenant_id' => $tenantId, 'balance' => 0]);
            }

            if ($vault->balance < $validated['start_amount']) {
                $currentBalance = number_format($vault->balance);
                return response()->json(['message' => "Saldo brankas tidak mencukupi (Tersedia: Rp {$currentBalance})"], 400);
            }

            $vault->decrement('balance', $validated['start_amount']);

            VaultTransaction::create([
                'type' => 'withdrawal',
                'amount' => -$validated['start_amount'],
                'balance_after' => $vault->balance,
                'description' => "Modal awal laci kasir (User: " . auth()->user()->username . ")",
                'created_by' => $userId
            ]);

            $session = CashierSession::create([
                'user_id' => $userId,
                'start_amount' => $validated['start_amount'],
                'start_denoms' => $validated['start_denoms'],
                'status' => 'open'
            ]);

            // [NEW] Record Cashier Mutation for Session Start
            CashierTransaction::create([
                'session_id' => $session->id,
                'user_id' => $userId,
                'type' => 'session_start',
                'amount' => $validated['start_amount'],
                'balance_after' => $validated['start_amount'],
                'description' => "Buka Sesi (Modal Awal)"
            ]);

            return response()->json($session);
        });
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'end_amount_physical' => 'required|numeric',
            'end_denoms' => 'nullable'
        ]);

        $userId = auth()->id();
        $session = $this->openSessionQuery($userId)->first();

        if (!$session) {
            return response()->json(['message' => 'Tidak ada sesi terbuka'], 404);
        }

        return DB::transaction(function () use ($session, $validated, $userId) {
            // Calculate expected balance from cash drawer mutations for this session only.
            $cashSales = $this->sessionCashSales($session);

            $expected = $session->start_amount + $cashSales;
            $difference = $validated['end_amount_physical'] - $expected;

            $session->update([
                'end_time' => now(),
                'status' => 'closed',
                'end_amount_calculated' => $expected,
                'end_amount_physical' => $validated['end_amount_physical'],
                'difference' => $difference,
                'end_denoms' => $validated['end_denoms']
            ]);

            // Deposit to Vault
            $tenantId = auth()->user()->tenant_id;
            $vault = Vault::where('tenant_id', $tenantId)->first();
            $vault->increment('balance', $validated['end_amount_physical']);

            VaultTransaction::create([
                'type' => 'deposit',
                'amount' => $validated['end_amount_physical'],
                'balance_after' => $vault->balance,
                'description' => "Setoran tutup sesi (Order Total: Rp " . number_format($cashSales) . ")",
                'created_by' => $userId
            ]);

            // [NEW] Record Cashier Mutation for Session End
            CashierTransaction::create([
                'session_id' => $session->id,
                'user_id' => $userId,
                'type' => 'session_end',
                'amount' => -$validated['end_amount_physical'],
                'balance_after' => 0, // Drawer emptied
                'description' => "Tutup Sesi (Setor ke Brankas)"
            ]);

            return response()->json($session);
        });
    }

    public function current(Request $request)
    {
        // Use authenticated user ID instead of request param for better reliability/security
        $userId = auth()->id();
        
        $session = $this->openSessionQuery($userId)->first();

        if (!$session) {
            return response()->json(null);
        }

        $cashSales = $this->sessionCashSales($session);

        $sessionData = $session->toArray();
        $sessionData['cash_sales'] = $cashSales;
        $sessionData['expected_amount'] = $session->start_amount + $cashSales;
        $sessionData['cashier_balance'] = $this->sessionDrawerBalance($session);

        return response()->json($sessionData);
    }
}
