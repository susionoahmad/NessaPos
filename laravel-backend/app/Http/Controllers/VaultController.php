<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Vault;
use App\Models\VaultTransaction;

class VaultController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        return response()->json(Vault::where('tenant_id', $tenantId)->first() ?: ['balance' => 0, 'tenant_id' => $tenantId]);
    }

    public function transactions()
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Filter transaksi berdasarkan user yang termasuk dalam tenant tersebut
        return response()->json(
            VaultTransaction::whereHas('user', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->with('user')->latest()->get()
        );
    }

    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'nullable',
            'created_by' => 'required'
        ]);

        $tenantId = auth()->user()->tenant_id;
        $vault = Vault::firstOrCreate(['tenant_id' => $tenantId], ['balance' => 0]);
        $vault->increment('balance', $validated['amount']);

        $tx = VaultTransaction::create([
            'type' => 'deposit',
            'amount' => $validated['amount'],
            'balance_after' => $vault->balance,
            'description' => $validated['description'] ?? 'Penyetoran manual ke brankas',
            'created_by' => $validated['created_by']
        ]);

        return response()->json($vault);
    }
}
