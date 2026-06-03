<template>
  <div v-if="isExpired" class="expiry-overlay">
    <div class="expiry-card">
      <div class="expiry-header">
        <div class="icon">{{ isWaiting ? '⏳' : '⚠️' }}</div>
        <h1>{{ isWaiting ? 'Menunggu Persetujuan' : 'Masa Aktif Habis' }}</h1>
        <p v-if="isWaiting">
          Permintaan aktivasi paket <b>{{ latestRequest?.package?.name }}</b> sedang diproses oleh Super Admin.
          <br/><small>Halaman ini akan otomatis terbuka jika sudah disetujui.</small>
        </p>
        <p v-else>
          Masa aktif toko <b>{{ tenant?.name }}</b> telah berakhir. Silakan pilih paket langganan untuk melanjutkan penggunaan sistem.
        </p>
        
        <div v-if="!isWaiting && latestRequest?.status === 'rejected'" class="rejection-notice">
          🚫 <b>Permintaan Terakhir Ditolak:</b> {{ latestRequest?.notes || 'Tidak ada alasan spesifik.' }}
        </div>
      </div>

      <div class="plans-grid" v-if="!isWaiting">
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

      <div class="expiry-footer">
        <button class="btn-logout" @click="logout">Keluar / Ganti Akun</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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

const loadPackages = async () => {
  try {
    const res = await api.get('/packages')
    packages.value = res.data
  } catch(e) {
    console.error("Error load packages", e)
  }
}

const handleChoosePlan = async (pkg: any) => {
  if (!confirm(`Apakah Anda ingin berlangganan paket ${pkg.name}?`)) return
  
  loading.value = true
  try {
    await api.post('/subscription/renew', { 
      package_id: pkg.id,
      payment_method: 'WhatsApp' 
    })
    
    const waNumber = tenant.value?.superadmin_phone
    if (!waNumber) {
      alert("Nomor WhatsApp Super Admin belum dikonfigurasi. Isi SUPERADMIN_PHONE di ENV backend.")
      return
    }

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
    alert("Permintaan diajukan. Silakan konfirmasi melalui WhatsApp.")
  } catch (e: any) {
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

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    return
  }

  await loadPackages()
  await authStore.refreshTenant()
  
  // Polling every 10 seconds if waiting
  pollInterval = setInterval(async () => {
    if (authStore.isAuthenticated && isExpired.value) {
      await authStore.refreshTenant()
    } else {
      clearInterval(pollInterval)
    }
  }, 10000)
})

import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
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
  overflow-y: auto; /* Enable scroll for long content */
  align-items: flex-start; /* Better for scrolling than center */
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

/* Plan Styles (copied from Settings if needed) */
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
</style>
