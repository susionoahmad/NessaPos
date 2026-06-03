import { notFound } from 'next/navigation'
import Link from 'next/link'
import AffiliateImage from './AffiliateImage'
import { apiUrl } from '../../lib/api'

async function getPost(slug: string) {
  try {
    const res = await fetch(apiUrl(`/posts/${slug}`), {
      cache: 'no-store',
    })
    if (!res.ok) throw new Error('Failed to fetch')
    return await res.json()
  } catch (error) {
    console.error('Failed to fetch post', error)
    return null
  }
}

export default async function PostPage({ params }: { params: { slug: string } }) {
  const post = await getPost(params.slug)
  if (!post) {
    notFound()
  }

  const hasHtml = /<[^>]+>/.test(post.content)
  const paragraphs = hasHtml
    ? [post.content]
    : post.content
        .split(/\r?\n\r?\n/)
        .map((paragraph: string) => paragraph.trim())
        .filter(Boolean)

  const intro = paragraphs.slice(0, 1)
  const benefits = paragraphs.slice(1, 2)
  const problems = paragraphs.slice(2, 3)
  const tips = paragraphs.slice(3)
  const affiliates = Array.isArray(post.affiliates) ? post.affiliates : []

  const renderParagraph = (paragraph: string, key: string) => {
    if (hasHtml) {
      return <div key={key} dangerouslySetInnerHTML={{ __html: paragraph }} />
    }
    return <p key={key}>{paragraph}</p>
  }

  return (
    <main className="page-shell">
      <header className="site-header">
        <Link href="/" className="brand" aria-label="NessaPOS home">
          <span className="brand-mark">N</span>
          <span>NessaPOS</span>
        </Link>
        <nav aria-label="Navigasi artikel">
          <Link href="/blog">Blog</Link>
          <Link href="/">Beranda</Link>
        </nav>
      </header>
      <article className="article">
        <Link href="/blog" className="back-link">Kembali ke blog</Link>
        <span className="eyebrow">Insight</span>
        <h1>{post.title}</h1>
        <p className="article-excerpt">{post.excerpt}</p>

        <section className="article-section">
          <h2>Penjelasan</h2>
          {intro.map((paragraph: string, index: number) => renderParagraph(paragraph, `intro-${index}`))}
        </section>

        {benefits.length > 0 && (
          <section className="article-section">
            <h2>Manfaat</h2>
            {benefits.map((paragraph: string, index: number) => renderParagraph(paragraph, `benefit-${index}`))}
          </section>
        )}

        {problems.length > 0 && (
          <section className="article-section">
            <h2>Masalah</h2>
            {problems.map((paragraph: string, index: number) => renderParagraph(paragraph, `problem-${index}`))}
          </section>
        )}

        {tips.length > 0 && (
          <section className="article-section">
            <h2>Tips & Rekomendasi</h2>
            {tips.map((paragraph: string, index: number) => renderParagraph(paragraph, `tip-${index}`))}
          </section>
        )}

        {affiliates.length > 0 && (
          <section className="article-section affiliate-section">
            <h2>Rekomendasi Produk</h2>
            <div className="affiliate-list">
              {affiliates.map((affiliate: any) => (
                <article key={affiliate.id} className="affiliate-card">
                  {affiliate.image && (
                    <AffiliateImage 
                      src={affiliate.image} 
                      alt={affiliate.product_name} 
                    />
                  )}
                  <div className="affiliate-content">
                    <div className="affiliate-badge">
                      <span className="affiliate-badge-icon">★</span>
                      Rekomendasi
                    </div>
                    <p className="affiliate-platform">{affiliate.platform}</p>
                    <h3>{affiliate.product_name}</h3>
                    <p className="affiliate-price">Rp {affiliate.price?.toLocaleString('id-ID')}</p>
                    <Link href={affiliate.url} target="_blank" rel="noopener noreferrer" className="button secondary">
                      Beli Sekarang
                    </Link>
                  </div>
                </article>
              ))}
            </div>
          </section>
        )}

        <section className="article-cta">
          <h2>Solusi POS Praktis</h2>
          <p>Untuk pengalaman kasir tanpa gangguan cetak browser, gunakan printer thermal yang kompatibel dan bridge lokal. NessaPOS mendukung alur transaksi, stok, laporan, dan print thermal dalam satu solusi.</p>
          <Link href="/coba-gratis" className="button">Coba Gratis NessaPOS</Link>
        </section>
      </article>
    </main>
  )
}
