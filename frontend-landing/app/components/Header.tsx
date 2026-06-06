'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'

export default function Header() {
  const pathname = usePathname()
  const isHome = pathname === '/'

  return (
    <header className="site-header">
      <Link href="/" className="brand" aria-label="NessaPOS home">
        <span className="brand-mark">N</span>
        <span>NessaPOS</span>
      </Link>
      <nav aria-label="Navigasi utama">
        <Link href={isHome ? "#beranda" : "/"}>Beranda</Link>
        <Link href={isHome ? "#fitur" : "/#fitur"}>Fitur</Link>
        <Link href={isHome ? "#harga" : "/#harga"}>Paket</Link>
        <Link href="/panduan">Panduan</Link>
        <Link href="/kontak">Kontak</Link>
        <Link href="/coba-gratis" className="nav-cta">Coba Gratis</Link>
        <Link href="/blog">Blog</Link>
      </nav>
    </header>
  )
}
