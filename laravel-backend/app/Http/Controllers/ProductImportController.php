<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductImportController extends Controller
{
    /**
     * POST /products/import
     * Accepts .csv or .xlsx file
     * Columns: id (optional), barcode, nama_barang, harga_beli_modal, harga_jual, stok_rak, stok_gudang
     */
    public function import(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        return $this->processImport($request, (int) $tenantId);
    }

    /**
     * POST /superadmin/tenants/{id}/products/import
     * Bulk import/update products for a specific tenant by SuperAdmin
     */
    public function importForTenant(Request $request, $id)
    {
        $tenant = \App\Models\Tenant::withoutGlobalScopes()->findOrFail($id);
        return $this->processImport($request, (int) $tenant->id);
    }

    private function processImport(Request $request, int $tenantId)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,txt|max:10240',
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        try {
            $rows = match (true) {
                in_array($ext, ['xlsx', 'xls']) => $this->parseXlsx($file->getRealPath()),
                default                          => $this->parseCsv($file->getRealPath()),
            };
        } catch (\Throwable $e) {
            Log::error('Product import parse error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json(['message' => 'File kosong atau format kolom tidak sesuai.'], 422);
        }

        $imported = 0;
        $created  = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $lineNum = $index + 2; // +2 because header = line 1

                $name = trim($row['nama_barang'] ?? $row['name'] ?? '');
                if ($name === '') {
                    $skipped++;
                    continue;
                }

                $rawId          = trim($row['id'] ?? $row['id_produk'] ?? '');
                $productId      = is_numeric($rawId) ? (int) $rawId : null;
                $rawBarcode     = trim($row['barcode'] ?? '');
                $barcode        = is_numeric($rawBarcode) ? sprintf("%.0f", (float)$rawBarcode) : $rawBarcode;
                $costPrice      = $this->toFloat($row['harga_beli_modal'] ?? $row['cost_price'] ?? 0);
                $sellingPrice   = $this->toFloat($row['harga_jual'] ?? $row['selling_price'] ?? 0);
                $shelfStock     = (int) $this->toFloat($row['stok_rak'] ?? $row['shelf_stock'] ?? 0);
                $warehouseStock = (int) $this->toFloat($row['stok_gudang'] ?? $row['warehouse_stock'] ?? 0);

                // Upsert per tenant:
                // 1. Jika ada kolom id dan cocok di tenant ini -> update
                // 2. Else jika barcode tidak kosong dan cocok di tenant ini -> update
                // 3. Else jika barcode kosong dan nama_barang sama persis di tenant ini -> update
                // 4. Selain itu -> buat record baru
                $product = null;
                if ($productId) {
                    $product = Product::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)
                        ->where('id', $productId)
                        ->first();
                }

                if (!$product && $barcode !== '') {
                    $product = Product::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)
                        ->where('barcode', $barcode)
                        ->first();
                }

                if (!$product && $barcode === '') {
                    $product = Product::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)
                        ->where('name', $name)
                        ->first();
                }

                if ($product) {
                    // Update existing product
                    $oldShelf = (int) $product->shelf_stock;
                    $oldWh    = (int) $product->warehouse_stock;

                    $updateData = [
                        'name'            => $name,
                        'cost_price'      => $costPrice,
                        'selling_price'   => $sellingPrice,
                        'shelf_stock'     => $shelfStock,
                        'warehouse_stock' => $warehouseStock,
                    ];
                    if ($barcode !== '' || array_key_exists('barcode', $row)) {
                        $updateData['barcode'] = $barcode ?: null;
                    }

                    $product->update($updateData);

                    // Record mutations if stock changed
                    $this->recordMutationIfChanged($tenantId, $product->id, $oldShelf, $shelfStock, 'SHELF');
                    $this->recordMutationIfChanged($tenantId, $product->id, $oldWh, $warehouseStock, 'WAREHOUSE');
                    $updated++;
                } else {
                    // Create new product
                    $product = Product::withoutGlobalScopes()->create([
                        'tenant_id'       => $tenantId,
                        'name'            => $name,
                        'barcode'         => $barcode ?: null,
                        'cost_price'      => $costPrice,
                        'selling_price'   => $sellingPrice,
                        'shelf_stock'     => $shelfStock,
                        'warehouse_stock' => $warehouseStock,
                    ]);

                    if ($shelfStock > 0 || $warehouseStock > 0) {
                        StockMutation::withoutGlobalScopes()->create([
                            'tenant_id'     => $tenantId,
                            'product_id'    => $product->id,
                            'type'          => 'ADJUSTMENT',
                            'from_location' => 'INITIAL',
                            'to_location'   => $shelfStock > 0 ? 'SHELF' : 'WAREHOUSE',
                            'quantity'      => $shelfStock + $warehouseStock,
                            'reference'     => 'Import Massal',
                            'created_by'    => Auth::id(),
                        ]);
                    }
                    $created++;
                }

                $imported++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product import DB error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi error saat menyimpan: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message'  => "Import selesai. {$imported} produk diproses ({$updated} diperbarui, {$created} baru), {$skipped} dilewati.",
            'imported' => $imported,
            'updated'  => $updated,
            'created'  => $created,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function toFloat(mixed $val): float
    {
        if (is_numeric($val)) return (float) $val;
        
        // Remove currency symbols and spaces
        $val = trim(str_ireplace(['Rp', ' '], '', $val));
        
        // If it contains both . and , we assume , is decimal and . is thousand separator (IDR)
        if (str_contains($val, '.') && str_contains($val, ',')) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        } elseif (str_contains($val, ',')) {
            // If only comma: check if it has exactly 3 digits after the comma (thousand separator)
            $parts = explode(',', $val);
            $lastPart = end($parts);
            if (strlen($lastPart) === 3) {
                $val = str_replace(',', '', $val);
            } else {
                $val = str_replace(',', '.', $val);
            }
        } elseif (str_contains($val, '.')) {
            // If only dot: check if it has exactly 3 digits after the dot (thousand separator)
            $parts = explode('.', $val);
            $lastPart = end($parts);
            if (strlen($lastPart) === 3 && count($parts) > 1) {
                $val = str_replace('.', '', $val);
            }
        }
        
        return (float) preg_replace('/[^\d.]/', '', $val);
    }

    private function recordMutationIfChanged(int $tenantId, int $productId, int $old, int $new, string $location): void
    {
        if ($old === $new) return;

        StockMutation::withoutGlobalScopes()->create([
            'tenant_id'     => $tenantId,
            'product_id'    => $productId,
            'type'          => 'ADJUSTMENT',
            'from_location' => $old < $new ? 'EXTERNAL' : $location,
            'to_location'   => $old < $new ? $location : 'ADJUSTMENT',
            'quantity'      => abs($new - $old),
            'reference'     => 'Import Massal',
            'created_by'    => Auth::id(),
        ]);
    }

    /**
     * Parse CSV file → array of associative arrays (header row as keys)
     */
    private function parseCsv(string $path): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');
        if (!$handle) throw new \RuntimeException('Tidak dapat membuka file CSV.');

        $header = null;
        while (($line = fgetcsv($handle, 0, ',')) !== false) {
            // Auto-detect delimiter (comma or semicolon)
            if ($header === null) {
                // Try semicolon if comma gives only 1 col
                if (count($line) === 1) {
                    rewind($handle);
                    $header = null;
                    while (($line = fgetcsv($handle, 0, ';')) !== false) {
                        if ($header === null) {
                            $header = array_map('trim', $line);
                            continue;
                        }
                        if (count($line) === count($header)) {
                            $rows[] = array_combine($header, array_map('trim', $line));
                        }
                    }
                    fclose($handle);
                    return $rows;
                }
                $header = array_map('trim', $line);
                continue;
            }

            if (count($line) !== count($header)) continue;  // skip malformed rows
            $rows[] = array_combine($header, array_map('trim', $line));
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Parse XLSX file using native PHP (unzip + parse XML)
     */
    private function parseXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Tidak dapat membuka file XLSX. Pastikan file tidak rusak.');
        }

        // Read shared strings (for text cells)
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = simplexml_load_string($ssXml);
            foreach ($ss->si as $si) {
                // Handle rich text (multiple <r> elements)
                $text = '';
                if (isset($si->t)) {
                    $text = (string) $si->t;
                } elseif (isset($si->r)) {
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }

        // Read first sheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('Sheet tidak ditemukan di dalam file XLSX.');
        }

        $sheet = simplexml_load_string($sheetXml);
        $rawRows = [];

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $type  = (string) ($cell['t'] ?? '');
                $value = (string) ($cell->v ?? '');

                if ($type === 's') {
                    // Shared string index
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'str') {
                    $value = (string) ($cell->is->t ?? $value);
                }
                // Number/date stays as-is

                $colLetter = preg_replace('/[0-9]/', '', (string) $cell['r']);
                $colIndex  = $this->columnIndex($colLetter);
                $rowData[$colIndex] = $value;
            }

            // Fill gaps with empty string
            if (!empty($rowData)) {
                $max = max(array_keys($rowData));
                for ($i = 0; $i <= $max; $i++) {
                    $rowData[$i] = $rowData[$i] ?? '';
                }
                ksort($rowData);
                $rawRows[] = array_values($rowData);
            }
        }

        if (empty($rawRows)) return [];

        $header = array_map('trim', $rawRows[0]);
        $rows   = [];
        foreach (array_slice($rawRows, 1) as $rawRow) {
            // Pad row to header length
            while (count($rawRow) < count($header)) {
                $rawRow[] = '';
            }
            $row = array_combine($header, array_slice($rawRow, 0, count($header)));
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Convert Excel column letter(s) to 0-based index (A=0, B=1, Z=25, AA=26)
     */
    private function columnIndex(string $col): int
    {
        $col   = strtoupper($col);
        $index = 0;
        $len   = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}
