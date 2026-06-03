<template>
  <div class="page-container">
    <div class="header">
      <h2>Manajemen Produk & Stok</h2>
      <div class="header-actions">
        <button class="btn-import" @click="showImportModal = true">Import Excel</button>
        <button class="btn-primary" @click="showForm = true; resetForm()">+ Tambah Produk</button>
      </div>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Barcode</th>
            <th>Nama Produk</th>
            <th>HPP (Modal)</th>
            <th>Harga Jual</th>
            <th class="text-center">Stok Rak</th>
            <th class="text-center">Stok Gudang</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in products" :key="p.id">
            <td class="font-mono">{{ p.barcode }}</td>
            <td class="font-bold">{{ p.name }}</td>
            <td class="text-dim">{{ formatCurrency(p.cost_price) }}</td>
            <td class="text-primary">{{ formatCurrency(p.selling_price) }}</td>
            <td class="text-center">
              <span class="badge" :class="p.shelf_stock <= 5 ? 'bg-red' : 'bg-blue'">{{ p.shelf_stock }}</span>
            </td>
            <td class="text-center">
              <span class="badge bg-slate">{{ p.warehouse_stock }}</span>
            </td>
            <td>
              <div class="actions-cell">
                <button class="btn-mutate" title="Mutasi Stok" @click="openMutate(p)">🔁</button>
                <button class="btn-edit" @click="editProduct(p)">Edit</button>
                <button class="btn-delete" @click="delProduct(p.id)">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-overlay">
      <div class="modal-content wide">
        <h3>{{ isEdit ? 'Edit Produk' : 'Tambah Produk Baru' }}</h3>
        
        <div class="form-row">
            <div class="form-group flex-2">
                <label>Nama Produk</label>
                <input v-model="form.name" type="text" placeholder="Contoh: Indomie Goreng" />
            </div>
            <div class="form-group flex-1">
                <label>Barcode</label>
                <input v-model="form.barcode" type="text" placeholder="Scan barcode..." />
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>HPP (Modal)</label>
                <input v-model.number="form.cost_price" type="number" />
            </div>
            <div class="form-group">
                <label>Harga Jual</label>
                <input v-model.number="form.selling_price" type="number" />
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Stok Awal (Rak)</label>
                <input v-model.number="form.shelf_stock" type="number" />
            </div>
            <div class="form-group">
                <label>Stok Awal (Gudang)</label>
                <input v-model.number="form.warehouse_stock" type="number" />
            </div>
        </div>

        <div class="actions">
          <button class="btn-cancel" @click="showForm = false">Batal</button>
          <button class="btn-save" @click="saveProduct">Simpan Produk</button>
        </div>
      </div>
    </div>

    <!-- Mutation Modal -->
    <div v-if="showMutate" class="modal-overlay">
        <div class="modal-content">
            <h3>Mutasi Stok: {{ selectedProduct?.name }}</h3>
            <p class="stock-info">Rak: {{ selectedProduct?.shelf_stock }} | Gudang: {{ selectedProduct?.warehouse_stock }}</p>
            
            <div class="form-group">
                <label>Arah Mutasi</label>
                <select v-model="mutateType">
                    <option value="G->R">Gudang ke Rak (Isi Rak)</option>
                    <option value="R->G">Rak ke Gudang</option>
                    <option value="SUP->G">Baru: Supplier ke Gudang</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Jumlah</label>
                <input v-model.number="mutateQty" type="number" min="1" />
            </div>

            <div class="actions">
                <button class="btn-cancel" @click="showMutate = false">Batal</button>
                <button class="btn-save" @click="processMutation">Proses Mutasi</button>
            </div>
        </div>
    </div>

    <!-- Import Progress Modal -->
    <div v-if="importing" class="modal-overlay">
        <div class="modal-content text-center">
            <div class="import-spinner">⏳</div>
            <h3>Memproses Impor...</h3>
            <p class="text-dim">Membaca dan menyimpan data produk.<br/>Mohon tunggu, jangan tutup aplikasi.</p>
        </div>
    </div>

    <!-- Import Result Modal -->
    <div v-if="importResult" class="modal-overlay" @click.self="importResult = null">
        <div class="modal-content text-center">
            <div class="import-result-icon" :class="importResult.success ? 'success' : 'error'">
                {{ importResult.success ? '✅' : '❌' }}
            </div>
            <h3>{{ importResult.success ? 'Import Berhasil!' : 'Import Gagal' }}</h3>
            <div class="import-stats" v-if="importResult.success">
                <div class="stat-item green">
                    <span class="stat-num">{{ importResult.imported }}</span>
                    <span class="stat-label">Produk Tersimpan</span>
                </div>
                <div class="stat-item gray" v-if="importResult.skipped > 0">
                    <span class="stat-num">{{ importResult.skipped }}</span>
                    <span class="stat-label">Dilewati (kosong)</span>
                </div>
            </div>
            <p v-if="!importResult.success" class="error-msg">{{ importResult.message }}</p>
            <div class="actions" style="margin-top: 24px;">
                <button class="btn-save" @click="importResult = null">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Import excel modal guide & upload -->
    <div v-if="showImportModal" class="modal-overlay" @click.self="showImportModal = false">
      <div class="modal-content wide import-modal-container">
        <div class="modal-header-with-icon">
          <span class="icon-circle">📂</span>
          <h3>Import Produk via Excel / CSV</h3>
        </div>
        
        <p class="subtitle-text">
          Unggah file Excel (.xlsx, .xls) atau CSV (.csv) untuk menambahkan atau memperbarui produk dalam jumlah banyak.
        </p>

        <div class="template-instructions">
          <h4>Petunjuk Format Kolom Template:</h4>
          <div class="table-scroll-container">
            <table class="instruction-table">
              <thead>
                <tr>
                  <th>Nama Kolom</th>
                  <th>Status</th>
                  <th>Tipe Data</th>
                  <th>Keterangan & Format</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code class="col-code">barcode</code></td>
                  <td><span class="badge-optional">Opsional</span></td>
                  <td>Teks / Angka</td>
                  <td>Barcode barang (misal: <code>8999881234567</code>). Jika kosong, sistem membuat barcode otomatis.</td>
                </tr>
                <tr>
                  <td><code class="col-code">nama_barang</code></td>
                  <td><span class="badge-required">Wajib</span></td>
                  <td>Teks</td>
                  <td>Nama lengkap produk (misal: <code>Sabun Mandi Lifebuoy</code>). Tidak boleh kosong.</td>
                </tr>
                <tr>
                  <td><code class="col-code">harga_beli_modal</code></td>
                  <td><span class="badge-required">Wajib</span></td>
                  <td>Angka / Uang</td>
                  <td>Harga modal. Bisa berupa angka bersih (<code>3500</code>) atau format mata uang (<code>Rp 3.500</code>).</td>
                </tr>
                <tr>
                  <td><code class="col-code">harga_jual</code></td>
                  <td><span class="badge-required">Wajib</span></td>
                  <td>Angka / Uang</td>
                  <td>Harga jual ke pelanggan. Bisa berupa angka bersih (<code>5000</code>) atau format mata uang (<code>Rp 5.000</code>).</td>
                </tr>
                <tr>
                  <td><code class="col-code">stok_rak</code></td>
                  <td><span class="badge-optional">Opsional</span></td>
                  <td>Angka</td>
                  <td>Stok awal produk di rak display kasir (default: <code>0</code>).</td>
                </tr>
                <tr>
                  <td><code class="col-code">stok_gudang</code></td>
                  <td><span class="badge-optional">Opsional</span></td>
                  <td>Angka</td>
                  <td>Stok awal produk di gudang penyimpanan (default: <code>0</code>).</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="tips-box">
            <strong>💡 Tips:</strong> Barcode yang berformat <em>Scientific Notation</em> (seperti 8.99E+12) dari Excel akan otomatis dikonversi dengan benar oleh sistem.
          </div>
        </div>

        <div class="import-modal-actions">
          <button id="btn-download-template" class="btn-download" @click="downloadTemplateCSV">
            📥 Download Template CSV
          </button>
          <div class="right-actions">
            <button id="btn-cancel-import" class="btn-cancel" @click="showImportModal = false">Batal</button>
            <button id="btn-select-file-import" class="btn-save" @click="triggerFileSelect">Pilih & Upload File</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import { formatCurrency } from '../utils/format'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const products = ref<any[]>([])
