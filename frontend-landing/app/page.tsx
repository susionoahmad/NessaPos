import Link from 'next/link'
import { apiUrl } from './lib/api'

export const dynamic = 'force-dynamic'

const features = [
  {
    title: 'Kasir Lebih Cepat',
    body: 'Proses transaksi, cari produk, atur keranjang, dan selesaikan pembayaran tanpa alur yang membingungkan.'
  },
  {
    title: 'Stok Lebih Rapi',
    body: 'Produk, harga, kategori, barcode, dan perubahan stok bisa dipantau dari satu tempat.'
  },
  {
    title: 'Laporan Jelas',
    body: 'Pantau penjualan harian, produk laris, pembayaran, dan performa kasir dengan ringkasan yang mudah dibaca.'
  },
  {
    title: 'Print Thermal Tanpa Ribet',
    body: 'Cetak struk ke printer thermal langsung dari alur kasir, tanpa bolak-balik membuka dialog browser.'
  },
  {
    title: 'Cocok untuk Banyak Toko',
    body: 'Kelola beberapa toko atau cabang dengan akses admin dan kasir yang lebih tertata.'
  },
  {
    title: 'Browser dan Desktop',
    body: 'Bisa dipakai lewat browser untuk akses praktis, atau desktop untuk kebutuhan kasir dan print yang lebih stabil.'
  }
]

const plans = [
  {
    name: 'Starter',
    price: 'Gratis',
    details: 'Coba fitur kasir, produk, stok, dan laporan sebelum mulai berlangganan.'
  },
  {
    name: 'Business',
    price: 'Bulanan',
    details: 'Untuk toko aktif yang butuh kasir cepat, laporan rutin, dan cetak struk thermal.'
  },
  {
    name: 'Scale',
    price: 'Tahunan',
    details: 'Untuk bisnis yang mulai mengelola banyak toko, cabang, atau tim kasir.'
  }
]

async function getHomePage() {
  try {
    const res = await fetch(apiUrl('/pages/home'), {
      cache: 'no-store',
    })
    if (!res.ok) throw new Error('Failed to fetch home page')
    return res.json()
  } catch (error) {
    console.error('Failed to fetch page data', error)
    return null
  }
}

async function getPackages() {
  try {
    const res = await fetch(apiUrl('/packages'), {
      cache: 'no-store',
    })
    if (!res.ok) throw new Error('Failed to fetch packages')
    return res.json()
  } catch (error) {
    console.error('Failed to fetch packages', error)
    return []
  }
}

export async function generateMetadata() {
  const pageData = await getHomePage()
  return {
    title: pageData?.meta_title || 'NessaPOS - Aplikasi Kasir Online untuk UMKM',
    description: pageData?.meta_description || 'POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.'
  }
}

