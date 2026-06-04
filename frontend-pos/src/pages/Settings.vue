<template>
  <div class="page-container" v-if="auth?.role === 'admin' || auth?.role === 'kasir'">
    <ReceiptPreview 
      v-if="showTestReceipt" 
      :order="dummyOrder" 
      :settings="form"
      @close="showTestReceipt = false"
    />

    <div class="header">
      <h2>Pusat Pengaturan Sistem</h2>
      <p>Konfigurasi identitas toko, manajemen akses pengguna, dan data master.</p>
    </div>

    <div class="settings-layout">
      <!-- Sidebar Menu -->
      <aside class="sidebar">
        <div class="menu-section">
          <div class="menu-header">
            <span class="icon">&#9881;</span>
            <span>General</span>
          </div>
          <div class="menu-items">
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'printer' }"
              @click="activeTab = 'printer'"
            >
              <span class="icon">🖨️</span>
              <span>Printer</span>
            </div>
            <div 
              class="menu-item" 
              v-if="auth?.role === 'admin'"
              :class="{ active: activeTab === 'store' }"
              @click="activeTab = 'store'"
            >
              Identitas Toko
            </div>
            <div 
              class="menu-item" 
              v-if="auth?.role === 'admin'"
              :class="{ active: activeTab === 'subscription' }"
              @click="activeTab = 'subscription'"
            >
              Langganan
            </div>
            <div 
              class="menu-item" 
              v-if="auth?.role === 'admin'"
              :class="{ active: activeTab === 'backup' }"
              @click="activeTab = 'backup'"
            >
              Cadangkan Data
            </div>
            <!-- Removed redundant Local Bridge menu -->
          </div>
        </div>

        <div class="menu-section" v-if="auth?.role === 'admin'">
          <div class="menu-header">
            <span class="icon">&#128101;</span>
            <span>Keamanan</span>
          </div>
          <div class="menu-items">
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'users' }"
              @click="activeTab = 'users'"
            >
              Daftar Pengguna
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="main-content">
        <!-- TAB: STORE SETTINGS -->
        <div v-if="activeTab === 'store'" class="tab-content">
          <div class="card settings-card">
            <div class="card-header">
              <h3>Identitas & Info Toko</h3>
              <p>Informasi ini akan muncul pada struk penjualan.</p>
            </div>

            <div v-if="successMsg" class="alert success">{{ successMsg }}</div>

            <div class="form-grid">
              <div class="form-group full">
                <label>Nama Toko</label>
                <input v-model="form.store_name" type="text" placeholder="Masukkan nama toko" />
              </div>

              <div class="form-group full">
                <label>Alamat Lengkap</label>
                <textarea v-model="form.store_address" rows="3" placeholder="Alamat fisik toko"></textarea>
              </div>

              <div class="form-group">
                <label>Nomor Telepon</label>
                <input v-model="form.store_phone" type="text" placeholder="e.g. 08123456789" />
              </div>

              <div class="form-group">
                <label>Pajak Layanan (%)</label>
                <input v-model.number="form.tax_rate" type="number" step="0.1" />
              </div>

              <div class="form-group">
                <label>Sistem Pajak</label>
                <select v-model="form.tax_type">
                  <option value="exclusive">Exclusive (Ditambah di akhir)</option>
                  <option value="inclusive">Inclusive (Sudah termasuk harga)</option>
                </select>
              </div>

              <div class="form-group">
                <label>Digit Harga (Desimal)</label>
                <select v-model.number="form.decimal_digits">
                  <option :value="0">0 Digit (Tanpa Desimal, e.g. Rp 50.000)</option>
                  <option :value="1">1 Digit (e.g. 50.000,5)</option>
                  <option :value="2">2 Digit (e.g. 50.000,50)</option>
                </select>
              </div>

              <div class="form-group full">
                <label>Logo Struk (Hitam Putih Disarankan)</label>
                <div class="logo-upload-wrapper">
                  <div class="logo-preview" v-if="logoPreview || form.receipt_logo">
                    <img :src="logoPreview || formatImageUrl(form.receipt_logo)" alt="Logo Struk" />
                    <button class="btn-remove-logo" @click="removeLogo">×</button>
                  </div>
                  <input type="file" @change="handleLogoChange" accept="image/*" />
                </div>
                <p class="field-hint">Logo akan muncul di bagian paling atas struk thermal Anda.</p>
              </div>

              <div class="form-group full border-top">
                <label>Teks Kaki Struk (Footer)</label>
                <textarea v-model="form.receipt_text" rows="2" placeholder="Pesan di bawah struk (e.g. Terima Kasih)"></textarea>
              </div>

              <div class="form-group full">
                <label>Nama Printer (Opsional)</label>
                <div class="input-with-button">
                  <input v-model="form.printer_name" type="text" placeholder="Kosongkan untuk menggunakan printer default" />
                  <button class="btn-secondary btn-inline" @click="testThermalPrint">Tes Print</button>
                </div>
              </div>

              <div class="form-group full">
                <label>Interval Refresh POS</label>
                <select v-model.number="form.refresh_interval_sec">
                  <option :value="0">Off</option>
                  <option :value="30">30 detik</option>
                  <option :value="60">60 detik</option>
                </select>
              </div>

              <div v-if="isWailsApp" class="form-group full">
                <label>URL API Online</label>
                <input v-model="desktopApiUrl" type="text" placeholder="https://example.com/api" />
                <p class="field-hint">Aplikasi desktop akan menggunakan URL ini untuk terhubung ke backend online yang sama dengan frontend web.</p>
              </div>

              <div class="form-group full">
                <label>Laci Kasir</label>
                <select v-model="form.cash_drawer_enabled">
                  <option :value="true">Aktif - wajib buka/tutup sesi</option>
                  <option :value="false">Nonaktif - transaksi tanpa sesi</option>
                </select>
              </div>

              <div class="form-group full">
                <label>Cetak Slip Sesi</label>
                <select v-model="form.print_session_slip">
                  <option :value="true">Aktif</option>
                  <option :value="false">Nonaktif</option>
                </select>
              </div>
            </div>

            <div class="card-footer">
              <button class="btn-primary btn-save" @click="saveSettings" :disabled="loading">
                <span v-if="loading">Menyimpan...</span>
                <span v-else>Simpan Perubahan</span>
              </button>
            </div>
          </div>
        </div>

        <!-- TAB: SUBSCRIPTION -->
        <div v-else-if="activeTab === 'subscription'" class="tab-content">
          <div class="card settings-card">
            <div class="card-header">
              <h3>Status Langganan</h3>
              <p>Informasi paket dan masa aktif toko Anda.</p>
            </div>
            
            <div class="subscription-status-banner">
              <div class="status-info">
                <span :class="['status-badge', 'status-' + (tenant?.subscription_status || 'unknown')]">
                   {{ subscriptionStatusLabel }}
                </span>
                <div class="status-text">
                   <h4>{{ planLabel }}</h4>
                   <p v-if="tenant?.subscription_plan !== 'lifetime'">
                     Aktif sampai: <b>{{ formatDate(tenant?.subscription_active_until || tenant?.trial_ends_at) }}</b>
                   </p>
                   <p v-else>Aktif Selamanya (Lifetime)</p>
                </div>
              </div>
            </div>

            <div class="plans-grid" v-if="packages.length > 0">
              <div 
                v-for="pkg in packages" 
                :key="pkg.slug"
                class="plan-card" 
                :class="[pkg.style?.card_class || '', { active: tenant?.subscription_plan === pkg.slug }]" 
                @click="choosePlan(pkg)"
              >
                <div class="popular-badge" v-if="pkg.style?.badge">{{ pkg.style.badge }}</div>
                <div class="plan-header">
                  <span class="plan-title">{{ pkg.name }}</span>
                  <div class="price">
                    Rp {{ (pkg.price / 1000).toLocaleString('id-ID') }}rb
                    <span v-if="pkg.duration_days === 30">/bln</span>
                    <span v-else-if="pkg.duration_days === 365">/thn</span>
                    <span v-else>/kali</span>
                  </div>
                </div>
                <ul class="plan-features">
                  <li v-for="(feat, idx) in pkg.features" :key="idx">✅ {{ feat }}</li>
                </ul>
                <button v-if="tenant?.subscription_plan === pkg.slug" class="btn-selected" disabled>Paket Aktif</button>
                <button v-else :class="['btn-plan', pkg.style?.button || '']">Pilih Paket</button>
              </div>
            </div>
            <div v-else style="padding: 24px; text-align: center; color: #64748b; background: rgba(0,0,0,0.02); border-radius: 12px; margin-top: 16px;">
              Memuat daftar paket langganan...
            </div>
          </div>
        </div>

        <!-- TAB: BACKUP -->
        <div v-else-if="activeTab === 'backup'" class="tab-content">
          <div class="card settings-card">
            <div class="card-header">
              <h3>Pencadangan Data (Backup)</h3>
              <p>Amankan data transaksi Anda secara berkala.</p>
            </div>
            <div class="form-grid">
              <div class="form-group full">
                <label>Info Backup</label>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; width: 100%;">
                  <p style="margin: 0 0 10px 0; font-size: 13px; color: #475569;">Karena ini adalah aplikasi berbasis web, backup dilakukan langsung di sisi server.</p>
                  <p style="margin: 0; font-size: 13px; color: #475569;">Hubungi Super Admin untuk mengunduh backup database.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: PRINTER SETTINGS -->
        <div v-else-if="activeTab === 'printer'" class="tab-content">
          <div class="card settings-card">
            <div class="card-header">
              <h3>Konfigurasi Printer Thermal</h3>
              <p>Pilih metode pencetakan yang sesuai dengan perangkat yang Anda gunakan.</p>
            </div>
            
            <div class="form-grid">
              <div class="form-group full">
                <label>Metode Pencetakan</label>
                <select v-model="printerConfig.method" @change="savePrinterConfig" class="modern-select">
                  <option value="browser">Browser Print (Pop-up)</option>
                  <option value="wails">Aplikasi Desktop (Wails/Bridge)</option>
                  <option value="rawbt">HP Android (Via RawBT App)</option>
                </select>
                <div class="field-hint" v-if="printerConfig.method === 'rawbt'" style="margin-top: 10px; padding: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: 13px; color: #92400e;">
                  💡 <b>Cara Pakai di HP:</b> Instal aplikasi <b>RawBT</b> di PlayStore. Pastikan printer bluetooth sudah terhubung ke HP.
                </div>
                <div class="field-hint" v-if="printerConfig.method === 'wails'" style="margin-top: 10px; padding: 10px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; font-size: 13px; color: #075985;">
                  💡 <b>Cara Pakai di PC:</b> Gunakan aplikasi desktop <b>NessaPOS</b> atau jalankan Bridge Service di PC Kasir.
                </div>
              </div>

              <div class="form-group full" v-if="printerConfig.method === 'wails'">
                <label>Port Bridge (Default: 12348)</label>
                <input type="number" v-model.number="printerConfig.port" @change="savePrinterConfig" />
                <p style="font-size: 11px; color: #94a3b8; margin-top: 4px;">*Jika port diubah, aplikasi Desktop harus direstart agar sinkron.</p>
              </div>

              <div class="form-group full" v-if="printerConfig.method === 'wails'">
                <label>Bridge Token (Keamanan)</label>
                <div class="input-with-button">
                  <input 
                    v-model="form.bridge_token" 
                    type="text" 
                    :readonly="!isWailsApp"
                    :placeholder="isWailsApp ? 'Tempel token dari Web di sini' : 'Klik Baru untuk generate'"
                    :style="!isWailsApp ? 'background:#f1f5f9; cursor:not-allowed;' : ''" 
                  />
                  <button v-if="!isWailsApp" class="btn-secondary btn-inline" @click="copyToken">Salin</button>
                  <button v-if="!isWailsApp" class="btn-inline" style="background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; border-radius:8px; font-weight:700;" @click="regenerateToken">🔄 Baru</button>
                </div>
                <div class="field-hint" style="margin-top: 5px; font-size: 11px; color: #64748b;">
                  {{ isWailsApp ? 'Tempel token yang Anda buat dari browser web ke sini agar diizinkan mencetak.' : 'Salin token ini dan tempelkan ke aplikasi Desktop Anda.' }}
                </div>
              </div>

              <div class="form-group full">
                <label>Ukuran Kertas</label>
                <select v-model="printerConfig.width" @change="savePrinterConfig" class="modern-select">
                  <option value="58mm">58mm (Standar Thermal)</option>
                  <option value="80mm">80mm (Besar/Autocut)</option>
                </select>
              </div>
              
              <div class="form-group full" style="border-top: 1px solid #f1f5f9; padding-top: 20px;">
                  <label>Status Koneksi</label>
                  <div class="status-indicator-row" :style="printerStatus ? 'background: #ecfdf5; border-color: #10b981;' : ''">
                    <span :class="['status-dot', printerStatus ? 'online' : 'offline']"></span>
                    <span class="status-text">
                      {{ printerStatus ? 'Siap Mencetak' : 'Printer/Bridge Tidak Terdeteksi' }}
                    </span>
                    <button class="btn-sm" style="margin-left: auto; font-size: 11px;" @click="checkPrinterStatus">Cek Ulang</button>
                  </div>
                  <p v-if="printerStatusMessage" class="bridge-status-detail">{{ printerStatusMessage }}</p>
                  <button class="btn-secondary" style="margin-top: 15px; width: 100%;" @click="testPrint">Tes Cetak Struk</button>
              </div>

              <div class="form-actions" style="margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <button class="btn-primary btn-save" style="width: 100%;" @click="saveSettings" :disabled="loading">
                  <span v-if="loading">Menyimpan...</span>
                  <span v-else>Simpan Pengaturan Printer</span>
                </button>
                <p v-if="successMsg" class="success-alert" style="margin-top: 10px; text-align: center;">{{ successMsg }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: USER MANAGEMENT -->
        <div v-else-if="activeTab === 'users'" class="tab-content">
          <div class="card table-card">
            <div class="card-header flex-between">
              <div>
                <h3>Pengaturan Akun</h3>
                <p>Kelola siapa saja yang bisa mengakses sistem ini.</p>
              </div>
              <button class="btn-success" @click="showUserModal = true">
                + User Baru
              </button>
            </div>

            <div class="table-wrapper">
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>USERNAME</th>
                    <th>HAK AKSES / ROLE</th>
                    <th class="text-right col-actions">AKSI <span class="aksi-icon">&#9881;</span></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in users" :key="user.id">
                    <td class="font-bold">{{ user.username }}</td>
                    <td>
                      <span :class="['role-pill', user.role.toLowerCase()]">
                        {{ user.role }}
                      </span>
                    </td>
                    <td class="text-right col-actions">
                      <div class="action-buttons">
                        <button class="btn-icon edit" @click="editUser(user)" title="Edit">
                          Edit
                        </button>
                        <button 
                          v-if="user.id !== auth?.id"
                          class="btn-icon delete" 
                          @click="deleteUserConfirm(user.id)" 
                          :disabled="user.username === 'admin'"
                          title="Hapus"
                        >
                          Hapus
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="users.length === 0">
                    <td colspan="3" class="no-data">Belum ada pengguna terdaftar.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- USER MODAL -->
    <div v-if="showUserModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>{{editingUserId ? 'Perbarui Akun' : 'Tambah Akun Baru' }}</h3>
          <button class="close-btn" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Username</label>
            <input v-model="userForm.username" type="text" placeholder="Username login" />
          </div>
          <div class="form-group">
            <label>Password {{editingUserId ? '(Kosongi jika tidak diubah)' : '' }}</label>
            <input v-model="userForm.password" type="password" placeholder="Password minimal 6 karakter" />
          </div>
          <div class="form-group">
            <label>Akses Level (Role)</label>
            <select v-model="userForm.role">
              <option value="admin">Administrator</option>
              <option value="kasir">Kasir Toko</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Batal</button>
          <button class="btn-primary" @click="saveUser">Simpan Akun</button>
        </div>
      </div>
    </div>
  </div>
  <div v-else class="forbidden">
    <div class="forbidden-card">
      <h1>Akses Ditolak</h1>
      <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman pengaturan sistem.</p>
    </div>
  </div>

</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import { useAuthStore } from '../store/auth'
import { TestPrint } from '../../wailsjs/go/api/API'

const authStore = useAuthStore()
const auth = computed(() => authStore.user || { role: '' })
const tenant = computed(() => authStore.tenant)
const activeTab = ref(authStore.user?.role === 'kasir' ? 'bridge' : 'store')
const loading = ref(false)
const successMsg = ref('')

// Store Form
const form = ref({
  store_name: '',
  store_address: '',
  store_phone: '',
  tax_rate: 10,
  tax_type: 'exclusive',
  receipt_text: '',
  printer_name: '',
  refresh_interval_sec: 0,
  print_session_slip: false,
  cash_drawer_enabled: true,
  bridge_token: '',
  bridge_port: 12348,
  allowed_origins: '*',
  receipt_logo: '',
  decimal_digits: 0
})

const logoFile = ref<File | null>(null)
const logoPreview = ref('')

const handleLogoChange = (e: any) => {
  const file = e.target.files[0]
  if (file) {
    logoFile.value = file
    logoPreview.value = URL.createObjectURL(file)
  }
}

const removeLogo = () => {
  logoFile.value = null
  logoPreview.value = ''
  form.value.receipt_logo = ''
}

const formatImageUrl = (path: string) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL || ''}/storage/${path}`
}

// User Management
const users = ref<any[]>([])
const showUserModal = ref(false)
const editingUserId = ref<number | null>(null)
const userForm = ref({
  username: '',
  password: '',
  role: 'kasir'
})

// Subscription labels
const subscriptionStatusLabel = computed(() => {
  const s = tenant.value?.subscription_status
  if (s === 'active') return 'Aktif'
  if (s === 'trial') return 'Trial'
  return 'Kedaluwarsa'
})

const planLabel = computed(() => {
  const p = tenant.value?.subscription_plan
  if (p === 'monthly') return 'Bulanan'
  if (p === 'yearly') return 'Tahunan'
  if (p === 'lifetime') return 'Seumur Hidup'
  return 'Trial (7 Hari)'
})

const formatDate = (dateStr: string | null | undefined) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const bridgeStatus = ref('offline')
const isWailsApp = computed(() => isWails())

const printerConfig = ref({
  method: localStorage.getItem('print_method') || 'browser',
  port: parseInt(localStorage.getItem('bridge_port') || '12348'),
  width: localStorage.getItem('printer_width') || '58mm'
})

const desktopApiUrl = ref(localStorage.getItem('api_url') || '')
const printerStatus = ref(false)
const printerStatusMessage = ref('')

const savePrinterConfig = () => {
  localStorage.setItem('print_method', printerConfig.value.method)
  localStorage.setItem('bridge_port', printerConfig.value.port.toString())
  localStorage.setItem('printer_width', printerConfig.value.width)
  
  // Also update bridge_token in localStorage from the form
  if (form.value.bridge_token) {
    localStorage.setItem('bridge_token', form.value.bridge_token)
  } else {
    localStorage.removeItem('bridge_token')
  }
  
  checkPrinterStatus()
}

const copyToken = () => {
  if (form.value.bridge_token) {
    navigator.clipboard.writeText(form.value.bridge_token)
    alert("Token berhasil disalin ke clipboard!")
  }
}

const regenerateToken = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
  let token = ''
  for (let i = 0; i < 16; i++) {
    token += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  form.value.bridge_token = token
  localStorage.setItem('bridge_token', token) // Update local immediately
  saveSettings() // Save to DB
}

const checkPrinterStatus = async () => {
  printerStatusMessage.value = ''

  if (printerConfig.value.method !== 'wails') {
    printerStatus.value = true // Browser/RawBT assume OK
    return
  }

  if (isWailsApp.value) {
    printerStatus.value = true
    printerStatusMessage.value = 'Aplikasi desktop mencetak langsung lewat backend Go lokal.'
    return
  }

  try {
    const bridge = await checkBridgeConnection()
    printerStatus.value = bridge.ok
    printerStatusMessage.value = bridge.ok
      ? 'Bridge lokal terhubung. Browser web siap mengirim perintah cetak ke aplikasi desktop.'
      : bridge.message || 'Bridge lokal belum merespons.'
  } catch {
    printerStatus.value = false
    printerStatusMessage.value = 'Bridge lokal belum merespons. Pastikan NessaPOS Desktop berjalan di PC kasir.'
  }
}

import ReceiptPreview from '../components/ReceiptPreview.vue'
import { printReceipt } from '../utils/printer'
import { checkBridgeConnection, isWails } from '../utils/bridge'

const showTestReceipt = ref(false)
const dummyOrder = ref({
  id: 'TEST-001',
  user_id: auth.value?.id,
  date: new Date().toLocaleString(),
  total_amount: 50000,
  tax_amount: 5000,
  discount: 0,
  final_amount: 55000,
  amount_paid: 100000,
  change_amount: 45000,
  items: [
    { product_name: 'Item Tes 1', price: 25000, quantity: 2, total: 50000 }
  ]
})

const testPrint = async () => {
  const method = localStorage.getItem('print_method') || 'browser'
  if (method === 'browser') {
    showTestReceipt.value = true
  } else {
    await printReceipt(dummyOrder.value)
  }
}

const testThermalPrint = async () => {
  try {
    if (!isWailsApp.value) {
      alert('Fitur ini hanya tersedia untuk aplikasi desktop Wails.')
      return
    }

    await TestPrint(form.value.printer_name || '')
    alert('Tes cetak thermal berhasil. Silakan periksa printer.')
  } catch (e: any) {
    console.error('Tes print thermal gagal:', e)
    alert('Tes cetak thermal gagal: ' + (e?.message || e))
  }
}

const checkBridgeStatus = async () => {
  try {
    const bridge = await checkBridgeConnection()
    bridgeStatus.value = bridge.ok ? 'online' : 'offline'
    if (!bridge.ok && bridge.message) {
      console.warn('[Bridge] Status check failed:', bridge.message)
    }
  } catch (e) {
    bridgeStatus.value = 'offline'
  }
}

const packages = ref<any[]>([])

const loadPackages = async () => {
  try {
    const res = await api.get('/packages')
    packages.value = res.data
  } catch(e) {
    console.error("Error load packages", e)
  }
}

onMounted(async () => {
  await loadTenantInfo()
  await loadSettings()
  await loadPackages()
  await checkBridgeStatus()
  await checkPrinterStatus()
  
  if (auth.value?.role === 'admin') {
    await loadUsers()
  }
})

const loadTenantInfo = async () => {
  try {
    const res = await api.get('/tenant/info')
    if (res.data) {
      // Update store and local storage with fresh tenant status
      localStorage.setItem('tenant', JSON.stringify(res.data))
      authStore.tenant = res.data
    }
  } catch(e) {
    console.error("Error refreshing tenant info", e)
  }
}

const loadSettings = async () => {
  try {
    const res = await api.get('/settings')
    if (res.data) {
      console.log("[Settings] Loaded data:", res.data)
      // Use Object.assign to keep default values if some fields are missing/null in res.data
      Object.assign(form.value, res.data)
      authStore.cashDrawerEnabled = form.value.cash_drawer_enabled !== false
      
      // Also sync to localStorage for the bridge utility
      if (res.data.bridge_token) localStorage.setItem('bridge_token', res.data.bridge_token)
      if (res.data.bridge_port) localStorage.setItem('bridge_port', res.data.bridge_port.toString())
    }
    desktopApiUrl.value = localStorage.getItem('api_url') || desktopApiUrl.value
  } catch(e) {
    console.error("Error loading settings", e)
  }
}

const loadUsers = async () => {
  try {
    const res = await api.get('/users')
    users.value = res.data || []
  } catch(e) {
    console.error("Error loading users", e)
  }
}

const saveSettings = async () => {
  successMsg.value = ''
  loading.value = true
  try {
    // Construct FormData for file upload support
    const fd = new FormData()
    Object.keys(form.value).forEach(key => {
      if (key === 'receipt_logo') return
      
      const val = (form.value as any)[key]
      if (val !== null && val !== undefined) {
        // Ensure numeric fields are sent as numbers/strings without extra whitespace
        // and Booleans are sent as 1 or 0
        if (typeof val === 'boolean') {
          fd.append(key, val ? '1' : '0')
        } else {
          fd.append(key, val.toString())
        }
      }
    })

    if (logoFile.value) {
      fd.append('receipt_logo', logoFile.value)
    } else if (form.value.receipt_logo === '') {
      // If logo was removed
      fd.append('receipt_logo', '')
    }

    await api.post('/settings', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    authStore.cashDrawerEnabled = form.value.cash_drawer_enabled !== false
    
    if (form.value.bridge_token) {
      localStorage.setItem('bridge_token', form.value.bridge_token)
    }
    if (form.value.bridge_port) {
      localStorage.setItem('bridge_port', form.value.bridge_port.toString())
    }
    if (desktopApiUrl.value) {
      localStorage.setItem('api_url', desktopApiUrl.value)
    } else {
      localStorage.removeItem('api_url')
    }
    
    if (isWailsApp.value) {
      try {
        const { UpdateSettings } = await import('../../wailsjs/go/api/API')
        if (UpdateSettings) {
          await UpdateSettings(form.value as any)
        }
      } catch (localErr) {
        console.warn("[Settings] Gagal update database lokal:", localErr)
      }
    }

    successMsg.value = 'Pengaturan berhasil disimpan!'
    await loadSettings()
    logoFile.value = null
    logoPreview.value = ''
    setTimeout(() => { successMsg.value = '' }, 3000)
  } catch(e: any) {
    let errorMsg = e.response?.data?.message || e.message
    if (e.response?.data?.errors) {
      const details = Object.values(e.response.data.errors).flat().join('\n')
      errorMsg += ':\n' + details
    }
    alert("Gagal menyimpan: " + errorMsg)
  } finally {
    loading.value = false
  }
}

const choosePlan = async (pkg: any) => {
  if (tenant.value?.subscription_plan === pkg.slug) return
  if (!confirm(`Apakah Anda ingin berlangganan paket ${pkg.name}?`)) return
  
  loading.value = true
  try {
    const res = await api.post('/subscription/renew', { 
      package_id: pkg.id,
      payment_method: 'WhatsApp' 
    })
    
    // Construct WA message
    const waNumber = tenant.value?.superadmin_phone || '6281392156513'
    const message = encodeURIComponent(
      `Halo Super Admin,\n\n` +
      `Saya ingin konfirmasi pembayaran untuk perpanjangan paket.\n` +
      `Store ID: ${tenant.value.slug}\n` +
      `Nama Toko: ${tenant.value.name}\n` +
      `Paket: ${pkg.name}\n` +
      `Harga: Rp ${pkg.price.toLocaleString('id-ID')}\n\n` +
      `Mohon bantuannya untuk aktivasi. Terima kasih!`
    )
    
    window.open(`https://wa.me/${waNumber}?text=${message}`, '_blank')
    
    alert("Permintaan berhasil diajukan. Silakan kirim pesan WhatsApp tersebut untuk konfirmasi pembayaran.")
    await loadTenantInfo()
  } catch (e: any) {
    alert("Gagal mengajukan paket: " + (e.response?.data?.message || e.message))
  } finally {
    loading.value = false
  }
}

