import { defineStore } from 'pinia'
import { ref } from 'vue'
import api, { getApiUrl } from '../api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any>(JSON.parse(localStorage.getItem('user') || 'null'))
  const tenant = ref<any>(JSON.parse(localStorage.getItem('tenant') || 'null'))
  const currentSession = ref<any>(null)
  const vaultBalance = ref(0)
  const cashDrawerEnabled = ref(true)
  const isAuthenticated = ref(!!localStorage.getItem('auth_token'))

  const clearSession = () => {
    user.value = null
    tenant.value = null
    currentSession.value = null
    isAuthenticated.value = false

    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    localStorage.removeItem('tenant')
  }

  const authenticate = async (username: string, pass: string, storeId: string) => {
    try {
      const res = await api.post('/login', {
        username: username,
        password: pass,
        store_id: storeId
      })
      
      const { access_token, user: userData, tenant: tenantData } = res.data
      
      localStorage.setItem('auth_token', access_token)
      localStorage.setItem('user', JSON.stringify(userData))
      localStorage.setItem('tenant', JSON.stringify(tenantData))
      localStorage.setItem('store_id', storeId)

      user.value = userData
      tenant.value = tenantData
      isAuthenticated.value = true
      return { success: true }
    } catch (e: any) {
      console.error("Login failed", e)
      const apiUrl = getApiUrl()
      const fallbackMessage = e.request
        ? `Tidak bisa terhubung ke ${apiUrl}. Periksa: backend aktif, URL API benar (rebuild app desktop jika perlu), firewall/SSL, dan CORS di server.`
        : 'Terjadi kesalahan pada server'

      return { 
        success: false, 
        message: e.response?.data?.message || fallbackMessage
      }
    }
  }

  const setAuth = (userData: any, tenantData: any, token: string) => {
    localStorage.setItem('auth_token', token)
    localStorage.setItem('user', JSON.stringify(userData))
    localStorage.setItem('tenant', JSON.stringify(tenantData))
    
    user.value = userData
    tenant.value = tenantData
    isAuthenticated.value = true
  }

  const refreshTenant = async () => {
    if (!isAuthenticated.value || !localStorage.getItem('auth_token')) {
      return
    }

    try {
      const res = await api.get('/tenant/info')
      if (res.data) {
        tenant.value = res.data
        localStorage.setItem('tenant', JSON.stringify(res.data))
      }
    } catch (e: any) {
      if (e.response?.status === 401) {
        clearSession()
        return
      }
      console.error("Refresh tenant failed", e)
    }
  }

  const refreshSettings = async () => {
    if (!isAuthenticated.value || !localStorage.getItem('auth_token')) {
      return
    }

    try {
      const res = await api.get('/settings')
      cashDrawerEnabled.value = res.data?.cash_drawer_enabled !== false
    } catch (e: any) {
      if (e.response?.status === 401) {
        clearSession()
        return
      }
      console.error("Refresh settings failed", e)
    }
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } catch (e) {
      console.error("Logout error", e)
    }
    
    clearSession()
  }

  // Communication refs
  const triggerSessionOpen = ref(0)
  const triggerSessionClose = ref(0)

  return { 
    user, tenant, currentSession, vaultBalance, cashDrawerEnabled, isAuthenticated, 
    authenticate, setAuth, logout, refreshTenant, refreshSettings, clearSession,
    triggerSessionOpen, triggerSessionClose
  }
})
