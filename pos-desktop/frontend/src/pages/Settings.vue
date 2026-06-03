<template>
  <div class="page-container" v-if="auth.role === 'admin'">
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
              :class="{ active: activeTab === 'store' }"
              @click="activeTab = 'store'"
            >
              Identitas Toko
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'license' }"
              @click="activeTab = 'license'"
            >
              Lisensi
            </div>
            <div 
              class="menu-item" 
              :class="{ active: activeTab === 'backup' }"
              @click="activeTab = 'backup'"
            >
              Cadangkan Data
            </div>
          </div>
        </div>

        <div class="menu-section">
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

              <div class="form-group full border-top">
                <label>Teks Kaki Struk (Footer)</label>
                <textarea v-model="form.receipt_text" rows="2" placeholder="Pesan di bawah struk (e.g. Terima Kasih)"></textarea>
              </div>

              <div class="form-group full">
                <label>Printer Thermal</label>
                <div class="input-with-button">
                  <input v-model="form.printer_name" type="text" placeholder="Kosongkan untuk auto-detect/default printer" />
                  <button class="btn-secondary btn-inline" @click="loadPrinters" :disabled="loadingPrinters">
                    <span v-if="loadingPrinters">Mendeteksi...</span>
                    <span v-else>Deteksi</span>
                  </button>
                  <button class="btn-primary btn-inline" @click="testPrint" :disabled="testingPrinter">
                    <span v-if="testingPrinter">Mencoba...</span>
                    <span v-else>Test Print</span>
                  </button>
                </div>
              </div>

              <div v-if="printers.length > 0" class="form-group full">
                <label>Pilih Printer</label>
                <select v-model="form.printer_name">
                  <option value="">Auto-Detect / Default</option>
                  <option v-for="p in printers" :key="p" :value="p">{{ p }}</option>
                </select>
              </div>

              <div class="form-group full">
                <label>Interval Refresh POS</label>
                <select v-model.number="form.refresh_interval_sec">
                  <option :value="0">Off</option>
                  <option :value="30">30 detik</option>
                  <option :value="60">60 detik</option>
                </select>
              </div>

              <div class="form-group full">
                <label>Cash Drawer</label>
                <select v-model="form.cash_drawer_enabled">
                  <option :value="true">On - kirim perintah buka laci</option>
                  <option :value="false">Off - tanpa perintah buka laci</option>
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

        <!-- TAB: LICENSE -->
        <div v-else-if="activeTab === 'license'" class="tab-content">
          <div class="card settings-card">
            <div class="card-header">
              <h3>Lisensi Aplikasi</h3>
              <p>Aktifkan lisensi untuk penggunaan penuh di perangkat ini.</p>
            </div>

            <div class="form-grid">
              <div class="form-group full">
                <label>Alur Aktivasi</label>
                <ol class="flow-list">
                  <li>Masukkan <b>License Key</b> (XXXX-XXXX-XXXX-XXXX) yang Anda beli.</li>
                  <li>Pastikan perangkat terhubung ke internet.</li>
                  <li>Klik <b>Aktifkan Online</b> untuk aktivasi instan.</li>
                </ol>
              </div>

              <div class="form-group full">
                <label>Status Lisensi</label>
                <div class="status-pill" :class="`status-${licenseStatus?.status || 'unknown'}`">
                  {{ licenseStatusLabel }}
                </div>
              </div>

              <div class="form-group full" v-if="licenseStatus?.status === 'ok' || licenseStatus?.status === 'trial'">
                <label>Masa Aktif</label>
                <div class="status-value">
                  <template v-if="licenseStatus.status === 'trial'">
                    <b>{{ licenseStatus.trial_days_left }} hari</b> (sampai {{ licenseStatus.trial_ends_at }}) <span class="badge-trial">TRIAL</span>
                  </template>
                  <template v-else>
                    <div v-if="isPermanen(licenseStatus.license_expiry)">
                      <b>Permanen (Tanpa Batas Waktu)</b>
                    </div>
                    <div v-else>
                      <b>{{ licenseStatus.license_expiry }}</b>
                      <span v-if="getDaysLeft(licenseStatus.license_expiry) <= 7" class="badge-trial">TRIAL</span>
                    </div>
                  </template>
                </div>
              </div>

              <div class="form-group full" v-if="licenseStatus?.licensee">
                <label>Pemilik Lisensi</label>
                <div class="status-value">{{ licenseStatus.licensee || 'Pengguna Lokal' }}</div>
              </div>

              <div class="form-group full" v-if="licenseStatus?.device_id">
                <label>Device ID</label>
                <div class="device-row">
                  <div class="device-id">{{ licenseStatus.device_id }}</div>
                  <button class="btn-secondary btn-inline" @click="copyDeviceId" :disabled="!licenseStatus?.device_id">
                    Copy Device ID
                  </button>
                </div>
              </div>

              <div class="form-group full" v-if="licenseStatus?.reason">
                <label>Alasan</label>
                <div>{{ licenseStatus.reason }}</div>
              </div>

              <div class="form-group full">
                <label>License Key</label>
                <div class="input-with-button">
                  <input v-model="licenseKey" type="text" placeholder="ABCD-1234-EFGH-5678" />
                  <button class="btn-primary btn-inline" @click="activateOnline" :disabled="activatingOnline || !licenseKey">
                    {{ activatingOnline ? 'Aktivasi...' : 'Aktifkan Online' }}
                  </button>
                </div>
              </div>

              <div class="form-group full border-top" style="margin-top: 20px;">
                <label>Aktivasi Manual</label>
                <p style="font-size: 11px; color: #64748b; margin-bottom: 8px;">Gunakan ini jika perangkat tidak memiliki akses internet.</p>
                <textarea v-model="licenseText" rows="3" placeholder="Paste license JSON manual di sini"></textarea>
              </div>
            </div>

            <div class="card-footer license-actions">
              <button class="btn-secondary" @click="loadLicenseStatus" :disabled="loadingLicense">
                {{ loadingLicense ? 'Memuat...' : 'Refresh Status' }}
              </button>
              <button class="btn-secondary" @click="activateFromSettings" :disabled="activatingLicense || !licenseText">
                {{ activatingLicense ? 'Aktifkan Manual' : 'Aktifkan Manual' }}
              </button>
            </div>
          </div>
        </div>

        <!-- TAB: BACKUP -->
        <div v-else-if="activeTab === 'backup'" class="tab-content">
          <div class="card settings-card">
            <div class="card-header">
              <h3>Pencadangan Data (Backup)</h3>
              <p>Amankan data transaksi Anda dengan membuat salinan database secara berkala.</p>
            </div>

            <div class="form-grid">
              <div class="form-group full">
                <label>Info Backup</label>
                <div class="info-box" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; width: 100%;">
                  <p style="margin: 0 0 10px 0; font-size: 13px; color: #475569;">Aplikasi melakukan <b>backup otomatis</b> setiap kali Anda menutup aplikasi.</p>
                  <p style="margin: 0 0 10px 0; font-size: 13px; color: #475569;">File backup disimpan di folder <code>/backups</code> di dalam folder instalasi aplikasi.</p>
                  <p style="margin: 0; font-size: 13px; color: #475569;">Sistem akan menyimpan maksimal 5 file backup terbaru dan menghapus yang lama secara otomatis.</p>
                </div>
              </div>

              <div class="form-group full border-top" style="margin-top: 20px; padding-top: 20px;">
                <label>Backup Manual</label>
                <div class="input-with-button">
                  <button class="btn-primary" @click="manualBackup" :disabled="backingUp" style="padding: 12px 30px;">
                    <span v-if="backingUp">Proses Mencadangkan...</span>
                    <span v-else>Klik Untuk Cadangkan Sekarang</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: USER MANAGEMENT -->
        <div v-else class="tab-content">
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
import { ref, onMounted, computed } from 'vue'
import { GetSettings, UpdateSettings, GetUsers, CreateUser, UpdateUser, DeleteUser, ListPrinters, GetLicenseStatus, ActivateLicense, ActivateOnline, TestPrint, PerformBackup } from '../../wailsjs/go/api/API'
import { useAuthStore } from '../store/auth'

