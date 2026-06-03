<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionRenewalRequest;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class SubscriptionRenewalController extends Controller
{
    /**
     * List all renewal requests (for SuperAdmin)
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        
        $requests = SubscriptionRenewalRequest::with(['tenant', 'package', 'processor'])
            ->when($status !== 'all', function($q) use ($status) {
                return $q->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Create a new renewal request (for Store Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $package = SubscriptionPackage::findOrFail($request->package_id);

        $renewalRequest = SubscriptionRenewalRequest::create([
            'tenant_id' => $user->tenant_id,
            'package_id' => $package->id,
            'price_at_request' => $package->price,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Permintaan perpanjangan paket berhasil diajukan.',
            'data' => $renewalRequest->load('package')
        ], 201);
    }

    /**
     * Approve a renewal request (for SuperAdmin)
     */
    public function approve(Request $request, $id)
    {
        $renewalRequest = SubscriptionRenewalRequest::findOrFail($id);
        
        if ($renewalRequest->status !== 'pending') {
            return response()->json(['message' => 'Permintaan ini sudah diproses.'], 422);
        }

        return DB::transaction(function () use ($renewalRequest) {
            $tenant = $renewalRequest->tenant;
            $package = $renewalRequest->package;

            // Calculate new active until date
            // If already active, add to existing date. If expired, start from today.
            $currentActiveUntil = $tenant->subscription_active_until;
            $baseDate = ($currentActiveUntil && $currentActiveUntil->isFuture()) 
                ? $currentActiveUntil 
                : now();

            $newActiveUntil = $baseDate->addDays($package->duration_days);

            // Update Tenant
            $tenant->update([
                'subscription_plan' => $package->slug,
                'subscription_active_until' => $newActiveUntil,
                'is_active' => true,
            ]);

            // Update Request status
            $renewalRequest->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Permintaan perpanjangan paket berhasil disetujui.',
                'new_active_until' => $newActiveUntil->format('Y-m-d H:i:s')
            ]);
        });
    }

    /**
     * Reject a renewal request (for SuperAdmin)
     */
    public function reject(Request $request, $id)
    {
        $renewalRequest = SubscriptionRenewalRequest::findOrFail($id);
        
        if ($renewalRequest->status !== 'pending') {
            return response()->json(['message' => 'Permintaan ini sudah diproses.'], 422);
        }

        $renewalRequest->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'notes' => $request->notes ?? $renewalRequest->notes,
        ]);

        return response()->json(['message' => 'Permintaan perpanjangan paket telah ditolak.']);
    }
}
