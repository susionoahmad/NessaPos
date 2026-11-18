<template>
  <div v-if="auth.user?.role === 'kasir'" class="pos-screen" @keydown="handleGlobalKeydown" tabindex="-1" ref="posWrapper">
    <!-- Header -->
    <header class="pos-header">
      <div class="header-left">
        <h1>POS System - {{ auth.currentSession ? 'Aktif' : 'Standby' }}</h1>
        <div v-if="auth.currentSession" class="cashier-balance">
          Saldo Laci Kasir: Rp {{ cashierBalance.toLocaleString() }}
        </div>
        <div v-if="auth.currentSession" class="cashier-daily">
          Penjualan Tunai Sesi Ini: Rp {{ currentSessionSales.toLocaleString() }}
        </div>
        <div v-if="auth.currentSession" class="cashier-counts">
          Transaksi Sesi Ini: Cash {{ dailyCashCount }} | Non‑Cash {{ dailyNonCashCount }}
        </div>
      </div>
      <div v-if="auth.currentSession" class="header-right">
        <button
          type="button"
          class="btn-hold-cart"
          :disabled="cart.length === 0"
          @click="openHoldModal"
          title="Tahan / simpan sementara transaksi ini (F7)"
        >
          ⏸ Tahan (F7)
        </button>
        <button
          type="button"
          class="btn-pending-list"
          :class="{ 'has-pending': pendingTransactions.length > 0 }"
          @click="openPendingModal"
          title="Lihat dan lanjutkan transaksi pending (F8)"
        >
          📋 Pending
          <span v-if="pendingTransactions.length > 0" class="pending-badge">{{ pendingTransactions.length }}</span>
          <span class="pending-key">(F8)</span>
        </button>
      </div>
    </header>

    <div class="pos-body">
      <!-- Left Panel -->
      <div class="pos-left">
        <div class="search-box">
          <div class="search-input-row">
            <input 
              type="text" 
              ref="searchInput"
              v-model="searchQuery" 
              @keyup.enter="handleBarcodeScan"
              @keydown.down.prevent.stop="moveSearchSelection(1)"
              @keydown.up.prevent.stop="moveSearchSelection(-1)"
              placeholder="Scan barcode / Cari barang (Tekan F1)" 
              autocomplete="off"
            />
            <button
              type="button"
              class="btn-show-all"
              :class="{ active: showAllProducts }"
              @click="toggleShowAllProducts"
              title="Tampilkan / sembunyikan semua daftar barang (Tekan F4)"
            >
              {{ showAllProducts ? '✕ Tutup Daftar (F4)' : `📦 Semua Barang (${products.length}) [F4]` }}
            </button>
          </div>
          
          <!-- Search Results Dropdown -->
          <div v-if="showAllProducts || searchQuery.trim().length >= 2" class="search-results" ref="searchResultsRef">
            <div class="search-results-header">
              <span>
                <template v-if="searchQuery.trim()">
                  Ditemukan <strong>{{ filteredProducts.length }}</strong> dari <strong>{{ products.length }}</strong> produk
                </template>
                <template v-else>
                  Menampilkan semua <strong>{{ filteredProducts.length }}</strong> produk
                </template>
              </span>
              <button type="button" class="btn-close-results" @click="closeSearchDropdown">✕ Tutup</button>
            </div>
            <div v-if="filteredProducts.length === 0" class="search-empty">
              {{ searchQuery.trim() ? `Produk "${searchQuery}" tidak ditemukan.` : 'Belum ada data produk.' }}
            </div>
            <div 
              v-for="(p, idx) in filteredProducts" 
              :key="p.id" 
              class="search-item"
              :class="{ 'active': searchSelectedIndex === idx }"
              @click="addFromSearch(p)"
            >
              <div class="si-info">
                <span class="si-name">{{ p.name }}</span>
                <span class="si-barcode">{{ p.barcode }}</span>
              </div>
              <div class="si-price">Rp {{ (p.selling_price || 0).toLocaleString() }}</div>
              <div class="si-stock">Stok: {{ p.shelf_stock || 0 }}</div>
            </div>
          </div>
        </div>
        
        <div class="cart-table-wrapper">
          <table class="cart-table">
            <thead>
              <tr>
                <th class="col-name">Produk</th>
                <th class="col-qty">Harga & Qty</th>
                <th class="col-disc">Diskon</th>
                <th class="col-total">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="(item, idx) in cart" 
                :key="idx" 
                :class="{'selected-row': selectedRowIndex === idx}"
                @click="openCartField(idx, 'qty')"
              >
                <td class="col-name">
                  <div class="pd-barcode">{{ item.product_barcode }}</div>
                  <div class="pd-name">{{ item.product_name }}</div>
                </td>
                <td
                  class="col-qty"
                  :class="{ 'active-cell': selectedRowIndex === idx && selectedCartField === 'qty' }"
                  @click.stop="openCartField(idx, 'qty')"
                >
                  <span v-if="!(editIndex === idx && editType === 'qty')">{{ item.quantity }}x Rp {{ item.price.toLocaleString() }}</span>
                  <input 
                    v-else
                    type="number" 
                    v-model.number="editValue" 
                    @blur="handleEditBlur(idx, 'qty')" 
                    @keyup.enter.stop="saveEdit" 
                    @keyup.esc.stop="cancelEdit"
                    @keydown.left.prevent.stop="navigateCartHorizontal('qty')"
                    @keydown.right.prevent.stop="navigateCartHorizontal('disc')"
                    @keydown.up.prevent.stop="navigateCartVertical(-1)"
                    @keydown.down.prevent.stop="navigateCartVertical(1)"
                    ref="editInput"
                    class="qty-edit-input"
                  />
                </td>
                <td
                  class="col-disc"
                  :class="{ 'active-cell': selectedRowIndex === idx && selectedCartField === 'disc' }"
                  @click.stop="openCartField(idx, 'disc')"
                >
                   <span v-if="!(editIndex === idx && editType === 'disc')">{{ item.discount > 0 ? '-Rp ' + item.discount.toLocaleString() : '-' }}</span>
                   <input 
                    v-else
                    type="number" 
                    v-model.number="editValue" 
                    @blur="handleEditBlur(idx, 'disc')" 
                    @keyup.enter.stop="saveEdit" 
                    @keyup.esc.stop="cancelEdit"
                    @keydown.left.prevent.stop="navigateCartHorizontal('qty')"
                    @keydown.right.prevent.stop="navigateCartHorizontal('disc')"
                    @keydown.up.prevent.stop="navigateCartVertical(-1)"
                    @keydown.down.prevent.stop="navigateCartVertical(1)"
                    ref="editInput"
                    class="qty-edit-input"
                  />
                </td>
                <td class="col-total">
                  Rp {{ item.total.toLocaleString() }}
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="cart.length === 0" class="empty-state">
            Belum ada barang di keranjang
          </div>
        </div>
      </div>

      <!-- Right Panel -->
      <div class="pos-right">
        <div class="summary-box">
          <div class="summary-header">RINGKASAN TRANSAKSI</div>
          <div class="summary-content">
            <div class="sc-row form-group">
              <label>Pelanggan</label>
              <select class="customer-select">
                <option value="umum">Pelanggan Umum</option>
              </select>
            </div>
            <div class="sc-row totals-row">
              <span>Subtotal</span>
              <span>Rp {{ subtotalBeforeDisc.toLocaleString() }}</span>
            </div>
            <div v-if="totalItemsDiscount > 0" class="sc-row totals-row text-orange">
              <span>Diskon Item</span>
              <span>-Rp {{ totalItemsDiscount.toLocaleString() }}</span>
            </div>
            <div v-if="totalDiscountAmount > 0" class="sc-row totals-row text-orange">
              <span>Diskon Tambahan (Ctrl+D)</span>
              <span>-Rp {{ totalDiscountAmount.toLocaleString() }}</span>
            </div>
            <div v-if="taxRate > 0" class="sc-row totals-row text-slate">
              <span>Pajak ({{ taxType === 'inclusive' ? 'Inc' : 'Exc' }} {{ taxRate }}%)</span>
              <span>{{ taxType === 'inclusive' ? '' : '+' }}Rp {{ taxAmount.toLocaleString() }}</span>
            </div>
            <div v-if="roundingAmount !== 0" class="sc-row totals-row text-slate">
              <span>Pembulatan</span>
              <span>{{ roundingAmount > 0 ? '+' : '' }}Rp {{ roundingAmount.toLocaleString() }}</span>
            </div>
          </div>
          <div class="big-total">
            <div class="bt-label">TOTAL AKHIR</div>
            <div class="bt-value">Rp {{ totalAmount.toLocaleString() }}</div>
          </div>

          <!-- Compact Side-by-Side Payment Buttons -->
          <div v-if="cart.length > 0" class="touch-actions">
            <button type="button" class="btn-pay-touch" @click="handleOpenPaymentModal">
              <span>💳</span> BAYAR (F10)
            </button>
            <button type="button" class="btn-pay-fast" @click="handleFastCashPayment">
              <span>💵</span> TUNAI PAS (F9)
            </button>
          </div>
        </div>

        <div class="shortcuts-box">
          <div class="sh-header">Keyboard Shortcuts</div>
          <div class="sh-grid">
            <div class="sh-item"><kbd>F1</kbd> Cari/Barcode</div>
            <div class="sh-item"><kbd>TAB</kbd> Ke Keranjang (QTY)</div>
            <div class="sh-item"><kbd>← →</kbd> QTY / Diskon</div>
            <div class="sh-item"><kbd>↑ ↓</kbd> Baris QTY / Diskon</div>
            <div class="sh-item"><kbd>F2</kbd> Ubah Qty</div>
            <div class="sh-item"><kbd>F3</kbd> Diskon Item</div>
            <div class="sh-item"><kbd>F4</kbd> Semua Barang</div>
            <div class="sh-item"><kbd>F7</kbd> Tahan Pending</div>
            <div class="sh-item"><kbd>F8</kbd> Daftar Pending</div>
            <div class="sh-item"><kbd>Ctrl+D</kbd> Diskon Total</div>
            <div class="sh-item"><kbd>DEL</kbd> Hapus Item</div>
            <div class="sh-item"><kbd>F9</kbd> Bayar Pas (Tunai)</div>
            <div class="sh-item"><kbd>F10</kbd> Bayar Custom</div>
            <div class="sh-item"><kbd>ESC</kbd> Batal / Tutup</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hold Pending Modal (F7) -->
    <div v-if="showHoldModal" class="pending-overlay" @click.self="closeHoldModal">
      <div class="pending-modal small">
        <div class="pm-header">
          <h3>⏸ Tahan Transaksi (Pending)</h3>
          <button type="button" class="pm-close" @click="closeHoldModal">&times;</button>
        </div>
        <div class="pm-body">
          <p class="pm-desc">
            Simpan sementara <strong>{{ cart.length }} item</strong> (Total <strong>Rp {{ totalAmount.toLocaleString() }}</strong>) agar kasir bisa melayani pelanggan berikutnya terlebih dahulu.
          </p>
          <div class="pm-field">
            <label>Catatan / Penanda Pelanggan (Opsional)</label>
            <input
              ref="holdNoteInput"
              v-model="holdNote"
              type="text"
              placeholder="Contoh: Ibu baju biru / Antrian 1 (Kosongkan untuk otomatis)"
              @keyup.enter="confirmHoldCart"
              @keyup.esc="closeHoldModal"
            />
          </div>
        </div>
        <div class="pm-footer">
          <button type="button" class="pm-btn-cancel" @click="closeHoldModal">Batal</button>
          <button type="button" class="pm-btn-confirm" @click="confirmHoldCart">Simpan ke Pending</button>
        </div>
      </div>
    </div>

    <!-- Pending Transactions List Modal (F8) -->
    <div v-if="showPendingModal" class="pending-overlay" @click.self="closePendingModal">
      <div class="pending-modal">
        <div class="pm-header">
          <h3>📋 Daftar Transaksi Pending ({{ pendingTransactions.length }})</h3>
          <button type="button" class="pm-close" @click="closePendingModal">&times;</button>
        </div>
        <div class="pm-body">
          <div v-if="pendingTransactions.length === 0" class="pm-empty">
            Belum ada transaksi pending yang tersimpan.
          </div>
          <div v-else class="pm-list">
            <div v-for="pt in pendingTransactions" :key="pt.id" class="pm-card">
              <div class="pm-card-top">
                <div>
                  <div class="pm-card-title">{{ pt.note }}</div>
                  <div class="pm-card-meta">Ditahan pukul {{ pt.time }} • {{ pt.items.length }} jenis barang ({{ pt.totalQty }} pcs)</div>
                </div>
                <div class="pm-card-total">Rp {{ (pt.total || 0).toLocaleString() }}</div>
              </div>
              <div class="pm-card-items">
                <span v-for="(it, i) in pt.items.slice(0, 4)" :key="i" class="pm-item-chip">
                  {{ it.quantity }}x {{ it.product_name }}
                </span>
                <span v-if="pt.items.length > 4" class="pm-item-chip more">
                  +{{ pt.items.length - 4 }} lainnya
                </span>
              </div>
              <div class="pm-card-actions">
                <button type="button" class="pm-btn-resume" @click="resumePending(pt.id)">
                  ▶ Lanjutkan Transaksi
                </button>
                <button type="button" class="pm-btn-delete" @click="deletePending(pt.id)">
                  🗑️ Hapus
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="pm-footer">
          <button type="button" class="pm-btn-cancel" @click="closePendingModal">Tutup (ESC)</button>
        </div>
      </div>
    </div>

    <!-- Payment Modal (F10) -->
    <PaymentModal 
      v-if="showPaymentModal" 
      :total="totalAmount" 
      @close="closePayment"
      @confirm="processPayment"
    />

    <!-- Receipt Preview -->
    <ReceiptPreview 
      v-if="showReceipt" 
      :order="lastOrder" 
      :settings="settings"
      @close="closeReceipt"
    />

    <!-- Session Open Modal -->
    <SessionOpenModal
      v-if="showSessionOpenModal"
      :vault-balance="auth.vaultBalance"
      @cancel="cancelSessionOpen"
      @open="handleSessionOpen"
    />

    <!-- Session Close Modal -->
    <SessionCloseModal
      v-if="showSessionCloseModal"
      :session="auth.currentSession"
      :sales="currentSessionSales"
      @cancel="showSessionCloseModal = false"
      @close="handleSessionClose"
    />

    <!-- Block Overlay if no session -->
    <div v-if="!auth.currentSession && !showSessionOpenModal" class="session-blocker">
      <div class="blocker-card">
        <div class="icon">⚠️</div>
        <h2>Sesi Belum Dibuka</h2>
        <p>Anda harus membuka sesi kasir sebelum melakukan transaksi.</p>
        <button class="btn-primary" @click="showSessionOpenModal = true">Buka Sesi Sekarang</button>
      </div>
    </div>
  </div>
  <div v-else class="pos-screen bg-slate">
    <div class="session-blocker">
      <div class="blocker-card">
        <div class="icon">🚫</div>
        <h2>Akses Dibatasi</h2>
        <p>Halaman Transaksi POS hanya untuk Kasir.</p>
        <button class="btn-primary" @click="router.push('/products')">Ke Dashboard Admin</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { 
  GetProducts, CreateOrder, GetSettings, 
  GetVault, GetOpenSession, OpenSession, CloseSession, GetSessionSales,
  PrintReceiptThermal, GetDailyCashSales, GetPaymentCounts, GetCashierBalance,
  PrintSessionOpenReceipt, PrintSessionCloseReceipt, KickDrawer
} from '../../wailsjs/go/api/API'
import PaymentModal from '../components/PaymentModal.vue'
import ReceiptPreview from '../components/ReceiptPreview.vue'
import SessionOpenModal from '../components/SessionOpenModal.vue'
import SessionCloseModal from '../components/SessionCloseModal.vue'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const router = useRouter()

