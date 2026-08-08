<template>
  <div v-if="isExpired" class="expiry-overlay">
    <div class="expiry-card">
      <div class="expiry-header">
        <div class="icon">{{ isWaiting ? '⏳' : '⚠️' }}</div>
        <h1>{{ isWaiting ? 'Menunggu Persetujuan' : 'Masa Aktif Habis' }}</h1>
        <p v-if="isWaiting">
          Permintaan aktivasi paket <b>{{ latestRequest?.package?.name }}</b> sedang diproses oleh Super Admin.
          <br/><small>Halaman ini akan otomatis terbuka jika sudah disetujui.</small>
          <br/><br/>
          <a
            :href="buildDirectWaLink(latestRequest?.package)"
            target="_blank"
            class="btn-wa-direct"
            style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; padding: 10px 18px; background-color: #25D366; color: white; border-radius: 8px; font-weight: 600; text-decoration: none;"
          >
            💬 Konfirmasi via WhatsApp
          </a>
        </p>
        <p v-else>
          Masa aktif toko <b>{{ tenant?.name }}</b> telah berakhir. Silakan pilih paket langganan untuk melanjutkan penggunaan sistem.
        </p>
        
        <div v-if="!isWaiting && latestRequest?.status === 'rejected'" class="rejection-notice">
          🚫 <b>Permintaan Terakhir Ditolak:</b> {{ latestRequest?.notes || 'Tidak ada alasan spesifik.' }}
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loadingPackages && !isWaiting" class="loading-state">
        <div class="spinner"></div>
        <p>Memuat paket langganan...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="packageError && !isWaiting" class="error-state">
        <div class="error-icon">😔</div>
        <p>Gagal memuat paket langganan.</p>
        <p class="error-sub">Periksa koneksi internet Anda, atau hubungi Admin untuk perpanjangan manual.</p>
        <div class="error-actions">
          <button class="btn-retry" @click="loadPackages">🔄 Coba Lagi</button>
          <a
            v-if="tenant?.superadmin_phone"
            :href="buildDirectWaLink()"
            target="_blank"
            class="btn-wa-direct"
          >
            💬 Hubungi Admin via WhatsApp
          </a>
        </div>
      </div>

      <!-- Packages Grid -->
      <div class="plans-grid" v-else-if="!isWaiting && packages.length > 0">
        <div 
          v-for="pkg in packages" 
          :key="pkg.slug"
          class="plan-card" 
          :class="[pkg.style?.card_class || '']" 
          @click="handleChoosePlan(pkg)"
        >
          <div class="popular-badge" v-if="pkg.style?.badge">{{ pkg.style.badge }}</div>
          <div class="plan-header">
            <span class="plan-title">{{ pkg.name }}</span>
            <div class="price">
              <span v-if="pkg.original_price && pkg.original_price > pkg.price" class="original-price">
                Rp {{ (pkg.original_price / 1000).toLocaleString('id-ID') }}rb
              </span>
              Rp {{ (pkg.price / 1000).toLocaleString('id-ID') }}rb
              <span v-if="pkg.duration_days === 30">/bln</span>
              <span v-else-if="pkg.duration_days === 365">/thn</span>
              <span v-else>/kali</span>
            </div>
          </div>
          <ul class="plan-features">
            <li v-for="(feat, idx) in pkg.features" :key="idx">✅ {{ feat }}</li>
          </ul>
          <button class="btn-plan" :disabled="loading">
            {{ loading ? 'Sedang memproses...' : 'Pilih Paket & Konfirmasi WA' }}
          </button>
        </div>
      </div>

      <!-- No packages fallback -->
      <div v-else-if="!isWaiting && !loadingPackages && packages.length === 0" class="error-state">
        <div class="error-icon">📦</div>
        <p>Belum ada paket aktif saat ini.</p>
        <p class="error-sub">Hubungi Admin untuk informasi perpanjangan langganan.</p>
        <a
          v-if="tenant?.superadmin_phone"
          :href="buildDirectWaLink()"
          target="_blank"
          class="btn-wa-direct"
        >
          💬 Hubungi Admin via WhatsApp
        </a>
      </div>

      <div class="expiry-footer">
        <button class="btn-logout" @click="logout">Keluar / Ganti Akun</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import { useAuthStore } from '../store/auth'
