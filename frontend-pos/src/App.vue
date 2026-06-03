<template>
  <div class="app-layout">
    <!-- Mobile Hamburger Toggle -->
    <button v-if="isAuthenticated" class="mobile-toggle" @click="isSidebarOpen = true">
      ☰ MENU
    </button>
    
    <!-- Mobile Overlay -->
    <div v-if="isAuthenticated && isSidebarOpen" class="sidebar-overlay" @click="isSidebarOpen = false"></div>

    <aside v-if="isAuthenticated" :class="['sidebar', { 'open': isSidebarOpen }]">
      <div class="logo">
        <h2>NessaPOS</h2>
      </div>
      <nav class="menu">
        <template v-if="userRole === 'superadmin'">
          <router-link to="/superadmin" class="menu-item superadmin-link"><span>⚡</span> SaaS Manager</router-link>
        </template>
        <template v-else-if="userRole === 'admin'">
          <router-link to="/dashboard" class="menu-item"><span>📊</span> Dashboard</router-link>
          <router-link to="/products" class="menu-item"><span>📦</span> Produk</router-link>
          <router-link to="/reports" class="menu-item"><span>📑</span> Laporan</router-link>
          <router-link to="/settings" class="menu-item"><span>⚙️</span> Pengaturan</router-link>
        </template>
        <template v-else>
          <router-link to="/pos" class="menu-item"><span>🖥️</span> POS Kasir</router-link>
          <router-link to="/settings" class="menu-item"><span>⚙️</span> Pengaturan</router-link>
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

  <SubscriptionOverlay />
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './store/auth'
import ChangePasswordModal from './components/ChangePasswordModal.vue'
import SubscriptionOverlay from './components/SubscriptionOverlay.vue'
const store = useAuthStore()
const router = useRouter()
const showChangePassword = ref(false)
const isSidebarOpen = ref(false)

const isAuthenticated = computed(() => store.isAuthenticated)
const userRole = computed(() => store.user?.role)

const showSessionButton = computed(() => {
  // Show only if user is NOT admin AND (it's a cashier OR user is currently on POS page)
  return store.user?.role !== 'admin' && (store.user?.role === 'kasir' || router.currentRoute.value.path === '/pos')
})

const handleLogout = async () => {
  await store.logout()
  router.push('/')
}

const handleSessionAction = () => {
  if (store.currentSession) {
    store.triggerSessionClose++
  } else {
    store.triggerSessionOpen++
  }
}

// Auto-close sidebar on route change for mobile
router.afterEach(() => {
  if (window.innerWidth <= 768) {
    isSidebarOpen.value = false
  }
})

onMounted(async () => {
  if (store.isAuthenticated) {
    await store.refreshTenant()
    await store.refreshSettings()
  }
})
</script>

<style scoped>
.app-layout {
  display: flex;
  height: 100vh;
  width: 100%;
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
.superadmin-link {
  background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(239,68,68,0.1));
  color: #fbbf24 !important;
  border-left: 3px solid #f59e0b;
  font-weight: 700;
}
.superadmin-link:hover, .superadmin-link.router-link-active {
  background: linear-gradient(135deg, rgba(245,158,11,0.25), rgba(239,68,68,0.15));
  border-left: 4px solid #f59e0b;
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
  overflow-y: auto;
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

/* Mobile Responsive */
.mobile-toggle {
  display: none;
  position: absolute;
  top: 15px;
  left: 15px;
  z-index: 90;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px 12px;
  font-weight: 800;
  color: #0f172a;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  cursor: pointer;
}

.sidebar-overlay {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(2px);
  z-index: 99;
}

@media (max-width: 768px) {
  .mobile-toggle {
    display: block;
  }
  .sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    height: 100vh;
    z-index: 100;
    width: 260px;
    transition: left 0.3s ease;
    box-shadow: 10px 0 30px rgba(0,0,0,0.5);
  }
  .sidebar.open {
    left: 0;
  }
  .sidebar-overlay {
    display: block;
  }
  .main-content {
    padding-top: 60px; /* Space for the hamburger toggle */
  }
}
</style>