const editUser = (user: any) => {
  editingUserId.value = user.id
  userForm.value = { username: user.username, password: '', role: user.role }
  showUserModal.value = true
}

const saveUser = async () => {
  if (!userForm.value.username) return alert("Username wajib diisi")
  try {
    if (editingUserId.value) {
      await api.put(`/users/${editingUserId.value}`, userForm.value)
    } else {
      await api.post('/users', userForm.value)
    }
    await loadUsers()
    closeModal()
  } catch(e) {
    alert("Gagal: " + e)
  }
}

const deleteUserConfirm = async (id: number) => {
  if (confirm("Hapus pengguna ini secara permanen?")) {
    try {
      await api.delete(`/users/${id}`)
      await loadUsers()
    } catch(e) { alert("Gagal: " + e) }
  }
}

const closeModal = () => {
  showUserModal.value = false
  editingUserId.value = null
  userForm.value = { username: '', password: '', role: 'kasir' }
}
</script>

<style scoped>
/* Base Styles */
.page-container {
  padding: 10px 15px;
  background: #f8fafc;
  height: 100vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.header {
  margin-bottom: 10px;
}
.header h2 {
  margin: 0;
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
}
.header p {
  color: #64748b;
  margin-top: 5px;
  font-size: 14px;
}

/* Layout */
.settings-layout {
  display: flex;
  gap: 20px;
  align-items: flex-start;
  flex: 1;
  min-height: 0;
}

/* Sidebar Style from Reports */
.sidebar {
  width: 240px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 10px;
  flex-shrink: 0;
  position: sticky;
  top: 20px;
}

.menu-section {
  margin-bottom: 8px;
}
.menu-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  font-weight: 800;
  color: #1e293b;
  font-size: 14px;
}
.menu-header .icon {
  font-size: 18px;
}

