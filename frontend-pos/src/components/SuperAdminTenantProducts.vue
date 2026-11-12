<template>
  <div class="sa-tenant-products">
    <!-- Top Controls: Tenant Selector & Action Buttons -->
    <div class="tp-header-card">
      <div class="tp-selector-group">
        <label class="tp-label">Pilih Toko (Tenant)</label>
        <div class="tp-selector-row">
          <select v-model="selectedTenantId" @change="onTenantChange" class="tp-select">
            <option :value="null" disabled>-- Pilih Toko untuk Mengambil Data Produk --</option>
            <option v-for="t in tenants" :key="t.id" :value="t.id">
              {{ t.name }} (@{{ t.slug }}) — {{ t.product_count ?? 0 }} produk
            </option>
          </select>
          <button
            v-if="selectedTenantId"
            class="btn-refresh"
            @click="loadProducts"
            :disabled="loading"
            title="Muat ulang data produk"
          >
            🔄 Muat Ulang
          </button>
        </div>
      </div>

      <div v-if="selectedTenantId" class="tp-actions">
        <button class="btn-export" @click="exportTenantProductsCSV" :disabled="loading">
          📥 Ambil / Download Data Produk (CSV)
        </button>
        <button class="btn-upload" @click="showUploadModal = true" :disabled="importing">
          📤 Upload & Perbarui Produk (CSV/Excel)
        </button>
        <button class="btn-add" @click="openProductModal()">
          + Tambah Produk
        </button>
      </div>
    </div>

    <!-- Alert -->
    <div v-if="feedback.msg" :class="['tp-alert', feedback.type]">
      <span>{{ feedback.msg }}</span>
      <button class="alert-close" @click="feedback.msg = ''">&times;</button>
    </div>

    <!-- Empty when no tenant selected -->
    <div v-if="!selectedTenantId" class="tp-empty-state">
      <div class="empty-icon">🏪</div>
      <h3>Pilih Toko Terlebih Dahulu</h3>
      <p>Pilih salah satu toko di atas untuk mengambil daftar produk, mengunduh ke CSV/Excel, memperbarui secara massal, dan mengunggahnya kembali.</p>
    </div>

    <!-- Tenant Products Content -->
    <template v-else>
      <div class="tp-toolbar">
        <div class="tp-search-box">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="🔍 Cari nama produk atau barcode..."
            class="tp-search-input"
          />
        </div>
        <div class="tp-toolbar-right">
          <span class="tp-count-badge">
            Menampilkan <b>{{ filteredProducts.length }}</b> dari <b>{{ products.length }}</b> produk
          </span>
          <button
            v-if="products.length > 0 && !inlineEditMode"
            class="btn-inline-toggle"
            @click="enableInlineEdit"
          >
            ✏️ Mode Edit Langsung di Tabel
          </button>
          <template v-if="inlineEditMode">
            <button class="btn-cancel-inline" @click="cancelInlineEdit" :disabled="savingBulk">
              Batal Edit Tabel
            </button>
            <button class="btn-save-inline" @click="saveBulkInlineEdit" :disabled="savingBulk">
              {{ savingBulk ? '⏳ Menyimpan...' : '💾 Simpan Perubahan Tabel' }}
            </button>
          </template>
        </div>
      </div>

      <div v-if="loading" class="tp-loading">Memuat produk milik toko...</div>

      <div v-else-if="products.length === 0" class="tp-empty-state">
        <div class="empty-icon">📦</div>
        <h3>Belum Ada Produk di Toko Ini</h3>
        <p>Anda dapat menambahkan produk baru atau mengunggah file CSV / Excel untuk mengisi data produk toko ini.</p>
        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 16px;">
          <button class="btn-export" @click="downloadBlankTemplateCSV">📄 Download Template CSV</button>
          <button class="btn-upload" @click="showUploadModal = true">📤 Upload Produk (CSV/Excel)</button>
        </div>
      </div>

      <div v-else class="tp-table-wrapper">
        <table class="tp-table">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th>Barcode</th>
              <th>Nama Barang</th>
              <th>HPP (Modal)</th>
              <th>Harga Jual</th>
              <th class="text-center">Stok Rak</th>
              <th class="text-center">Stok Gudang</th>
              <th v-if="!inlineEditMode" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in filteredProducts" :key="p.id">
              <td class="font-mono text-dim">#{{ p.id }}</td>

              <!-- Inline Edit Mode -->
              <template v-if="inlineEditMode">
                <td>
                  <input v-model="p.barcode" class="inline-input font-mono" placeholder="Kosong..." />
                </td>
                <td>
                  <input v-model="p.name" class="inline-input" placeholder="Nama produk" />
                </td>
                <td>
                  <input v-model.number="p.cost_price" type="number" class="inline-input num-input" />
                </td>
                <td>
                  <input v-model.number="p.selling_price" type="number" class="inline-input num-input" />
                </td>
                <td class="text-center">
                  <input v-model.number="p.shelf_stock" type="number" class="inline-input stock-input" />
                </td>
                <td class="text-center">
                  <input v-model.number="p.warehouse_stock" type="number" class="inline-input stock-input" />
                </td>
              </template>

              <!-- Normal Read Mode -->
              <template v-else>
                <td class="font-mono">{{ p.barcode || '-' }}</td>
                <td class="font-bold">{{ p.name }}</td>
                <td class="text-dim">{{ formatCurrency(p.cost_price) }}</td>
                <td class="text-sky">{{ formatCurrency(p.selling_price) }}</td>
                <td class="text-center">
                  <span :class="['stock-badge', p.shelf_stock <= 5 ? 'low' : 'normal']">
                    {{ p.shelf_stock }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="stock-badge warehouse">{{ p.warehouse_stock }}</span>
                </td>
                <td class="text-center">
                  <div class="row-actions">
                    <button class="btn-row-edit" @click="openProductModal(p)">Edit</button>
                    <button class="btn-row-delete" @click="deleteProduct(p)">Hapus</button>
                  </div>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Modal: Upload & Update CSV/Excel -->
    <div v-if="showUploadModal" class="modal-overlay" @click.self="showUploadModal = false">
      <div class="modal wide-modal">
        <div class="modal-header">
          <h3>📤 Upload & Perbarui Data Produk — {{ selectedTenantName }}</h3>
          <button class="modal-close" @click="showUploadModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <p class="modal-desc">
            Unggah file <b>CSV (.csv)</b> atau <b>Excel (.xlsx, .xls)</b> yang sudah Anda perbarui untuk langsung memperbarui data produk milik toko <b>{{ selectedTenantName }}</b>.
          </p>

          <div class="guide-box">
            <h4>Cara Kerja Sinkronisasi / Pembaruan Massal:</h4>
            <ul>
              <li>
                <b>Menimpa Data Lama (Update):</b> Jika kolom <code>id</code> atau <code>barcode</code> cocok dengan produk yang sudah ada di toko ini (atau <code>nama_barang</code> sama untuk produk tanpa barcode), sistem otomatis <b>memperbarui harga, nama, dan stok</b> produk tersebut tanpa menggandakan data.
              </li>
              <li>
                <b>Menambah Produk Baru (Insert):</b> Jika Anda menambahkan baris baru dengan <code>id</code> dikosongkan dan <code>barcode</code> baru, sistem akan <b>menambahkan produk baru</b> ke toko ini.
              </li>
            </ul>
          </div>

          <div class="col-table-wrap">
            <table class="col-guide-table">
              <thead>
                <tr>
                  <th>Kolom</th>
                  <th>Status</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>id</code></td>
                  <td><span class="tag-opt">Opsional</span></td>
                  <td>ID produk dari hasil download CSV. Biarkan tetap terisi untuk update produk lama, atau kosongkan untuk produk baru.</td>
                </tr>
                <tr>
                  <td><code>barcode</code></td>
                  <td><span class="tag-opt">Opsional</span></td>
                  <td>Kode barcode produk. Juga digunakan sebagai acuan update jika kolom <code>id</code> tidak ada.</td>
                </tr>
                <tr>
                  <td><code>nama_barang</code></td>
                  <td><span class="tag-req">Wajib</span></td>
                  <td>Nama produk (tidak boleh kosong).</td>
                </tr>
                <tr>
                  <td><code>harga_beli_modal</code></td>
                  <td><span class="tag-req">Wajib</span></td>
                  <td>Harga modal / HPP (contoh: <code>3500</code>).</td>
                </tr>
                <tr>
                  <td><code>harga_jual</code></td>
                  <td><span class="tag-req">Wajib</span></td>
                  <td>Harga jual ke pelanggan (contoh: <code>5000</code>).</td>
                </tr>
                <tr>
                  <td><code>stok_rak</code></td>
                  <td><span class="tag-opt">Opsional</span></td>
                  <td>Jumlah stok di rak kasir (default: <code>0</code>).</td>
                </tr>
                <tr>
                  <td><code>stok_gudang</code></td>
                  <td><span class="tag-opt">Opsional</span></td>
                  <td>Jumlah stok di gudang (default: <code>0</code>).</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer split-footer">
          <button class="btn-export" @click="exportTenantProductsCSV">
            📥 Download Data Produk Saat Ini (CSV)
          </button>
          <div style="display: flex; gap: 10px;">
            <button class="btn-cancel" @click="showUploadModal = false">Batal</button>
            <button class="btn-save" @click="triggerFileUpload" :disabled="importing">
              {{ importing ? '⏳ Mengunggah...' : '📂 Pilih File & Upload' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Import Result -->
    <div v-if="importResult" class="modal-overlay" @click.self="importResult = null">
      <div class="modal" style="max-width: 440px; text-align: center;">
        <div class="modal-body" style="padding: 28px;">
          <div style="font-size: 48px; margin-bottom: 10px;">
            {{ importResult.success ? '✅' : '❌' }}
          </div>
          <h3 style="margin: 0 0 8px; color: white;">
            {{ importResult.success ? 'Pembaruan Produk Berhasil!' : 'Upload Gagal' }}
          </h3>
          <p style="color: #94a3b8; font-size: 13px; margin: 0 0 18px;">
            {{ importResult.message }}
          </p>
          <div v-if="importResult.success" class="result-stats">
            <div class="r-stat blue">
              <span class="r-num">{{ importResult.updated ?? 0 }}</span>
              <span class="r-lbl">Diperbarui</span>
            </div>
            <div class="r-stat green">
              <span class="r-num">{{ importResult.created ?? 0 }}</span>
              <span class="r-lbl">Produk Baru</span>
            </div>
            <div v-if="importResult.skipped > 0" class="r-stat gray">
              <span class="r-num">{{ importResult.skipped }}</span>
              <span class="r-lbl">Dilewati</span>
            </div>
          </div>
          <button class="btn-save" style="width: 100%; margin-top: 16px;" @click="importResult = null">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Single Add / Edit Product -->
    <div v-if="showProductModal" class="modal-overlay" @click.self="showProductModal = false">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editingProductId ? 'Edit Produk Toko' : 'Tambah Produk ke Toko' }}</h3>
          <button class="modal-close" @click="showProductModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="mf-group">
            <label>Nama Produk</label>
            <input v-model="productForm.name" placeholder="Contoh: Sabun Lifebuoy" />
          </div>
          <div class="mf-group">
            <label>Barcode (Opsional)</label>
            <input v-model="productForm.barcode" placeholder="Contoh: 8999881234567" />
          </div>
          <div class="form-grid-2">
            <div class="mf-group">
              <label>HPP / Harga Modal</label>
              <input v-model.number="productForm.cost_price" type="number" />
            </div>
            <div class="mf-group">
              <label>Harga Jual</label>
              <input v-model.number="productForm.selling_price" type="number" />
            </div>
          </div>
          <div class="form-grid-2">
            <div class="mf-group">
              <label>Stok Rak</label>
              <input v-model.number="productForm.shelf_stock" type="number" />
            </div>
            <div class="mf-group">
              <label>Stok Gudang</label>
              <input v-model.number="productForm.warehouse_stock" type="number" />
            </div>
          </div>
          <div v-if="productFormError" class="mf-error">{{ productFormError }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="showProductModal = false">Batal</button>
          <button class="btn-save" @click="saveSingleProduct" :disabled="savingSingle">
            {{ savingSingle ? 'Menyimpan...' : 'Simpan Produk' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import api from '../api'
import { formatCurrency } from '../utils/format'

const props = defineProps<{
  tenants: any[]
  initialTenantId?: number | null
}>()

const emit = defineEmits<{
  (e: 'refresh-tenants'): void
}>()

const selectedTenantId = ref<number | null>(props.initialTenantId ?? null)
const products = ref<any[]>([])
const backupProducts = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')

// Inline Bulk Edit State
const inlineEditMode = ref(false)
const savingBulk = ref(false)

// Upload / Import State
const showUploadModal = ref(false)
const importing = ref(false)
const importResult = ref<any>(null)

// Single Product Modal State
const showProductModal = ref(false)
const editingProductId = ref<number | null>(null)
const savingSingle = ref(false)
const productFormError = ref('')
const productForm = ref({
  name: '',
  barcode: '',
  cost_price: 0,
  selling_price: 0,
  shelf_stock: 0,
  warehouse_stock: 0,
})

const feedback = ref<{ msg: string; type: string }>({ msg: '', type: 'success' })

const showFeedback = (msg: string, type = 'success') => {
  feedback.value = { msg, type }
  setTimeout(() => {
    if (feedback.value.msg === msg) feedback.value.msg = ''
  }, 5000)
}

const selectedTenantName = computed(() => {
  const t = props.tenants.find(item => item.id === selectedTenantId.value)
  return t ? `${t.name} (@${t.slug})` : ''
})

const selectedTenantSlug = computed(() => {
  const t = props.tenants.find(item => item.id === selectedTenantId.value)
  return t ? t.slug : 'tenant'
})

const filteredProducts = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return products.value
  return products.value.filter(p =>
    (p.name || '').toLowerCase().includes(q) ||
    (p.barcode || '').toLowerCase().includes(q) ||
    String(p.id).includes(q)
  )
})

watch(
  () => props.initialTenantId,
  (newId) => {
    if (newId && newId !== selectedTenantId.value) {
      selectedTenantId.value = newId
      loadProducts()
    }
  }
)

onMounted(() => {
  if (selectedTenantId.value) {
    loadProducts()
  } else if (props.tenants.length > 0 && props.initialTenantId) {
    selectedTenantId.value = props.initialTenantId
    loadProducts()
  }
})

const onTenantChange = () => {
  inlineEditMode.value = false
  searchQuery.value = ''
  loadProducts()
}

const loadProducts = async () => {
  if (!selectedTenantId.value) return
  loading.value = true
  inlineEditMode.value = false
  try {
    const res = await api.get(`/superadmin/tenants/${selectedTenantId.value}/products`)
    products.value = res.data.products || []
  } catch (e: any) {
    showFeedback('Gagal mengambil data produk: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    loading.value = false
  }
}

// ─── Export / Download CSV ──────────────────────────────────────────────────

const escapeCsvField = (val: any): string => {
  if (val === null || val === undefined) return ''
  const str = String(val)
  if (str.includes(',') || str.includes('"') || str.includes('\n')) {
    return `"${str.replace(/"/g, '""')}"`
  }
  return str
}

const exportTenantProductsCSV = () => {
  if (!selectedTenantId.value) return

  const headers = ['id', 'barcode', 'nama_barang', 'harga_beli_modal', 'harga_jual', 'stok_rak', 'stok_gudang']
  const rows = products.value.map(p => [
    escapeCsvField(p.id),
    escapeCsvField(p.barcode ?? ''),
    escapeCsvField(p.name ?? ''),
    escapeCsvField(Number(p.cost_price ?? 0)),
    escapeCsvField(Number(p.selling_price ?? 0)),
    escapeCsvField(Number(p.shelf_stock ?? 0)),
    escapeCsvField(Number(p.warehouse_stock ?? 0)),
  ])

  const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  const dateStr = new Date().toISOString().slice(0, 10)
  link.setAttribute('href', url)
  link.setAttribute('download', `produk_${selectedTenantSlug.value}_${dateStr}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  showFeedback(`Berhasil mengunduh ${products.value.length} data produk ke file CSV.`)
}

const downloadBlankTemplateCSV = () => {
  const headers = ['id', 'barcode', 'nama_barang', 'harga_beli_modal', 'harga_jual', 'stok_rak', 'stok_gudang']
  const sampleRow = ['', '8999881234567', 'Sabun Mandi Lifebuoy', '3500', '5000', '10', '50']
  const csvContent = '\uFEFF' + [headers.join(','), sampleRow.join(',')].join('\r\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `template_produk_${selectedTenantSlug.value}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

// ─── Upload / Import CSV or Excel ───────────────────────────────────────────

const triggerFileUpload = () => {
  if (!selectedTenantId.value) return
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.csv,.xlsx,.xls'
  input.onchange = async (e: any) => {
    const file = e.target.files?.[0]
    if (!file) return

    showUploadModal.value = false
    importing.value = true
    importResult.value = null

    const formData = new FormData()
    formData.append('file', file)

    try {
      const res = await api.post(`/superadmin/tenants/${selectedTenantId.value}/products/import`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        timeout: 60000,
      })
      await loadProducts()
      emit('refresh-tenants')
      importResult.value = {
        success: true,
        imported: res.data.imported ?? 0,
        updated: res.data.updated ?? 0,
        created: res.data.created ?? 0,
        skipped: res.data.skipped ?? 0,
        message: res.data.message,
      }
    } catch (err: any) {
      importResult.value = {
        success: false,
        message: err.response?.data?.message || err.message || 'Gagal mengunggah file.',
      }
    } finally {
      importing.value = false
    }
  }
  input.click()
}

// ─── Inline Bulk Table Edit ─────────────────────────────────────────────────

const enableInlineEdit = () => {
  backupProducts.value = products.value.map(p => ({ ...p }))
  inlineEditMode.value = true
}

const cancelInlineEdit = () => {
  products.value = backupProducts.value.map(p => ({ ...p }))
  inlineEditMode.value = false
}

const saveBulkInlineEdit = async () => {
  if (!selectedTenantId.value) return
  savingBulk.value = true
  try {
    const res = await api.post(`/superadmin/tenants/${selectedTenantId.value}/products/bulk-update`, {
      products: products.value,
    })
    inlineEditMode.value = false
    showFeedback(res.data.message || 'Perubahan produk berhasil disimpan!')
    await loadProducts()
  } catch (e: any) {
    showFeedback('Gagal menyimpan perubahan: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    savingBulk.value = false
  }
}

// ─── Single Product Add / Edit / Delete ─────────────────────────────────────

const openProductModal = (p?: any) => {
  productFormError.value = ''
  if (p) {
    editingProductId.value = p.id
    productForm.value = {
      name: p.name || '',
      barcode: p.barcode || '',
      cost_price: Number(p.cost_price ?? 0),
      selling_price: Number(p.selling_price ?? 0),
      shelf_stock: Number(p.shelf_stock ?? 0),
      warehouse_stock: Number(p.warehouse_stock ?? 0),
    }
  } else {
    editingProductId.value = null
    productForm.value = {
      name: '',
      barcode: '',
      cost_price: 0,
      selling_price: 0,
      shelf_stock: 0,
      warehouse_stock: 0,
    }
  }
  showProductModal.value = true
}

const saveSingleProduct = async () => {
  if (!selectedTenantId.value) return
  if (!productForm.value.name.trim()) {
    productFormError.value = 'Nama produk wajib diisi.'
    return
  }

  savingSingle.value = true
  productFormError.value = ''
  try {
    if (editingProductId.value) {
      await api.put(
        `/superadmin/tenants/${selectedTenantId.value}/products/${editingProductId.value}`,
        productForm.value
      )
      showFeedback('Produk berhasil diperbarui!')
    } else {
      await api.post(`/superadmin/tenants/${selectedTenantId.value}/products`, productForm.value)
      showFeedback('Produk baru berhasil ditambahkan!')
      emit('refresh-tenants')
    }
    showProductModal.value = false
    await loadProducts()
  } catch (e: any) {
    productFormError.value = e.response?.data?.message || e.message || 'Gagal menyimpan produk.'
  } finally {
    savingSingle.value = false
  }
}

const deleteProduct = async (p: any) => {
  if (!selectedTenantId.value) return
  if (!confirm(`Hapus produk "${p.name}" dari toko ini?`)) return
  try {
    await api.delete(`/superadmin/tenants/${selectedTenantId.value}/products/${p.id}`)
    showFeedback('Produk berhasil dihapus.')
    await loadProducts()
    emit('refresh-tenants')
  } catch (e: any) {
    showFeedback('Gagal menghapus produk: ' + (e.response?.data?.message || e.message), 'error')
  }
}
</script>

<style scoped>
.sa-tenant-products {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.tp-header-card {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.tp-selector-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  min-width: 0;
}

.tp-label {
  font-size: 12px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.tp-selector-row {
  display: flex;
  gap: 12px;
  align-items: center;
  width: 100%;
  min-width: 0;
}

.tp-select {
  flex: 1;
  min-width: 0;
  width: 100%;
  max-width: 100%;
  padding: 11px 14px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 10px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  outline: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tp-select:focus {
  border-color: #38bdf8;
}

.tp-select option {
  background: #0f172a;
  color: white;
}

.btn-refresh {
  flex-shrink: 0;
  padding: 11px 16px;
  background: rgba(255, 255, 255, 0.08);
  color: #e2e8f0;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  white-space: nowrap;
}

.btn-refresh:hover {
  background: rgba(255, 255, 255, 0.14);
}

.tp-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  padding-top: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.btn-export {
  padding: 11px 16px;
  background: rgba(14, 165, 233, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.35);
  border-radius: 10px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-export:hover {
  background: rgba(14, 165, 233, 0.25);
}

.btn-upload {
  padding: 11px 16px;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-upload:hover {
  opacity: 0.92;
}

.btn-add {
  padding: 11px 16px;
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}

/* Alert */
.tp-alert {
  padding: 12px 18px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tp-alert.success {
  background: rgba(52, 211, 153, 0.15);
  border: 1px solid #34d399;
  color: #34d399;
}

.tp-alert.error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid #ef4444;
  color: #fca5a5;
}

.alert-close {
  background: none;
  border: none;
  color: inherit;
  font-size: 18px;
  cursor: pointer;
}

/* Toolbar */
.tp-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.tp-search-box {
  flex: 1;
  min-width: 240px;
  max-width: 420px;
}

.tp-search-input {
  width: 100%;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 10px;
  color: white;
  font-size: 14px;
  outline: none;
}

.tp-search-input:focus {
  border-color: #38bdf8;
}

.tp-toolbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.tp-count-badge {
  font-size: 13px;
  color: #94a3b8;
}

.tp-count-badge b {
  color: white;
}

.btn-inline-toggle {
  padding: 9px 14px;
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}

.btn-cancel-inline {
  padding: 9px 14px;
  background: rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
}

.btn-save-inline {
  padding: 9px 16px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}

/* Table */
.tp-table-wrapper {
  background: rgba(15, 23, 42, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  overflow: auto;
  max-height: calc(100vh - 300px);
}

.tp-table {
  width: 100%;
  border-collapse: collapse;
}

.tp-table th {
  background: #0f172a;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  position: sticky;
  top: 0;
  z-index: 5;
}

.tp-table td {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  font-size: 14px;
  color: #f1f5f9;
}

.tp-table tr:hover td {
  background: rgba(255, 255, 255, 0.03);
}

.font-mono {
  font-family: 'JetBrains Mono', monospace;
  font-size: 13px;
}

.font-bold {
  font-weight: 700;
}

.text-dim {
  color: #94a3b8;
}

.text-sky {
  color: #38bdf8;
  font-weight: 700;
}

.text-center {
  text-align: center !important;
}

.stock-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.stock-badge.normal {
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
}

.stock-badge.low {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

.stock-badge.warehouse {
  background: rgba(148, 163, 184, 0.2);
  color: #cbd5e1;
}

.row-actions {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-row-edit {
  padding: 5px 12px;
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.btn-row-delete {
  padding: 5px 12px;
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

/* Inline inputs */
.inline-input {
  width: 100%;
  padding: 7px 10px;
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(56, 189, 248, 0.4);
  border-radius: 6px;
  color: white;
  font-size: 13px;
}

.inline-input.num-input {
  max-width: 130px;
}

.inline-input.stock-input {
  max-width: 85px;
  text-align: center;
}

/* Empty & Loading */
.tp-empty-state,
.tp-loading {
  text-align: center;
  padding: 60px 24px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px dashed rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  color: #94a3b8;
}

.tp-empty-state h3 {
  color: white;
  margin: 8px 0;
}

.empty-icon {
  font-size: 48px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 20px;
}

.modal {
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 20px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.modal.wide-modal {
  max-width: 680px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-header h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 800;
  color: white;
}

.modal-close {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 24px;
  cursor: pointer;
}

.modal-body {
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  max-height: 70vh;
  overflow-y: auto;
}

.modal-desc {
  margin: 0;
  font-size: 13.5px;
  color: #cbd5e1;
  line-height: 1.5;
}

.guide-box {
  background: rgba(14, 165, 233, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.25);
  border-radius: 12px;
  padding: 14px 16px;
  font-size: 13px;
  color: #e2e8f0;
}

.guide-box h4 {
  margin: 0 0 8px;
  color: #38bdf8;
  font-size: 13.5px;
}

.guide-box ul {
  margin: 0;
  padding-left: 18px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.col-table-wrap {
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  overflow: hidden;
}

.col-guide-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
}

.col-guide-table th {
  background: rgba(15, 23, 42, 0.8);
  color: #94a3b8;
  text-align: left;
  padding: 9px 12px;
}

.col-guide-table td {
  padding: 8px 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  color: #cbd5e1;
}

.tag-req {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  padding: 2px 7px;
  border-radius: 4px;
  font-weight: 700;
  font-size: 11px;
}

.tag-opt {
  background: rgba(148, 163, 184, 0.2);
  color: #cbd5e1;
  padding: 2px 7px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 11px;
}

.mf-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.mf-group label {
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
}

.mf-group input {
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 10px;
  color: white;
  font-size: 14px;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.mf-error {
  color: #fca5a5;
  background: rgba(239, 68, 68, 0.12);
  padding: 10px;
  border-radius: 8px;
  font-size: 13px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  flex-wrap: wrap;
}

.modal-footer.split-footer {
  justify-content: space-between;
}

.btn-cancel {
  background: rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  border: none;
  padding: 10px 18px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.btn-save {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.result-stats {
  display: flex;
  justify-content: center;
  gap: 12px;
}

.r-stat {
  flex: 1;
  padding: 12px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.r-stat.blue {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
}

.r-stat.green {
  background: rgba(52, 211, 153, 0.15);
  color: #34d399;
}

.r-stat.gray {
  background: rgba(148, 163, 184, 0.15);
  color: #94a3b8;
}

.r-num {
  font-size: 26px;
  font-weight: 900;
}

.r-lbl {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  margin-top: 4px;
}
</style>