export default async function Home() {
  const pageData = await getHomePage()

  const getSectionContent = (sectionName: string, key: string) => {
    if (!pageData) return null
    const section = pageData.sections.find((s: any) => s.name === sectionName)
    if (!section) return null
    const content = section.contents.find((c: any) => c.key === key)
    return content ? content.value : null
  }

  const dynamicFeatures = pageData ? (() => {
    try {
      const featuresRaw = getSectionContent('features', 'items')
      if (!featuresRaw) return features
      return typeof featuresRaw === 'string' ? JSON.parse(featuresRaw) : featuresRaw
    } catch {
      return features
    }
  })() : features

  const dynamicButtons = pageData ? (() => {
    try {
      const buttonsRaw = getSectionContent('hero', 'buttons')
      if (!buttonsRaw) return []
      return typeof buttonsRaw === 'string' ? JSON.parse(buttonsRaw) : buttonsRaw
    } catch {
      return []
    }
  })() : []

  const apiPackages = await getPackages()
  const dynamicPlans = apiPackages.length > 0 ? apiPackages.map((p: any) => ({
    name: p.name,
    price: p.slug === 'lifetime' ? `Rp ${p.price.toLocaleString('id-ID')}` : `Rp ${p.price.toLocaleString('id-ID')} / ${p.slug === 'monthly' ? 'bln' : 'thn'}`,
    details: (p.features || []).join(', ')
  })) : plans

  return (
    <main className="page-shell">
      <header className="site-header">
        <Link href="/" className="brand" aria-label="NessaPOS home">
          <span className="brand-mark">N</span>
          <span>NessaPOS</span>
        </Link>
        <nav aria-label="Navigasi utama">
          <a href="#fitur">Fitur</a>
          <a href="#alur">Alur</a>
          <a href="#harga">Paket</a>
          <Link href="/panduan">Panduan</Link>
          <Link href="/coba-gratis">Coba Gratis</Link>
          <Link href="/blog">Blog</Link>
        </nav>
      </header>

      <section className="hero" id="beranda">
        <div className="hero-copy">
          <span className="eyebrow">POS SaaS untuk toko modern</span>
          <h1>{getSectionContent('hero', 'title') || 'NessaPOS'}</h1>
          <p>
            {getSectionContent('hero', 'subtitle') || 'Sistem kasir yang membantu toko melayani pembeli lebih cepat, menjaga stok tetap rapi, mencetak struk thermal tanpa ribet, dan memantau penjualan dari mana saja.'}
          </p>
          <p className="architecture-note">
            Dibangun dengan arsitektur modern berbasis web, API, dan desktop untuk performa maksimal.
          </p>
          <div className="hero-actions">
            {dynamicButtons.length > 0 ? (
              dynamicButtons.map((button: any, index: number) => {
                const isExternal = button.link?.startsWith('http')
                const isAnchor = button.link?.startsWith('#')
                const buttonClass = button.type === 'primary' ? 'button' : 'button secondary'
                
                if (isExternal) {
                  return (
                    <a key={index} href={button.link} className={buttonClass} target="_blank" rel="noopener noreferrer">
                      {button.text}
                    </a>
                  )
                } else if (isAnchor) {
                  return (
                    <a key={index} href={button.link} className={buttonClass}>
                      {button.text}
                    </a>
                  )
                } else {
                  return (
                    <Link key={index} href={button.link} className={buttonClass}>
                      {button.text}
                    </Link>
                  )
                }
              })
            ) : (
              <>
                <Link href="/coba-gratis" className="button">Coba Gratis</Link>
                <a href="#fitur" className="button secondary">Lihat Fitur</a>
              </>
            )}
          </div>
          <dl className="hero-stats" aria-label="Ringkasan fitur NessaPOS">
            <div>
              <dt>Cloud</dt>
              <dd>Data aman</dd>
            </div>
            <div>
              <dt>2 Mode</dt>
              <dd>Browser & desktop</dd>
            </div>
            <div>
              <dt>Thermal</dt>
              <dd>Print struk cepat</dd>
            </div>
          </dl>
        </div>

        <div className="product-visual" aria-label="Pratinjau dashboard POS NessaPOS">
          <div className="visual-toolbar">
            <span></span>
            <span></span>
            <span></span>
            <strong>Kasir Aktif</strong>
          </div>
          <div className="visual-grid">
            <div className="visual-sidebar">
              <span className="active"></span>
              <span></span>
              <span></span>
              <span></span>
            </div>
            <div className="visual-products">
              <div className="visual-summary">
                <p>Penjualan hari ini</p>
                <strong>Rp 4.280.000</strong>
              </div>
              <div className="product-row">
                <span>Kopi Susu</span>
                <b>24</b>
              </div>
              <div className="product-row accent">
                <span>Roti Coklat</span>
                <b>18</b>
              </div>
              <div className="product-row">
                <span>Teh Botol</span>
                <b>31</b>
              </div>
            </div>
            <div className="visual-receipt">
              <h2>Struk</h2>
              <p>#TRX-0428</p>
              <div></div>
              <div></div>
              <strong>Rp 86.000</strong>
              <button type="button">Print</button>
            </div>
          </div>
        </div>
      </section>

      <section className="section" id="fitur">
        <div className="section-heading">
          <span className="eyebrow">Fitur inti</span>
          <h2>{getSectionContent('features', 'title') || 'Dibuat untuk toko yang ingin kerja lebih ringan'}</h2>
          <p>{getSectionContent('features', 'subtitle') || 'NessaPOS membantu kasir, admin, dan pemilik toko bekerja dengan alur yang sederhana.'}</p>
        </div>
        <div className="features">
          {dynamicFeatures.map((feature: any) => (
            <article key={feature.title}>
              <h3>{feature.title}</h3>
              <p>{feature.body}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="section workflow" id="alur">
        <div className="section-heading">
          <span className="eyebrow">Alur kerja</span>
          <h2>Dari buka toko sampai tutup kasir</h2>
        </div>
        <ol className="steps">
          <li>
            <span>01</span>
            <h3>Siapkan toko</h3>
            <p>Masukkan data toko, akun admin, dan akun kasir sesuai kebutuhan operasional.</p>
          </li>
          <li>
            <span>02</span>
            <h3>Masukkan produk</h3>
            <p>Atur produk, harga, kategori, barcode, dan stok awal agar siap dijual.</p>
          </li>
          <li>
            <span>03</span>
            <h3>Layani transaksi</h3>
            <p>Kasir memproses belanja pelanggan dan mencetak struk thermal saat pembayaran selesai.</p>
          </li>
          <li>
            <span>04</span>
            <h3>Pantau laporan</h3>
            <p>Pemilik toko melihat ringkasan penjualan, stok, dan aktivitas kasir secara berkala.</p>
          </li>
        </ol>
      </section>

      <section className="section pricing" id="harga">
        <div className="section-heading">
          <span className="eyebrow">Paket</span>
          <h2>{getSectionContent('pricing', 'title') || 'Mulai dari toko pertama, lanjut saat bisnis tumbuh'}</h2>
          <p>Pilih paket sesuai kebutuhan kasir, jumlah toko, dan skala operasional Anda.</p>
        </div>
        <div className="plans">
          {dynamicPlans.map((plan: any) => (
            <article key={plan.name}>
              <p>{plan.name}</p>
              <h3>{plan.price}</h3>
              <span>{plan.details}</span>
            </article>
          ))}
        </div>
      </section>

      <section className="section faq">
        <div className="section-heading">
          <span className="eyebrow">Pertanyaan umum</span>
          <h2>Yang biasanya ditanyakan sebelum mulai</h2>
        </div>
        <div className="faq-grid">
          <article>
            <h3>Bisa dipakai di browser dan desktop?</h3>
            <p>Bisa. Browser cocok untuk akses praktis, sedangkan desktop cocok untuk kasir dan print thermal.</p>
          </article>
          <article>
            <h3>Apakah bisa print thermal?</h3>
            <p>Bisa. NessaPOS dirancang agar cetak struk ke printer thermal terasa cepat dan praktis.</p>
          </article>
          <article>
            <h3>Apakah data aman?</h3>
            <p>Data toko tersimpan di cloud dengan akses akun, sehingga lebih mudah dipantau dan dicadangkan.</p>
          </article>
        </div>
      </section>

      <section className="cta">
        <div>
          <span className="eyebrow">Siap dirilis</span>
          <h2>Coba NessaPOS untuk toko Anda</h2>
          <p>Mulai dari fitur kasir, stok, laporan, dan print thermal. Cocok untuk toko yang ingin naik kelas tanpa sistem yang rumit.</p>
        </div>
        <Link href="/coba-gratis" className="button">Coba Gratis</Link>
      </section>
    </main>
  )
}