const posWrapper = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)
const searchResultsRef = ref<HTMLElement | null>(null)
const qtyInput = ref<HTMLInputElement[] | null>(null)

const products = ref<any[]>([])
const settings = ref<any>(null)

const searchQuery = ref('')
const showAllProducts = ref(false)
const searchSelectedIndex = ref(0)
const cart = ref<any[]>([])
const selectedRowIndex = ref(-1)
const selectedCartField = ref<'qty' | 'disc'>('qty')
let isSwitchingCartCell = false

const editIndex = ref(-1)
const editType = ref<'qty' | 'disc'>('qty')
const editValue = ref(0)
const editInput = ref<HTMLInputElement[] | null>(null)

const showPaymentModal = ref(false)
const showReceipt = ref(false)
const lastOrder = ref<any>(null)
const totalDiscountAmount = ref(0)

// Pending / Hold Transactions
const pendingTransactions = ref<any[]>([])
const showHoldModal = ref(false)
const showPendingModal = ref(false)
const holdNote = ref('')
const holdNoteInput = ref<HTMLInputElement | null>(null)

const getPendingStorageKey = () => `nessapos_desktop_pending_carts_${auth.user?.id || 'kasir'}`

const loadPendingTransactions = () => {
  try {
    const raw = localStorage.getItem(getPendingStorageKey())
    pendingTransactions.value = raw ? JSON.parse(raw) : []
  } catch {
    pendingTransactions.value = []
  }
}