.menu-items {
  padding-left: 15px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.menu-item {
  padding: 10px 15px;
  border-radius: 8px;
  font-size: 14px;
  color: #64748b;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
.menu-item:hover {
  background: #f1f5f9;
  color: #0ea5e9;
}
.menu-item.active {
  background: #eff6ff;
  color: #0ea5e9;
}

/* Main Content */
.main-content {
  flex: 1;
  min-width: 0;
  height: 100%;
  overflow-y: auto;
  padding-right: 10px; /* Space for scrollbar */
}

.card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05);
  overflow: hidden;
}

.card-header {
  padding: 12px 20px;
  border-bottom: 1px solid #f1f5f9;
}
.card-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1e293b;
}
.card-header p {
  margin: 5px 0 0;
  font-size: 13px;
  color: #94a3b8;
}

/* Form Layout - Horizontal Alignment */
.settings-content {
  max-width: 900px;
}

.form-grid {
  padding: 10px 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  overflow-y: auto;
  max-height: calc(100vh - 160px);
}

.form-group {
  display: flex;
  align-items: center;
  gap: 15px;
}

.form-group label {
  width: 130px;
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0;
}

.main-content input, .main-content textarea, .main-content select {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid #f1f5f9;
  border-radius: 8px;
  font-size: 13px;
  color: #1e293b;
  font-weight: 600;
  outline: none;
  transition: all 0.2s;
  background: #f8fafc;
}

