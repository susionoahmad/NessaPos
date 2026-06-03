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
    </header>

    <div class="pos-body">
      <!-- Left Panel -->
      <div class="pos-left">
        <div class="search-box">
          <input 
            type="text" 
            ref="searchInput"
            v-model="searchQuery" 
            @keyup.enter="handleBarcodeScan"
            @keydown.down="moveSearchSelection(1)"
            @keydown.up="moveSearchSelection(-1)"
            placeholder="Scan barcode / Cari barang (Tekan F1)" 
            autocomplete="off"
          />
          
          <!-- Search Results Dropdown -->
          <div v-if="searchQuery.length >= 2 && filteredProducts.length > 0" class="search-results">
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
                @click="selectedRowIndex = idx"
              >
                <td class="col-name">
                  <div class="pd-barcode">{{ item.product_barcode }}</div>
                  <div class="pd-name">{{ item.product_name }}</div>
                </td>
                <td class="col-qty">
                  <span v-if="editIndex !== idx" @dblclick="startEditQty(idx)">{{ item.quantity }}x Rp {{ item.price.toLocaleString() }}</span>
                  <input 
                    v-else-if="editType === 'qty'"
                    type="number" 
                    v-model.number="editValue" 
                    @blur="saveEdit" 
                    @keyup.enter="saveEdit" 
                    @keyup.esc="cancelEdit"
                    ref="editInput"
                    class="qty-edit-input"
                  />
                  <span v-else>{{ item.quantity }}x Rp {{ item.price.toLocaleString() }}</span>
                </td>
                <td class="col-disc">
                   <span v-if="editIndex !== idx" @dblclick="startEditDisc(idx)">{{ item.discount > 0 ? '-Rp ' + item.discount.toLocaleString() : '-' }}</span>
                   <input 
                    v-else-if="editType === 'disc'"
                    type="number" 
                    v-model.number="editValue" 
                    @blur="saveEdit" 
                    @keyup.enter="saveEdit" 
                    @keyup.esc="cancelEdit"
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
        </div>

        <div class="shortcuts-box">
          <div class="sh-header">Keyboard Shortcuts</div>
          <div class="sh-grid">
            <div class="sh-item"><kbd>F1</kbd> Cari/Barcode</div>
            <div class="sh-item"><kbd>TAB</kbd> Ke Keranjang</div>
            <div class="sh-item"><kbd>↕</kbd> Navigasi Item</div>
            <div class="sh-item"><kbd>F2</kbd> Ubah Qty</div>
            <div class="sh-item"><kbd>F3</kbd> Diskon Item</div>
            <div class="sh-item"><kbd>Ctrl+D</kbd> Diskon Total</div>
            <div class="sh-item"><kbd>DEL</kbd> Hapus Item</div>
            <div class="sh-item"><kbd>F9</kbd> Bayar Pas (Tunai)</div>
            <div class="sh-item"><kbd>F10</kbd> Bayar Custom</div>
            <div class="sh-item" style="grid-column: span 2;"><kbd>ESC</kbd> Batalkan Transaksi</div>
          </div>
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
const qtyInput = ref<HTMLInputElement[] | null>(null)

const products = ref<any[]>([])
const settings = ref<any>(null)

const searchQuery = ref('')
const searchSelectedIndex = ref(0)
const cart = ref<any[]>([])
const selectedRowIndex = ref(-1)

const editIndex = ref(-1)
const editType = ref<'qty' | 'disc'>('qty')
const editValue = ref(0)
const editInput = ref<HTMLInputElement[] | null>(null)

