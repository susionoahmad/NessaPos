import axios from 'axios'

type ApiMode = 'local' | 'online'

const API_MODE = (import.meta.env.VITE_API_MODE || '').trim().toLowerCase() as ApiMode | ''
const LOCAL_API_URL = import.meta.env.VITE_API_URL_LOCAL || 'http://localhost:8000/api'
const ONLINE_API_URL = import.meta.env.VITE_API_URL_ONLINE || 'https://nessapos.kalkulatorin.com'
const LEGACY_API_URL = import.meta.env.VITE_API_URL || ''

export const normalizeApiUrl = (url: string): string => {
  const trimmed = url?.trim() || ''
  if (!trimmed) {
    return normalizeApiUrl(LOCAL_API_URL)
  }
  let normalized = trimmed.replace(/\/+$|^\s+|\s+$/g, '')
  if (!normalized.toLowerCase().endsWith('/api')) {
    normalized = normalized.replace(/\/+$/, '') + '/api'
  }
  return normalized
}

export const getApiUrl = () => {
  if (API_MODE === 'online') {
    return normalizeApiUrl(ONLINE_API_URL)
  }

  if (API_MODE === 'local') {
    return normalizeApiUrl(LOCAL_API_URL)
  }

  return normalizeApiUrl(LEGACY_API_URL || LOCAL_API_URL)
}

const api = axios.create({
  baseURL: getApiUrl(),
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Attach auth token to every request and allow runtime API URL override
api.interceptors.request.use(config => {
  config.baseURL = getApiUrl()
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, error => Promise.reject(error))

// On 401 — clear session and redirect to login (skip for login endpoint itself)
api.interceptors.response.use(
  response => response,
  error => {
    const isLoginCall = error.config?.url?.includes('/login')
    if (error.response?.status === 401 && !isLoginCall) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      localStorage.removeItem('tenant')
      localStorage.removeItem('store_id')
      window.location.replace('/')
    }
    return Promise.reject(error)
  }
)

export default api