const persistPendingTransactions = () => {
  try {
    localStorage.setItem(getPendingStorageKey(), JSON.stringify(pendingTransactions.value))
  } catch (e) {
    console.error('Failed saving pending transactions', e)
  }
}

const openHoldModal = () => {
  if (cart.value.length === 0) return
  if (editIndex.value !== -1) {
    commitCurrentEdit()
    editIndex.value = -1
  }
  showAllProducts.value = false
  holdNote.value = ''
  showHoldModal.value = true
  nextTick(() => {
    holdNoteInput.value?.focus()
  })
}

const closeHoldModal = () => {
  showHoldModal.value = false
  searchInput.value?.focus()
}

const confirmHoldCart = () => {
  if (cart.value.length === 0) {
    showHoldModal.value = false
    return
  }
  const now = new Date()
  const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  const defaultNote = `Pending #${pendingTransactions.value.length + 1}`
  const totalQty = cart.value.reduce((acc, item) => acc + (Number(item.quantity) || 0), 0)

  pendingTransactions.value.unshift({
    id: Date.now(),
    note: holdNote.value.trim() || defaultNote,
    time: timeStr,
    items: cart.value.map(item => ({ ...item })),
    totalDiscountAmount: totalDiscountAmount.value,
    total: totalAmount.value,
    totalQty
  })
  persistPendingTransactions()

  cart.value = []
  totalDiscountAmount.value = 0
  selectedRowIndex.value = -1
  searchQuery.value = ''
  showHoldModal.value = false
  nextTick(() => {
    searchInput.value?.focus()
  })
}

const openPendingModal = () => {
  if (editIndex.value !== -1) {
    commitCurrentEdit()
    editIndex.value = -1
  }
  showAllProducts.value = false
  showPendingModal.value = true
}

const closePendingModal = () => {
  showPendingModal.value = false
  searchInput.value?.focus()
}

