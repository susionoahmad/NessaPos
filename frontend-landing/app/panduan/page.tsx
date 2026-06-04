import Link from 'next/link'
import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Panduan Setup & Printer - NessaPOS',
  description: 'Cara setup printer thermal, kiosk mode, bridge, dan RawBT untuk NessaPOS.',
}

export default function PanduanPage() {
  return (
    <main className="page-shell">
      <header className="site-header">
        <Link href="/" className="brand" aria-label="NessaPOS home">
          <span className="brand-mark">N</span>
          <span>NessaPOS</span>
        </Link>
        <nav aria-label="Navigasi utama">
          <Link href="/#fitur">Fitur</Link>
          <Link href="/#harga">Paket</Link>
          <Link href="/coba-gratis">Coba Gratis</Link>
          <Link href="/blog">Blog</Link>
        </nav>
      </header>

      <section className="article">
        <span className="eyebrow">Pusat Bantuan</span>
        <h1>Panduan Setup & Printer</h1>
        <p>Ikuti langkah-langkah di bawah ini untuk mengonfigurasi NessaPOS agar siap digunakan untuk transaksi dan mencetak struk.</p>

        <hr style={{ margin: '40px 0', border: '0', borderTop: '1px solid #e4e8ee' }} />

        <section id="pengaturan">
          <h2>1. Cara Setup di Menu Pengaturan</h2>
          <p>Setelah login pertama kali, hal pertama yang harus Anda lakukan adalah melengkapi data toko Anda:</p>
          <ul>
            <li>Buka menu <strong>Pengaturan</strong> di sidebar POS.</li>
            <li><strong>Profil Toko:</strong> Isi nama toko, alamat, dan nomor telepon yang akan muncul di struk.</li>
            <li><strong>Logo Struk:</strong> Unggah logo toko Anda (disarankan format hitam putih untuk printer thermal).</li>
            <li><strong>Digit Harga:</strong> Atur apakah Anda ingin menggunakan desimal atau tidak.</li>
          </ul>
        </section>

        <section id="printer" style={{ marginTop: '60px' }}>
          <h2>2. Panduan Setup Printer Thermal</h2>
          <p>NessaPOS mendukung berbagai cara untuk mencetak struk tergantung pada perangkat yang Anda gunakan.</p>
          
          <div className="affiliate-section" style={{ background: '#f0f9f6', borderLeft: '4px solid #1f7a6a', marginTop: '24px' }}>
            <h3 style={{ marginTop: 0 }}>A. Browser Print (Kiosk Mode)</h3>
            <p>Digunakan jika Anda ingin mencetak langsung dari Google Chrome tanpa muncul dialog print preview.</p>
            <ol>
              <li>Tutup semua jendela Chrome yang sedang terbuka.</li>
              <li>Klik kanan pada shortcut Chrome di Desktop, pilih <strong>Properties</strong>.</li>
              <li>Pada kolom <strong>Target</strong>, tambahkan kode ini di akhir (setelah tanda petik): <code> --kiosk-printing</code>.</li>
              <li>Klik Apply dan OK. Buka Chrome menggunakan shortcut tersebut.</li>
              <li>Sekarang, saat Anda tekan Print di NessaPOS, struk akan langsung keluar ke printer default.</li>
            </ol>
          </div>

          <div className="affiliate-section" style={{ background: '#f8faf9', borderLeft: '4px solid #4d5b6d', marginTop: '24px' }}>
            <h3 style={{ marginTop: 0 }}>B. NessaPOS Bridge (Rekomendasi Desktop)</h3>
            <p>Aplikasi pendukung untuk koneksi printer yang lebih stabil dan fitur "Silent Print" yang lebih canggih.</p>
            <ol>
              <li>Download <strong>NessaPOS Bridge</strong> dari halaman download.</li>
              <li>Install dan jalankan aplikasi tersebut di komputer kasir Anda.</li>
              <li>Di aplikasi POS (Browser), masuk ke <strong>Pengaturan {'>'} Printer</strong>.</li>
              <li>Pilih mode <strong>Bridge</strong> dan masukkan alamat local (biasanya <code>localhost:8080</code>).</li>
              <li>Tes Print untuk memastikan koneksi berhasil.</li>
            </ol>
          </div>

          <div className="affiliate-section" style={{ background: '#fffaf1', borderLeft: '4px solid #8f6647', marginTop: '24px' }}>
            <h3 style={{ marginTop: 0 }}>C. Mobile Setup (RawBT)</h3>
            <p>Digunakan untuk mencetak struk dari HP/Tablet Android menggunakan Printer Bluetooth.</p>
            <ol>
              <li>Install aplikasi <strong>RawBT</strong> dari Google Play Store.</li>
              <li>Hubungkan HP Anda dengan Printer Bluetooth di pengaturan Bluetooth HP.</li>
              <li>Buka aplikasi RawBT, pilih printer Anda, dan pastikan statusnya "Ready".</li>
              <li>Di NessaPOS (Mobile), saat Anda menekan Print, pilih opsi "Send to RawBT".</li>
              <li>Struk akan otomatis terkirim dari NessaPOS ke aplikasi RawBT untuk dicetak.</li>
            </ol>
          </div>
        </section>

        <div className="article-cta" style={{ marginTop: '60px' }}>
          <h2>Butuh Bantuan Lebih Lanjut?</h2>
          <p>Jika Anda mengalami kendala teknis saat setup, tim support kami siap membantu Anda melalui WhatsApp.</p>
          <Link href="/#kontak" className="button">Tanya Support</Link>
        </div>
      </section>

      <footer style={{ marginTop: '100px', padding: '40px 0', borderTop: '1px solid #e4e8ee', textAlign: 'center', color: '#526173', fontSize: '0.9rem' }}>
        <p>&copy; {new Date().getFullYear()} NessaPOS. All rights reserved.</p>
      </footer>
    </main>
  )
}
