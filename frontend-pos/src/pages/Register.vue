<template>
  <div class="register-wrapper">
    <div class="register-card">
      <div class="brand">
        <div class="brand-icon">N</div>
        <h1 class="title">Buka Toko Baru</h1>
        <p class="subtitle">Mulai bisnis Anda dengan NessaPOS hari ini</p>
      </div>

      <div v-if="errorMsg" class="alert error">{{ errorMsg }}</div>
      <div v-if="successMsg" class="alert success">{{ successMsg }}</div>

      <form @submit.prevent="handleRegister">
        <div class="form-grid">
          <div class="form-group">
            <label>Nama Toko</label>
            <input v-model="form.store_name" type="text" placeholder="e.g. Kedai Kopi Mantap" required />
          </div>

          <div class="form-group">
            <label>ID Toko (Store ID)</label>
            <div class="input-with-prefix">
              <span class="prefix">pos.id/</span>
              <input v-model="form.store_slug" type="text" placeholder="kedai-kopi" required />
            </div>
            <p class="hint">Digunakan untuk login ke toko Anda</p>
          </div>

          <div class="divider"><span>Data Pemilik</span></div>

          <div class="form-group">
            <label>Username Admin</label>
            <input v-model="form.username" type="text" placeholder="username" required />
          </div>

          <div class="form-group">
            <label>Password</label>
            <input v-model="form.password" type="password" placeholder="••••••••" required />
          </div>
        </div>

        <div class="trial-badge">
          🎁 Termasuk **7 Hari Trial Gratis** semua fitur
        </div>

        <button class="btn-primary" :disabled="loading">
          {{ loading ? 'Mendaftarkan...' : 'Daftar Sekarang' }}
        </button>

        <div class="footer-links">
          Sudah punya toko? <router-link to="/">Masuk di sini</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const router = useRouter()
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const form = reactive({
  store_name: '',
  store_slug: '',
  username: '',
  password: ''
})

const handleRegister = async () => {
  errorMsg.value = ''
  successMsg.value = ''
  loading.value = true

  try {
    const res = await api.post('/register', form)
    successMsg.value = 'Pendaftaran berhasil! Mengalihkan...'
    
    // Instant login and update store state
    auth.setAuth(res.data.user, res.data.tenant, res.data.access_token)
    localStorage.setItem('store_id', res.data.tenant.slug)

    setTimeout(() => {
      router.push('/setup-wizard')
    }, 1500)
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message || 'Pendaftaran gagal. Silakan coba lagi.'
    loading.value = false
  }
}
</script>

<style scoped>
.register-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 40px 20px;
  background:
    linear-gradient(135deg, rgba(24, 33, 47, 0.04), transparent 32%),
    linear-gradient(315deg, rgba(20, 151, 128, 0.08), transparent 36%),
    #f7f3ec;
}

.register-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  padding: 32px 40px;
  border-radius: 24px;
  box-shadow: 0 25px 70px rgba(24, 33, 47, 0.15);
  border: 1px solid rgba(24, 33, 47, 0.1);
  width: 100%;
  max-width: 480px;
}

.brand {
  text-align: center;
  margin-bottom: 28px;
}

.brand-icon {
  display: inline-grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  color: #ffffff;
  background: #1f7a6a;
  font-size: 22px;
  margin: 0 auto 12px;
}

.title {
  font-size: 26px;
  font-weight: 850;
  color: #18212f;
  margin: 0;
  letter-spacing: -0.5px;
}

.subtitle {
  color: #526173;
  font-size: 14px;
  margin-top: 6px;
  line-height: 1.5;
}

.alert {
  padding: 12px 16px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-size: 14px;
  font-weight: 700;
}

.error {
  background: #fff5f4;
  border: 1px solid rgba(225, 94, 77, 0.2);
  color: #e15e4d;
}

.success {
  background: #f8faf9;
  border: 1px solid rgba(31, 122, 106, 0.2);
  color: #1f7a6a;
}

.form-grid { display: flex; flex-direction: column; gap: 18px; }

.divider {
  display: flex;
  align-items: center;
  margin: 12px 0;
}

.divider::before, .divider::after {
  content: "";
  flex: 1;
  height: 1px;
  background: rgba(24, 33, 47, 0.08);
}

.divider span {
  padding: 0 14px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  color: #526173;
  letter-spacing: 0.1em;
}

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
  padding: 12px 16px;
  border: 1.5px solid rgba(24, 33, 47, 0.12);
  border-radius: 12px;
  font-size: 15px;
  transition: all 0.2s;
  box-sizing: border-box;
  background: rgba(255, 255, 255, 0.5);
  color: #18212f;
  outline: none;
}

.form-group input:focus {
  border-color: #1f7a6a;
  background: white;
  box-shadow: 0 0 0 4px rgba(31, 122, 106, 0.1);
}

.input-with-prefix {
  display: flex;
  align-items: center;
  background: rgba(24, 33, 47, 0.04);
  border: 1.5px solid rgba(24, 33, 47, 0.12);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s;
}

.input-with-prefix:focus-within {
  border-color: #1f7a6a;
  background: white;
  box-shadow: 0 0 0 4px rgba(31, 122, 106, 0.1);
}

.input-with-prefix .prefix {
  padding-left: 16px;
  color: #526173;
  font-size: 14px;
  font-weight: 700;
}

.input-with-prefix input {
  border: none;
  background: transparent;
  box-shadow: none;
}

.hint {
  margin-top: 6px;
  font-size: 11px;
  color: #526173;
  font-weight: 600;
}

.trial-badge {
  background: #fffaf1;
  color: #8f6647;
  border: 1px solid #e8b84e;
  padding: 12px;
  border-radius: 12px;
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  margin: 20px 0;
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
}

.footer-links {
  text-align: center;
  margin-top: 28px;
  font-size: 14px;
  color: #526173;
  font-weight: 600;
}

.footer-links a {
  color: #1f7a6a;
  font-weight: 800;
  text-decoration: none;
}

.footer-links a:hover {
  text-decoration: underline;
}
</style>
