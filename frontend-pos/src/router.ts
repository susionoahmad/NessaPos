import { createRouter, createWebHistory } from 'vue-router'
import Login from './pages/Login.vue'
import Register from './pages/Register.vue'
import SetupWizard from './pages/SetupWizard.vue'
import POS from './pages/POS.vue'
import Products from './pages/Products.vue'
import Reports from './pages/Reports.vue'
import Settings from './pages/Settings.vue'
import Dashboard from './pages/Dashboard.vue'
import SuperAdmin from './pages/SuperAdmin.vue'

const routes = [
  { path: '/', component: Login, meta: { public: true } },
  { path: '/register', component: Register, meta: { public: true } },
  { path: '/setup-wizard', component: SetupWizard, meta: { roles: ['admin'] } },
  { path: '/pos', component: POS, meta: { roles: ['kasir'] } },
  { path: '/dashboard', component: Dashboard, meta: { roles: ['admin'] } },
  { path: '/products', component: Products, meta: { roles: ['admin'] } },
  { path: '/reports', component: Reports, meta: { roles: ['admin'] } },
  { path: '/settings', component: Settings, meta: { roles: ['admin', 'kasir'] } },
  { path: '/superadmin', component: SuperAdmin, meta: { roles: ['superadmin'] } },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach((to, _from) => {
  const token = localStorage.getItem('auth_token')
  const user = JSON.parse(localStorage.getItem('user') || 'null')

  // Allow public routes (login page)
  if (to.meta.public) {
    // If already logged in, redirect to correct home
    if (token && user) {
      if (user.role === 'superadmin') return '/superadmin'
      if (user.role === 'admin') return '/dashboard'
      return '/pos'
    }
    return true
  }

  // No token → go to login
  if (!token || !user) return '/'

  // Role check
  const allowed = to.meta.roles as string[] | undefined
  if (allowed && !allowed.includes(user.role)) {
    // Redirect to correct home for this role
    if (user.role === 'superadmin') return '/superadmin'
    if (user.role === 'admin') return '/dashboard'
    return '/pos'
  }

  return true
})

export default router
