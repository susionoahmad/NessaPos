import { defineStore } from 'pinia'
import { ref } from 'vue'
import { GetSettings, Login } from '../../wailsjs/go/api/API'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<any>(null)
  const currentSession = ref<any>(null)
  const vaultBalance = ref(0)
  const cashDrawerEnabled = ref(true)
  const isAuthenticated = ref(false)

  const authenticate = async (username: string, pass: string) => {
    try {
      const res = await Login(username, pass)
      user.value = res
      isAuthenticated.value = true
      return true
    } catch (e) {
      console.error("Login failed", e)
      return false
    }
  }

  const logout = () => {
    user.value = null
    currentSession.value = null
    isAuthenticated.value = false
  }

  const refreshSettings = async () => {
    try {
      const settings = await GetSettings()
      cashDrawerEnabled.value = settings?.cash_drawer_enabled !== false
    } catch (e) {
      console.error("Refresh settings failed", e)
    }
  }

  // Communication refs
  const triggerSessionOpen = ref(0)
  const triggerSessionClose = ref(0)

  return { 
    user, currentSession, vaultBalance, cashDrawerEnabled, isAuthenticated, 
    authenticate, logout, refreshSettings,
    triggerSessionOpen, triggerSessionClose
  }
})