const resumePending = (pendingId: number) => {
  const idx = pendingTransactions.value.findIndex(p => p.id === pendingId)
  if (idx === -1) return

  if (cart.value.length > 0) {
    const swap = confirm('Keranjang saat ini masih berisi barang. Tahan (simpan ke Pending) keranjang saat ini dulu sebelum melanjutkan transaksi yang dipilih?')
    if (!swap) return
    const now = new Date()
    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
    const totalQty = cart.value.reduce((acc, item) => acc + (Number(item.quantity) || 0), 0)
    pendingTransactions.value.push({
      id: Date.now() + 1,
      note: `Otomatis ditahan (${timeStr})`,
      time: timeStr,
      items: cart.value.map(item => ({ ...item })),
      totalDiscountAmount: totalDiscountAmount.value,
      total: totalAmount.value,
      totalQty
    })
  }

  const targetIndex = pendingTransactions.value.findIndex(p => p.id === pendingId)
  const [picked] = pendingTransactions.value.splice(targetIndex, 1)

  // Re-validate stock against current product list
  const warnings: string[] = []
  const restoredItems: any[] = []
  for (const item of picked.items) {
    const latest = products.value.find(p => p.id === item.product_id)
    if (latest) {
      if (latest.shelf_stock <= 0) {
        warnings.push(`- ${item.product_name}: stok rak habis (dilewati)`)
        continue
      }
      const adjustedQty = Math.min(item.quantity, latest.shelf_stock)
      if (adjustedQty < item.quantity) {
        warnings.push(`- ${item.product_name}: qty disesuaikan dari ${item.quantity} menjadi ${adjustedQty} (sisa stok rak)`)
      }
      restoredItems.push({
        ...item,
        quantity: adjustedQty,
        total: (item.price * adjustedQty) - (item.discount || 0)
      })
    } else {
      restoredItems.push({ ...item })
    }
  }

  cart.value = restoredItems
  totalDiscountAmount.value = picked.totalDiscountAmount || 0
  selectedRowIndex.value = cart.value.length > 0 ? 0 : -1
  persistPendingTransactions()
  showPendingModal.value = false

  if (warnings.length > 0) {
    alert('Perhatian perubahan stok saat transaksi pending dilanjutkan:\n' + warnings.join('\n'))
  }

  nextTick(() => {
    searchInput.value?.focus()
  })
}

const deletePending = (pendingId: number) => {
  if (!confirm('Hapus transaksi pending ini?')) return
  pendingTransactions.value = pendingTransactions.value.filter(p => p.id !== pendingId)
  persistPendingTransactions()
}

// Session Management
const showSessionOpenModal = ref(false)
const showSessionCloseModal = ref(false)
const currentSessionSales = ref(0)
const dailyCashSales = ref(0)
const dailyCashCount = ref(0)
const dailyNonCashCount = ref(0)
const cashierBalance = ref(0)
const cashDrawerEnabled = computed(() => settings.value?.cash_drawer_enabled !== false)

onMounted(async () => {
  if (!auth.user) {
    router.push('/')
    return
  }

  // Load Initial Data
  products.value = await GetProducts() || []
  settings.value = await GetSettings() || { tax_rate: 10 }
  auth.cashDrawerEnabled = settings.value?.cash_drawer_enabled !== false
  loadPendingTransactions()
  
  // Refresh Session & Vault
  await refreshVaultAndSession()
  await refreshDailyCashSales()
  await refreshDailyPaymentCounts()
  applyRefreshInterval()
  startSettingsPolling()

  if (posWrapper.value) {
    posWrapper.value.focus()
  }
  if (searchInput.value && auth.currentSession) {
    searchInput.value.focus()
  }
})
let refreshTimer: number | null = null
let settingsPollTimer: number | null = null

// Listen for Sidebar Triggers
watch(() => auth.triggerSessionOpen, () => {
  if (!auth.currentSession) {
    showSessionOpenModal.value = true
  }
})

watch(() => auth.triggerSessionClose, async () => {
  if (auth.currentSession) {
    await refreshVaultAndSession()
    showSessionCloseModal.value = true
  }
})

const refreshVaultAndSession = async () => {
    try {
        const v = await GetVault()
        auth.vaultBalance = v?.balance || 0
        
        const s = await GetOpenSession(auth.user.id)
        auth.currentSession = s
        
        if (s) {
            currentSessionSales.value = await GetSessionSales(s.id, auth.user.id, s.start_time)
            cashierBalance.value = await GetCashierBalance(auth.user.id)
        } else {
            cashierBalance.value = 0
        }
    } catch(e) {
        console.error("Refresh error", e)
    }
}

const refreshDailyCashSales = async () => {
  try {
    dailyCashSales.value = await GetDailyCashSales()
  } catch (e) {
    console.error("Daily cash sales error", e)
  }
}

const refreshDailyPaymentCounts = async () => {
  try {
    const counts = await GetPaymentCounts(auth.user?.id || 0, auth.currentSession?.id || 0)
    dailyCashCount.value = counts?.cash || 0
    dailyNonCashCount.value = counts?.non_cash || 0
  } catch (e) {
    console.error("Daily payment counts error", e)
  }
}

const applyRefreshInterval = () => {
  if (refreshTimer !== null) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
  const intervalSec = settings.value?.refresh_interval_sec ?? 30
  if (intervalSec <= 0) return
  refreshTimer = window.setInterval(async () => {
    await refreshVaultAndSession()
    await refreshDailyCashSales()
    await refreshDailyPaymentCounts()
  }, intervalSec * 1000)
}

watch(() => settings.value?.refresh_interval_sec, () => {
  applyRefreshInterval()
})

const startSettingsPolling = () => {
  if (settingsPollTimer !== null) {
    clearInterval(settingsPollTimer)
  }
  settingsPollTimer = window.setInterval(async () => {
    try {
      const s = await GetSettings()
      if (s) {
        settings.value = { ...s } as any
        auth.cashDrawerEnabled = settings.value?.cash_drawer_enabled !== false
      }
    } catch (e) {
      console.error("Settings poll error", e)
    }
  }, 15000)
}

onUnmounted(() => {
  if (refreshTimer !== null) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
  if (settingsPollTimer !== null) {
    clearInterval(settingsPollTimer)
    settingsPollTimer = null
  }
})

