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
          <Link href="/panduan">Panduan</Link>
          <Link href="/kontak">Kontak</Link>
          <Link href="/coba-gratis" className="nav-cta">Coba Gratis</Link>
        </nav>
      </header>

      <section className="article">
        <span className="eyebrow">Pusat Bantuan</span>
        <h1>Panduan Setup & Printer</h1>
        <p>Ikuti langkah-langkah di bawah ini untuk mengonfigurasi NessaPOS agar siap digunakan untuk transaksi dan mencetak struk.</p>

        <hr style={{ margin: '40px 0', border: '0', borderTop: '1px solid #e4e8ee' }} />

        <section id="pengaturan">
          <h2>1. Cara Setup di Menu Pengaturan</h2>
          <p>Setelah login pertama kali, lengkapi identitas bisnis Anda agar struk terlihat profesional:</p>
          <ul>
            <li>Buka menu <strong>Pengaturan</strong> di sidebar kiri aplikasi POS.</li>
            <li><strong>Identitas Toko:</strong> Isi Nama, Alamat, dan Telepon. Bagian ini wajib diisi karena akan muncul di bagian atas struk.</li>
            <li><strong>Logo Struk:</strong> Unggah file gambar logo Anda. Disarankan menggunakan file PNG dengan latar belakang putih/transparan. Logo akan muncul otomatis di setiap struk transaksi.</li>
            <li><strong>Digit Harga (Desimal):</strong> Pilih apakah Anda ingin menampilkan angka di belakang koma. Jika Anda menjual barang dengan harga bulat, pilih <strong>0 Digit</strong> untuk tampilan yang lebih bersih (e.g. Rp 50.000).</li>
          </ul>
        </section>

        <section id="printer" style={{ marginTop: '60px' }}>
          <h2>2. Panduan Setup Printer Thermal</h2>
          <p>NessaPOS mendukung berbagai cara untuk mencetak struk tergantung pada perangkat yang Anda gunakan.</p>
          
          <div className="affiliate-section" style={{ background: '#f0f9f6', borderLeft: '4px solid #1f7a6a', marginTop: '24px' }}>
            <h3 style={{ marginTop: 0 }}>A. Browser Print (Kiosk Mode)</h3>
            <p>Paling praktis untuk PC Desktop yang hanya menggunakan satu printer thermal.</p>
            <ol>
              <li>Tutup semua jendela Chrome. Klik kanan shortcut Chrome di Desktop {'>'} <strong>Properties</strong>.</li>
              <li>Pada <strong>Target</strong>, tambahkan <code> --kiosk-printing</code> di bagian paling akhir.</li>
              <li>Saat print di POS, struk akan keluar otomatis tanpa preview browser.</li>
            </ol>
          </div>

          <div className="affiliate-section" style={{ background: '#f0f4f9', borderLeft: '4px solid #0e7490', marginTop: '24px', padding: '20px' }}>
            <h3 style={{ marginTop: 0 }}>B. NessaPOS Bridge (Rekomendasi Stabil)</h3>
            <p>Metode tercanggih untuk mencetak tanpa batas ke berbagai merk printer thermal (USB/Bluetooth) di Windows.</p>
            <div style={{ background: '#fffbeb', padding: '15px', borderRadius: '8px', border: '1px solid #fde68a', marginBottom: '15px' }}>
              <p style={{ margin: 0, fontWeight: 'bold', color: '#92400e' }}>⚠️ KRUSIAL: Sinkronisasi Token Keamanan</p>
              <p style={{ margin: '5px 0 0 0', fontSize: '14px', color: '#92400e' }}>
                Agar web browser diizinkan memerintahkan print ke komputer Anda, <b>TOKEN</b> yang ada di menu Pengaturan Web dan Aplikasi Desktop <b>WAJIB SAMA</b>.
              </p>
            </div>
            <ol>
              <li>Buka <strong>Pengaturan {'>'} Printer</strong> di aplikasi NessaPOS (di browser).</li>
              <li>Cari bagian <strong>Bridge Token</strong>, klik tombol "Salin" atau buat baru jika masih kosong.</li>
              <li>Buka aplikasi <strong>NessaPOS Desktop</strong> di PC Anda, masuk ke Pengaturan, dan <strong>Tempel (Paste)</strong> token tersebut.</li>
              <li>Pastikan <strong>Port</strong> di Web dan Desktop sama (Default: <code>12348</code>).</li>
              <li>Simpan di kedua sisi, lalu lakukan "Tes Print".</li>
            </ol>
          </div>

          <div className="affiliate-section" style={{ background: '#fffaf1', borderLeft: '4px solid #8f6647', marginTop: '24px' }}>
            <h3 style={{ marginTop: 0 }}>C. Mobile Bluetooth (RawBT)</h3>
            <p>Khusus pengguna HP Android yang menggunakan printer bluetooth portable mini.</p>
            <ol>
              <li>Install <strong>RawBT</strong> dari Play Store dan sambungkan bluetooth printer ke HP.</li>
              <li>Pilih printer di aplikasi RawBT sampai statusnya "Ready".</li>
              <li>Di aplikasi NessaPOS, buka <strong>Pengaturan {'>'} Printer</strong> dan pilih metode <strong>RawBT</strong>.</li>
              <li>Saat transaksi selesai, tekan Cetak Struk maka data akan otomatis terkirim ke printer.</li>
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
