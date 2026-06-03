const DEFAULT_API_URL = 'http://localhost:8000/api'

export function getApiUrl() {
  const rawUrl = process.env.NEXT_PUBLIC_API_URL || DEFAULT_API_URL
  const normalizedUrl = rawUrl.trim().replace(/\/+$/, '')

  return normalizedUrl.toLowerCase().endsWith('/api')
    ? normalizedUrl
    : `${normalizedUrl}/api`
}

export function apiUrl(path: string) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`

  return `${getApiUrl()}${normalizedPath}`
}