// Keyboard shortcuts
const handleGlobalKeydown = async (e: KeyboardEvent) => {
  if (showPaymentModal.value || showReceipt.value) return;

  if (showHoldModal.value) {
    if (e.key === 'Escape') {
      e.preventDefault()
      closeHoldModal()
    }
    return
  }

  if (showPendingModal.value) {
    if (e.key === 'Escape') {
      e.preventDefault()
      closePendingModal()
    }
    return
  }

  if (e.key === 'F1') {
    e.preventDefault()
    if (editIndex.value !== -1) {
      commitCurrentEdit()
      editIndex.value = -1
    }
    searchInput.value?.focus()
  } 
  else if (e.key === 'F2') {
    e.preventDefault()
    if (selectedRowIndex.value >= 0 && selectedRowIndex.value < cart.value.length) {
      startEditQty(selectedRowIndex.value)
    }
  }
  else if (e.key === 'F3') {
    e.preventDefault()
    if (selectedRowIndex.value >= 0 && selectedRowIndex.value < cart.value.length) {
      startEditDisc(selectedRowIndex.value)
    }
  }
  else if (e.key === 'F4') {
    e.preventDefault()
    if (editIndex.value !== -1) {
      commitCurrentEdit()
      editIndex.value = -1
    }
    toggleShowAllProducts()
  }
  else if (e.key === 'F7') {
    e.preventDefault()
    if (cart.value.length > 0) {
      openHoldModal()
    }
  }
  else if (e.key === 'F8') {
    e.preventDefault()
    openPendingModal()
  }
  else if (e.key === 'F9') {
    e.preventDefault()
    if (editIndex.value !== -1) {
      commitCurrentEdit()
      editIndex.value = -1
    }
    if (cart.value.length > 0) {
      processPayment({ method: 'Cash', amount: totalAmount.value, change: 0 })
    }
  }
  else if (e.key === 'F10') {
    e.preventDefault()
    if (editIndex.value !== -1) {
      commitCurrentEdit()
      editIndex.value = -1
    }
    if (cart.value.length > 0) {
      showPaymentModal.value = true
    }
  }
  else if (e.key === 'Escape') {
    if (editIndex.value !== -1) {
      cancelEdit()
    } else if (showAllProducts.value || searchQuery.value.trim().length > 0) {
      closeSearchDropdown()
    } else {
      // Cancel transaction
      if (cart.value.length > 0 && confirm('Batalkan transaksi ini?')) {
        cart.value = []
        searchQuery.value = ''
        selectedRowIndex.value = -1
        searchInput.value?.focus()
      }
    }
  }
  else if (e.key === 'Delete') {
    if (selectedRowIndex.value >= 0 && selectedRowIndex.value < cart.value.length && editIndex.value === -1) {
      cart.value.splice(selectedRowIndex.value, 1)
      if (selectedRowIndex.value >= cart.value.length) {
        selectedRowIndex.value = cart.value.length - 1
      }
      // Totals are computed automatically
    }
  }
  else if (e.key === 'ArrowLeft') {
    if (document.activeElement !== searchInput.value && cart.value.length > 0) {
      e.preventDefault()
      navigateCartHorizontal('qty')
    }
  }
  else if (e.key === 'ArrowRight') {
    if (document.activeElement !== searchInput.value && cart.value.length > 0) {
      e.preventDefault()
      navigateCartHorizontal('disc')
    }
  }
  else if (e.key === 'ArrowUp') {
    if (document.activeElement !== searchInput.value && cart.value.length > 0) {
      e.preventDefault()
      navigateCartVertical(-1)
    }
  }
  else if (e.key === 'ArrowDown') {
    if (document.activeElement !== searchInput.value && cart.value.length > 0) {
      e.preventDefault()
      navigateCartVertical(1)
    }
  }
  else if (e.key === 'Tab') {
    e.preventDefault()
    showAllProducts.value = false
    if (cart.value.length > 0) {
      const targetRow = selectedRowIndex.value >= 0 && selectedRowIndex.value < cart.value.length ? selectedRowIndex.value : 0
      if (editIndex.value === -1) {
        openCartField(targetRow, 'qty')
      } else {
        openCartField(targetRow, selectedCartField.value === 'qty' ? 'disc' : 'qty')
      }
    } else {
      posWrapper.value?.focus()
    }
  }
  else if (e.ctrlKey && e.key.toLowerCase() === 'l') { // Ctrl+L to close session
    e.preventDefault()
    if (auth.currentSession) {
        await refreshVaultAndSession()
        showSessionCloseModal.value = true
    }
  }
  else if (e.ctrlKey && e.key.toLowerCase() === 'd') {
    e.preventDefault()
    const d = prompt('Masukkan nominal diskon tambahan (Rp):', totalDiscountAmount.value.toString())
    if (d !== null && !isNaN(parseInt(d))) {
      totalDiscountAmount.value = parseInt(d)
    }
  }
}

const filteredProducts = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) {
    return showAllProducts.value ? products.value : []
  }
  if (!showAllProducts.value && q.length < 2) return []
  return products.value.filter(p =>
    String(p.name || '').toLowerCase().includes(q) ||
    String(p.barcode || '').toLowerCase().includes(q)
  )
})

const toggleShowAllProducts = () => {
  showAllProducts.value = !showAllProducts.value
  searchSelectedIndex.value = 0
  if (showAllProducts.value) {
    nextTick(() => {
      searchInput.value?.focus()
      if (searchResultsRef.value) searchResultsRef.value.scrollTop = 0
    })
  }
}

const closeSearchDropdown = () => {
  showAllProducts.value = false
  searchQuery.value = ''
  searchSelectedIndex.value = 0
}

watch(searchQuery, () => {
  searchSelectedIndex.value = 0
  if (searchResultsRef.value) {
    searchResultsRef.value.scrollTop = 0
  }
})

const moveSearchSelection = (dir: number) => {
  if (filteredProducts.value.length === 0) return
  searchSelectedIndex.value = (searchSelectedIndex.value + dir + filteredProducts.value.length) % filteredProducts.value.length
  nextTick(() => {
    const activeEl = searchResultsRef.value?.querySelector('.search-item.active') as HTMLElement | null
    activeEl?.scrollIntoView({ block: 'nearest' })
  })
}

const addFromSearch = (product: any) => {
  addToCart(product)
  searchQuery.value = ''
  showAllProducts.value = false
  searchSelectedIndex.value = 0
  searchInput.value?.focus()
}

const handleBarcodeScan = () => {
  // If there are search results (including Show All Products / F4) and we press enter, add the selected one
  if (filteredProducts.value.length > 0) {
    const selected = filteredProducts.value[searchSelectedIndex.value] || filteredProducts.value[0]
    if (selected) {
      addFromSearch(selected)
    }
    return
  }

  if (!searchQuery.value.trim()) return;

  const q = searchQuery.value.trim().toLowerCase()
  const match = products.value.find(p => String(p.barcode || '').toLowerCase() === q || String(p.name || '').toLowerCase() === q)
  
  if (match) {
    addToCart(match)
    searchQuery.value = ''
  } else {
    alert('Barang tidak ditemukan!')
  }
}

