<template>
  <div class="sa-layout">
    <!-- Header -->
    <div class="sa-header">
      <div class="sa-header-left">
        <div class="sa-logo">⚡</div>
        <div>
          <h1 class="sa-title">NessaPOS <span class="saas-badge">SaaS</span></h1>
          <p class="sa-subtitle">Super Admin Panel — Manajemen Toko & Langganan</p>
        </div>
      </div>
      <div class="sa-header-right">
        <span class="sa-stat">
          <b>{{ tenants.length }}</b> Toko Terdaftar
        </span>
        <span class="sa-stat active">
          <b>{{ tenants.filter(t => t.subscription_status === 'active').length }}</b> Aktif
        </span>
        <button class="btn-new-tenant" @click="openNewModal">+ Toko Baru</button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="sa-tabs">
      <button :class="{ active: activeTab === 'tenants' }" @click="activeTab = 'tenants'">Manajemen Toko</button>
      <button :class="{ active: activeTab === 'packages' }" @click="activeTab = 'packages'">Harga & Rekomendasi</button>
      <button :class="{ active: activeTab === 'renewals' }" @click="activeTab = 'renewals'">
        Persetujuan Paket
        <span v-if="saStats.pending_renewals_count > 0" class="tab-badge">{{ saStats.pending_renewals_count }}</span>
      </button>
      <button :class="{ active: activeTab === 'cms' }" @click="activeTab = 'cms'">CMS & Marketing</button>
      <button :class="{ active: activeTab === 'desktop' }" @click="activeTab = 'desktop'">Lisensi Desktop</button>
    </div>

    <!-- Alert -->
    <div v-if="alert.msg" :class="['sa-alert', alert.type]">{{ alert.msg }}</div>

    <!-- Tenant Grid -->
    <div class="sa-content" v-if="activeTab === 'tenants'">
      <div v-if="loading" class="sa-loading">Memuat data toko...</div>
      <div v-else class="tenant-grid">
        <div
          v-for="tenant in tenants"
          :key="tenant.id"
          class="tenant-card"
          :class="{ inactive: !tenant.is_active }"
        >
          <!-- Card Header -->
          <div class="tc-header">
            <div class="tc-avatar">{{ tenant.name.charAt(0).toUpperCase() }}</div>
            <div class="tc-info">
              <div class="tc-name">{{ tenant.name }}</div>
              <div class="tc-slug">@{{ tenant.slug }}</div>
            </div>
            <div :class="['tc-status', tenant.subscription_status]">
              {{ statusLabel(tenant.subscription_status) }}
            </div>
          </div>

          <!-- Subscription Info -->
          <div class="tc-body">
            <div class="tc-row">
              <span class="tc-label">Paket</span>
              <span class="tc-value plan">{{ planLabel(tenant.subscription_plan) }}</span>
            </div>
            <div class="tc-row">
              <span class="tc-label">Berakhir</span>
              <span class="tc-value">
                <template v-if="tenant.subscription_plan === 'lifetime'">∞ Selamanya</template>
                <template v-else-if="tenant.subscription_active_until">{{ formatDate(tenant.subscription_active_until) }}</template>
                <template v-else-if="tenant.trial_ends_at">Trial: {{ formatDate(tenant.trial_ends_at) }}</template>
                <template v-else>-</template>
              </span>
            </div>
            <div class="tc-row">
              <span class="tc-label">Pengguna</span>
              <span class="tc-value">{{ tenant.user_count }} akun</span>
            </div>
            <div class="tc-row">
              <span class="tc-label">Bergabung</span>
              <span class="tc-value">{{ formatDate(tenant.created_at) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="tc-footer">
            <button class="btn-edit" @click="openEditModal(tenant)">Edit</button>
            <button
              :class="['btn-toggle', tenant.is_active ? 'deactivate' : 'activate']"
              @click="toggleTenant(tenant)"
            >
              {{ tenant.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
            </button>
          </div>
        </div>

        <div v-if="tenants.length === 0" class="empty-state">
          <div class="empty-icon">🏪</div>
          <p>Belum ada toko terdaftar.</p>
          <button class="btn-new-tenant" @click="openNewModal">+ Tambah Toko Pertama</button>
        </div>
      </div>
    </div>

    <!-- Packages Grid -->
    <div class="sa-content" v-else-if="activeTab === 'packages'">
      <div v-if="loadingPackages" class="sa-loading">Memuat data paket...</div>
      <div v-else class="tenant-grid packages-grid">
        <div v-for="pkg in packages" :key="pkg.id" class="tenant-card package-card">
          <div class="tc-header">
            <div class="tc-info">
              <div class="tc-name">{{ pkg.name }}</div>
              <div class="tc-slug">ID: {{ pkg.slug }}</div>
            </div>
          </div>
          <div class="tc-body">
            <div class="tc-row">
              <span class="tc-label">Harga</span>
              <div class="tc-value plan">
                <span v-if="pkg.original_price" class="original-price">Rp {{ pkg.original_price.toLocaleString('id-ID') }}</span>
                <span>Rp {{ pkg.price.toLocaleString('id-ID') }}</span>
              </div>
            </div>
            <div class="tc-row">
              <span class="tc-label">Fitur</span>
            </div>
            <ul class="pkg-features">
              <li v-for="(feat, idx) in pkg.features" :key="idx">✅ {{ feat }}</li>
            </ul>
          </div>
          <div class="tc-footer">
            <button class="btn-edit" @click="openPackageModal(pkg)">Edit Harga/Fitur</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Renewals Grid -->
    <div class="sa-content" v-else-if="activeTab === 'renewals'">
      <div class="sa-sub-header">
        <div class="status-filters">
          <button :class="{ active: renewalStatusFilter === 'pending' }" @click="renewalStatusFilter = 'pending'; fetchRenewals()">Menunggu ({{ saStats.pending_renewals_count }})</button>
          <button :class="{ active: renewalStatusFilter === 'approved' }" @click="renewalStatusFilter = 'approved'; fetchRenewals()">Disetujui</button>
          <button :class="{ active: renewalStatusFilter === 'rejected' }" @click="renewalStatusFilter = 'rejected'; fetchRenewals()">Ditolak</button>
          <button :class="{ active: renewalStatusFilter === 'all' }" @click="renewalStatusFilter = 'all'; fetchRenewals()">Semua</button>
        </div>
      </div>

      <div v-if="loadingRenewals" class="sa-loading">Memuat permintaan...</div>
      <div v-else class="tenant-grid">
        <div v-for="req in renewals" :key="req.id" class="tenant-card renewal-card" :class="req.status">
          <div class="tc-header">
            <div class="tc-avatar">{{ req.tenant.name.charAt(0).toUpperCase() }}</div>
            <div class="tc-info">
              <div class="tc-name">{{ req.tenant.name }}</div>
              <div class="tc-slug">@{{ req.tenant.slug }}</div>
            </div>
            <div :class="['tc-status', req.status]">
              {{ req.status === 'pending' ? 'Menunggu' : (req.status === 'approved' ? 'Disetujui' : 'Ditolak') }}
            </div>
          </div>
          <div class="tc-body">
            <div class="tc-row">
              <span class="tc-label">Paket Diminta</span>
              <span class="tc-value plan">{{ req.package.name }}</span>
            </div>
            <div class="tc-row">
              <span class="tc-label">Harga</span>
              <span class="tc-value">Rp {{ req.price_at_request.toLocaleString('id-ID') }}</span>
            </div>
            <div class="tc-row">
              <span class="tc-label">Tgl. Request</span>
              <span class="tc-value">{{ formatDate(req.created_at) }}</span>
            </div>
            <div class="tc-row" v-if="req.payment_method">
              <span class="tc-label">Metode</span>
              <span class="tc-value">{{ req.payment_method }}</span>
            </div>
            <div class="tc-row" v-if="req.notes" style="margin-top: 4px; flex-direction: column; align-items: flex-start; gap: 4px;">
              <span class="tc-label">Catatan:</span>
              <span class="tc-value" style="font-weight: 400; font-style: italic; font-size: 12px; color: #94a3b8;">"{{ req.notes }}"</span>
            </div>
          </div>
          <div class="tc-footer" v-if="req.status === 'pending'">
            <button class="btn-approve" @click="approveRenewal(req.id)">Setujui & Aktifkan</button>
            <button class="btn-reject" @click="rejectRenewal(req.id)">Tolak</button>
          </div>
          <div class="tc-footer processed-info" v-else>
             <span>Diproses: {{ formatDate(req.processed_at) }}</span>
          </div>
        </div>
        <div v-if="renewals.length === 0" class="empty-state">
          <div class="empty-icon">📝</div>
          <p>Tidak ada permintaan perpanjangan.</p>
        </div>
      </div>
    </div>

    <!-- CMS & Marketing -->
    <div class="sa-content" v-else-if="activeTab === 'cms'">
      <SuperAdminCmsPanel />
    </div>
    <div class="sa-content" v-else-if="activeTab === 'desktop'">
      <SuperAdminDesktopLicenses />
    </div>

    <!-- Modal: New / Edit Tenant -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ editingId ? 'Edit Data Toko' : 'Daftarkan Toko Baru' }}</h3>
          <button class="modal-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <!-- New Tenant Fields -->
          <template v-if="!editingId">
            <div class="mf-group">
              <label>Nama Toko</label>
              <input v-model="form.name" placeholder="Contoh: Warung Makan Sari" />
            </div>
            <div class="mf-group">
              <label>Store ID (slug, otomatis)</label>
              <input v-model="form.slug" placeholder="kosongkan untuk otomatis" />
            </div>
            <div class="mf-group">
              <label>Username Admin Toko</label>
              <input v-model="form.admin_username" placeholder="admin" />
            </div>
            <div class="mf-group">
              <label>Password Admin Toko</label>
              <input v-model="form.admin_password" type="password" placeholder="Min. 6 karakter" />
            </div>
          </template>

          <!-- Edit Fields (shown for both new and edit) -->
          <template v-if="editingId">
            <div class="mf-group">
              <label>Nama Toko</label>
              <input v-model="form.name" />
            </div>
            <div class="mf-group">
              <label>Alamat</label>
              <input v-model="form.address" placeholder="Opsional" />
            </div>
            <div class="mf-group">
              <label>Telepon</label>
              <input v-model="form.phone" placeholder="Opsional" />
            </div>
          </template>

          <div class="mf-group">
            <label>Paket Langganan</label>
            <select v-model="form.subscription_plan">
              <option value="trial">Trial (Default 7 Hari)</option>
              <option value="monthly">Bulanan</option>
              <option value="yearly">Tahunan</option>
              <option value="lifetime">Seumur Hidup</option>
            </select>
          </div>

          <div class="mf-group" v-if="form.subscription_plan === 'trial'">
            <label>Trial Berakhir Pada (Kosongkan untuk otomatis 7 hari saat daftar)</label>
            <input v-model="form.trial_ends_at" type="date" />
          </div>

          <div class="mf-group" v-else-if="form.subscription_plan !== 'lifetime'">
            <label>Langganan Aktif Sampai</label>
            <input v-model="form.subscription_active_until" type="date" />
          </div>

          <div v-if="formError" class="mf-error">{{ formError }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closeModal">Batal</button>
          <button class="btn-save" @click="saveModal" :disabled="saving">
            {{ saving ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Daftarkan Toko') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Edit Package -->
    <div v-if="showPackageModal" class="modal-overlay" @click.self="closePackageModal">
      <div class="modal">
        <div class="modal-header">
          <h3>Edit Harga & Paket: {{ packageForm.slug.toUpperCase() }}</h3>
          <button class="modal-close" @click="closePackageModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="mf-group">
            <label>Nama Tampilan Paket</label>
            <input v-model="packageForm.name" />
          </div>
          <div class="mf-group">
            <label>Harga Promo (Rupiah)</label>
            <input v-model.number="packageForm.price" type="number" />
          </div>
          <div class="mf-group">
            <label>Harga Coret / Normal (Opsional)</label>
            <input v-model.number="packageForm.original_price" type="number" placeholder="Kosongkan jika tidak promo" />
          </div>
          <div class="mf-group">
            <label>Daftar Fitur (Pisahkan dengan koma)</label>
            <textarea v-model="packageForm.featuresStr" rows="4" placeholder="Misal: Transaksi Tanpa Batas, Multi Kasir, Analitik"></textarea>
          </div>
          <div v-if="packageFormError" class="mf-error">{{ packageFormError }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="closePackageModal">Batal</button>
          <button class="btn-save" @click="savePackageModal" :disabled="savingPackage">
            {{ savingPackage ? 'Menyimpan...' : 'Simpan Pembaruan Harga' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../api'
import SuperAdminCmsPanel from '../components/SuperAdminCmsPanel.vue'
import SuperAdminDesktopLicenses from '../components/SuperAdminDesktopLicenses.vue'

const activeTab = ref('tenants')
const tenants = ref<any[]>([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const editingId = ref<number | null>(null)
const formError = ref('')
const alert = ref<{ msg: string; type: string }>({ msg: '', type: '' })

// Package State
const packages = ref<any[]>([])
const loadingPackages = ref(false)
const showPackageModal = ref(false)
const editingPackageId = ref<number | null>(null)
const packageFormError = ref('')
const savingPackage = ref(false)

// Renewal State
const renewals = ref<any[]>([])
const loadingRenewals = ref(false)
const saStats = ref({
  pending_renewals_count: 0,
  total_tenants: 0,
  active_tenants: 0
})

const renewalStatusFilter = ref('pending')

const packageForm = ref({
  slug: '',
  name: '',
  price: 0,
  original_price: 0,
  featuresStr: ''
})

const form = ref({
  name: '',
  slug: '',
  address: '',
  phone: '',
  admin_username: 'admin',
  admin_password: '',
  subscription_plan: 'trial',
  subscription_active_until: '',
  trial_ends_at: '',
})

const showAlert = (msg: string, type = 'success') => {
  alert.value = { msg, type }
  setTimeout(() => alert.value = { msg: '', type: '' }, 4000)
}

const loadTenants = async () => {
  loading.value = true
  try {
    const res = await api.get('/superadmin/tenants')
    tenants.value = res.data
  } catch (e: any) {
    showAlert('Gagal memuat data: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    loading.value = false
  }
}

const fetchPackages = async () => {
  loadingPackages.value = true
  try {
    const res = await api.get('/superadmin/packages')
    packages.value = res.data
  } catch (e: any) {
    showAlert('Gagal memuat paket: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    loadingPackages.value = false
  }
}

const fetchStats = async () => {
  try {
    const res = await api.get('/superadmin/stats')
    saStats.value = res.data
  } catch (e) {}
}

const fetchRenewals = async () => {
  loadingRenewals.value = true
  try {
    const res = await api.get('/superadmin/renewals', {
      params: { status: renewalStatusFilter.value }
    })
    renewals.value = res.data
  } catch (e: any) {
    showAlert('Gagal memuat persetujuan: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    loadingRenewals.value = false
  }
}

onMounted(() => {
  loadTenants()
  fetchPackages()
  fetchRenewals()
  fetchStats()
})

const openNewModal = () => {
  editingId.value = null
  form.value = { name: '', slug: '', address: '', phone: '', admin_username: 'admin', admin_password: '', subscription_plan: 'trial', subscription_active_until: '', trial_ends_at: '' }
  formError.value = ''
  showModal.value = true
}

const openEditModal = (tenant: any) => {
  editingId.value = tenant.id
  form.value = {
    name: tenant.name,
    slug: tenant.slug,
    address: tenant.address || '',
    phone: tenant.phone || '',
    admin_username: '',
    admin_password: '',
    subscription_plan: tenant.subscription_plan,
    subscription_active_until: tenant.subscription_active_until ? tenant.subscription_active_until.substring(0, 10) : '',
    trial_ends_at: tenant.trial_ends_at ? tenant.trial_ends_at.substring(0, 10) : '',
  }
  formError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingId.value = null
}

// Package Modals Logic
const openPackageModal = (pkg: any) => {
  editingPackageId.value = pkg.id
  packageForm.value = {
    slug: pkg.slug,
    name: pkg.name,
    price: pkg.price,
    original_price: pkg.original_price || 0,
    featuresStr: (pkg.features || []).join(', ')
  }
  packageFormError.value = ''
  showPackageModal.value = true
}

const closePackageModal = () => {
  showPackageModal.value = false
  editingPackageId.value = null
}

const savePackageModal = async () => {
  packageFormError.value = ''
  if (!packageForm.value.name) { packageFormError.value = 'Nama paket wajib diisi'; return }

  savingPackage.value = true
  try {
    const features = packageForm.value.featuresStr.split(',').map(f => f.trim()).filter(f => f.length > 0)
    await api.put(`/superadmin/packages/${editingPackageId.value}`, {
      name: packageForm.value.name,
      price: packageForm.value.price,
      original_price: packageForm.value.original_price || null,
      features: features
    })
    
    closePackageModal()
    showAlert('Harga paket berhasil disimpan!')
    fetchPackages()
  } catch (e: any) {
    packageFormError.value = e.response?.data?.message || e.message
  } finally {
    savingPackage.value = false
  }
}

const saveModal = async () => {
  formError.value = ''
  if (!form.value.name) { formError.value = 'Nama toko wajib diisi'; return }

  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/superadmin/tenants/${editingId.value}/subscription`, {
        name: form.value.name,
        address: form.value.address,
        phone: form.value.phone,
        subscription_plan: form.value.subscription_plan,
        subscription_active_until: form.value.subscription_active_until || null,
        trial_ends_at: form.value.trial_ends_at || null,
      })
      showAlert('Data toko berhasil diperbarui!')
    } else {
      if (!form.value.admin_password) { formError.value = 'Password admin wajib diisi'; saving.value = false; return }
      await api.post('/superadmin/tenants', {
        name: form.value.name,
        slug: form.value.slug || undefined,
        admin_username: form.value.admin_username,
        admin_password: form.value.admin_password,
        subscription_plan: form.value.subscription_plan,
        subscription_active_until: form.value.subscription_active_until || null,
        trial_ends_at: form.value.trial_ends_at || null,
      })
      showAlert('Toko baru berhasil didaftarkan!')
    }
    closeModal()
    await loadTenants()
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'Terjadi kesalahan'
  } finally {
    saving.value = false
  }
}

const toggleTenant = async (tenant: any) => {
  const action = tenant.is_active ? 'nonaktifkan' : 'aktifkan'
  if (!confirm(`Yakin ingin ${action} toko "${tenant.name}"?`)) return
  try {
    await api.post(`/superadmin/tenants/${tenant.id}/toggle`)
    await loadTenants()
    showAlert(`Toko berhasil di${action}.`)
  } catch (e: any) {
    showAlert('Gagal: ' + (e.response?.data?.message || e.message), 'error')
  }
}

const statusLabel = (status: string) => {
  if (status === 'active') return 'Aktif'
  if (status === 'trial') return 'Trial'
  return 'Kedaluwarsa'
}

const planLabel = (plan: string) => {
  if (plan === 'monthly') return 'Bulanan'
  if (plan === 'yearly') return 'Tahunan'
  if (plan === 'lifetime') return 'Seumur Hidup'
  return 'Trial'
}

const formatDate = (val: string) => {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const approveRenewal = async (id: number) => {
  if (!confirm('Setujui perpanjangan paket ini?')) return
  try {
    await api.post(`/superadmin/renewals/${id}/approve`)
    showAlert('Paket berhasil diaktifkan!')
    fetchRenewals()
    loadTenants()
    fetchStats()
  } catch (e: any) {
    showAlert('Gagal menyetujui: ' + (e.response?.data?.message || e.message), 'error')
  }
}

const rejectRenewal = async (id: number) => {
  const notes = prompt('Alasan penolakan (opsional):')
  if (notes === null) return // Cancelled prompt
  try {
    await api.post(`/superadmin/renewals/${id}/reject`, { notes })
    showAlert('Permintaan ditolak.')
    fetchRenewals()
    fetchStats()
  } catch (e: any) {
    showAlert('Gagal menolak: ' + (e.response?.data?.message || e.message), 'error')
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.sa-layout {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0c4a6e 100%);
  color: white;
  display: flex;
  flex-direction: column;
  font-family: 'Inter', system-ui, sans-serif;
}

/* Header */
.sa-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 32px;
  background: rgba(255,255,255,0.05);
  border-bottom: 1px solid rgba(255,255,255,0.08);
  backdrop-filter: blur(10px);
  gap: 16px;
}
.sa-header-left { display: flex; align-items: center; gap: 16px; }
.sa-logo { font-size: 32px; }
.sa-title {
  font-size: 24px;
  font-weight: 900;
  margin: 0;
  color: white;
  letter-spacing: -0.5px;
}
.saas-badge {
  display: inline-block;
  background: linear-gradient(135deg, #f59e0b, #ef4444);
  font-size: 11px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 999px;
  vertical-align: middle;
  margin-left: 6px;
  letter-spacing: 1px;
}
.sa-subtitle { font-size: 13px; color: #94a3b8; margin: 2px 0 0; }

.sa-header-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.sa-stat {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.1);
  padding: 8px 16px;
  border-radius: 999px;
  font-size: 13px;
  color: #cbd5e1;
}
.sa-stat b { color: white; }
.sa-stat.active b { color: #34d399; }

.btn-new-tenant {
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.btn-new-tenant:hover { opacity: 0.85; transform: translateY(-1px); }

/* Alert */
.sa-alert {
  margin: 12px 32px 0;
  padding: 12px 20px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
}
.sa-alert.success { background: rgba(52,211,153,0.15); border: 1px solid #34d399; color: #34d399; }
.sa-alert.error { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; }

/* Tabs */
.sa-tabs {
  display: flex;
  padding: 0 32px;
  gap: 16px;
  margin-top: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.sa-tabs button {
  background: transparent;
  border: none;
  color: #94a3b8;
  padding: 12px 16px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}
.sa-tabs button:hover { color: white; }
.sa-tabs button.active { color: white; border-bottom-color: #0ea5e9; }

/* Content */
.sa-content { flex: 1; padding: 24px 32px; overflow-y: auto; }
.sa-loading { text-align: center; color: #94a3b8; padding: 60px; font-size: 16px; }

/* Grid */
.tenant-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

/* Tenant Card */
.tenant-card {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  transition: all 0.3s;
}
.tenant-card:hover { transform: translateY(-3px); border-color: rgba(14,165,233,0.4); box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
.tenant-card.inactive { opacity: 0.5; filter: grayscale(60%); }

.package-card {
  border-top: 4px solid #0ea5e9;
}
.pkg-features {
  margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #cbd5e1;
}

.tc-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 20px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.tc-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 900;
  flex-shrink: 0;
}
.tc-info { flex: 1; min-width: 0; }
.tc-name { font-weight: 800; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tc-slug { font-size: 12px; color: #64748b; }

.tc-status {
  font-size: 11px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 999px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}
.tc-status.active { background: rgba(52,211,153,0.2); color: #34d399; border: 1px solid rgba(52,211,153,0.3); }
.tc-status.trial { background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }
.tc-status.expired { background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }

.tc-body { padding: 14px 20px; display: flex; flex-direction: column; gap: 8px; }
.tc-row { display: flex; justify-content: space-between; align-items: center; }
.tc-label { font-size: 12px; color: #64748b; font-weight: 600; }
.tc-value { font-size: 13px; font-weight: 700; color: #e2e8f0; }
.tc-value.plan { color: #38bdf8; display: flex; flex-direction: column; align-items: flex-end; }
.original-price { font-size: 11px; color: #94a3b8; text-decoration: line-through; margin-bottom: 2px; }

.tc-footer {
  display: flex;
  gap: 8px;
  padding: 12px 20px 18px;
}
.btn-edit, .btn-toggle {
  flex: 1;
  padding: 9px;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-edit { background: rgba(255,255,255,0.08); color: #e2e8f0; }
.btn-edit:hover { background: rgba(255,255,255,0.15); }
.btn-toggle.deactivate { background: rgba(239,68,68,0.15); color: #fca5a5; }
.btn-toggle.deactivate:hover { background: rgba(239,68,68,0.25); }
.btn-toggle.activate { background: rgba(52,211,153,0.15); color: #34d399; }
.btn-toggle.activate:hover { background: rgba(52,211,153,0.25); }

/* Empty State */
.empty-state { text-align: center; padding: 80px 20px; color: #64748b; grid-column: 1 / -1; }
.empty-icon { font-size: 64px; margin-bottom: 16px; }

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 20px;
}
.modal {
  background: #1e293b;
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 20px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.5);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.modal-header h3 { margin: 0; font-size: 18px; font-weight: 800; color: white; }
.modal-close { background: none; border: none; color: #64748b; font-size: 24px; cursor: pointer; }
.modal-body { padding: 24px; display: flex; flex-direction: column; gap: 14px; }
.mf-group { display: flex; flex-direction: column; gap: 6px; }
.mf-group label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.mf-group input, .mf-group select {
  padding: 10px 14px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 10px;
  color: white;
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
}
.mf-group input:focus, .mf-group select:focus {
  border-color: #0ea5e9;
  background: rgba(14,165,233,0.08);
}
.mf-group select option { background: #1e293b; }
.mf-error { color: #fca5a5; font-size: 13px; font-weight: 600; background: rgba(239,68,68,0.1); padding: 10px 14px; border-radius: 8px; }
.modal-footer {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  padding: 16px 24px 20px;
  border-top: 1px solid rgba(255,255,255,0.08);
}
.btn-cancel { background: rgba(255,255,255,0.06); color: #94a3b8; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }
.btn-save { background: linear-gradient(135deg, #0ea5e9, #6366f1); color: white; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 700; cursor: pointer; }
.btn-save:hover { opacity: 0.9; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

.tab-badge {
  background: #ef4444;
  color: white;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 999px;
  margin-left: 6px;
  vertical-align: middle;
}

.btn-approve {
  flex: 2;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 9px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}

.btn-reject {
  flex: 1;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
  padding: 9px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}

.processed-info {
  font-size: 11px;
  color: #64748b;
  justify-content: center;
  padding: 10px;
}

.tc-status.pending { background: rgba(245,158,11,0.2); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
.tc-status.approved { background: rgba(52,211,153,0.2); color: #34d399; border: 1px solid rgba(52,211,153,0.3); }
.tc-status.rejected { background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }

.sa-sub-header {
  margin-bottom: 20px;
}

.status-filters {
  display: flex;
  gap: 10px;
}

.status-filters button {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  color: #94a3b8;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.status-filters button:hover {
  background: rgba(255,255,255,0.1);
  color: white;
}

.status-filters button.active {
  background: #0ea5e9;
  color: white;
  border-color: #0ea5e9;
}

@media (max-width: 900px) {
  .sa-header {
    align-items: flex-start;
    flex-direction: column;
    padding: 20px;
  }

  .sa-header-left {
    align-items: flex-start;
    width: 100%;
  }

  .sa-logo {
    font-size: 28px;
    line-height: 1;
  }

  .sa-title {
    font-size: 22px;
  }

  .sa-header-right {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    width: 100%;
  }

  .btn-new-tenant {
    grid-column: 1 / -1;
    width: 100%;
  }

  .sa-tabs {
    gap: 8px;
    margin-top: 0;
    overflow-x: auto;
    padding: 0 20px;
    scrollbar-width: thin;
  }

  .sa-tabs button {
    flex: 0 0 auto;
    padding: 12px 10px;
    font-size: 13px;
    white-space: nowrap;
  }

  .sa-alert {
    margin: 12px 20px 0;
  }

  .sa-content {
    padding: 18px 20px 24px;
  }

  .tenant-grid,
  .tenant-grid.packages-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .status-filters {
    overflow-x: auto;
    padding-bottom: 4px;
  }

  .status-filters button {
    flex: 0 0 auto;
    white-space: nowrap;
  }
}

@media (max-width: 520px) {
  .sa-layout {
    min-height: calc(100vh - 60px);
  }

  .sa-header {
    padding: 16px;
  }

  .sa-header-left {
    gap: 10px;
  }

  .sa-title {
    font-size: 19px;
  }

  .sa-subtitle {
    font-size: 12px;
    line-height: 1.35;
  }

  .saas-badge {
    font-size: 9px;
    padding: 2px 6px;
  }

  .sa-header-right {
    gap: 8px;
  }

  .sa-stat {
    border-radius: 10px;
    padding: 8px 10px;
    text-align: center;
  }

  .sa-stat b {
    display: block;
    font-size: 18px;
    line-height: 1.1;
  }

  .sa-tabs {
    padding: 0 16px;
  }

  .sa-content {
    padding: 16px;
  }

  .sa-loading {
    padding: 42px 16px;
  }

  .tc-header {
    align-items: flex-start;
    gap: 10px;
    padding: 16px 16px 10px;
  }

  .tc-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    font-size: 17px;
  }

  .tc-status {
    font-size: 10px;
    padding: 4px 8px;
  }

  .tc-body {
    padding: 12px 16px;
  }

  .tc-row {
    align-items: flex-start;
    gap: 8px;
  }

  .tc-value {
    max-width: 60%;
    text-align: right;
    overflow-wrap: anywhere;
  }

  .tc-footer {
    flex-direction: column;
    padding: 12px 16px 16px;
  }

  .btn-edit,
  .btn-toggle,
  .btn-approve,
  .btn-reject {
    width: 100%;
    min-height: 40px;
  }

  .empty-state {
    padding: 52px 16px;
  }

  .empty-icon {
    font-size: 48px;
  }

  .modal-overlay {
    align-items: flex-end;
    padding: 12px;
  }

  .modal {
    max-height: calc(100vh - 24px);
    overflow: hidden;
    border-radius: 16px;
  }

  .modal-header {
    padding: 16px;
  }

  .modal-header h3 {
    font-size: 16px;
    line-height: 1.3;
  }

  .modal-body {
    max-height: calc(100vh - 180px);
    overflow-y: auto;
    padding: 16px;
  }

  .modal-footer {
    flex-direction: column-reverse;
    padding: 14px 16px 16px;
  }

  .btn-cancel,
  .btn-save {
    width: 100%;
    min-height: 42px;
  }
}
</style>
