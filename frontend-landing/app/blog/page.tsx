import Link from 'next/link'
import { apiUrl, fetchWithTimeout } from '../lib/api'
import Header from '../components/Header'

export const dynamic = 'force-dynamic'

async function getPosts() {
  try {
    const res = await fetchWithTimeout(apiUrl('/posts'), {
      cache: 'no-store',
      timeout: 3000
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
      <Header />
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