const addToCart = (product: any) => {
  if (product.shelf_stock <= 0) {
    alert('Stok produk ini sedang kosong atau sudah habis di rak!')
    return
  }
  const exist = cart.value.find(item => item.product_id === product.id)
  if (exist) {
    if (exist.quantity < product.shelf_stock) {
      exist.quantity++
      exist.total = (exist.price * exist.quantity) - exist.discount
    } else {
      alert('Stok di rak tidak mencukupi!')
    }
  } else {
    cart.value.unshift({
      product_id: product.id,
      product_barcode: product.barcode,
      product_name: product.name,
      price: product.selling_price,
      quantity: 1,
      discount: 0,
      total: product.selling_price
    })
  }
  selectedRowIndex.value = 0
}

const openCartField = (idx: number, field: 'qty' | 'disc') => {
  if (idx < 0 || idx >= cart.value.length) return
  isSwitchingCartCell = true
  if (editIndex.value !== -1 && (editIndex.value !== idx || editType.value !== field)) {
    commitCurrentEdit()
  }
  selectedRowIndex.value = idx
  selectedCartField.value = field
  editIndex.value = idx
  editType.value = field
  editValue.value = field === 'qty' ? cart.value[idx].quantity : cart.value[idx].discount
  focusEditInput()
}

const startEditQty = (idx: number) => {
  openCartField(idx, 'qty')
}

const startEditDisc = (idx: number) => {
  openCartField(idx, 'disc')
}

const navigateCartHorizontal = (targetField: 'qty' | 'disc') => {
  if (cart.value.length === 0) return
  const row = selectedRowIndex.value >= 0 && selectedRowIndex.value < cart.value.length ? selectedRowIndex.value : 0
  openCartField(row, targetField)
}

const navigateCartVertical = (dir: number) => {
  if (cart.value.length === 0) return
  const currentRow = selectedRowIndex.value >= 0 && selectedRowIndex.value < cart.value.length ? selectedRowIndex.value : 0
  const nextRow = Math.max(0, Math.min(cart.value.length - 1, currentRow + dir))
  openCartField(nextRow, selectedCartField.value)
}

const focusEditInput = () => {
  nextTick(() => {
    const el = Array.isArray(editInput.value) ? editInput.value[0] : (editInput.value as HTMLInputElement | null)
    if (el) {
      el.focus()
      el.select()
    }
    isSwitchingCartCell = false
  })
}

const commitCurrentEdit = () => {
  if (editIndex.value === -1) return
  const item = cart.value[editIndex.value]
  if (!item) return
  if (editType.value === 'qty') {
    const product = products.value.find(p => p.id === item.product_id)
    const maxStock = product ? product.shelf_stock : 1
    const val = Number(editValue.value) || 1
    if (val > maxStock) {
      alert('Stok tidak mencukupi! Maksimal stok di rak: ' + maxStock)
      item.quantity = maxStock
    } else {
      item.quantity = Math.max(1, val)
    }
  } else {
    item.discount = Math.max(0, Number(editValue.value) || 0)
  }
  item.total = (item.price * item.quantity) - item.discount
}

const handleEditBlur = (idx: number, type: 'qty' | 'disc') => {
  if (isSwitchingCartCell) return
  if (editIndex.value === idx && editType.value === type) {
    saveEdit()
  }
}

const saveEdit = () => {
  if (editIndex.value !== -1) {
    commitCurrentEdit()
    editIndex.value = -1
    posWrapper.value?.focus()
  }
}

const cancelEdit = () => {
  editIndex.value = -1
  posWrapper.value?.focus()
}

const subtotalBeforeDisc = computed(() => cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0))
const totalItemsDiscount = computed(() => cart.value.reduce((acc, item) => acc + item.discount, 0))
const taxRate = computed(() => settings.value?.tax_rate || 0)
const taxType = computed(() => settings.value?.tax_type || 'exclusive')

const taxAmount = computed(() => {
  const afterDisc = subtotalBeforeDisc.value - totalItemsDiscount.value - totalDiscountAmount.value
  if (taxType.value === 'inclusive') {
    // Inclusive: Tax = Total - (Total / (1 + Rate/100))
    return Math.round(afterDisc - (afterDisc / (1 + taxRate.value / 100)))
  } else {
    // Exclusive: Tax = Net * Rate/100
    return Math.round((afterDisc * taxRate.value) / 100)
  }
})

const totalRaw = computed(() => {
  const afterDisc = subtotalBeforeDisc.value - totalItemsDiscount.value - totalDiscountAmount.value
  if (taxType.value === 'inclusive') {
    return Math.max(0, afterDisc)
  } else {
    return Math.max(0, afterDisc + taxAmount.value)
  }
})

const totalAmount = computed(() => {
  // Round to nearest 100
  return Math.round(totalRaw.value / 100) * 100
})

const roundingAmount = computed(() => totalAmount.value - totalRaw.value)

const closePayment = () => {
  showPaymentModal.value = false
  posWrapper.value?.focus()
}

const handleOpenPaymentModal = () => {
  if (editIndex.value !== -1) {
    commitCurrentEdit()
    editIndex.value = -1
  }
  if (cart.value.length > 0) {
    showPaymentModal.value = true
  }
}

const handleFastCashPayment = () => {
  if (editIndex.value !== -1) {
    commitCurrentEdit()
    editIndex.value = -1
  }
  if (cart.value.length > 0) {
    processPayment({ method: 'Cash', amount: totalAmount.value, change: 0 })
  }
}

