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

/**
 * Custom fetch with timeout to prevent long loading states when API is down
 */
export async function fetchWithTimeout(url: string, options: any = {}) {
  const { timeout = 5000, ...fetchOptions } = options;

  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), timeout);

  try {
    const response = await fetch(url, {
      ...fetchOptions,
      signal: controller.signal,
    });
    clearTimeout(id);
    return response;
  } catch (error) {
    clearTimeout(id);
    throw error;
  }
}