const authStore = useAuthStore()
const auth = authStore.user
const activeTab = ref('store')
const loading = ref(false)
const successMsg = ref('')
const loadingPrinters = ref(false)
const printers = ref<string[]>([])
const loadingLicense = ref(false)
const licenseStatus = ref<any>(null)
const licenseText = ref('')
const licenseKey = ref('')
const activatingLicense = ref(false)
const activatingOnline = ref(false)
const testingPrinter = ref(false)
const backingUp = ref(false)

const licenseStatusLabel = computed(() => {
  const s = licenseStatus.value?.status
  if (!s) return '-'
  if (s === 'ok') return 'Aktif'
  if (s === 'trial') return 'Trial'
  if (s === 'expired') return 'Expired'
  if (s === 'invalid') return 'Invalid'
  if (s === 'tampered') return 'Terdeteksi manipulasi waktu'
  return 'Tidak aktif'
})

const isPermanen = (expiryStr: string) => {
  if (!expiryStr) return true
  try {
    const expiry = new Date(expiryStr)
    const limit = new Date()
    limit.setFullYear(limit.getFullYear() + 30)
    return expiry > limit
  } catch {
    return false
  }
}

const getDaysLeft = (expiryStr: string) => {
  if (!expiryStr) return 9999
  const expiry = new Date(expiryStr)
  const now = new Date()
  const diff = expiry.getTime() - now.getTime()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

// Store Form
const form = ref({
  id: 1,
  store_name: '',
  store_address: '',
  store_phone: '',
  tax_rate: 10,
  tax_type: 'exclusive',
  receipt_text: '',
  printer_name: '',
  refresh_interval_sec: 30,
  print_session_slip: true,
  cash_drawer_enabled: true
})

// User Management
const users = ref<any[]>([])
const showUserModal = ref(false)
const editingUserId = ref<number | null>(null)
const userForm = ref({
  username: '',
  password: '',
  role: 'kasir'
})

onMounted(async () => {
  if (auth?.role === 'admin') {
    await loadSettings()
    await loadUsers()
    await loadLicenseStatus()
  }
})

const loadSettings = async () => {
  try {
    const s = await GetSettings()
    if (s) {
      form.value = { ...s } as any
      authStore.cashDrawerEnabled = form.value.cash_drawer_enabled !== false
    }
  } catch(e) {
    console.error("Error loading settings", e)
  }
}

const loadUsers = async () => {
  try {
    const rawUsers = await GetUsers()
    users.value = rawUsers || []
  } catch(e) {
    console.error("Error loading users", e)
  }
}

const loadPrinters = async () => {
  loadingPrinters.value = true
  try {
    const names = await ListPrinters()
    printers.value = names || []
    if (printers.value.length === 1 && !form.value.printer_name) {
      form.value.printer_name = printers.value[0]
    }
  } catch (e) {
    console.error("Error loading printers", e)
    alert("Gagal mendeteksi printer.")
  } finally {
    loadingPrinters.value = false
  }
}

const saveSettings = async () => {
  successMsg.value = ''
  loading.value = true
  try {
    await UpdateSettings(form.value as any)
    authStore.cashDrawerEnabled = form.value.cash_drawer_enabled !== false
    successMsg.value = 'Pengaturan berhasil diperbarui!'
    setTimeout(() => successMsg.value = '', 3000)
  } catch(e) {
    alert("Gagal: " + e)
  } finally {
    loading.value = false
  }
}

const loadLicenseStatus = async () => {
  loadingLicense.value = true
  try {
    licenseStatus.value = await GetLicenseStatus()
  } catch (e) {
    console.error("Error loading license status", e)
  } finally {
    loadingLicense.value = false
  }
}

const copyDeviceId = async () => {
  if (!licenseStatus.value?.device_id) return
  try {
    await navigator.clipboard.writeText(licenseStatus.value.device_id)
    alert('Device ID berhasil disalin.')
  } catch {
    alert('Gagal menyalin Device ID.')
  }
}

const activateFromSettings = async () => {
  if (!licenseText.value) return
  activatingLicense.value = true
  try {
    await ActivateLicense(licenseText.value)
    await loadLicenseStatus()
    alert('Lisensi berhasil diaktifkan secara manual.')
    licenseText.value = ''
  } catch (e) {
    alert('Aktivasi gagal: ' + e)
  } finally {
    activatingLicense.value = false
  }
}

const activateOnline = async () => {
  if (!licenseKey.value) return
  activatingOnline.value = true
  try {
    await ActivateOnline(licenseKey.value)
    await loadLicenseStatus()
    alert('Aktivasi Online Berhasil! Lisensi Anda sekarang aktif.')
    licenseKey.value = ''
  } catch (e) {
    alert('Aktivasi Online Gagal: ' + e)
  } finally {
    activatingOnline.value = false
  }
}

const testPrint = async () => {
  testingPrinter.value = true
  try {
    await TestPrint(form.value.printer_name)
    alert("Test print dikirim ke printer. Silakan cek printer Anda.")
  } catch (e) {
    alert("Gagal mencetak: " + e)
  } finally {
    testingPrinter.value = false
  }
}

const manualBackup = async () => {
  backingUp.value = true
  try {
    await PerformBackup()
    alert("Backup data berhasil dibuat di folder 'backups'!")
  } catch (e) {
    alert("Gagal mencadangkan data: " + e)
  } finally {
    backingUp.value = false
  }
}


const editUser = (user: any) => {
  editingUserId.value = user.id
  userForm.value = {
    username: user.username,
    password: '',
    role: user.role
  }
  showUserModal.value = true
}

const saveUser = async () => {
  if (!userForm.value.username) return alert("Username wajib diisi")
  if (!editingUserId.value && !userForm.value.password) return alert("Password wajib diisi")

  try {
    if (editingUserId.value) {
      await UpdateUser({
        id: editingUserId.value,
        ...userForm.value
      } as any)
    } else {
      await CreateUser(userForm.value as any)
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
      await DeleteUser(id)
      await loadUsers()
    } catch(e) {
      alert("Gagal: " + e)
    }
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
  gap: 10px;
  width: 100%;
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
.status-ok { background: #dcfce7; color: #15803d; }
.status-trial { background: #e0f2fe; color: #0369a1; }
.status-expired { background: #fee2e2; color: #b91c1c; }
.status-invalid { background: #fee2e2; color: #b91c1c; }
.status-tampered { background: #fff7ed; color: #c2410c; }

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
</style>