const showPaymentModal = ref(false)
const showReceipt = ref(false)
const lastOrder = ref<any>(null)
const totalDiscountAmount = ref(0)

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

  if (e.key === 'F1') {
    e.preventDefault()
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
  else if (e.key === 'F9') {
    e.preventDefault()
    if (cart.value.length > 0) {
      processPayment({ method: 'Cash', amount: totalAmount.value, change: 0 })
    }
  }
  else if (e.key === 'F10') {
    e.preventDefault()
    if (cart.value.length > 0) {
      showPaymentModal.value = true
    }
  }
  else if (e.key === 'Escape') {
    if (editIndex.value !== -1) {
      cancelEdit()
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
  else if (e.key === 'ArrowUp') {
    if (editIndex.value === -1) {
      e.preventDefault()
      if (selectedRowIndex.value > 0) {
        selectedRowIndex.value--
      }
    }
  }
  else if (e.key === 'ArrowDown') {
    if (editIndex.value === -1) {
      e.preventDefault()
      if (selectedRowIndex.value < cart.value.length - 1) {
        selectedRowIndex.value++
      }
    }
  }
  else if (e.key === 'Tab') {
    e.preventDefault()
    posWrapper.value?.focus()
    if (cart.value.length > 0 && selectedRowIndex.value === -1) {
        selectedRowIndex.value = 0
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
  if (searchQuery.value.length < 2) return []
  const q = searchQuery.value.toLowerCase()
  return products.value.filter(p => 
    p.name.toLowerCase().includes(q) || 
    p.barcode.toLowerCase().includes(q)
  ).slice(0, 10)
})

const moveSearchSelection = (dir: number) => {
  if (filteredProducts.value.length === 0) return
  searchSelectedIndex.value = (searchSelectedIndex.value + dir + filteredProducts.value.length) % filteredProducts.value.length
}

const addFromSearch = (product: any) => {
  addToCart(product)
  searchQuery.value = ''
  searchSelectedIndex.value = 0
  searchInput.value?.focus()
}

const handleBarcodeScan = () => {
  if (!searchQuery.value.trim()) return;

  // If there are search results and we press enter, add the selected one
  if (filteredProducts.value.length > 0) {
    addFromSearch(filteredProducts.value[searchSelectedIndex.value])
    return
  }

  const q = searchQuery.value.toLowerCase()
  const match = products.value.find(p => p.barcode === q || p.name.toLowerCase() === q)
  
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

const startEditQty = (idx: number) => {
  editIndex.value = idx
  editType.value = 'qty'
  editValue.value = cart.value[idx].quantity
  focusEditInput()
}

const startEditDisc = (idx: number) => {
  editIndex.value = idx
  editType.value = 'disc'
  editValue.value = cart.value[idx].discount
  focusEditInput()
}

const focusEditInput = () => {
  nextTick(() => {
    if (editInput.value && editInput.value[0]) {
        (editInput.value[0] as HTMLInputElement).focus();
        (editInput.value[0] as HTMLInputElement).select();
    }
  })
}

const saveEdit = () => {
  if (editIndex.value !== -1) {
    const item = cart.value[editIndex.value]
    if (editType.value === 'qty') {
      const product = products.value.find(p => p.id === item.product_id)
      const maxStock = product ? product.shelf_stock : 1
      
      if (editValue.value > maxStock) {
        alert('Stok tidak mencukupi! Maksimal stok di rak: ' + maxStock)
        item.quantity = maxStock
      } else {
        item.quantity = Math.max(1, editValue.value)
      }
    } else {
      item.discount = Math.max(0, editValue.value)
    }
    item.total = (item.price * item.quantity) - item.discount
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

.search-box input {
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
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 100;
  margin-top: 4px;
  overflow: hidden;
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
  gap: 16px;
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
  border-radius: 8px;
  padding: 20px;
}

.summary-header {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px dashed #cbd5e1;
}

.summary-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.sc-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-group label {
  color: #475569;
  font-size: 14px;
  font-weight: 600;
}

.customer-select {
  padding: 8px 12px;
  background-color: white;
  border: 1px solid #cbd5e1;
  color: #334155;
  border-radius: 6px;
  outline: none;
  font-weight: 500;
  min-width: 150px;
}

.totals-row {
  font-size: 15px;
  color: #475569;
  font-weight: 500;
}

.totals-row.text-orange {
  color: #f59e0b;
}

.big-total {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 2px dashed #e2e8f0;
  text-align: left;
}

.bt-label {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 8px;
}

.bt-value {
  font-size: 32px;
  font-weight: 800;
  color: #0ea5e9;
}

.shortcuts-box {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  flex: 1;
}

.sh-header {
  font-size: 14px;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sh-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.sh-item {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: #475569;
  font-weight: 500;
}

.sh-item kbd {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 24px;
  min-width: 24px;
  padding: 0 8px;
  margin-right: 12px;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-family: inherit;
  font-size: 12px;
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
