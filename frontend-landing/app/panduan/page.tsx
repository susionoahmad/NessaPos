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
          <p>Setelah login pertama kali sebagai Admin, buka menu <strong>Pengaturan</strong> di sidebar kiri. Lengkapi semua bagian di bawah ini sebelum mulai berjualan.</p>

          {/* Tab Identitas Toko */}
          <div className="affiliate-section" style={{ background: '#f8fafc', borderLeft: '4px solid #22c55e', marginTop: '24px' }}>
            <h3 style={{ marginTop: 0 }}>🏪 Tab: Identitas &amp; Info Toko</h3>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
              <thead>
                <tr style={{ background: '#f1f5f9' }}>
                  <th style={{ padding: '8px 12px', textAlign: 'left', width: '30%' }}>Pengaturan</th>
                  <th style={{ padding: '8px 12px', textAlign: 'left' }}>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Nama Toko</td>
                  <td style={{ padding: '8px 12px' }}>Nama bisnis Anda. Akan muncul di baris pertama setiap struk.</td>
                </tr>
                <tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Alamat &amp; Telepon</td>
                  <td style={{ padding: '8px 12px' }}>Informasi kontak toko. Muncul di bawah nama pada struk.</td>
                </tr>
                <tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Logo Struk</td>
                  <td style={{ padding: '8px 12px' }}>Upload gambar logo (PNG transparan/putih, maks 1MB). Logo muncul di atas nama toko. Untuk printer thermal, disarankan logo hitam-putih agar tercetak tajam.</td>
                </tr>
                <tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Digit Harga</td>
                  <td style={{ padding: '8px 12px' }}>
                    Atur presisi harga yang ditampilkan di struk dan layar kasir:
                    <ul style={{ margin: '5px 0', paddingLeft: '18px' }}>
                      <li><strong>0 Digit</strong> — Harga bulat, contoh: <code>Rp 50.000</code> (paling umum)</li>
                      <li><strong>1 Digit</strong> — contoh: <code>Rp 50.000,5</code></li>
                      <li><strong>2 Digit</strong> — contoh: <code>Rp 50.000,50</code></li>
                    </ul>
                  </td>
                </tr>
                <tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Sistem Pajak</td>
                  <td style={{ padding: '8px 12px' }}>
                    Pilih cara pajak dibebankan:
                    <ul style={{ margin: '5px 0', paddingLeft: '18px' }}>
                      <li><strong>Inclusive (Sudah Termasuk)</strong> — Harga yang tertera sudah termasuk pajak. Struk akan menampilkan rincian pajak yang sudah ada di dalam harga.</li>
                      <li><strong>Exclusive (Ditambahkan di Akhir)</strong> — Pajak dihitung dan ditambahkan di atas subtotal. Cocok untuk restoran/kafe yang menerapkan Service Charge/PPN terpisah.</li>
                    </ul>
                    Jika tidak ada pajak, isi Tarif Pajak dengan <strong>0</strong>.
                  </td>
                </tr>
                <tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Tarif Pajak (%)</td>
                  <td style={{ padding: '8px 12px' }}>Masukkan angka persentase saja, tanpa simbol %. Contoh: untuk PPN 11%, isi <strong>11</strong>. Untuk tidak ada pajak, isi <strong>0</strong>.</td>
                </tr>
                <tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Ukuran Kertas Struk</td>
                  <td style={{ padding: '8px 12px' }}>
                    Sesuaikan dengan lebar kertas printer thermal Anda:
                    <ul style={{ margin: '5px 0', paddingLeft: '18px' }}>
                      <li><strong>58mm</strong> — Printer portabel/bluetooth kecil (umum untuk mobile)</li>
                      <li><strong>80mm</strong> — Printer desktop standar kasir (paling umum)</li>
                    </ul>
                    Salah pilih ukuran akan membuat teks terpotong atau ada spasi berlebih.
                  </td>
                </tr>
                <tr>
                  <td style={{ padding: '8px 12px', fontWeight: 'bold' }}>Laci Kasir (Cash Drawer)</td>
                  <td style={{ padding: '8px 12px' }}>
                    <ul style={{ margin: '5px 0', paddingLeft: '18px' }}>
                      <li><strong>Aktif</strong> — Laci uang akan otomatis terbuka setiap kali ada transaksi tunai yang berhasil. Pastikan laci sudah terhubung ke printer via kabel RJ11/RJ12.</li>
                      <li><strong>Nonaktif</strong> — Laci tidak terbuka otomatis. Pilih ini jika Anda tidak memiliki laci kasir atau menggunakan laci manual.</li>
                    </ul>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {/* Teks Kaki Struk */}
          <div className="affiliate-section" style={{ background: '#f8fafc', borderLeft: '4px solid #94a3b8', marginTop: '16px' }}>
            <h3 style={{ marginTop: 0 }}>✍️ Teks Kaki Struk (Footer)</h3>
            <p>Pesan yang muncul di bagian paling bawah setiap struk. Contoh: <em>"Terima kasih, selamat datang kembali!"</em> atau nomor WA untuk komplain.</p>
          </div>
        </section>

        <section id="daftar-kasir" style={{ marginTop: '60px' }}>
          <h2>2. Daftarkan Akun Kasir</h2>
          <p>Setelah pengaturan toko selesai, langkah wajib berikutnya adalah mendaftarkan akun untuk setiap kasir yang akan menggunakan sistem.</p>

          <div style={{ background: '#fff7ed', border: '1px solid #fed7aa', borderRadius: '12px', padding: '20px', marginTop: '20px' }}>
            <p style={{ margin: 0, fontWeight: 'bold', color: '#c2410c' }}>⚠️ Mengapa Ini Wajib?</p>
            <p style={{ margin: '5px 0 0 0', fontSize: '14px', color: '#c2410c' }}>
              Admin <strong>tidak bisa</strong> membuka sesi kasir. Kasir harus login dengan akun sendiri untuk dapat membuka sesi, menerima transaksi, dan mencetak struk. Tanpa kasir terdaftar, sistem tidak bisa digunakan untuk transaksi.
            </p>
          </div>

          <div className="affiliate-section" style={{ background: '#f0f9f6', borderLeft: '4px solid #22c55e', marginTop: '24px' }}>
            <h3 style={{ marginTop: 0 }}>Langkah Mendaftarkan Kasir:</h3>
            <ol>
              <li>Login sebagai <strong>Admin</strong>, buka menu <strong>Pengaturan {'>'} Daftar Pengguna</strong>.</li>
              <li>Klik tombol <strong>"Tambah Kasir"</strong>.</li>
              <li>Isi <strong>Username</strong> dan <strong>Password</strong> untuk kasir tersebut.</li>
              <li>Klik <strong>Simpan</strong>. Akun kasir siap digunakan.</li>
              <li>Berikan username dan password tersebut kepada kasir Anda.</li>
              <li>Kasir login di <strong>alamat yang sama</strong> ({'"'}pos.nessapos.my.id{'"'}), lalu pilih menu <strong>Buka Sesi Kasir</strong> untuk mulai bekerja.</li>
            </ol>

            <div style={{ background: '#dcfce7', padding: '12px 16px', borderRadius: '8px', marginTop: '15px', fontSize: '14px' }}>
              <strong>💡 Tips:</strong> Anda bisa mendaftarkan lebih dari satu kasir. Setiap kasir memiliki sesi dan laporan transaksi masing-masing yang bisa difilter di menu Laporan.
            </div>
          </div>
        </section>

        <section id="printer" style={{ marginTop: '60px' }}>
          <h2>3. Panduan Setup Printer Thermal</h2>
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
          <Link href="/kontak" className="button">Tanya Support</Link>
        </div>
      </section>

      <footer style={{ marginTop: '100px', padding: '40px 0', borderTop: '1px solid #e4e8ee', textAlign: 'center', color: '#526173', fontSize: '0.9rem' }}>
        <p>&copy; {new Date().getFullYear()} NessaPOS. All rights reserved.</p>
      </footer>
    </main>
  )
}
