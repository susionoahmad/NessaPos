<template>
  <div class="page-container" v-if="auth.user?.role === 'admin'">
    <div class="header">
      <h2>Pusat Laporan & Analisa</h2>
      <p>Kelola dan pantau seluruh aktivitas keuangan dari satu tempat.</p>
    </div>

    <div class="report-layout">
      <!-- Sidebar Menu (Collapse Style) -->
      <aside class="report-sidebar">
        <div class="menu-section">
          <div class="menu-header" @click="toggleMenu('sales')">
            <span class="icon">📊</span>
            <span>Penjualan</span>
            <span class="arrow" :class="{ open: openMenus.sales }">▼</span>
          </div>
          <div class="menu-items" v-show="openMenus.sales">
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'sales-list' }"
              @click="activeTab = 'sales-list'"
            >
              📜 Daftar Penjualan Detail
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'tax-report' }"
              @click="activeTab = 'tax-report'"
            >
              📑 Laporan Pajak Transaksi
            </div>
          </div>
        </div>

        <div class="menu-section">
          <div class="menu-header" @click="toggleMenu('finance')">
            <span class="icon">💰</span>
            <span>Keuangan</span>
            <span class="arrow" :class="{ open: openMenus.finance }">▼</span>
          </div>
          <div class="menu-items" v-show="openMenus.finance">
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'vault' }"
              @click="activeTab = 'vault'"
            >
              🏦 Rekening Koran Brankas
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'cashier-mutations' }"
              @click="activeTab = 'cashier-mutations'"
            >
              💸 Rekening Koran Kasir
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'sessions' }"
              @click="activeTab = 'sessions'"
            >
              🕒 History Sesi Kasir
            </div>
          </div>
        </div>

        <div class="menu-section">
          <div class="menu-header" @click="toggleMenu('inventory')">
            <span class="icon">📦</span>
            <span>Stok & Laba</span>
            <span class="arrow" :class="{ open: openMenus.inventory }">▼</span>
          </div>
          <div class="menu-items" v-show="openMenus.inventory">
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'stock-mutations' }"
              @click="activeTab = 'stock-mutations'"
            >
              🔄 Mutasi Stok Barang
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'profit-loss' }"
              @click="activeTab = 'profit-loss'"
            >
              📈 Laporan Laba Rugi
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'fast-moving' }"
              @click="activeTab = 'fast-moving'"
            >
              🚀 Fast Moving
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'slow-moving' }"
              @click="activeTab = 'slow-moving'"
            >
              🐢 Slow Moving
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'critical-stock' }"
              @click="activeTab = 'critical-stock'"
            >
              ⚠️ Stok Kritis
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Report Display -->
      <main class="report-main">
        <!-- Filters Bar (Shared) -->
        <div class="filters-bar card sticky-filters">
          <div class="filter-group" v-if="['sales-list', 'vault', 'cashier-mutations', 'sessions'].includes(activeTab)">
            <label>Pilih Kasir</label>
            <select v-model.number="filterUser">
              <option :value="0">Semua Kasir</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.username }}</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Dari Tanggal</label>
            <input type="date" v-model="filterDateFrom" />
          </div>
          <div class="filter-group">
            <label>Sampai Tanggal</label>
            <input type="date" v-model="filterDateTo" />
          </div>
          <button class="btn-reset" @click="resetFilters">Reset</button>
          <button class="btn-print" @click="handlePrint">🖨️ Cetak</button>
          <button class="btn-excel" @click="handleExportExcel">📥 Excel</button>
        </div>

        <!-- Content Area -->
        <div class="content-area">
          
          <!-- Tab: Rekening Koran Kasir -->
          <div v-if="activeTab === 'cashier-mutations'" class="tab-content">
            <div class="table-card">
              <h3>Mutasi Laci Kasir (Rekening Koran Kasir)</h3>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Waktu</th>
                    <th>Detail Transaksi</th>
                    <th class="text-right">Masuk</th>
                    <th class="text-right">Keluar</th>
                    <th class="text-right">Saldo Laci</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in filteredCashier" :key="t.id">
                    <td>{{ new Date(t.created_at).toLocaleString('id-ID') }}</td>
                    <td>
                      <div class="font-bold">{{ t.description }}</div>
                      <span class="badge" :class="t.type">{{ t.type }}</span>
                      <span class="user-info">oleh {{ t.username }}</span>
                    </td>
                    <td class="text-right text-success">{{ t.amount > 0 ? formatCurrency(t.amount) : '-' }}</td>
                    <td class="text-right text-danger">{{ t.amount < 0 ? formatCurrency(Math.abs(t.amount)) : '-' }}</td>
                    <td class="text-right font-bold">{{ formatCurrency(t.balance_after) }}</td>
                  </tr>
                  <tr v-if="filteredCashier.length === 0">
                    <td colspan="5" class="text-center">Belum ada mutasi laci kasir.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab: Laporan Pajak Transaksi -->
          <div v-if="activeTab === 'tax-report'" class="tab-content">
            <div class="table-card">
              <div class="tc-header">
                <h3>Rekapitulasi Pajak per Transaksi</h3>
                <div class="count">Total Pajak: {{ formatCurrency(totalTax) }}</div>
              </div>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Invoice</th>
                    <th>Waktu</th>
                    <th>Kasir</th>
                    <th class="text-right">Total Transaksi</th>
                    <th class="text-right">Nominal Pajak</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in filteredSales" :key="order.id">
                    <td class="font-bold">#{{ order.id }}</td>
                    <td>{{ new Date(order.created_at).toLocaleString('id-ID') }}</td>
                    <td><span class="username-pill">{{ order.username }}</span></td>
                    <td class="text-right">{{ formatCurrency(order.final_amount) }}</td>
                    <td class="text-right font-bold text-slate">{{ formatCurrency(order.tax_amount || 0) }}</td>
                  </tr>
                  <tr v-if="filteredSales.length === 0">
                    <td colspan="5" class="text-center">Tidak ada data transaksi.</td>
                  </tr>
                </tbody>
                <tfoot v-if="filteredSales.length > 0">
                  <tr style="background: #f8fafc;">
                    <td colspan="4" class="text-right font-bold">TOTAL PAJAK TERKUMPUL</td>
                    <td class="text-right font-bold text-success" style="font-size: 16px;">{{ formatCurrency(totalTax) }}</td>
                  </tr>
                </tfoot>
              </table>
              <div class="alert info" style="margin-top: 15px; font-size: 12px; color: #64748b; padding: 10px; background: #f1f5f9; border-radius: 8px;">
                💡 Laporan ini mencatat setiap rupiah pajak yang dipungut dari setiap transaksi untuk keperluan audit dan pelaporan pajak negara.
              </div>
            </div>
          </div>
          <!-- Tab: Daftar Penjualan Detail -->
          <div v-if="activeTab === 'sales-list'" class="tab-content">
            <div class="table-card">
              <div class="tc-header">
                <h3>Daftar Transaksi Penjualan</h3>
                <span class="count">{{ filteredSales.length }} Transaksi</span>
              </div>
              <div class="sales-list">
                <div v-for="order in filteredSales" :key="order.id" class="order-item card">
                  <div class="order-header" @click="toggleOrder(order.id)">
                    <div class="oh-left">
                      <span class="order-id">#{{ order.id }}</span>
                      <span class="order-date">{{ new Date(order.created_at).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="oh-center">
                      <span class="username-pill">{{ order.username }}</span>
                      <span class="pay-method">{{ order.payment_method }}</span>
                    </div>
                    <div class="oh-right">
                      <div class="oh-tax-info" v-if="order.tax_amount > 0">
                        <span class="tax-label">Pajak</span>
                        <span class="tax-value">{{ formatCurrency(order.tax_amount) }}</span>
                      </div>
                      <span class="order-total">{{ formatCurrency(order.final_amount) }}</span>
                    </div>
                  </div>
                  
                  <div class="order-details" :class="{ 'print-visible': true }" v-if="expandedOrders.includes(order.id) || isPrinting">
                    <table class="detail-table">
                      <thead>
                        <tr>
                          <th>Nama Barang</th>
                          <th>Harga</th>
                          <th>Qty</th>
                          <th class="text-right">Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, idx) in order.items" :key="idx">
                          <td>{{ item.product_name }}</td>
                          <td>{{ formatCurrency(item.price) }}</td>
                          <td>{{ item.quantity }}</td>
                          <td class="text-right">{{ formatCurrency(item.total) }}</td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr>
                          <td colspan="3" class="text-right">Subtotal</td>
                          <td class="text-right">{{ formatCurrency(order.total_amount) }}</td>
                        </tr>
                        <tr v-if="order.discount > 0">
                          <td colspan="3" class="text-right text-orange">Diskon</td>
                          <td class="text-right text-orange">-{{ formatCurrency(order.discount) }}</td>
                        </tr>
                        <tr v-if="order.tax_amount > 0">
                          <td colspan="3" class="text-right">Pajak (Service)</td>
                          <td class="text-right">+{{ formatCurrency(order.tax_amount) }}</td>
                        </tr>
                        <tr class="grand-total">
                          <td colspan="3" class="text-right">Grand Total</td>
                          <td class="text-right">{{ formatCurrency(order.final_amount) }}</td>
                        </tr>
                      </tfoot>
                    </table>
                    <div style="margin-top: 15px; display: flex; justify-content: flex-end;">
                        <button class="btn-print" @click.stop="reprintThermal(order)">
                             Cetak Ulang Struk Thermal
                        </button>
                    </div>
                  </div>
                </div>
                <div v-if="filteredSales.length === 0" class="no-data">
                  Tidak ada data penjualan sesuai filter.
                </div>
              </div>
            </div>
          </div>

          <!-- Tab: Vault Transactions -->
          <div v-if="activeTab === 'vault'" class="tab-content">
            <div class="vault-header card bg-dark">
                <div class="vh-label">Saldo Brankas Saat Ini</div>
                <div class="vault-balance">{{ formatCurrency(vault.balance) }}</div>
                <button class="btn-capital" @click="showCapitalModal = true">Tambah Modal</button>
            </div>
            <div class="table-card">
              <h3>Mutasi Kas Besar (Rekening Koran)</h3>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Waktu</th>
                    <th>Keterangan</th>
                    <th class="text-right">Debet</th>
                    <th class="text-right">Kredit</th>
                    <th class="text-right">Saldo</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in filteredVault" :key="t.id">
                    <td>{{ new Date(t.created_at).toLocaleString('id-ID') }}</td>
                    <td>
                        <div class="font-bold">{{ t.description }}</div>
                        <span class="badge" :class="t.type">{{ t.type }}</span>
                        <span class="user-info">oleh {{ t.username }}</span>
                    </td>
                    <td class="text-right text-success">{{ t.amount > 0 ? formatCurrency(t.amount) : '-' }}</td>
                    <td class="text-right text-danger">{{ t.amount < 0 ? formatCurrency(Math.abs(t.amount)) : '-' }}</td>
                    <td class="text-right font-bold">{{ formatCurrency(t.balance_after) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab: History Sesi Kasir -->
          <div v-if="activeTab === 'sessions'" class="tab-content">
            <div class="table-card">
              <h3>Riwayat Sesi Kasir</h3>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Status</th>
                    <th>Detail Kasir</th>
                    <th>Waktu</th>
                    <th class="text-right">Modal / Fisik</th>
                    <th class="text-right">Selisih</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in filteredSessions" :key="s.id">
                    <td><span class="status-badge" :class="s.status">{{ s.status }}</span></td>
                    <td>
                      <div class="font-bold">{{ s.username }}</div>
                      <div class="text-small">ID Sesi: #{{ s.id }}</div>
                    </td>
                    <td>
                      <div class="text-small">Mulai: {{ new Date(s.start_time).toLocaleString('id-ID') }}</div>
                      <div class="text-small" v-if="s.end_time">Selesai: {{ new Date(s.end_time).toLocaleString('id-ID') }}</div>
                    </td>
                    <td class="text-right">
                      <div class="text-small">M: {{ formatCurrency(s.start_amount || 0) }}</div>
                      <div class="val" v-if="s.end_amount_physical">F: {{ formatCurrency(s.end_amount_physical || 0) }}</div>
                    </td>
                    <td class="text-right font-bold" :class="(s.difference || 0) < 0 ? 'text-danger' : 'text-success'">
                       {{ (s.difference || 0) > 0 ? '+' : '' }}{{ s.difference ? formatCurrency(s.difference) : '-' }}
                    </td>
                  </tr>
                  <tr v-if="filteredSessions.length === 0">
                    <td colspan="5" class="text-center">Belum ada riwayat sesi.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab: Stock Mutations -->
          <div v-if="activeTab === 'stock-mutations'" class="tab-content">
            <div class="table-card">
              <h3>Riwayat Mutasi Stok</h3>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Waktu</th>
                    <th>Nama Barang</th>
                    <th>Tipe</th>
                    <th>Dari -> Ke</th>
                    <th class="text-right">Jumlah</th>
                    <th>Referensi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in filteredMutations" :key="m.id">
                    <td>{{ new Date(m.created_at).toLocaleString('id-ID') }}</td>
                    <td class="font-bold">{{ m.product_name }}</td>
                    <td><span class="badge" :class="m.type">{{ m.type }}</span></td>
                    <td>{{ m.from_location }} ➔ {{ m.to_location }}</td>
                    <td class="text-right font-bold" :class="m.type === 'SALE' ? 'text-danger' : 'text-success'">
                      {{ m.type === 'SALE' ? '-' : '+' }}{{ m.quantity }}
                    </td>
                    <td class="text-small">{{ m.reference }}</td>
                  </tr>
                  <tr v-if="filteredMutations.length === 0">
                    <td colspan="6" class="text-center">Belum ada mutasi stok.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab: Laba Rugi -->
          <div v-if="activeTab === 'profit-loss'" class="tab-content">
            <div class="stats-row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <div class="stat-box card">
                    <label>Total Omzet (Net)</label>
                    <div class="val text-success">{{ formatCurrency(totalRevenue) }}</div>
                </div>
                <div class="stat-box card">
                    <label>Total HPP (Modal)</label>
                    <div class="val text-danger">{{ formatCurrency(totalCOGS) }}</div>
                </div>
                <div class="stat-box card Highlight">
                    <label>Estimasi Laba</label>
                    <div class="val text-profit">{{ formatCurrency(totalRevenue - totalCOGS) }}</div>
                </div>
                <div class="stat-box card" style="background: #f8fafc;">
                    <label>Pajak Terkumpul</label>
                    <div class="val" style="color: #64748b;">{{ formatCurrency(totalTax) }}</div>
                </div>
            </div>

            <div class="table-card">
              <h3>Detail Laba per Transaksi</h3>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Waktu</th>
                    <th>#ID</th>
                    <th class="text-right">Penjualan</th>
                    <th class="text-right">HPP</th>
                    <th class="text-right">Laba</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in profitLossData" :key="s.id">
                    <td>{{ new Date(s.date).toLocaleString('id-ID') }}</td>
                    <td class="font-mono">#{{ s.id }}</td>
                    <td class="text-right">{{ formatCurrency(s.revenue) }}</td>
                    <td class="text-right text-dim">{{ formatCurrency(s.cogs) }}</td>
                    <td class="text-right font-bold text-success">{{ formatCurrency(s.revenue - s.cogs) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab: Fast Moving -->
          <div v-if="activeTab === 'fast-moving'" class="tab-content">
            <div class="table-card">
              <div class="tc-header">
                <h3>Produk Terlaris (Fast Moving)</h3>
                <span class="badge SALE">Data Berdasarkan Filter Tanggal</span>
              </div>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Peringkat</th>
                    <th>Nama Produk</th>
                    <th class="text-right">Total Terjual</th>
                    <th class="text-right">Total Nilai Jual</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(p, idx) in fastMovingData" :key="p.id">
                    <td><span class="rank-badge">{{ idx + 1 }}</span></td>
                    <td class="font-bold">{{ p.name }}</td>
                    <td class="text-right font-bold">{{ p.totalQty }} unit</td>
                    <td class="text-right text-success">{{ formatCurrency(p.totalRevenue) }}</td>
                  </tr>
                  <tr v-if="fastMovingData.length === 0">
                    <td colspan="4" class="text-center">Tidak ada data penjualan.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab: Slow Moving -->
          <div v-if="activeTab === 'slow-moving'" class="tab-content">
            <div class="table-card">
              <div class="tc-header">
                <h3>Produk Kurang Laku (Slow Moving)</h3>
                <span class="badge MUTATION">Data Berdasarkan Filter Tanggal</span>
              </div>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Nama Produk</th>
                    <th class="text-right">Total Terjual</th>
                    <th class="text-right">Stok (Rak+Gudang)</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in slowMovingData" :key="p.id">
                    <td class="font-bold">{{ p.name }}</td>
                    <td class="text-right">{{ p.totalQty }} unit</td>
                    <td class="text-right">{{ p.shelfStock + p.warehouseStock }}</td>
                    <td><span class="badge" :class="p.totalQty === 0 ? 'SESSION_START' : 'MUTATION'">{{ p.totalQty === 0 ? 'Mati' : 'Lambat' }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab: Stok Kritis -->
          <div v-if="activeTab === 'critical-stock'" class="tab-content">
            <div class="table-card">
              <div class="tc-header">
                <h3>Peringatan Stok Kritis</h3>
                <span class="badge SESSION_START">Stok Di Bawah 10 Unit</span>
              </div>
              <table class="report-table">
                <thead>
                  <tr>
                    <th>Barcode</th>
                    <th>Nama Produk</th>
                    <th class="text-right">Stok Rak</th>
                    <th class="text-right">Stok Gudang</th>
                    <th class="text-right">Total Stok</th>
                    <th>Tindakan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in criticalStockData" :key="p.id">
                    <td class="font-mono">{{ p.barcode }}</td>
                    <td class="font-bold text-danger">{{ p.name }}</td>
                    <td class="text-right">{{ p.shelf_stock }}</td>
                    <td class="text-right">{{ p.warehouse_stock }}</td>
                    <td class="text-right font-bold text-danger">{{ p.shelf_stock + p.warehouse_stock }}</td>
                    <td><span class="badge SESSION_START">Segera Order</span></td>
                  </tr>
                  <tr v-if="criticalStockData.length === 0">
                    <td colspan="6" class="text-center text-success">Semua stok aman (di atas 10 unit).</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </main>
    </div>

    <!-- Capital Modal -->
    <div v-if="showCapitalModal" class="modal-overlay">
      <div class="modal-content">
        <h3>Tambah Modal Kas Besar</h3>
        <div class="form-group">
          <label>Nominal (Rp)</label>
          <input type="number" v-model.number="capitalAmount" />
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <input type="text" v-model="capitalDesc" placeholder="e.g. Setoran Awal Pemilik" />
        </div>
        <div class="actions">
          <button @click="showCapitalModal = false">Batal</button>
          <button class="btn-primary" @click="handleAddCapital" :disabled="capitalAmount <= 0">Simpan Ke Brankas</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, reactive } from 'vue'
import api from '../api'
import { formatCurrency } from '../utils/format'
import { useAuthStore } from '../store/auth'
import { printReceipt } from '../utils/printer'

const auth = useAuthStore()
const activeTab = ref('sales-list')
const openMenus = reactive({ sales: true, finance: true, inventory: true })

const vault = ref<any>({})
const vaultTransactions = ref<any[]>([])
const cashierTransactions = ref<any[]>([])
const sessions = ref<any[]>([])
const users = ref<any[]>([])
const salesData = ref<any[]>([])
const stockMutations = ref<any[]>([])
const products = ref<any[]>([])
const expandedOrders = ref<number[]>([])

const filterUser = ref(0)
const todayStr = new Date().toISOString().split('T')[0]
const filterDateFrom = ref(todayStr)
const filterDateTo = ref(todayStr)

const showCapitalModal = ref(false)
const capitalAmount = ref(0)
const capitalDesc = ref('')

onMounted(async () => {
    loadData()
})

const loadData = async () => {
    if (auth.user?.role === 'admin') {
        const [v, vt, ct, ss, us, sd, sm, ps] = await Promise.all([
            api.get('/vault'), 
            api.get('/vault/transactions'), 
            api.get('/cashier-transactions'), 
            api.get('/sessions'), 
            api.get('/users'), 
            api.get('/orders'),
            api.get('/stock-mutations'), 
            api.get('/products')
        ])
        
        vault.value = v.data || {}
        
        // Data Flattening for template compatibility
        vaultTransactions.value = (vt.data || []).map((t: any) => ({ ...t, username: t.username || t.user?.username }))
        cashierTransactions.value = (ct.data || []).map((t: any) => ({ ...t, username: t.username || t.user?.username }))
        sessions.value = (ss.data || []).map((s: any) => ({ ...s, username: s.username || s.user?.username }))
        users.value = us.data || []
        salesData.value = (sd.data || []).map((o: any) => ({ ...o, username: o.user?.username }))
        stockMutations.value = (sm.data || []).map((m: any) => ({ 
            ...m, 
            username: m.user?.username,
            product_name: m.product?.name 
        }))
        products.value = ps.data || []
    }
}

const toggleMenu = (key: 'sales' | 'finance' | 'inventory') => {
    openMenus[key] = !openMenus[key]
}

const toggleOrder = (id: number) => {
    const idx = expandedOrders.value.indexOf(id)
    if (idx > -1) expandedOrders.value.splice(idx, 1)
    else expandedOrders.value.push(id)
}

const filteredSales = computed(() => {
    let data = [...salesData.value]
    if (filterUser.value) data = data.filter(o => o.user_id === filterUser.value)
    if (filterDateFrom.value) data = data.filter(o => (o.created_at || '').split(/[ T]/)[0] >= filterDateFrom.value)
    if (filterDateTo.value) data = data.filter(o => (o.created_at || '').split(/[ T]/)[0] <= filterDateTo.value)
    return data
})

const filteredVault = computed(() => {
    let data = [...vaultTransactions.value]
    if (filterUser.value) data = data.filter(t => t.created_by === filterUser.value)
    if (filterDateFrom.value) data = data.filter(t => (t.created_at || '').split(/[ T]/)[0] >= filterDateFrom.value)
    if (filterDateTo.value) data = data.filter(t => (t.created_at || '').split(/[ T]/)[0] <= filterDateTo.value)
    return data
})

const filteredCashier = computed(() => {
    let data = [...cashierTransactions.value]
    if (filterUser.value) data = data.filter(t => t.user_id === filterUser.value)
    if (filterDateFrom.value) data = data.filter(t => (t.created_at || '').split(/[ T]/)[0] >= filterDateFrom.value)
    if (filterDateTo.value) data = data.filter(t => (t.created_at || '').split(/[ T]/)[0] <= filterDateTo.value)
    return data
})

const filteredSessions = computed(() => {
    let data = [...sessions.value]
    if (filterUser.value) data = data.filter(s => s.user_id === filterUser.value)
    if (filterDateFrom.value) data = data.filter(s => (s.start_time || '').split(/[ T]/)[0] >= filterDateFrom.value)
    if (filterDateTo.value) data = data.filter(s => (s.start_time || '').split(/[ T]/)[0] <= filterDateTo.value)
    return data
})

const filteredMutations = computed(() => {
    let data = [...stockMutations.value]
    if (filterDateFrom.value) data = data.filter(m => (m.created_at || '').split(/[ T]/)[0] >= filterDateFrom.value)
    if (filterDateTo.value) data = data.filter(m => (m.created_at || '').split(/[ T]/)[0] <= filterDateTo.value)
    return data
})

const getProductHpp = (productId: number) => {
    const product = products.value.find(p => p.id === productId)
    return product?.cost_price || 0
}

const profitLossData = computed(() => {
    return filteredSales.value.map(s => ({
        id: s.id,
        date: s.created_at,
        revenue: s.final_amount - s.tax_amount,
        cogs: s.items.reduce((acc: any, it: any) => acc + (getProductHpp(it.product_id) * it.quantity), 0)
    }))
})

const totalRevenue = computed(() => profitLossData.value.reduce((acc, s) => acc + s.revenue, 0))
const totalCOGS = computed(() => profitLossData.value.reduce((acc, s) => acc + s.cogs, 0))
const totalTax = computed(() => filteredSales.value.reduce((acc, s) => acc + s.tax_amount, 0))

const productSalesStats = computed(() => {
    const stats: Record<number, { name: string, totalQty: number, totalRevenue: number }> = {}
    
    products.value.forEach(p => {
        stats[p.id] = { name: p.name, totalQty: 0, totalRevenue: 0 }
    })

    filteredSales.value.forEach(order => {
        order.items.forEach((it: any) => {
            if (stats[it.product_id]) {
                stats[it.product_id].totalQty += it.quantity
                stats[it.product_id].totalRevenue += it.total
            }
        })
    })

    return Object.entries(stats).map(([id, s]) => ({
        id: parseInt(id),
        ...s,
        shelfStock: products.value.find(p => p.id === parseInt(id))?.shelf_stock || 0,
        warehouseStock: products.value.find(p => p.id === parseInt(id))?.warehouse_stock || 0
    }))
})

const fastMovingData = computed(() => {
    return [...productSalesStats.value]
        .filter(s => s.totalQty > 0)
        .sort((a, b) => b.totalQty - a.totalQty)
})

const slowMovingData = computed(() => {
    return [...productSalesStats.value]
        .sort((a, b) => a.totalQty - b.totalQty)
})

const criticalStockData = computed(() => {
    return products.value.filter(p => (p.shelf_stock + p.warehouse_stock) < 10)
})

const resetFilters = () => {
    filterUser.value = 0
    filterDateFrom.value = todayStr
    filterDateTo.value = todayStr
}

const handleAddCapital = async () => {
    try {
        await api.post('/vault/deposit', {
            amount: capitalAmount.value,
            description: capitalDesc.value,
            created_by: auth.user.id
        })
        showCapitalModal.value = false
        capitalAmount.value = 0
        capitalDesc.value = ''
        loadData()
    } catch(e: any) { 
        alert("Gagal: " + (e.response?.data?.message || e.message)) 
    }
}

const reprintThermal = async (order: any) => {
    await printReceipt(order)
}

const isPrinting = ref(false)
const handlePrint = () => {
  isPrinting.value = true
  setTimeout(() => {
    window.print()
    isPrinting.value = false
  }, 800)
}

const handleExportExcel = () => {
  let csvContent = "data:text/csv;charset=utf-8," 
  let filename = `laporan_${activeTab.value}_${filterDateFrom.value}.csv`
  
  if (activeTab.value === 'sales-list') {
    csvContent += "ID,Waktu,Kasir,Metode,Total\n"
    filteredSales.value.forEach(o => {
      csvContent += `${o.id},${o.created_at},${o.username},${o.payment_method},${o.final_amount}\n`
    })
  } else if (activeTab.value === 'tax-report') {
    csvContent += "Invoice,Waktu,Kasir,Total,Pajak\n"
    filteredSales.value.forEach(o => {
      csvContent += `${o.id},${o.created_at},${o.username},${o.final_amount},${o.tax_amount}\n`
    })
  } else if (activeTab.value === 'profit-loss') {
    csvContent += "Invoice,Waktu,Penjualan,HPP,Laba\n"
    profitLossData.value.forEach(s => {
      csvContent += `${s.id},${s.date},${s.revenue},${s.cogs},${s.revenue - s.cogs}\n`
    })
  } else {
    alert("Ekspor untuk tab ini akan segera hadir.")
    return
  }

  const encodedUri = encodeURI(csvContent)
  const link = document.createElement("a")
  link.setAttribute("href", encodedUri)
  link.setAttribute("download", filename)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<style scoped>
.page-container { 
    padding: 15px 25px; 
    background: #f8fafc; 
    height: 100vh; 
    overflow: hidden; 
    display: flex;
    flex-direction: column;
}
.header { margin-bottom: 15px; flex-shrink: 0; }
.header h2 { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; }
.header p { color: #64748b; margin-top: 2px; font-size: 13px; }

.report-layout { 
    display: flex; 
    gap: 20px; 
    align-items: flex-start; 
    flex: 1; 
    overflow: hidden; /* Prevent layout from scrolling */
}

/* Sidebar Menu - Slimmer */
.report-sidebar { 
    width: 220px; 
    background: white; 
    border-radius: 12px; 
    border: 1px solid #e2e8f0; 
    padding: 8px; 
    height: 100%; 
    overflow-y: auto; 
    flex-shrink: 0; 
}
.menu-section { margin-bottom: 6px; }
.menu-header { 
    display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; cursor: pointer; 
    font-weight: 700; color: #1e293b; transition: background 0.2s; font-size: 13.5px;
}
.menu-header:hover { background: #f1f5f9; }
.menu-header .icon { font-size: 16px; }
.menu-header .arrow { margin-left: auto; font-size: 9px; transition: transform 0.3s; color: #94a3b8; }
.menu-header .arrow.open { transform: rotate(180deg); }

.menu-items { padding-left: 12px; margin-top: 2px; display: flex; flex-direction: column; gap: 2px; }
.menu-item { 
    padding: 8px 12px; border-radius: 6px; font-size: 13px; color: #64748b; cursor: pointer; font-weight: 600;
}
.menu-item:hover { background: #f8fafc; color: #0ea5e9; }
.menu-item.active { background: #eff6ff; color: #0ea5e9; }

/* Main Area */
.report-main { 
    flex: 1; 
    min-width: 0; 
    height: 100%;
    display: flex;
    flex-direction: column;
} 

.content-area {
    flex: 1;
    overflow-y: auto;
    padding-right: 5px;
}

/* Slim scrollbar */
.content-area::-webkit-scrollbar, .report-sidebar::-webkit-scrollbar {
  width: 6px;
}
.content-area::-webkit-scrollbar-track, .report-sidebar::-webkit-scrollbar-track {
  background: transparent;
}
.content-area::-webkit-scrollbar-thumb, .report-sidebar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.filters-bar { display: flex; gap: 15px; align-items: flex-end; padding: 12px 20px; margin-bottom: 15px; border: 1px solid #e2e8f0; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-group label { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.filter-group select, .filter-group input { padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; outline: none; background: #fff; }
.btn-reset { padding: 6px 12px; background: #f1f5f9; border: none; border-radius: 6px; color: #64748b; font-weight: 700; cursor: pointer; font-size: 12px; margin-bottom: 2px; }
.btn-print { padding: 6px 14px; background: #1e293b; border: none; border-radius: 6px; color: white; font-weight: 700; cursor: pointer; font-size: 12px; margin-bottom: 2px; transition: background 0.2s; }
.btn-print:hover { background: #334155; }
.btn-excel { padding: 6px 14px; background: #16a34a; border: none; border-radius: 6px; color: white; font-weight: 700; cursor: pointer; font-size: 12px; margin-bottom: 2px; transition: background 0.2s; }
.btn-excel:hover { background: #15803d; }

/* Sales List - Tighter */
.tc-header { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; margin-bottom: 15px; }
.tc-header h3 { margin: 0; font-size: 18px; color: #1e293b; font-weight: 800; text-align: left; width: 100%; }
.tc-header .count { font-size: 12px; background: #f1f5f9; padding: 2px 10px; border-radius: 20px; color: #64748b; font-weight: 700; width: fit-content; }

.sales-list { display: flex; flex-direction: column; gap: 8px; }
.order-item { padding: 0; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; }
.order-header { display: flex; align-items: center; padding: 10px 15px; cursor: pointer; transition: background 0.1s; gap: 15px; }

.oh-left { display: flex; flex-direction: column; width: 150px; flex-shrink: 0; }
.order-id { font-weight: 800; color: #0f172a; font-size: 14px; }
.order-date { font-size: 11px; color: #94a3b8; }

.oh-center { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.pay-method { font-size: 11px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; white-space: nowrap; }

.oh-tax-info { display: flex; flex-direction: column; align-items: flex-end; line-height: 1.1; margin-right: 15px; }
.tax-label { font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; }
.tax-value { font-size: 11px; font-weight: 700; color: #64748b; }

.order-total { font-size: 16px; font-weight: 800; color: #10b981; }

.order-details { padding: 15px; background: #fafbfc; border-top: 1px dashed #e2e8f0; }
.detail-table { width: 100%; border-collapse: collapse; color: #1e293b; }
.detail-table th { text-align: left; font-size: 10px; text-transform: uppercase; color: #64748b; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; font-weight: 800; }
.detail-table td { padding: 8px 0; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #0f172a; font-weight: 600; }
.detail-table tfoot td { padding-top: 10px; font-weight: 700; font-size: 13px; color: #1e293b; }
.detail-table .grand-total td { font-size: 15px; font-weight: 800; color: #059669; padding-top: 12px; border-bottom: none; }

.table-card {
  background: white;
  border-radius: 12px;
  padding: 15px;
  border: 1px solid #e2e8f0;
  margin-bottom: 20px;
  overflow-x: auto; /* Handle horizontal scroll on mobile */
  width: 100%;
}

/* Table General - Slimmer */
.report-table { width: 100%; border-collapse: collapse; color: #1e293b; }
.report-table th { text-align: left; padding: 10px 12px; font-size: 10px; text-transform: uppercase; background: #f1f5f9; color: #475569; border-bottom: 2px solid #e2e8f0; font-weight: 800; }
.report-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #1e293b; font-weight: 500; }
.report-table tr:hover { background: #f8fafc; }

/* Vault Card - Slimmer */
.vault-header { text-align: left; padding: 25px; background: #1e293b; color: white; margin-bottom: 15px; border-radius: 12px; }
.vault-balance { font-size: 32px; font-weight: 900; color: #38bdf8; margin: 5px 0 12px; }
.btn-capital { padding: 8px 16px; font-size: 13px; border-radius: 6px; }

.card { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

.text-right { text-align: right; }
.text-danger { color: #dc2626; font-weight: 800; }
.text-success { color: #16a34a; font-weight: 800; }
.text-orange { color: #d97706; font-weight: 800; }
.font-bold { font-weight: 700; color: #0f172a; }
.text-small { font-size: 11px; color: #94a3b8; }
.no-data { text-align: center; padding: 60px; color: #94a3b8; font-weight: 600; }

.username-pill { background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 800; margin-right: 8px; }
.badge.ADD_CAPITAL { background: #dcfce7; color: #15803d; }
.badge.SESSION_START { background: #fee2e2; color: #b91c1c; }
.badge.SESSION_END { background: #ecfdf5; color: #047857; }
.badge.SALE { background: #eff6ff; color: #1d4ed8; }
.badge.PURCHASE { background: #fef9c3; color: #854d0e; }
.badge.MUTATION { background: #f3f4f6; color: #4b5563; }
.badge.ADJUSTMENT { background: #fef3c7; color: #92400e; }

.stats-row { display: flex; gap: 15px; margin-bottom: 20px; }
.stat-box { flex: 1; text-align: left; padding: 15px 20px; }
.stat-box label { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; display: block; }
.stat-box .val { font-size: 24px; font-weight: 900; }
.text-profit { color: #047857; font-size: 28px; } /* Large bold green */
.Highlight { background: #ecfdf5; border: 1px solid #10b981; }
.text-dim { color: #94a3b8; }
.font-mono { font-family: monospace; font-size: 12px; }

.rank-badge {
    background: #1e293b;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 800;
}

.status-badge { font-size: 10px; padding: 4px 10px; border-radius: 20px; font-weight: 800; }
.status-badge.OPEN { background: #dcfce7; color: #15803d; }
.status-badge.CLOSED { background: #f1f5f9; color: #475569; }

.modal-overlay { 
    position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15,23,42,0.6); 
    display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px);
}
.modal-content { background: white; padding: 25px; border-radius: 12px; width: 400px; color: #1e293b; }
.modal-content h3 { margin-top: 0; margin-bottom: 20px; color: #0f172a; font-weight: 800; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 700; font-size: 12px; margin-bottom: 8px; color: #64748b; text-transform: uppercase; }
.form-group input { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; outline: none; font-size: 16px; color: #1e293b; font-weight: 600; }
.actions { display: flex; gap: 10px; margin-top: 25px; }
.actions button { flex: 1; padding: 12px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; }
.btn-primary { background: #1e293b; color: white; }

@media print {
    @page { margin: 8mm; }
    .report-sidebar, .filters-bar, .btn-capital, .header, .header p, .tc-header .badge, .alert {
        display: none !important;
    }
    .page-container {
        padding: 0 !important;
        margin: 0 !important;
        height: auto;
        overflow: visible;
    }
    .report-layout {
        display: block;
    }
    .report-main {
        padding: 0;
    }
    .content-area {
        overflow: visible;
        height: auto;
    }
    .table-card, .card {
        box-shadow: none;
        border: none;
        padding: 0;
    }
    .report-table th {
        background: #f1f5f9 !important;
        color: black !important;
        border-bottom: 1px solid #000 !important;
    }
    .tc-header h3 {
        display: block !important;
        font-size: 20px !important;
        margin-bottom: 10px !important;
        border-bottom: 2px solid #1e293b;
        padding-bottom: 5px;
    }
    .tc-header .count {
        display: inline-block !important;
        margin-bottom: 10px;
    }
    .order-details.print-visible {
        display: block !important;
        border-bottom: 2px solid #eee;
        margin-bottom: 10px;
    }
    .order-header { padding: 5px 10px !important; }
    .order-details { padding: 10px !important; }
    .detail-table td { padding: 4px 0 !important; }
    .order-item {
        break-inside: avoid-page;
        page-break-inside: avoid;
        margin-bottom: 15px;
        border: 1px solid #ddd !important;
        display: block;
        width: 100%;
        overflow: visible !important; /* Fix cutoff */
        height: auto !important;
    }
    .sales-list {
        display: block;
        height: auto !important;
        overflow: visible !important;
    }
    .report-main, .content-area {
        height: auto !important;
        overflow: visible !important;
        display: block !important;
    }
}
/* Mobile Responsive for Reports */
@media (max-width: 768px) {
  .page-container {
    padding: 10px;
    height: auto;
    overflow: auto;
  }
  
  .report-layout {
    flex-direction: column;
    overflow: visible !important;
    height: auto !important;
  }
  
  .report-sidebar {
    width: 100%;
    position: relative;
    top: 0;
    padding: 5px;
    display: flex;
    flex-direction: column;
    height: auto !important;
    overflow: visible !important;
    border-radius: 0;
    border: none;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .menu-section {
    margin-bottom: 5px;
  }
  
  .menu-header {
    display: flex !important;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    align-items: center;
    gap: 8px;
  }

  .menu-header .arrow {
    display: none;
  }
  
  .menu-items {
    display: flex !important;
    flex-direction: row !important;
    overflow-x: auto !important;
    padding: 5px;
    gap: 10px;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
    margin-top: 0 !important;
    padding-left: 5px !important;
  }
  
  .menu-item {
    padding: 8px 15px !important;
    background: #f1f5f9 !important;
    border: none !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    flex-shrink: 0 !important;
    height: auto !important;
    display: block !important;
    color: #64748b !important;
    box-shadow: none !important;
  }
  
  .menu-item.active {
    background: #0ea5e9 !important;
    color: white !important;
    font-weight: 700 !important;
  }

  .report-main {
    height: auto !important;
    overflow: visible !important;
  }

  .content-area {
    overflow: visible !important;
    height: auto !important;
  }

  .stats-row {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px !important;
  }
  
  .stat-box .val {
    font-size: 18px;
  }

  .filters-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    padding: 15px;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .filter-group input, .filter-group select {
    width: 100%;
    height: 40px;
    font-size: 14px;
  }

  .table-card {
    padding: 10px;
    margin-bottom: 15px;
  }

  .report-table th, .report-table td {
    padding: 8px;
    font-size: 12px;
  }
}
</style>