.input-with-button {
  display: flex;
  gap: 8px;
}

.input-with-button input {
  flex: 1;
}

.logo-upload-wrapper {
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.logo-preview {
  position: relative;
  width: 120px;
  height: 120px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.logo-preview img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.btn-remove-logo {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  border-radius: 99px;
  background: #ef4444;
  color: #white;
  border: 2px solid #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  font-size: 16px;
  line-height: 1;
}

.btn-remove-logo:hover {
  background: #dc2626;
}

.btn-inline {
  padding: 10px 16px;
  white-space: nowrap;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 12px;
  background: #f1f5f9;
  color: #475569;
}
.status-active { background: #dcfce7; color: #15803d; }
.status-ok { background: #dcfce7; color: #15803d; }
.status-trial { background: #e0f2fe; color: #0369a1; }
.status-expired { background: #fee2e2; color: #b91c1c; }
.status-invalid { background: #fee2e2; color: #b91c1c; }

.status-value {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}
.status-value.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 13px;
  background: #f1f5f9;
  padding: 4px 10px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.badge-trial {
  background: #f59e0b;
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 4px;
  margin-left: 8px;
  vertical-align: middle;
}
.device-id {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
}
.device-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.flow-list {
  margin: 0;
  padding-left: 18px;
  color: #475569;
  font-size: 13px;
  line-height: 1.5;
}
.flow-list li {
  margin: 4px 0;
}

.main-content input:focus, .main-content textarea:focus {
  border-color: #0ea5e9;
  background: white;
  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
}

.form-group.border-top {
  padding-top: 10px;
  margin-top: 5px;
  border-top: 1px dashed #e2e8f0;
}

.card-footer {
  padding: 10px 24px;
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #f1f5f9;
}
.license-actions {
  gap: 10px;
}

/* Buttons */
.btn-primary {
  background: #0ea5e9;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.1s, background 0.2s;
}
.btn-primary:hover { background: #0284c7; transform: translateY(-1px); }
.btn-primary:active { transform: translateY(0); }

.btn-success { background: #10b981; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; }
.btn-success:hover { background: #059669; }

.btn-secondary { background: #e2e8f0; color: #475569; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }

/* Table Style */
.table-wrapper {
  overflow-x: auto;
}
.modern-table {
  width: 100%;
  border-collapse: collapse;
}
.modern-table th {
  text-align: left;
  padding: 15px 24px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
  background: #f8fafc;
  font-weight: 800;
}
.col-actions {
  width: 190px;
  min-width: 190px;
}
.modern-table td {
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  color: #1e293b;
}

.role-pill {
  font-size: 11px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 20px;
  text-transform: uppercase;
}
.role-pill.admin { background: #fee2e2; color: #b91c1c; }
.role-pill.kasir { background: #dcfce7; color: #15803d; }

.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  align-items: center;
  width: 100%;
}
.btn-icon {
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  min-width: 88px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.btn-icon.edit { background: #eff6ff; color: #1d4ed8; }
.btn-icon.delete { background: #fff1f2; color: #be123c; }

.aksi-icon {
  font-size: 12px;
  margin-left: 6px;
  vertical-align: middle;
}

/* Modal */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: white;
  width: 100%;
  max-width: 450px;
  border-radius: 20px;
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
  overflow: hidden;
}
.modal-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 18px; font-weight: 800; }
.close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; }
.modal-body { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
.modal-footer { padding: 20px 24px; background: #f8fafc; display: flex; justify-content: flex-end; gap: 12px; }

/* Helpers */
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.text-right { text-align: right; }
.font-bold { font-weight: 700; }
.alert { padding: 12px 18px; border-radius: 10px; margin: 0 24px 24px; font-weight: 600; font-size: 14px; }
.alert.success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.no-data { text-align: center; color: #94a3b8; padding: 40px !important; }

/* Forbidden Page */
.forbidden {
  display: flex; align-items: center; justify-content: center; min-height: 80vh;
}
.forbidden-card { text-align: center; }
.forbidden-card h1 { font-size: 40px; color: #ef4444; }

/* Subscription Styles */
.subscription-status-banner {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 24px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  margin-bottom: 30px;
}
.status-info { display: flex; align-items: center; gap: 20px; }
.status-badge {
  padding: 8px 16px;
  border-radius: 99px;
  font-weight: 800;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.status-active { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.status-trial { background: #fff7ed; color: #9a3412; border: 1px solid #fbbf24; }
.status-expired { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

.status-text h4 { margin: 0; font-size: 18px; color: #0f172a; }
.status-text p { margin: 4px 0 0; font-size: 14px; color: #64748b; }

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-top: 20px;
}
.plan-card {
  background: white;
  border: 2px solid #f1f5f9;
  border-radius: 20px;
  padding: 24px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  position: relative;
  cursor: pointer;
}
.plan-card:hover { border-color: #0ea5e9; transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.plan-card.active { border-color: #0ea5e9; background: #f0f9ff; }
.plan-card.featured { border-color: #6366f1; }
.plan-card.featured .btn-plan { background: #6366f1; }

.popular-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: #6366f1;
  color: white;
  font-size: 10px;
  font-weight: 900;
  padding: 4px 12px;
  border-radius: 99px;
  text-transform: uppercase;
}

.plan-title { font-size: 14px; font-weight: 800; color: #64748b; text-transform: uppercase; }
.price { font-size: 24px; font-weight: 900; color: #0f172a; margin-top: 8px; }
.price span { font-size: 14px; color: #94a3b8; font-weight: 600; }

.plan-features {
  list-style: none;
  padding: 0;
  margin: 20px 0;
  flex-grow: 1;
}
.plan-features li {
  font-size: 13px;
  color: #475569;
  margin-bottom: 10px;
  font-weight: 500;
}

.btn-plan {
  width: 100%;
  padding: 12px;
  background: #0ea5e9;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.2s;
}
.btn-plan:hover { opacity: 0.9; }
.plan-card.active .btn-plan { background: #10b981; }

@media (max-width: 768px) {
  .status-info { flex-direction: column; text-align: center; }
}
.btn-sm {
  padding: 4px 10px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  cursor: pointer;
}
.btn-sm:hover { background: #f8fafc; }

.status-indicator-row {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f1f5f9;
  padding: 10px 15px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}
.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
.status-dot.online { background: #10b981; box-shadow: 0 0 8px rgba(16,185,129,0.5); }
.status-dot.offline { background: #ef4444; }
.status-text { font-size: 13px; font-weight: 600; color: #475569; }
.bridge-status-detail {
  margin: 8px 0 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.5;
}
/* Mobile Responsive for Settings */
@media (max-width: 768px) {
  .page-container {
    padding: 10px;
    height: auto;
    overflow: auto;
  }
  
  .settings-layout {
    flex-direction: column;
    gap: 15px;
  }
  
  .sidebar {
    width: 100%;
    position: relative;
    top: 0;
    padding: 5px;
    display: flex;
    flex-direction: column;
  }
  
  .menu-section {
    margin-bottom: 5px;
  }
  
  .menu-items {
    display: flex;
    flex-direction: row;
    overflow-x: auto;
    padding: 5px;
    gap: 10px;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
  }
  
  .menu-item {
    padding: 8px 15px;
    background: #f1f5f9;
    flex-shrink: 0;
  }
  
  .menu-header {
    padding: 8px 12px;
  }

  .form-group {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }

  .form-group label {
    width: 100%;
  }

  .main-content {
    padding-right: 0;
  }
  
  .form-grid {
    max-height: none;
    overflow: visible;
  }
}
</style>