const processPayment = async (paymentData: any) => {
  const orderReq = {
    user_id: Number(auth.user?.id || 1),
    total_amount: Number(subtotalBeforeDisc.value) || 0,
    tax_amount: Number(taxAmount.value) || 0,
    discount: Number(totalItemsDiscount.value + totalDiscountAmount.value) || 0,
    final_amount: Number(totalAmount.value) || 0,
    items: cart.value.map(c => ({
      product_id: Number(c.product_id) || null,
      product_name: String(c.product_name || ''),
      quantity: Number(c.quantity) || 0,
      price: Number(c.price) || 0,
      discount: Number(c.discount) || 0,
      total: Number(c.total) || 0
    })),
    payment_method: String(paymentData.method || 'Cash'),
    amount_paid: Number(paymentData.amount) || 0,
    change_amount: Number(paymentData.change) || 0
  }
  
  try {
    await CreateOrder(orderReq as any)
    try {
      await PrintReceiptThermal(orderReq as any)
      if (cashDrawerEnabled.value && String(paymentData.method || '').toLowerCase() === 'cash') {
        await KickDrawer()
      }
    } catch (e) {
      console.error("Thermal print error:", e)
      alert("Struk gagal dicetak: " + e)
    }
    await refreshVaultAndSession()
    await refreshDailyCashSales()
    await refreshDailyPaymentCounts()
    lastOrder.value = { ...orderReq, date: new Date().toLocaleString() }
    showPaymentModal.value = false
    showReceipt.value = true
    products.value = await GetProducts() || []
  } catch(e) {
    console.error("Order error:", e)
    alert("Failed to process transaction.")
  }
}

const closeReceipt = () => {
  showReceipt.value = false
  cart.value = []
  totalDiscountAmount.value = 0
  selectedRowIndex.value = -1
  searchInput.value?.focus()
  refreshVaultAndSession() // Update sales count after order
}

// Session Callbacks
const cancelSessionOpen = () => {
    showSessionOpenModal.value = false
}

const handleSessionOpen = async (data: any) => {
    try {
        const sess = await OpenSession(auth.user.id, data.amount, data.denoms)
        auth.currentSession = sess
        showSessionOpenModal.value = false
        try {
          await PrintSessionOpenReceipt({
            user_id: auth.user.id,
            username: auth.user.username,
            amount: data.amount,
            denoms: data.denoms
          } as any)
        } catch (e) {
          console.error("Session open print error:", e)
          alert("Slip buka sesi gagal dicetak: " + e)
        }
        alert("Sesi berhasil dibuka.")
        nextTick(() => searchInput.value?.focus())
        refreshVaultAndSession()
    } catch(e) {
        alert("Gagal buka sesi: " + e)
    }
}

const handleSessionClose = async (data: any) => {
    try {
        const sessionSnapshot = auth.currentSession
        const cashSales = currentSessionSales.value
        await CloseSession(auth.user.id, data.physicalAmount, data.denoms)
        try {
          if (sessionSnapshot) {
            const expected = (sessionSnapshot.start_amount || 0) + cashSales
            const difference = data.physicalAmount - expected
            await PrintSessionCloseReceipt({
              user_id: auth.user.id,
              username: auth.user.username,
              start_amount: sessionSnapshot.start_amount || 0,
              cash_sales: cashSales,
              expected: expected,
              physical: data.physicalAmount,
              difference: difference,
              denoms: data.denoms
            } as any)
          }
        } catch (e) {
          console.error("Session close print error:", e)
          alert("Slip tutup sesi gagal dicetak: " + e)
        }
        auth.currentSession = null
        showSessionCloseModal.value = false
        alert("Sesi ditutup. Saldo laci telah disetor ke brankas.")
        refreshVaultAndSession()
    } catch(e) {
        alert("Gagal tutup sesi: " + e)
    }
}


</script>

<style scoped>
.pos-screen {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
  background-color: #f4f6f9;
  color: #334155;
  outline: none;
  font-family: inherit;
}

.pos-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: white;
  padding: 16px 24px;
  color: #1e293b;
  border-bottom: 1px solid #e2e8f0;
}

.pos-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}

.cashier-balance {
  margin-top: 6px;
  font-size: 13px;
  font-weight: 700;
  color: #0ea5e9;
}

.cashier-daily {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 700;
  color: #22c55e;
}

.cashier-counts {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-hold-cart,
.btn-pending-list {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-hold-cart {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fcd34d;
}
.btn-hold-cart:hover:not(:disabled) {
  background: #fef3c7;
}
.btn-hold-cart:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.btn-pending-list {
  background: #f8fafc;
  color: #334155;
  border: 1px solid #cbd5e1;
}
.btn-pending-list:hover {
  background: #f1f5f9;
}
.btn-pending-list.has-pending {
  background: #eff6ff;
  color: #1d4ed8;
  border-color: #93c5fd;
}

.pending-badge {
  background: #ef4444;
  color: white;
  font-size: 11px;
  font-weight: 800;
  padding: 1px 7px;
  border-radius: 999px;
}

.pending-key {
  font-size: 11px;
  opacity: 0.75;
}

/* Pending Modals */
.pending-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
  padding: 20px;
}

.pending-modal {
  background: white;
  width: 100%;
  max-width: 560px;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 85vh;
}

.pending-modal.small {
  max-width: 440px;
}

.pm-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.pm-header h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 800;
  color: #0f172a;
}

.pm-close {
  background: none;
  border: none;
  font-size: 24px;
  color: #64748b;
  cursor: pointer;
  line-height: 1;
}

.pm-body {
  padding: 18px 20px;
  overflow-y: auto;
}

.pm-desc {
  margin: 0 0 14px;
  font-size: 13.5px;
  color: #475569;
  line-height: 1.5;
}

.pm-field label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 6px;
}

.pm-field input {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  box-sizing: border-box;
  outline: none;
}

.pm-field input:focus {
  border-color: #0ea5e9;
}

.pm-empty {
  text-align: center;
  padding: 36px 16px;
  color: #64748b;
  font-size: 14px;
}

.pm-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pm-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
  background: #f8fafc;
}

.pm-card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 10px;
}

.pm-card-title {
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
}

