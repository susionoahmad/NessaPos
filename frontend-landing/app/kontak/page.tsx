import Link from 'next/link'
import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Hubungi Kami - NessaPOS',
  description: 'Hubungi Ahmad Susiono untuk dukungan teknis dan info kemitraan NessaPOS.',
}

export default function KontakPage() {
  const waNumber = "6281392156513"
  const email = "susiono.ahmad@gmail.com"
  const name = "Ahmad Susiono"

  return (
    <main className="page-shell">
      <header className="site-header">
        <Link href="/" className="brand">
          <span className="brand-mark">N</span>
          <span>NessaPOS</span>
        </Link>
        <nav>
          <Link href="/#fitur">Fitur</Link>
          <Link href="/#harga">Paket</Link>
          <Link href="/panduan">Panduan</Link>
          <Link href="/kontak">Kontak</Link>
          <Link href="/coba-gratis" className="nav-cta">Coba Gratis</Link>
        </nav>
      </header>

      <section className="article">
        <span className="eyebrow">Hubungi Kami</span>
        <h1>Ada Pertanyaan? Kami Siap Membantu</h1>
        <p>Silakan hubungi kami untuk dukungan teknis, pertanyaan seputar paket berlangganan, atau kerjasama kemitraan.</p>

        <div className="contact-grid" style={{ 
          display: 'grid', 
          gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', 
          gap: '30px', 
          marginTop: '50px' 
        }}>
          {/* Card WhatsApp */}
          <div className="contact-card" style={{ 
            background: '#f0f9f6', 
            padding: '30px', 
            borderRadius: '20px', 
            border: '1px solid #d1fae5',
            textAlign: 'center'
          }}>
            <div style={{ fontSize: '40px', marginBottom: '15px' }}>💬</div>
            <h3>WhatsApp Support</h3>
            <p style={{ color: '#475569', marginBottom: '20px' }}>Diskusi lebih cepat lewat chat WhatsApp.</p>
            <p style={{ fontWeight: 'bold', fontSize: '18px', marginBottom: '25px' }}>{name}</p>
            <a 
              href={`https://wa.me/${waNumber}?text=Halo%20NessaPOS,%20saya%20ingin%20bertanya%20tentang...`} 
              className="button"
              style={{ background: '#22c55e' }}
              target="_blank"
              rel="noopener noreferrer"
            >
              Chat WhatsApp
            </a>
          </div>

          {/* Card Email */}
          <div className="contact-card" style={{ 
            background: '#f8fafc', 
            padding: '30px', 
            borderRadius: '20px', 
            border: '1px solid #e2e8f0',
            textAlign: 'center'
          }}>
            <div style={{ fontSize: '40px', marginBottom: '15px' }}>✉️</div>
            <h3>Email Service</h3>
            <p style={{ color: '#475569', marginBottom: '20px' }}>Kirimkan detail pertanyaan atau keluhan Anda.</p>
            <p style={{ fontWeight: 'bold', fontSize: '18px', marginBottom: '25px' }}>{email}</p>
            <a 
              href={`mailto:${email}`} 
              className="button"
              style={{ background: '#334155' }}
            >
              Kirim Email
            </a>
          </div>
        </div>

        <div className="faq-short" style={{ marginTop: '80px', textAlign: 'center' }}>
          <h2>Lokasi & Jam Operasional</h2>
          <p>Kami melayani dukungan teknis secara online setiap hari:</p>
          <p><strong>Setiap Hari: 08:00 - 21:00 WIB</strong></p>
        </div>
      </section>

      <footer style={{ marginTop: '100px', padding: '40px 0', borderTop: '1px solid #e4e8ee', textAlign: 'center', color: '#526173', fontSize: '0.9rem' }}>
        <p>&copy; {new Date().getFullYear()} NessaPOS. Dikembangkan oleh {name}.</p>
      </footer>
    </main>
  )
}