import api from '../api'

const authStore = useAuthStore()
const tenant = computed(() => authStore.tenant)
const latestRequest = computed(() => tenant.value?.latest_renewal)
const isWaiting = computed(() => latestRequest.value?.status === 'pending')

const isExpired = computed(() => {
  return authStore.isAuthenticated && 
         authStore.user?.role !== 'superadmin' && 
         authStore.tenant?.subscription_status === 'expired'
})

const packages = ref<any[]>([])
const loading = ref(false)
const loadingPackages = ref(false)
const packageError = ref(false)

const loadPackages = async () => {
  loadingPackages.value = true
  packageError.value = false
  try {
    const res = await api.get('/packages')
    const allPackages = Array.isArray(res.data) ? res.data : []
    // Tampilkan hanya paket cloud saja (slug tidak diawali dengan local_)
    packages.value = allPackages.filter((pkg: any) => pkg.slug && !pkg.slug.startsWith('local_'))
  } catch(e) {
    console.error("Error load packages", e)
    packageError.value = true
    packages.value = []
  } finally {
    loadingPackages.value = false
  }
}

const buildDirectWaLink = (pkg?: any) => {
  let waNumber = tenant.value?.superadmin_phone || '6281392156513'
  // Remove non-digit characters (e.g. +, spaces, dashes)
  waNumber = waNumber.replace(/\D/g, '')
  
  const pkgName = pkg?.name || latestRequest.value?.package?.name || '-'
  const priceText = pkg?.price ? `\nHarga: Rp ${pkg.price.toLocaleString('id-ID')}` : ''
  const message = encodeURIComponent(
    `Halo Super Admin,\n\n` +
    `Saya ingin konfirmasi pembayaran untuk perpanjangan paket.\n` +
    `Store ID: ${tenant.value?.slug || '-'}\n` +
    `Nama Toko: ${tenant.value?.name || '-'}\n` +
    `Paket: ${pkgName}${priceText}\n\n` +
    `Mohon bantuannya untuk aktivasi. Terima kasih!`
  )
  return `https://wa.me/${waNumber}?text=${message}`
}

const handleChoosePlan = async (pkg: any) => {
  if (!confirm(`Apakah Anda ingin berlangganan paket ${pkg.name}?`)) return
  
  // Pre-open tab synchronously within user click event so browser popup blocker allows it
  const waWindow = window.open('about:blank', '_blank')

  loading.value = true
  try {
    await api.post('/subscription/renew', { 
      package_id: pkg.id,
      payment_method: 'WhatsApp' 
    })
    
    let waNumber = tenant.value?.superadmin_phone || '6281392156513'
    waNumber = waNumber.replace(/\D/g, '')

    const message = encodeURIComponent(
      `Halo Super Admin,\n\n` +
      `Saya ingin konfirmasi pembayaran untuk perpanjangan paket.\n` +
      `Store ID: ${tenant.value?.slug || '-'}\n` +
      `Nama Toko: ${tenant.value?.name || '-'}\n` +
      `Paket: ${pkg.name}\n` +
      `Harga: Rp ${pkg.price?.toLocaleString('id-ID') || '0'}\n\n` +
      `Mohon bantuannya untuk aktivasi. Terima kasih!`
    )
    
    const waUrl = `https://wa.me/${waNumber}?text=${message}`
    if (waWindow && !waWindow.closed) {
      waWindow.location.href = waUrl
    } else {
      window.location.href = waUrl
    }
    
    alert("Permintaan diajukan. Silakan konfirmasi melalui WhatsApp.")
    await authStore.refreshTenant()
  } catch (e: any) {
    if (waWindow && !waWindow.closed) waWindow.close()
    alert("Gagal: " + (e.response?.data?.message || e.message))
  } finally {
    loading.value = false
  }
}

