import type { Metadata } from 'next'
import './globals.css'

const DEFAULT_SUPERADMIN_PHONE = '6281392156513'

export const metadata: Metadata = {
  title: 'NessaPOS - Landing',
  description: 'Sistem POS dan penjualan online untuk bisnis Anda',
}

function getWhatsAppLink() {
  const phone = (process.env.NEXT_PUBLIC_SUPERADMIN_PHONE || DEFAULT_SUPERADMIN_PHONE)
    .replace(/[^\d]/g, '')
  const message = encodeURIComponent(
    'Halo NessaPOS, saya ingin konsultasi dan bertanya tentang aplikasi kasir.'
  )

  return `https://wa.me/${phone}?text=${message}`
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body>
        {children}
        <a
          className="floating-wa"
          href={getWhatsAppLink()}
          target="_blank"
          rel="noopener noreferrer"
          aria-label="Hubungi Kami melalui WhatsApp"
        >
          <span className="floating-wa-icon" aria-hidden="true">WA</span>
          <span>Hubungi Kami</span>
        </a>
      </body>
    </html>
  )
}
