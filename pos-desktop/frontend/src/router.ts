import { createRouter, createWebHashHistory } from 'vue-router'
import Login from './pages/Login.vue'
import POS from './pages/POS.vue'
import Products from './pages/Products.vue'
import Reports from './pages/Reports.vue'
import Settings from './pages/Settings.vue'
import Dashboard from './pages/Dashboard.vue'

const routes = [
  { path: '/', component: Login },
  { path: '/pos', component: POS },
  { path: '/dashboard', component: Dashboard },
  { path: '/products', component: Products },
  { path: '/reports', component: Reports },
  { path: '/settings', component: Settings }
]

const router = createRouter({
  // Wails requires hash history for routing with embedded file serving
  history: createWebHashHistory(),
  routes
})

export default router
