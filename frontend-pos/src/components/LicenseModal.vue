<template>
  <div class="overlay">
    <div class="card">
      <div class="header-row">
        <h2>Aktivasi Lisensi</h2>
        <button v-if="closable" class="close-btn" @click="emit('close')">&times;</button>
      </div>
      <p class="subtitle" v-if="status?.trial_days_left > 0">
        Trial berlaku {{ status.trial_days_left }} hari lagi. Silakan aktifkan lisensi resmi.
      </p>
      <p class="subtitle" v-else>
        Aplikasi membutuhkan lisensi resmi untuk digunakan.
      </p>

      <div class="status">
        <div><strong>Status:</strong> {{ statusLabel }}</div>
        <div v-if="status?.trial_days_left !== undefined">
          <strong>Sisa Trial:</strong> {{ status.trial_days_left }} hari (sampai {{ status.trial_ends_at }})
        </div>
        <div class="device-row">
          <strong>Device ID:</strong> 
          <span class="device-id">{{ status?.device_id }}</span>
          <button class="btn-copy" @click="copyDeviceId" :disabled="!status?.device_id">Copy</button>
        </div>
        <div v-if="status?.reason"><strong>Alasan:</strong> {{ status.reason }}</div>
      </div>

      <div class="form-group">
        <label>License Key</label>
        <div class="input-with-button">
          <input v-model="licenseKey" type="text" placeholder="XXXX-XXXX-XXXX-XXXX" @keyup.enter="activateOnline" />
          <button class="btn-primary" @click="activateOnline" :disabled="loadingOnline || !licenseKey">
            {{ loadingOnline ? 'Aktivasi...' : 'Aktifkan Online' }}
          </button>
        </div>
      </div>

      <div class="form-group" style="margin-top: 20px;">
        <label>Aktivasi Manual (Offline)</label>
        <textarea v-model="licenseText" rows="3" placeholder="Paste license JSON di sini"></textarea>
      </div>

      <div class="actions">
        <button class="btn-secondary" @click="refresh" :disabled="loading">Cek Status</button>
        <button v-if="licenseText" class="btn-secondary" @click="activate" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Aktifkan Manual' }}
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

const statusLabel = computed(() => {
  const s = status.value?.status
  if (!s) return '-'
  if (s === 'ok') return 'Aktif'
  if (s === 'trial') return 'Trial'
  if (s === 'expired') return 'Expired'
  if (s === 'invalid') return 'Invalid'
  if (s === 'tampered') return 'Terdeteksi manipulasi waktu'
  return 'Tidak aktif'
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
      alert('Aktivasi Berhasil!')
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
    alert('Device ID berhasil disalin.')
  } catch {
    alert('Gagal menyalin Device ID.')
  }
}

onMounted(loadStatus)
</script>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
}
.card {
  background: #fff;
  width: 100%;
  max-width: 520px;
  padding: 24px;
  border-radius: 14px;
  box-shadow: 0 20px 30px rgba(0,0,0,0.25);
}
.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.close-btn {
  border: none;
  background: transparent;
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
  color: #94a3b8;
}
h2 { margin: 0; }
.subtitle { margin: 0 0 16px; color: #64748b; font-size: 13px; }
.status {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
  font-size: 12px;
  margin-bottom: 16px;
}
.device-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.device-id {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 11px;
  background: #eef2f7;
  padding: 2px 6px;
  border-radius: 6px;
}
.btn-copy {
  border: none;
  background: #e2e8f0;
  color: #475569;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
  font-size: 11px;
}
.form-group label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: #475569;
  display: block;
  margin-bottom: 6px;
}
.input-with-button {
  display: flex;
  gap: 8px;
}
.input-with-button input {
  flex: 1;
  padding: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
}
textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 11px;
  resize: vertical;
}
.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}
.btn-primary {
  background: #0ea5e9;
  color: #fff;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
.btn-secondary {
  background: #e2e8f0;
  color: #475569;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
</style>
