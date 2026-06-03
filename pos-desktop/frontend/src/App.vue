<template>
  <div class="app-layout">
    <aside v-if="isAuthenticated" class="sidebar">
      <div class="logo">
        <h2>NessaPOS</h2>
      </div>
      <nav class="menu">
        <template v-if="userRole === 'admin'">
          <router-link to="/dashboard" class="menu-item"><span>📊</span> Dashboard</router-link>
          <router-link to="/products" class="menu-item"><span>📦</span> Produk</router-link>
          <router-link to="/reports" class="menu-item"><span>📑</span> Laporan</router-link>
          <router-link to="/settings" class="menu-item"><span>⚙️</span> Pengaturan</router-link>
        </template>
        <template v-else>
          <router-link to="/pos" class="menu-item"><span>🖥️</span> POS Kasir</router-link>
          <div class="menu-item" @click="showChangePassword = true"><span>&#128274;</span> Ganti Password</div>
        </template>
      </nav>
      <div class="sidebar-footer">
        <!-- Session Button moved here, only shows if on POS page or for cashier -->
        <button 
          v-if="showSessionButton"
          class="btn-session" 
          :class="{ 'btn-close': store.currentSession }"
          @click="handleSessionAction"
        >
          {{ store.currentSession ? 'Tutup Sesi Kasir' : 'Buka Sesi Kasir' }}
        </button>
        <button class="btn-logout" @click="handleLogout" style="margin-top: 10px;">
          Logout
        </button>
      </div>
    </aside>
    <main class="main-content">
      <router-view></router-view>
    </main>
  </div>

  <ChangePasswordModal 
    v-if="showChangePassword" 
    @close="showChangePassword = false"
  />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './store/auth'
import ChangePasswordModal from './components/ChangePasswordModal.vue'
const store = useAuthStore()
const router = useRouter()
const showChangePassword = ref(false)

const isAuthenticated = computed(() => store.isAuthenticated)
const userRole = computed(() => store.user?.role)

const showSessionButton = computed(() => {
  // Show only if user is NOT admin AND (it's a cashier OR user is currently on POS page)
  return store.user?.role !== 'admin' && (store.user?.role === 'kasir' || router.currentRoute.value.path === '/pos')
})

const handleLogout = () => {
  store.logout()
  router.push('/')
}

const handleSessionAction = () => {
  if (store.currentSession) {
    store.triggerSessionClose++
  } else {
    store.triggerSessionOpen++
  }
}

onMounted(async () => {
  if (store.isAuthenticated) {
    await store.refreshSettings()
  }
})
</script>

<style scoped>
.app-layout {
  display: flex;
  height: 100vh;
  width: 100vw;
  background-color: #2b2b2b;
  overflow: hidden;
}
.sidebar {
  width: 250px;
  background: #1e293b;
  color: white;
  display: flex;
  flex-direction: column;
}
.logo {
  padding: 20px 25px;
  text-align: left;
  border-bottom: 1px solid #334155;
}
.logo h2 {
  margin: 0;
  color: #38bdf8;
  font-weight: 700;
  letter-spacing: 1px;
}
.menu {
  flex: 1;
  padding: 20px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.menu-item {
  padding: 15px 20px;
  color: #cbd5e1;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s;
}
.menu-item:hover, .router-link-active {
  background: #334155;
  color: white;
  border-left: 4px solid #38bdf8;
}
.sidebar-footer {
  padding: 20px;
  border-top: 1px solid #334155;
}
.btn-session {
  width: 100%;
  padding: 12px;
  background: #0ea5e9;
  border: none;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 600;
  font-size: 14px;
}
.btn-session:hover {
  background: #0284c7;
}
.btn-session.btn-close {
  background: #f59e0b;
}
.btn-session.btn-close:hover {
  background: #d97706;
}
.btn-logout {
  width: 100%;
  padding: 12px;
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 600;
  font-size: 14px;
}
.btn-logout:hover {
  background: #ef4444;
  color: white;
}
.main-content {
  flex: 1;
  overflow-y: hidden;
  position: relative;
  display: flex;
  flex-direction: column;
  background-color: white;
}
@media print {
  .sidebar {
    display: none !important;
  }
  .main-content {
    padding: 0 !important;
    overflow: visible !important;
    height: auto !important;
    width: 100% !important;
  }
  .app-layout {
    display: block !important;
    height: auto !important;
    width: 100% !important;
    overflow: visible !important;
    background: white !important;
  }
}
</style>