const logout = async () => {
  await authStore.logout()
  window.location.href = '/' // Direct redirect to login
}

let pollInterval: any = null

const startPolling = () => {
  if (pollInterval) clearInterval(pollInterval)
  pollInterval = setInterval(async () => {
    if (authStore.isAuthenticated && isExpired.value) {
      await authStore.refreshTenant()
    } else {
      stopPolling()
    }
  }, 10000)
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

// Watch isExpired to load packages and start/stop polling dynamically
watch(isExpired, async (newVal) => {
  if (newVal) {
    await loadPackages()
    await authStore.refreshTenant()
    startPolling()
  } else {
    stopPolling()
  }
}, { immediate: true })

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.expiry-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: flex;
  justify-content: center;
  padding: 20px;
  overflow-y: auto;
  align-items: flex-start;
}

.expiry-card {
  background: white;
  width: 100%;
  max-width: 900px;
  border-radius: 24px;
  padding: 30px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  margin-top: 40px;
  margin-bottom: 40px;
}

.expiry-header {
  text-align: center;
  margin-bottom: 40px;
}

.expiry-header .icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.expiry-header h1 {
  font-size: 32px;
  font-weight: 900;
  color: #0f172a;
  margin: 0 0 12px;
}

.expiry-header p {
  color: #64748b;
  font-size: 16px;
  max-width: 600px;
  margin: 0 auto;
}

.rejection-notice {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  padding: 12px;
  border-radius: 12px;
  margin-top: 16px;
  font-size: 14px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #0ea5e9;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error State */
.error-state {
  text-align: center;
  padding: 32px 20px;
  margin-bottom: 24px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 16px;
}

.error-icon {
  font-size: 40px;
  margin-bottom: 12px;
}

.error-state p {
  color: #334155;
  font-weight: 600;
  margin: 0 0 4px;
  font-size: 16px;
}

.error-sub {
  color: #94a3b8 !important;
  font-weight: 400 !important;
  font-size: 14px !important;
  margin-bottom: 20px !important;
}

.error-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

.btn-retry {
  padding: 10px 20px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  color: #334155;
  font-size: 14px;
}

.btn-retry:hover {
  background: #e2e8f0;
}

.btn-wa-direct {
  display: inline-block;
  padding: 10px 20px;
  background: #25D366;
  color: white;
  border-radius: 10px;
  font-weight: 700;
  text-decoration: none;
  font-size: 14px;
  transition: background 0.2s;
}

.btn-wa-direct:hover {
  background: #1ebe5c;
}

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.plan-card {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 20px;
  padding: 24px;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
  display: flex;
  flex-direction: column;
}

.plan-card:hover {
  border-color: #0ea5e9;
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.plan-header { margin-bottom: 20px; }
.plan-title { font-weight: 800; font-size: 18px; color: #1e293b; }
.price { font-size: 24px; font-weight: 900; color: #0f172a; margin-top: 4px; }
.original-price {
  font-size: 14px;
  font-weight: 400;
  color: #94a3b8;
  text-decoration: line-through;
  display: block;
  margin-bottom: 2px;
}

.plan-features {
  list-style: none;
  padding: 0;
  margin: 0 0 20px;
  flex: 1;
}

.plan-features li {
  font-size: 14px;
  color: #475569;
  margin-bottom: 8px;
}

.btn-plan {
  width: 100%;
  padding: 12px;
  background: #0ea5e9;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-plan:hover:not(:disabled) {
  background: #0284c7;
}

.btn-plan:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.expiry-footer {
  text-align: center;
  border-top: 1px solid #f1f5f9;
  padding-top: 24px;
}

.btn-logout {
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  padding: 10px 24px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.btn-logout:hover {
  background: #ef4444;
  color: white;
}

/* Plan Styles */
.popular-badge {
  position: absolute;
  top: -12px;
  right: 20px;
  background: #f59e0b;
  color: white;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
}

.plan-card.highlight {
  border-color: #0ea5e9;
  background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
}
</style>
