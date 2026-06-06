'use client'

import { track } from './AnalyticsTracker'

interface TryOptionButtonProps {
  url: string
  label: string
  title: string
}

export default function TryOptionButton({ url, label, title }: TryOptionButtonProps) {
  const handleClick = () => {
    const isDesktop = title.toLowerCase().includes('desktop') || label.toLowerCase().includes('download')
    const text = (title + ' ' + label).toLowerCase()

    if (isDesktop) {
      if (text.includes('local') || text.includes('lokal')) {
        track('desktop_download_local_click')
      } else if (text.includes('cloud')) {
        track('desktop_download_cloud_click')
      } else {
        track('desktop_download_click')
      }
    } else if (title.toLowerCase().includes('web') || title.toLowerCase().includes('pos')) {
      track('pos_frontend_click')
    }
  }

  return (
    <a href={url} className="button" onClick={handleClick}>
      {label}
    </a>
  )
}
