'use client'

import { useEffect } from 'react'

export function track(eventType: 'landing_page_visit' | 'desktop_download_click' | 'pos_frontend_click' | 'desktop_download_local_click' | 'desktop_download_cloud_click') {
  // Use env variable or try to auto-detect if on production domain
  let apiUrl = process.env.NEXT_PUBLIC_API_URL || '';
  
  if (!apiUrl && typeof window !== 'undefined') {
    const host = window.location.hostname;
    if (host.includes('nessapos.my.id')) {
      apiUrl = 'https://api.nessapos.my.id';
    } else if (host.includes('nessapos.vercel.app')) {
      apiUrl = 'https://api.nessapos.my.id';
    } else {
      apiUrl = 'http://localhost:8000'; // fallback for local dev
    }
  }

  // Ensure /api suffix
  const finalUrl = apiUrl.replace(/\/+$/, '').toLowerCase().endsWith('/api') 
    ? apiUrl 
    : `${apiUrl}/api`;

  console.log(`[Analytics] Tracking ${eventType} to ${finalUrl}...`);

  fetch(`${finalUrl}/analytics/track`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ event_type: eventType }),
  }).catch(err => {
    console.error(`[Analytics] Failed to track ${eventType}:`, err);
  })
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
