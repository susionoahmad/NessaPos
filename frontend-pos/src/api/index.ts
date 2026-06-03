import axios from 'axios'

export const DEFAULT_API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const normalizeApiUrl = (url: string) => {
  const trimmed = url?.trim() || ''
  if (!trimmed) {
    return DEFAULT_API_URL
  }
  let normalized = trimmed.replace(/\/+$|^\s+|\s+$/g, '')
  if (!normalized.toLowerCase().endsWith('/api')) {
    normalized = normalized.replace(/\/+$/, '') + '/api'
  }
  return normalized
}

export const getApiUrl = () => {
  return normalizeApiUrl(DEFAULT_API_URL)
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
