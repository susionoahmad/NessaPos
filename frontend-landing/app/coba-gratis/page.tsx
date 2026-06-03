import Link from 'next/link'
import { apiUrl } from '../lib/api'

async function getTryPage() {
  try {
    const res = await fetch(apiUrl('/pages/coba-gratis'), {
      cache: 'no-store',
    })
    if (!res.ok) throw new Error('Failed to fetch coba-gratis page')
    return res.json()
  } catch (error) {
    console.error('Failed to fetch try page data', error)
    return null
  }
}

export async function generateMetadata() {
  const pageData = await getTryPage()
  return {
    title: pageData?.meta_title || 'Coba Gratis NessaPOS',
    description: pageData?.meta_description || 'Pilih cara mencoba NessaPOS.'
  }
}

export default async function TryFreePage() {
  const pageData = await getTryPage()

  const getSectionContent = (sectionName: string, key: string) => {
    const section = pageData?.sections?.find((s: any) => s.name === sectionName)
    return section?.contents?.find((c: any) => c.key === key)?.value
  }

  const heroTitle = getSectionContent('hero', 'title') || 'Pilih cara mencoba NessaPOS'
  const heroSubtitle = getSectionContent('hero', 'subtitle') || 'Mulai dari browser atau download aplikasi desktop.'

  const dynamicOptions = (() => {
    try {
      const items = getSectionContent('options', 'items')
      if (!items) return []
      return typeof items === 'string' ? JSON.parse(items) : items
    } catch {
      return []
    }
  })()

  return (
    <main className="page-shell">
      <header className="site-header">
        <Link href="/" className="brand" aria-label="NessaPOS home">
          <span className="brand-mark">N</span>
          <span>NessaPOS</span>
        </Link>
        <nav aria-label="Navigasi coba gratis">
          <Link href="/">Beranda</Link>
          <Link href="/blog">Blog</Link>
        </nav>
      </header>

      <section className="try-hero">
        <span className="eyebrow">Coba gratis</span>
        <h1>{heroTitle}</h1>
        <p>{heroSubtitle}</p>
      </section>

      <section className="try-options" aria-label="Pilihan coba gratis NessaPOS">
        {dynamicOptions.map((option: any) => (
          <article key={option.title}>
            <h2>{option.title}</h2>
            <p>{option.description}</p>
            {option.note && <span>{option.note}</span>}
            <div className="option-actions">
              <a href={option.link_url} className="button">
                {option.link_label}
              </a>
            </div>
          </article>
        ))}
      </section>

      <section className="try-note">
        <h2>Bingung mulai dari mana?</h2>
        <p>
          Kami merekomendasikan <strong>Buka POS Web</strong> untuk mencoba fitur dasar secara instan. Jika Anda ingin mencoba integrasi printer thermal dan laci kasir di toko, silakan unduh versi <strong>Desktop</strong>. Hubungi tim support kami jika Anda memerlukan bantuan instalasi atau konsultasi perangkat.
        </p>
      </section>
    </main>
  )
}