const showForm = ref(false)
const showMutate = ref(false)
const isEdit = ref(false)
const importing = ref(false)
const importProgress = ref(0)
const showImportModal = ref(false)

const selectedProduct = ref<any>(null)
const mutateQty = ref(1)
const mutateType = ref('G->R')

const form = ref({
  id: null,
  name: '',
  barcode: '',
  cost_price: 0,
  selling_price: 0,
  shelf_stock: 0,
  warehouse_stock: 0
})

const loadData = async () => {
  try {
    const res = await api.get('/products')
    products.value = res.data || []
  } catch (e) {
    console.error("Load products error", e)
  }
}

onMounted(loadData)

const resetForm = () => {
  isEdit.value = false
  form.value = {
    id: null,
    name: '',
    barcode: '',
    cost_price: 0,
    selling_price: 0,
    shelf_stock: 0,
    warehouse_stock: 0
  }
}

const editProduct = (p: any) => {
  form.value = { ...p }
  isEdit.value = true
  showForm.value = true
}

const saveProduct = async () => {
  try {
    if (isEdit.value) {
      await api.put(`/products/${form.value.id}`, form.value)
    } else {
      await api.post('/products', form.value)
    }
    showForm.value = false
    loadData()
  } catch(e) {
    alert("Error saving: " + e)
  }
}

