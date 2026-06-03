<template>
  <div class="login-wrapper">
    <div class="login-card">
      <!-- Logo / Title -->
      <div class="login-brand" @click="handleLogoClick">
        <div class="brand-icon">N</div>
        <h1 class="title">NessaPOS</h1>
        <p class="subtitle">Masuk ke akun Anda untuk melanjutkan</p>
      </div>

      <div v-if="errorMsg" class="error">{{ errorMsg }}</div>

      <!-- Store ID (hidden for superadmin mode) -->
      <div v-if="!isSuperAdminMode" class="form-group">
        <label>Store ID</label>
        <input v-model="storeId" type="text" placeholder="e.g. toko-utama" @keyup.enter="handleLogin" />
      </div>
      <div v-else class="superadmin-badge">
        <span>⚡</span> Mode Super Admin
      </div>

      <div class="form-group">
        <label>Username</label>
        <input v-model="username" type="text" placeholder="username" @keyup.enter="handleLogin" />
      </div>

      <div class="form-group">
        <label>Password</label>
        <input v-model="password" type="password" placeholder="••••••••" @keyup.enter="handleLogin" />
      </div>

      <button class="btn-primary" @click="handleLogin" :disabled="loading">
        {{ loading ? 'Masuk...' : 'Sign In' }}
      </button>

      <!-- Toggle Superadmin Mode (Hidden by default) -->
      <div class="footer-links">
        <div v-if="showSaOption" class="sa-toggle" @click="isSuperAdminMode = !isSuperAdminMode">
          {{ isSuperAdminMode ? '← Kembali ke Login Toko' : 'Login sebagai Super Admin' }}
        </div>
        <div v-if="!isSuperAdminMode" class="register-section">
          <p>Belum punya toko?</p>
          <router-link to="/register" class="btn-register">Daftar Toko Baru</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const storeId = ref(localStorage.getItem('store_id') || '')
const username = ref('')
const password = ref('')
const errorMsg = ref('')
const loading = ref(false)
const isSuperAdminMode = ref(false)
const showSaOption = ref(false)
const logoClicks = ref(0)

const router = useRouter()
const auth = useAuthStore()

const handleLogoClick = () => {
  logoClicks.value++
  if (logoClicks.value >= 5) {
    showSaOption.value = !showSaOption.value
    logoClicks.value = 0
  }
}

const handleLogin = async () => {
  if (!isSuperAdminMode.value && !storeId.value) {
    errorMsg.value = 'Store ID harus diisi.'
    return
  }
  if (!username.value || !password.value) {
    errorMsg.value = 'Username dan Password harus diisi.'
    return
  }

  errorMsg.value = ''
  loading.value = true

  const loginStoreId = isSuperAdminMode.value ? 'superadmin' : storeId.value
  const result = await auth.authenticate(username.value, password.value, loginStoreId) as any
  loading.value = false

  if (result.success) {
    const role = auth.user?.role
    if (role === 'superadmin') {
      router.push('/superadmin')
    } else if (role === 'admin') {
      router.push('/dashboard')
    } else {
      router.push('/pos')
    }
  } else {
    errorMsg.value = result.message || 'Username atau Password salah'
  }
}

onMounted(() => {
  if (auth.isAuthenticated) {
    const role = auth.user?.role
    if (role === 'superadmin') router.push('/superadmin')
    else if (role === 'admin') router.push('/dashboard')
    else router.push('/pos')
  }
})
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
  cursor: pointer;
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
  margin-bottom: 12px;
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

.superadmin-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fffaf1;
  border: 1px solid #e8b84e;
  color: #8f6647;
  padding: 12px 16px;
  border-radius: 12px;
  font-weight: 800;
  font-size: 13px;
  margin-bottom: 20px;
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

.footer-links {
  text-align: center;
  margin-top: 28px;
}

.sa-toggle {
  margin-bottom: 24px;
  font-size: 12px;
  color: #526173;
  cursor: pointer;
  background: rgba(24, 33, 47, 0.05);
  padding: 8px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.2s;
}

.sa-toggle:hover {
  background: rgba(24, 33, 47, 0.1);
  color: #18212f;
}

.register-section {
  border-top: 1px solid rgba(24, 33, 47, 0.08);
  padding-top: 24px;
}

.register-section p {
  font-size: 14px;
  color: #526173;
  margin-bottom: 14px;
}

.btn-register {
  display: block;
  text-decoration: none;
  padding: 12px;
  border: 1.5px solid rgba(24, 33, 47, 0.12);
  color: #18212f;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 800;
  transition: all 0.2s;
}

.btn-register:hover {
  border-color: #1f7a6a;
  color: #1f7a6a;
  background: rgba(31, 122, 106, 0.04);
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
</style>
