import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: 'NessaPOS - Landing',
  description: 'Sistem POS dan penjualan online untuk bisnis Anda',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body>{children}</body>
    </html>
  )
}
