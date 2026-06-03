<template>
  <div class="login-wrapper">
    <div class="login-card">
      <!-- Logo / Title -->
      <div class="login-brand">
        <div class="brand-icon">N</div>
        <h1 class="title">NessaPOS</h1>
        <p class="subtitle">Aplikasi Kasir Desktop Offline</p>
      </div>

      <div v-if="licenseStatus?.status === 'trial'" class="trial">
        🎁 Mode Trial Aktif: {{ licenseStatus.trial_days_left }} hari lagi (sampai {{ licenseStatus.trial_ends_at }})
      </div>
      
      <div v-if="errorMsg" class="error">{{ errorMsg }}</div>
      
      <div class="form-group">
        <label>Username</label>
        <input v-model="username" type="text" placeholder="admin or kasir" @keyup.enter="handleLogin" :disabled="licenseBlocked" />
      </div>
      
      <div class="form-group">
        <label>Password</label>
        <input v-model="password" type="password" placeholder="••••••••" @keyup.enter="handleLogin" :disabled="licenseBlocked" />
      </div>
      
      <button class="btn-primary" @click="handleLogin" :disabled="loading || licenseBlocked">
        {{ loading ? 'Masuk...' : 'Sign In' }}
      </button>

      <div class="footer-note" v-if="licenseBlocked">
        <p>Aplikasi membutuhkan lisensi untuk digunakan.</p>
      </div>
    </div>

    <LicenseModal v-if="licenseBlocked" @activated="handleActivated" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
import { GetLicenseStatus } from '../../wailsjs/go/api/API'
import LicenseModal from '../components/LicenseModal.vue'

const username = ref('')
const password = ref('')
const errorMsg = ref('')
const loading = ref(false)
const licenseStatus = ref<any>(null)

const licenseBlocked = computed(() => {
  const s = licenseStatus.value?.status
  return s && s !== 'ok' && s !== 'trial'
})

const router = useRouter()
const auth = useAuthStore()

const loadLicense = async () => {
  try {
    licenseStatus.value = await GetLicenseStatus()
  } catch (e) {
    console.error('License status error', e)
  }
}

const handleActivated = (status: any) => {
  licenseStatus.value = status
}

const handleLogin = async () => {
  if (licenseBlocked.value) {
    errorMsg.value = 'Lisensi dibutuhkan untuk mengakses sistem.'
    return
  }
  errorMsg.value = ''
  loading.value = true
  const success = await auth.authenticate(username.value, password.value)
  loading.value = false
  
  if (success) {
    if (auth.user?.role === 'admin') {
      router.push('/dashboard')
    } else {
      router.push('/pos')
    }
  } else {
    errorMsg.value = 'Username atau Password salah'
  }
}

onMounted(loadLicense)
</script>

<style scoped>
.login-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  width: 100%;
  background:
    linear-gradient(135deg, rgba(24, 33, 47, 0.04), transparent 32%),
    linear-gradient(315deg, rgba(20, 151, 128, 0.08), transparent 36%),
    #f7f3ec;
}

.login-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 25px 70px rgba(24, 33, 47, 0.15);
  border: 1px solid rgba(24, 33, 47, 0.1);
  width: 100%;
  max-width: 400px;
}

.login-brand {
  text-align: center;
  margin-bottom: 28px;
}

.brand-icon {
  display: inline-grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  color: #ffffff;
  background: #1f7a6a;
  font-size: 24px;
  margin: 0 auto 12px;
}

.title {
  font-size: 28px;
  font-weight: 850;
  color: #18212f;
  margin: 0;
  letter-spacing: -0.5px;
}

.subtitle {
  color: #526173;
  margin: 8px 0 0;
  font-size: 14px;
  line-height: 1.5;
}

.form-group { margin-bottom: 20px; }
.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 800;
  color: #18212f;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.form-group input {
  width: 100%;
  padding: 14px 16px;
  border: 1.5px solid rgba(24, 33, 47, 0.12);
  border-radius: 12px;
  font-size: 15px;
  outline: none;
  transition: all 0.2s;
  box-sizing: border-box;
  background: rgba(255, 255, 255, 0.5);
  color: #18212f;
}

.form-group input:focus {
  border-color: #1f7a6a;
  background: white;
  box-shadow: 0 0 0 4px rgba(31, 122, 106, 0.1);
}

.btn-primary {
  width: 100%;
  padding: 14px;
  background: #18212f;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 8px;
  border: 1px solid #18212f;
}

.btn-primary:hover {
  background: #243146;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(24, 33, 47, 0.2);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.error {
  color: #e15e4d;
  background: #fff5f4;
  border: 1px solid rgba(225, 94, 77, 0.2);
  padding: 12px 16px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-size: 14px;
  font-weight: 700;
}

.trial {
  background: #fffaf1;
  border: 1px solid #e8b84e;
  color: #8f6647;
  padding: 12px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-size: 13px;
  font-weight: 700;
  text-align: center;
}

.footer-note {
  text-align: center;
  margin-top: 24px;
  font-size: 13px;
  color: #e15e4d;
  font-weight: 700;
}
</style>
