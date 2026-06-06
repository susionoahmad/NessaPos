'use client'

import { useEffect } from 'react'

export function track(eventType: 'landing_page_visit' | 'desktop_download_click' | 'pos_frontend_click' | 'desktop_download_local_click' | 'desktop_download_cloud_click') {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'
  fetch(`${apiUrl}/analytics/track`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ event_type: eventType }),
  }).catch(err => console.error('Failed to track event:', err))
}

export default function AnalyticsTracker() {
  useEffect(() => {
    // Only track home page visit for now
    if (window.location.pathname === '/') {
      track('landing_page_visit')
    }
  }, [])

  return null
}
