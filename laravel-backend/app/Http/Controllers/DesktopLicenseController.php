<?php

namespace App\Http\Controllers;

use App\Models\DesktopLicense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DesktopLicenseController extends Controller
{
    /**
     * Activate a desktop license
     * POST /api/desktop/activate
     */
    public function activate(Request $request)
    {
        $request->validate([
            'serial_key' => 'required|string',
            'device_id' => 'required|string',
        ]);

        $license = DesktopLicense::where('serial_key', $request->serial_key)->first();

        if (!$license) {
            return response()->json(['message' => 'Serial key tidak ditemukan.'], 404);
        }

        if (!$license->is_active) {
            return response()->json(['message' => 'Lisensi ini telah dinonaktifkan.'], 403);
        }

        // Lock to device_id if not already locked
        if ($license->device_id && $license->device_id !== $request->device_id) {
            return response()->json(['message' => 'Lisensi ini sudah terdaftar untuk perangkat lain.'], 403);
        }

        if (!$license->device_id) {
            $license->device_id = $request->device_id;
            $license->activated_at = now();
            $license->save();
        }

        // Check expiry
        if ($license->expiry_date && $license->expiry_date->isPast()) {
            return response()->json(['message' => 'Lisensi ini telah kedaluwarsa.'], 403);
        }

        // Generate Signed License Blob
        try {
            $blob = $this->generateSignedBlob($license);
            return response()->json(json_decode($blob), 200);
        } catch (\Exception $e) {
            Log::error("Failed to generate license blob: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menghasilkan sertifikat lisensi.'], 500);
        }
    }

    /**
     * SuperAdmin: List all licenses
     */
    public function index()
    {
        return response()->json(DesktopLicense::latest()->get());
    }

    /**
     * SuperAdmin: Create new license
     */
    public function store(Request $request)
    {
        $request->validate([
            'serial_key' => 'required|string|unique:desktop_licenses',
            'licensee_name' => 'required|string',
            'expiry_date' => 'nullable|date',
        ]);

        $license = DesktopLicense::create([
            'serial_key' => $request->serial_key,
            'licensee_name' => $request->licensee_name,
            'expiry_date' => $request->expiry_date,
            'is_active' => true,
        ]);

        return response()->json($license, 201);
    }

    /**
     * SuperAdmin: Update license
     */
    public function update(Request $request, $id)
    {
        $license = DesktopLicense::findOrFail($id);

        $request->validate([
            'licensee_name' => 'sometimes|string',
            'expiry_date' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
            'reset_device' => 'sometimes|boolean',
        ]);

        if ($request->has('licensee_name')) $license->licensee_name = $request->licensee_name;
        if ($request->has('expiry_date')) $license->expiry_date = $request->expiry_date;
        if ($request->has('is_active')) $license->is_active = $request->is_active;
        
        if ($request->reset_device) {
            $license->device_id = null;
            $license->activated_at = null;
        }

        $license->save();

        return response()->json($license);
    }

    /**
     * SuperAdmin: Delete license
     */
    public function destroy($id)
    {
        $license = DesktopLicense::findOrFail($id);
        $license->delete();
        return response()->json(['message' => 'Lisensi berhasil dihapus.']);
    }

    /**
     * Generate Ed25519 Signed License Blob compatible with Go backend
     */
    private function generateSignedBlob(DesktopLicense $license)
    {
        $issuedTo = $license->licensee_name;
        $deviceId = $license->device_id;
        $issuedAt = now()->format('Y-m-d');
        $expiry = $license->expiry_date ? $license->expiry_date->format('Y-m-d') : "";

        // Format: IssuedTo|DeviceID|IssuedAt|Expiry
        $payloadStr = "{$issuedTo}|{$deviceId}|{$issuedAt}|{$expiry}";
        
        $privateKeyBase64 = env('DESKTOP_LICENSE_PRIVATE_KEY');
        $privateKey = base64_decode($privateKeyBase64);

        if (strlen($privateKey) !== 64) {
            throw new \Exception("Invalid private key length. Expected 64 bytes.");
        }

        // Ed25519 Signature
        $signature = sodium_crypto_sign_detached($payloadStr, $privateKey);

        return json_encode([
            'payload' => [
                'issued_to' => $issuedTo,
                'device_id' => $deviceId,
                'issued_at' => $issuedAt,
                'expiry' => $expiry
            ],
            'signature' => base64_encode($signature)
        ]);
    }
}