const delProduct = async (id: number) => {
  if (confirm("Hapus produk ini secara permanen?")) {
    try {
      await api.delete(`/products/${id}`)
      loadData()
    } catch(e) {
      alert("Error deleting: " + e)
    }
  }
}

const openMutate = (p: any) => {
    selectedProduct.value = p
    mutateQty.value = 1
    showMutate.value = true
}

const processMutation = async () => {
    if (!selectedProduct.value || mutateQty.value < 1) return
    
    // Mutation logic would call a custom endpoint in a real app
    // For now, we update stocks directly via PUT /products/{id}
    const p = { ...selectedProduct.value }

    if (mutateType.value === 'G->R') {
        if (p.warehouse_stock < mutateQty.value) { alert("Stok gudang tidak mencukupi!"); return; }
        p.warehouse_stock -= mutateQty.value
        p.shelf_stock += mutateQty.value
    } else if (mutateType.value === 'R->G') {
        if (p.shelf_stock < mutateQty.value) { alert("Stok rak tidak mencukupi!"); return; }
        p.shelf_stock -= mutateQty.value
        p.warehouse_stock += mutateQty.value
    } else if (mutateType.value === 'SUP->G') {
        p.warehouse_stock += mutateQty.value
    }

    try {
        await api.put(`/products/${p.id}`, p)
        // Record mutation could be a separate log endpoint
        showMutate.value = false
        loadData()
    } catch(e) {
        alert("Gagal memproses mutasi: " + e)
    }
}

const importResult = ref<any>(null)

const handleExcelImport = () => {
    // Accept CSV and XLSX
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = '.csv,.xlsx,.xls'
    input.onchange = async (e: any) => {
        const file = e.target.files[0]
        if (!file) return

        const formData = new FormData()
        formData.append('file', file)

        importing.value = true
        importResult.value = null
        try {
            const res = await api.post('/products/import', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                timeout: 60000,
            })
            await loadData()
            importResult.value = {
                success: true,
                imported: res.data.imported ?? 0,
                skipped: res.data.skipped ?? 0,
                message: res.data.message,
            }
        } catch(e: any) {
            importResult.value = {
                success: false,
                message: e.response?.data?.message || e.message || 'Gagal menghubungi server.',
            }
        } finally {
            importing.value = false
        }
    }
    input.click()
}

