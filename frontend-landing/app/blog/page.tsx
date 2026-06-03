import Link from 'next/link'
import { apiUrl } from '../lib/api'

async function getPosts() {
  try {
    const res = await fetch(apiUrl('/posts'), {
      cache: 'no-store',
    })
    if (!res.ok) throw new Error('Failed to fetch')
    const json = await res.json()
    return Array.isArray(json?.data) ? json.data : []
  } catch (error) {
    console.error('Failed to fetch posts', error)
    return []
  }
}

export default async function Blog() {
  const posts = await getPosts()

  return (
    <main className="page-shell">
      <header className="site-header">
        <Link href="/" className="brand" aria-label="NessaPOS home">
          <span className="brand-mark">N</span>
          <span>NessaPOS</span>
        </Link>
        <nav aria-label="Navigasi blog">
          <Link href="/">Beranda</Link>
        </nav>
      </header>
      <section className="blog-hero">
        <span className="eyebrow">Blog</span>
        <h1>Panduan POS untuk toko modern</h1>
        <p>Artikel edukasi tentang sistem kasir, print thermal, multi-toko, dan perangkat pendukung seperti printer serta barcode scanner.</p>
      </section>
      <section className="posts" aria-label="Daftar artikel">
        {posts.map((post: any) => (
          <article key={post.slug} className="post-card">
            <Link href={`/blog/${post.slug}`}>
              <h2>{post.title}</h2>
            </Link>
            <p>{post.excerpt}</p>
          </article>
        ))}
      </section>
    </main>
  )
}
