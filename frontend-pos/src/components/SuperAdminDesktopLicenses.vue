<template>
  <div class="desktop-licenses">
    <div class="section-header">
      <div>
        <h2 class="section-title">Manajemen Lisensi Desktop</h2>
        <p class="section-subtitle">Kelola serial key dan aktivasi perangkat untuk aplikasi POS Desktop Offline.</p>
      </div>
      <button class="btn-primary" @click="openModal()">+ Generate Serial Key</button>
    </div>

    <div v-if="loading" class="loading-state">Memuat data lisensi...</div>
    <div v-else-if="licenses.length === 0" class="empty-state">
      <div class="empty-icon">🔑</div>
      <p>Belum ada lisensi desktop yang dibuat.</p>
    </div>
    <div v-else class="license-table-wrapper">
      <table class="license-table">
        <thead>
          <tr>
            <th>Serial Key</th>
            <th>Pemegang Lisensi</th>
            <th>Status</th>
            <th>Device ID</th>
            <th>Masa Berlaku</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lic in licenses" :key="lic.id" :class="{ inactive: !lic.is_active }">
            <td data-label="Serial Key">
              <code class="serial-key">{{ lic.serial_key }}</code>
              <button class="btn-copy" @click="copyToClipboard(lic.serial_key)" title="Salin Serial Key">📋</button>
            </td>
            <td data-label="Pemegang Lisensi">{{ lic.licensee_name }}</td>
            <td data-label="Status">
              <span :class="['status-badge', getStatusClass(lic)]">
                {{ getStatusLabel(lic) }}
              </span>
            </td>
            <td data-label="Device ID">
              <span v-if="lic.device_id" class="device-id" :title="lic.device_id">
                {{ lic.device_id.substring(0, 8) }}...
              </span>
              <span v-else class="not-activated">Belum Aktivasi</span>
            </td>
            <td data-label="Masa Berlaku">
              {{ lic.expiry_date ? formatDate(lic.expiry_date) : 'Lifetime' }}
            </td>
            <td data-label="Aksi">
              <div class="action-buttons">
                <button class="btn-icon" @click="shareToWhatsApp(lic)" title="Kirim via WhatsApp">📱</button>
                <button class="btn-icon" @click="openModal(lic)" title="Edit">✏️</button>
                <button class="btn-icon" @click="toggleActive(lic)" :title="lic.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                  {{ lic.is_active ? '🚫' : '✅' }}
                </button>
                <button v-if="lic.device_id" class="btn-icon" @click="resetDevice(lic)" title="Reset Device Lock">🔄</button>
                <button class="btn-icon delete" @click="deleteLicense(lic.id)" title="Hapus">🗑️</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Create/Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editingId ? 'Edit Lisensi' : 'Tambah Lisensi Baru' }}</h3>
          <button class="modal-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="mf-group">
            <label>Serial Key</label>
            <div class="input-with-action">
              <input v-model="form.serial_key" placeholder="Contoh: NESSA-XXXX-XXXX-XXXX" readonly style="font-family: monospace; font-weight: 700; color: #38bdf8;" />
              <button v-if="!editingId" class="btn-secondary" @click="generateRandomKey" title="Buat Key Baru">🔀 Random</button>
              <button class="btn-secondary btn-copy-modal" @click="copyKey" title="Salin Serial Key">
                {{ copied ? '✅ Disalin!' : '📋 Salin' }}
              </button>
            </div>
          </div>
          <div class="mf-group">
            <label>Nama Pemegang Lisensi</label>
            <input v-model="form.licensee_name" placeholder="Contoh: Toko Berkah" />
          </div>
          <div class="mf-group">
            <label>Masa Berlaku</label>
            <select v-model="duration" @change="updateExpiryDate">
              <option value="1month">1 Bulan</option>
              <option value="1year">1 Tahun</option>
              <option value="lifetime">Lifetime (Selamanya)</option>
              <option value="custom">Custom (Pilih Tanggal)</option>
            </select>
          </div>
          <div class="mf-group" v-if="duration === 'custom' || form.expiry_date">
            <label>Tanggal Kedaluwarsa</label>
            <input v-model="form.expiry_date" type="date" />
          </div>
          <div v-if="formError" class="mf-error">{{ formError }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closeModal">Batal</button>
          <button class="btn-save" @click="saveLicense" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan Lisensi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../api'

const licenses = ref<any[]>([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const copied = ref(false)
const editingId = ref<number | null>(null)
const formError = ref('')

const form = ref({
  serial_key: '',
  licensee_name: '',
  expiry_date: ''
})

const duration = ref('1month')

const updateExpiryDate = () => {
  const now = new Date()
  if (duration.value === '1month') {
    const d = new Date()
    d.setMonth(d.getMonth() + 1)
    form.value.expiry_date = d.toISOString().substring(0, 10)
  } else if (duration.value === '1year') {
    const d = new Date()
    d.setFullYear(d.getFullYear() + 1)
    form.value.expiry_date = d.toISOString().substring(0, 10)
  } else if (duration.value === 'lifetime') {
    form.value.expiry_date = ''
  }
}

const fetchLicenses = async () => {
  loading.value = true
  try {
    const res = await api.get('/superadmin/desktop-licenses')
    licenses.value = res.data
  } catch (e: any) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchLicenses)

const openModal = (lic: any = null) => {
  if (lic) {
    editingId.value = lic.id
    form.value = {
      serial_key: lic.serial_key,
      licensee_name: lic.licensee_name,
      expiry_date: lic.expiry_date ? lic.expiry_date.substring(0, 10) : ''
    }
    duration.value = lic.expiry_date ? 'custom' : 'lifetime'
  } else {
    editingId.value = null
    form.value = { serial_key: '', licensee_name: '', expiry_date: '' }
    duration.value = '1month'
    updateExpiryDate()
    generateRandomKey()
  }
  formError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const generateRandomKey = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  const segment = () => {
    let s = ''
    for (let i = 0; i < 4; i++) s += chars.charAt(Math.floor(Math.random() * chars.length))
    return s
  }
  form.value.serial_key = `NESSA-${segment()}-${segment()}-${segment()}`
}

const saveLicense = async () => {
  if (!form.value.serial_key || !form.value.licensee_name) {
    formError.value = 'Serial key dan nama pemegang wajib diisi.'
    return
  }

  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/superadmin/desktop-licenses/${editingId.value}`, form.value)
    } else {
      await api.post('/superadmin/desktop-licenses', form.value)
    }
    closeModal()
    fetchLicenses()
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'Gagal menyimpan lisensi.'
  } finally {
    saving.value = false
  }
}

const toggleActive = async (lic: any) => {
  try {
    await api.put(`/superadmin/desktop-licenses/${lic.id}`, { is_active: !lic.is_active })
    fetchLicenses()
  } catch (e) {}
}

const resetDevice = async (lic: any) => {
  if (!confirm('Yakin ingin mereset kunci perangkat? Lisensi bisa digunakan di komputer lain.')) return
  try {
    await api.put(`/superadmin/desktop-licenses/${lic.id}`, { reset_device: true })
    fetchLicenses()
  } catch (e) {}
}

const deleteLicense = async (id: number) => {
  if (!confirm('Hapus lisensi ini secara permanen?')) return
  try {
    await api.delete(`/superadmin/desktop-licenses/${id}`)
    fetchLicenses()
  } catch (e) {}
}

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  alert('Serial Key disalin!')
}

const copyKey = () => {
  if (!form.value.serial_key) return
  navigator.clipboard.writeText(form.value.serial_key)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

const shareToWhatsApp = (lic: any) => {
  const expiry = lic.expiry_date ? formatDate(lic.expiry_date) : 'Selamanya (Lifetime)'
  const message = `Halo ${lic.licensee_name || 'Pelanggan'},\n\nBerikut adalah Serial Key NessaPOS Desktop Anda:\n\n🔑 Serial Key: *${lic.serial_key}*\n📅 Masa Berlaku: *${expiry}*\n\nSilakan salin dan tempelkan key tersebut pada menu aktivasi di aplikasi desktop.\n\nTerima kasih telah menggunakan NessaPOS!`
  
  const encoded = encodeURIComponent(message)
  window.open(`https://wa.me/?text=${encoded}`, '_blank')
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const getStatusClass = (lic: any) => {
  if (!lic.is_active) return 'inactive'
  if (lic.expiry_date && new Date(lic.expiry_date) < new Date()) return 'expired'
  if (lic.device_id) return 'active'
  return 'available'
}

const getStatusLabel = (lic: any) => {
  if (!lic.is_active) return 'Nonaktif'
  if (lic.expiry_date && new Date(lic.expiry_date) < new Date()) return 'Expired'
  if (lic.device_id) return 'Teraktivasi'
  return 'Tersedia'
}
</script>

<style scoped>
.desktop-licenses {
  color: white;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.section-title { margin: 0; font-size: 20px; font-weight: 800; }
.section-subtitle { margin: 4px 0 0; font-size: 13px; color: #94a3b8; }

.btn-primary {
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.license-table-wrapper {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  overflow: hidden;
}

.license-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.license-table th {
  text-align: left;
  padding: 14px 20px;
  background: rgba(255, 255, 255, 0.05);
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.05em;
}

.license-table td {
  padding: 14px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.serial-key {
  font-family: monospace;
  background: rgba(0,0,0,0.3);
  padding: 4px 8px;
  border-radius: 4px;
  color: #38bdf8;
  font-weight: 700;
  margin-right: 8px;
}

.btn-copy {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  opacity: 0.6;
}
.btn-copy:hover { opacity: 1; }

.status-badge {
  font-size: 11px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 999px;
  text-transform: uppercase;
}
.status-badge.active { background: rgba(52, 211, 153, 0.15); color: #34d399; }
.status-badge.available { background: rgba(14, 165, 233, 0.15); color: #38bdf8; }
.status-badge.inactive { background: rgba(239, 68, 68, 0.15); color: #fca5a5; }
.status-badge.expired { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

.device-id { color: #94a3b8; font-size: 12px; }
.not-activated { color: #64748b; font-style: italic; font-size: 12px; }

.action-buttons { display: flex; gap: 8px; }
.btn-icon {
  background: rgba(255,255,255,0.05);
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-icon:hover { background: rgba(255,255,255,0.15); }
.btn-icon.delete:hover { background: rgba(239, 68, 68, 0.2); }

/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal {
  background: #1e293b;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
  width: 100%;
  max-width: 440px;
  padding: 24px;
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-header h3 { margin: 0; font-size: 18px; }
.modal-close { background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; }

.mf-group { margin-bottom: 16px; }
.mf-group label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
.mf-group input {
  width: 100%;
  padding: 10px 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: white;
}
.mf-group select {
  width: 100%;
  padding: 10px 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: white;
  appearance: none;
}
.mf-group select option {
  background: #1e293b;
}
.input-with-action { display: flex; gap: 8px; }
.btn-secondary { background: rgba(255,255,255,0.1); border: none; color: white; padding: 0 12px; border-radius: 8px; cursor: pointer; white-space: nowrap; }
.btn-copy-modal { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; font-size: 13px; padding: 0 14px; transition: all 0.2s; }
.btn-copy-modal:hover { background: rgba(34, 197, 94, 0.25); }

.modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
.btn-cancel { background: none; border: none; color: #94a3b8; cursor: pointer; font-weight: 600; }
.btn-save { background: #0ea5e9; border: none; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }

.mf-error { color: #fca5a5; font-size: 12px; margin-top: 8px; }

@media (max-width: 760px) {
  .section-header {
    align-items: stretch;
    flex-direction: column;
    gap: 14px;
  }

  .section-title {
    font-size: 18px;
  }

  .btn-primary {
    width: 100%;
    min-height: 42px;
  }

  .license-table-wrapper {
    background: transparent;
    border: none;
    overflow: visible;
  }

  .license-table,
  .license-table tbody,
  .license-table tr,
  .license-table td {
    display: block;
    width: 100%;
  }

  .license-table thead {
    display: none;
  }

  .license-table tr {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    margin-bottom: 12px;
    padding: 12px;
  }

  .license-table td {
    align-items: flex-start;
    border-bottom: none;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    padding: 8px 0;
  }

  .license-table td::before {
    color: #94a3b8;
    content: attr(data-label);
    flex: 0 0 108px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
  }

  .serial-key {
    display: inline-block;
    margin: 0 4px 6px 0;
    max-width: 100%;
    overflow-wrap: anywhere;
  }

  .action-buttons {
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .modal-overlay {
    align-items: flex-end;
    padding: 12px;
  }

  .modal {
    max-height: calc(100vh - 24px);
    overflow-y: auto;
    padding: 18px;
  }

  .input-with-action,
  .modal-footer {
    flex-direction: column;
  }

  .btn-secondary,
  .btn-cancel,
  .btn-save {
    min-height: 40px;
    width: 100%;
  }
}
</style>