const downloadTemplateCSV = () => {
  const headers = ['barcode', 'nama_barang', 'harga_beli_modal', 'harga_jual', 'stok_rak', 'stok_gudang'];
  const sampleRow = ['8999881234567', 'Sabun Mandi Lifebuoy', '3500', '5000', '10', '50'];
  const csvContent = [headers.join(','), sampleRow.join(',')].join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", "template_stok_produk.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

const triggerFileSelect = () => {
  showImportModal.value = false;
  handleExcelImport();
}
</script>

<style scoped>
.page-container {
  padding: 30px;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.header h2 {
  margin: 0;
  color: #0f172a;
  font-weight: 800;
}
.header-actions {
    display: flex;
    gap: 12px;
}
.btn-import {
    padding: 10px 20px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}
.btn-primary {
  padding: 10px 24px;
  background: #0ea5e9;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.2);
}

.table-container {
  background: white;
  border-radius: 16px;
  padding: 0;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
  overflow: auto; /* Handle both X and Y */
  border: 1px solid #e2e8f0;
  max-height: calc(100vh - 200px);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  font-weight: 700;
  color: #334155;
  background: #f8fafc;
  padding: 16px;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  border-bottom: 2px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 10;
}

td {
  padding: 16px;
  color: #0f172a;
  font-size: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.font-bold { font-weight: 700; }
.font-mono { font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 13px; color: #64748b; }
.text-dim { color: #94a3b8; font-size: 13px; }
.text-primary { color: #0284c7; font-weight: 800; }
.text-center { text-align: center; }

.badge {
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
    color: white;
}
.bg-red { background: #ef4444; }
.bg-blue { background: #3b82f6; }
.bg-slate { background: #64748b; }

.actions-cell {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-mutate {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 6px;
    cursor: pointer;
}

.btn-edit {
  padding: 6px 12px;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}
.btn-delete {
  padding: 6px 12px;
  background: #fee2e2;
  color: #ef4444;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}
.modal-content {
  background: white;
  width: 440px;
  padding: 32px;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}
.modal-content.wide {
    width: 90%;
    max-width: 600px;
}
.modal-content h3 {
  margin-top: 0;
  margin-bottom: 24px;
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
}
.stock-info {
    background: #f0f9ff;
    color: #0369a1;
    padding: 12px;
    border-radius: 8px;
    font-weight: 700;
    margin-bottom: 20px;
}
.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}
.flex-1 { flex: 1; }
.flex-2 { flex: 2; }

.form-group {
  margin-bottom: 16px;
  flex: 1;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 700;
  color: #334155;
  font-size: 13px;
}
.form-group input, .form-group select {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  box-sizing: border-box;
  font-size: 15px;
  transition: border-color 0.2s;
}
.form-group input:focus {
    outline: none;
    border-color: #3b82f6;
}
.actions {
  display: flex;
  gap: 12px;
  margin-top: 32px;
}

.progress-container {
    width: 100%;
    height: 12px;
    background: #f1f5f9;
    border-radius: 6px;
    margin-top: 20px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: #0ea5e9;
    transition: width 0.3s ease;
}

.mt-4 { margin-top: 16px; }
.btn-cancel, .btn-save {
  flex: 1;
  padding: 14px;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  font-size: 15px;
}
.btn-cancel {
  background: #f1f5f9;
  color: #475569;
}
.btn-save {
  background: #10b981;
  color: white;
}

/* Import UI */
.import-spinner {
  font-size: 48px;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-bottom: 12px;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.import-result-icon {
  font-size: 52px;
  margin-bottom: 12px;
}
.import-stats {
  display: flex;
  justify-content: center;
  gap: 24px;
  margin: 20px 0;
}
.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 24px;
  border-radius: 12px;
  min-width: 100px;
}
.stat-item.green { background: #dcfce7; }
.stat-item.gray  { background: #f1f5f9; }
.stat-num {
  font-size: 36px;
  font-weight: 900;
  line-height: 1;
}
.stat-item.green .stat-num { color: #16a34a; }
.stat-item.gray  .stat-num { color: #64748b; }
.stat-label {
  font-size: 12px;
  font-weight: 600;
  margin-top: 6px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.stat-item.green .stat-label { color: #15803d; }
.stat-item.gray  .stat-label { color: #64748b; }
.error-msg {
  color: #ef4444;
  background: #fee2e2;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-top: 12px;
}

/* Import Template Guide CSS */
.import-modal-container {
  max-width: 700px !important;
  width: 90% !important;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header-with-icon {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.modal-header-with-icon h3 {
  margin: 0 !important;
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
}
.icon-circle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: #e0f2fe;
  border-radius: 50%;
  font-size: 20px;
}
.subtitle-text {
  color: #475569;
  font-size: 14px;
  margin-top: 0;
  margin-bottom: 20px;
}
.template-instructions {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
  max-height: 280px;
  overflow-y: auto;
}
/* Scrollbar kustom untuk petunjuk template */
.template-instructions::-webkit-scrollbar {
  width: 6px;
}
.template-instructions::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}
.template-instructions::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.template-instructions::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
.template-instructions h4 {
  margin-top: 0;
  margin-bottom: 12px;
  color: #0f172a;
  font-size: 15px;
  font-weight: 700;
}
.table-scroll-container {
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
}
.instruction-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.instruction-table th {
  background: #f1f5f9;
  color: #334155;
  padding: 10px 12px;
  font-weight: 700;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
  position: static;
  z-index: 1;
}
.instruction-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  color: #0f172a;
  text-align: left;
}
.col-code {
  background: #f1f5f9;
  color: #0f172a;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-weight: 600;
}
.badge-required {
  background: #fee2e2;
  color: #ef4444;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}
.badge-optional {
  background: #f1f5f9;
  color: #64748b;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}
.tips-box {
  margin-top: 12px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #b45309;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.5;
  text-align: left;
}
.import-modal-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.right-actions {
  display: flex;
  gap: 12px;
}
.btn-download {
  padding: 10px 16px;
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}
.btn-download:hover {
  background: #dcfce7;
}
</style>
