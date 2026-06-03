<template>
  <div class="overlay">
    <div class="card">
      <div class="header-row">
        <h3>Aktivasi Lisensi Desktop</h3>
        <button v-if="closable" class="close-btn" @click="emit('close')">&times;</button>
      </div>
      
      <div class="trial-banner" v-if="status?.status === 'trial'">
        🎁 Mode Trial Aktif: {{ status.trial_days_left }} hari lagi
      </div>

      <p class="subtitle">
        {{ status?.status === 'trial' 
          ? 'Gunakan serial key resmi untuk mengaktifkan lisensi permanen.' 
          : 'Aplikasi membutuhkan lisensi resmi untuk digunakan di perangkat ini.' }}
      </p>

      <div class="status-box">
        <div class="status-row">
          <span class="label">Status:</span>
          <span :class="['value-badge', status?.status]">
            {{ statusLabel }}
          </span>
        </div>
        <div class="status-row">
          <span class="label">Hardware ID:</span>
          <div class="device-info">
            <code class="device-id">{{ status?.device_id }}</code>
            <button class="btn-copy" @click="copyDeviceId" :disabled="!status?.device_id">Salin</button>
          </div>
        </div>
        <div class="status-row" v-if="status?.reason">
          <span class="label">Catatan:</span>
          <span class="value reason">{{ status.reason }}</span>
        </div>
      </div>

      <div class="form-section">
        <div class="form-group">
          <label>Serial Key</label>
          <div class="input-with-button">
            <input v-model="licenseKey" type="text" placeholder="NESSA-XXXX-XXXX-XXXX" @keyup.enter="activateOnline" />
            <button class="btn-primary" @click="activateOnline" :disabled="loadingOnline || !licenseKey">
              {{ loadingOnline ? 'Mengaktifkan...' : 'Aktivasi Online' }}
            </button>
          </div>
          <p class="input-hint">Pastikan komputer terhubung ke internet saat aktivasi.</p>
        </div>

        <div class="offline-toggle" @click="showManual = !showManual">
          {{ showManual ? '− Sembunyikan Aktivasi Manual' : '+ Gunakan Aktivasi Manual (Offline)' }}
        </div>

        <div class="form-group manual-area" v-if="showManual">
          <label>Sertifikat Lisensi (JSON)</label>
          <textarea v-model="licenseText" rows="3" placeholder="Paste data sertifikat lisensi di sini"></textarea>
          <button v-if="licenseText" class="btn-secondary" @click="activate" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Aktifkan Secara Manual' }}
          </button>
        </div>
      </div>

      <div class="actions">
        <button class="btn-refresh" @click="refresh" :disabled="loading">
          {{ loading ? '...' : 'Cek Status' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ActivateLicense, GetLicenseStatus, ActivateOnline } from '../../wailsjs/go/api/API'

const emit = defineEmits(['activated', 'close'])
defineProps({
  closable: { type: Boolean, default: false }
})
const status = ref<any>(null)
const licenseText = ref('')
const licenseKey = ref('')
const loading = ref(false)
const loadingOnline = ref(false)
const showManual = ref(false)

const statusLabel = computed(() => {
  const s = status.value?.status
  if (!s) return '-'
  if (s === 'ok') return 'Aktif'
  if (s === 'trial') return 'Trial'
  if (s === 'expired') return 'Kedaluwarsa'
  if (s === 'invalid') return 'Tidak Valid'
  if (s === 'tampered') return 'Manipulasi Waktu'
  return 'Tidak Aktif'
})

const loadStatus = async () => {
  status.value = await GetLicenseStatus()
}

const refresh = async () => {
  loading.value = true
  try {
    await loadStatus()
  } finally {
    loading.value = false
  }
}

const activate = async () => {
  if (!licenseText.value) return
  loading.value = true
  try {
    await ActivateLicense(licenseText.value)
    await loadStatus()
    if (status.value?.status === 'ok') {
      emit('activated', status.value)
    }
  } catch (e) {
    alert('Aktivasi gagal: ' + e)
  } finally {
    loading.value = false
  }
}

const activateOnline = async () => {
  if (!licenseKey.value) return
  loadingOnline.value = true
  try {
    await ActivateOnline(licenseKey.value)
    await loadStatus()
    if (status.value?.status === 'ok') {
      alert('Aktivasi Berhasil! Lisensi Anda telah aktif.')
      emit('activated', status.value)
    }
  } catch (e) {
    alert('Aktivasi Online Gagal: ' + e)
  } finally {
    loadingOnline.value = false
  }
}

const copyDeviceId = async () => {
  if (!status.value?.device_id) return
  try {
    await navigator.clipboard.writeText(status.value.device_id)
    alert('Hardware ID berhasil disalin.')
  } catch {
    alert('Gagal menyalin ID.')
  }
}

onMounted(loadStatus)
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(24, 33, 47, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
  padding: 20px;
}
.card {
  background: #fff;
  width: 100%;
  max-width: 480px;
  padding: 32px;
  border-radius: 20px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.3);
  border: 1px solid rgba(24, 33, 47, 0.1);
}
.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.header-row h3 { margin: 0; font-size: 20px; font-weight: 850; color: #18212f; }
.close-btn { border: none; background: transparent; font-size: 24px; cursor: pointer; color: #94a3b8; }

.trial-banner {
  background: #fffaf1;
  border: 1px solid #e8b84e;
  color: #8f6647;
  padding: 10px;
  border-radius: 10px;
  font-weight: 800;
  font-size: 13px;
  margin-bottom: 16px;
  text-align: center;
}

.subtitle { margin: 0 0 20px; color: #526173; font-size: 14px; line-height: 1.5; }

.status-box {
  background: #f8fafc;
  border: 1px solid #e2e8ee;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
}
.status-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
.status-row:last-child { margin-bottom: 0; }
.label { font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; width: 100px; padding-top: 2px; }

.value-badge { font-size: 11px; font-weight: 800; padding: 2px 10px; border-radius: 99px; text-transform: uppercase; }
.value-badge.ok { background: #dcfce7; color: #15803d; }
.value-badge.trial { background: #dbeafe; color: #1d4ed8; }
.value-badge.expired, .value-badge.invalid { background: #fee2e2; color: #b91c1c; }

.device-info { flex: 1; display: flex; gap: 8px; align-items: center; }
.device-id { font-family: monospace; font-size: 11px; background: #fff; border: 1px solid #e2e8ee; padding: 2px 8px; border-radius: 6px; color: #1f7a6a; flex: 1; overflow: hidden; text-overflow: ellipsis; }
.btn-copy { border: none; background: #18212f; color: #fff; padding: 4px 10px; border-radius: 6px; font-weight: 800; cursor: pointer; font-size: 10px; }

.form-group label { font-size: 13px; font-weight: 800; color: #18212f; display: block; margin-bottom: 8px; text-transform: uppercase; }
.input-with-button { display: flex; gap: 10px; }
.input-with-button input { flex: 1; padding: 12px; border: 1.5px solid #e2e8ee; border-radius: 10px; font-size: 14px; outline: none; }
.input-with-button input:focus { border-color: #1f7a6a; }

.input-hint { font-size: 11px; color: #64748b; margin-top: 6px; font-weight: 600; }

.offline-toggle { margin-top: 20px; font-size: 12px; color: #1f7a6a; font-weight: 700; cursor: pointer; }
.manual-area { margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e2e8ee; }
textarea { width: 100%; padding: 12px; border: 1.5px solid #e2e8ee; border-radius: 10px; font-size: 11px; font-family: monospace; resize: vertical; margin-bottom: 10px; }

.actions { display: flex; justify-content: flex-end; margin-top: 24px; border-top: 1px solid #e2e8ee; padding-top: 16px; }

.btn-primary { background: #18212f; color: #fff; border: none; padding: 12px 18px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: all 0.2s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(24, 33, 47, 0.2); }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-secondary { background: #f8fafc; color: #18212f; border: 1.5px solid #e2e8ee; padding: 10px 16px; border-radius: 10px; font-weight: 800; cursor: pointer; width: 100%; }

.btn-refresh { background: none; border: none; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: underline; }
</style>