.pm-card-meta {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.pm-card-total {
  font-size: 16px;
  font-weight: 800;
  color: #0284c7;
  white-space: nowrap;
}

.pm-card-items {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}

.pm-item-chip {
  font-size: 11.5px;
  background: white;
  border: 1px solid #e2e8f0;
  color: #334155;
  padding: 3px 8px;
  border-radius: 6px;
}

.pm-item-chip.more {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
  font-weight: 700;
}

.pm-card-actions {
  display: flex;
  gap: 8px;
}

.pm-btn-resume {
  flex: 1;
  padding: 9px 12px;
  background: #0ea5e9;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}
.pm-btn-resume:hover {
  background: #0284c7;
}

.pm-btn-delete {
  padding: 9px 12px;
  background: #fee2e2;
  color: #dc2626;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}
.pm-btn-delete:hover {
  background: #fecaca;
}

.pm-footer {
  padding: 12px 20px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.pm-btn-cancel {
  padding: 9px 16px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}

.pm-btn-confirm {
  padding: 9px 18px;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
}
.pm-btn-confirm:hover {
  background: #d97706;
}

.pos-body {
  flex: 1;
  display: flex;
  overflow: hidden;
  padding: 20px;
  gap: 20px;
}

.pos-left {
  flex: 65;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.search-input-row {
  display: flex;
  gap: 10px;
  align-items: stretch;
}

.search-box input {
  flex: 1;
  min-width: 0;
  width: 100%;
  padding: 14px 16px;
  background-color: white;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  font-size: 15px;
  border-radius: 8px;
  box-sizing: border-box;
  outline: none;
  transition: border-color 0.2s;
}
.search-box input:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 2px rgba(56,189,248,0.2);
}

.btn-show-all {
  flex-shrink: 0;
  padding: 0 16px;
  background: white;
  color: #0284c7;
  border: 1px solid #bae6fd;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}
.btn-show-all:hover {
  background: #f0f9ff;
  border-color: #38bdf8;
}
.btn-show-all.active {
  background: #0ea5e9;
  color: white;
  border-color: #0ea5e9;
}

.search-box {
  position: relative;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
  z-index: 100;
  margin-top: 4px;
  max-height: calc(100vh - 230px);
  overflow-y: auto;
}

.search-results-header {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #f8fafc;
  color: #64748b;
  font-size: 12px;
  padding: 8px 16px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.search-results-header strong {
  color: #0f172a;
}

.btn-close-results {
  background: #e2e8f0;
  color: #334155;
  border: none;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}
.btn-close-results:hover {
  background: #cbd5e1;
}

.search-empty {
  padding: 24px 16px;
  text-align: center;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.search-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  gap: 15px;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.2s;
}

.search-item:last-child {
  border-bottom: none;
}

.search-item:hover, .search-item.active {
  background-color: #f0f9ff;
}

.si-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.si-name {
  font-weight: 700;
  color: #1e293b;
  font-size: 14px;
}

.si-barcode {
  font-size: 11px;
  color: #94a3b8;
}

.si-price {
  font-weight: 800;
  color: #0ea5e9;
  font-size: 14px;
}

.si-stock {
  font-size: 11px;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 4px;
}

.cart-table-wrapper {
  flex: 1;
  background-color: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow-y: auto;
  position: relative;
}

.cart-table thead {
  background: #f8fafc;
  position: sticky;
  top: 0;
  z-index: 10;
}

.cart-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  color: #64748b;
  border-bottom: 2px solid #e2e8f0;
}

.col-disc {
  text-align: right;
  color: #f59e0b;
}

.cart-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.cart-table tr.selected-row {
  background-color: #f0f9ff;
  border-left: 4px solid #0ea5e9;
}

.cart-table td.active-cell {
  background-color: #e0f2fe;
  box-shadow: inset 0 0 0 2px #0ea5e9;
  border-radius: 4px;
}

.pd-barcode {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 4px;
  font-weight: 600;
}
.pd-name {
  font-size: 15px;
  color: #1e293b;
  font-weight: 500;
}

.col-qty {
  text-align: right;
  color: #475569;
  font-weight: 500;
}
.col-total {
  text-align: right;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}
.col-name {
  width: 50%;
}

.qty-edit-input {
  width: 60px;
  padding: 4px;
  background: white;
  border: 1px solid #0ea5e9;
  color: #0f172a;
  text-align: right;
  outline: none;
  border-radius: 4px;
}

.cart-footer {
  padding: 20px;
  background: #f8fafc;
  border-top: 2px solid #e2e8f0;
}

.cf-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 15px;
  color: #475569;
  font-weight: 500;
}

.grand-total-row {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 2px dashed #cbd5e1;
  color: #0f172a;
  font-weight: 800;
}

.total-big {
  font-size: 28px;
  color: #0ea5e9;
}

.empty-state {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #94a3b8;
  font-size: 15px;
}

.pos-right {
  flex: 35;
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 0;
}

.shortcuts-box {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.summary-box {
  background-color: white;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px 16px;
}

.summary-header {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px dashed #cbd5e1;
}

.summary-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.sc-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-group label {
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}

.customer-select {
  padding: 6px 10px;
  background-color: white;
  border: 1px solid #cbd5e1;
  color: #334155;
  border-radius: 6px;
  outline: none;
  font-size: 13px;
  font-weight: 500;
  min-width: 140px;
}

.totals-row {
  font-size: 13.5px;
  color: #475569;
  font-weight: 500;
}

.totals-row.text-orange {
  color: #f59e0b;
}

.big-total {
  margin-top: 12px;
  padding-top: 10px;
  border-top: 2px dashed #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 8px;
}

.bt-label {
  font-size: 14px;
  font-weight: 800;
  color: #1e293b;
}

.bt-value {
  font-size: 26px;
  font-weight: 800;
  color: #0ea5e9;
  line-height: 1.1;
}

.touch-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-top: 12px;
}

.btn-pay-touch, .btn-pay-fast {
  padding: 10px 8px;
  border-radius: 8px;
  border: none;
  font-weight: 800;
  font-size: 12.5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  transition: all 0.15s;
  color: white;
  white-space: nowrap;
}

.btn-pay-touch {
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  box-shadow: 0 3px 8px rgba(14, 165, 233, 0.25);
}

.btn-pay-fast {
  background: #10b981;
  box-shadow: 0 3px 8px rgba(16, 185, 129, 0.25);
}

.btn-pay-touch:hover, .btn-pay-fast:hover {
  filter: brightness(1.06);
}

.btn-pay-touch:active, .btn-pay-fast:active {
  transform: scale(0.98);
}

.shortcuts-box {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px 16px;
  flex: 1;
}

.sh-header {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sh-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 10px;
}

.sh-item {
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #475569;
  font-weight: 500;
}

.sh-item kbd {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 21px;
  min-width: 22px;
  padding: 0 6px;
  margin-right: 8px;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 5px;
  font-family: inherit;
  font-size: 11px;
  font-weight: 700;
  color: #0ea5e9;
  box-shadow: 0 2px 0 #e2e8f0;
}

.session-blocker {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(241, 245, 249, 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.blocker-card {
  background: white;
  padding: 40px;
  border-radius: 20px;
  text-align: center;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
  max-width: 400px;
}
.blocker-card .icon { font-size: 48px; margin-bottom: 20px; }
.blocker-card h2 { margin: 0 0 12px; color: #1e293b; }
.blocker-card p { color: #64748b; margin-bottom: 30px; }

.text-danger { color: #ef4444; }
.text-success { color: #22c55e; }

.bg-slate {
    background-color: #1e293b;
}

.btn-primary {
  padding: 12px 24px;
  background: #0ea5e9;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
</style>
