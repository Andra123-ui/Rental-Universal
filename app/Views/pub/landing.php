<?= view('partials/header', ['title' => 'Beranda']) ?>

<section class="hero">
  <div class="wrap">
    <div class="hero-grid">
      <div class="eyebrow-free">
        <h1>Sewa barang dan jasa apa saja, dalam satu akun.</h1>
        <p class="lead">Dari kendaraan, kamera, ruang acara, sampai jasa teknisi dan guide — cek ketersediaan, booking, dan pantau semua riwayat transaksi Anda tanpa perlu mengisi data berulang kali.</p>
        <div class="hero-ctas">
          <a href="<?= base_url('/katalog') ?>" class="btn btn-primary">Lihat Katalog</a>
          <a href="#cara-kerja" class="btn btn-outline">Cara Kerja</a>
        </div>
      </div>

      <div class="hero-art">
        <div class="art-tile">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13l1.5-5A2 2 0 0 1 6.4 6.5h11.2A2 2 0 0 1 19.5 8l1.5 5"/><rect x="2.5" y="13" width="19" height="5.5" rx="1.2"/><circle cx="7" cy="18.5" r="1.4"/><circle cx="17" cy="18.5" r="1.4"/></svg>
          <span>Kendaraan</span>
        </div>
        <div class="art-tile">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7l1.5-3h5L16 7"/><circle cx="12" cy="13.5" r="3.4"/></svg>
          <span>Kamera & Alat</span>
        </div>
        <div class="art-tile tall">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M9 21v-6h6v6"/></svg>
          <strong>Ruang &amp; Villa</strong>
        </div>
        <div class="art-tile">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="4" height="9"/><rect x="10" y="5" width="4" height="14"/><rect x="16" y="8" width="4" height="11"/></svg>
          <span>Jasa & Personel</span>
        </div>
      </div>
    </div>

    <form class="search-card" method="get" action="<?= base_url('/katalog') ?>">
      <div class="search-field">
        <label for="s-kategori">Kategori</label>
        <select id="s-kategori" name="kategori">
          <option value="">Semua kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= esc($cat['slug']) ?>"><?= esc($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="search-field">
        <label for="s-mulai">Tanggal mulai</label>
        <input type="date" id="s-mulai" name="start_at">
      </div>
      <div class="search-field">
        <label for="s-selesai">Tanggal selesai</label>
        <input type="date" id="s-selesai" name="end_at">
      </div>
      <button type="submit" class="btn btn-primary">Cek Ketersediaan</button>
    </form>
  </div>
</section>

<section class="features">
  <div class="wrap">
    <div class="section-head">
      <h2>Kenapa booking di sini</h2>
      <p>Satu sistem untuk seluruh proses sewa — mulai dari cek jadwal sampai riwayat transaksi.</p>
    </div>
    <div class="feature-grid">
      <div class="feature">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1.2"/><rect x="13" y="3" width="8" height="8" rx="1.2"/><rect x="3" y="13" width="8" height="8" rx="1.2"/><rect x="13" y="13" width="8" height="8" rx="1.2"/></svg>
        <div><h3>Katalog lengkap</h3><p>Barang dan jasa tersedia dalam satu tempat, tanpa harus pindah platform.</p></div>
      </div>
      <div class="feature">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 3v4M16 3v4"/><path d="M8.5 15l2 2 4-4"/></svg>
        <div><h3>Booking online 24 jam</h3><p>Cek ketersediaan dan ajukan booking kapan saja.</p></div>
      </div>
      <div class="feature">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M9 18h6"/><path d="M9.5 9l1.8 1.8L15 7"/></svg>
        <div><h3>Verifikasi nomor HP</h3><p>Login memakai OTP WhatsApp, tanpa perlu mengingat kata sandi.</p></div>
      </div>
      <div class="feature">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
        <div><h3>Riwayat tersimpan</h3><p>Semua transaksi lama dan baru bisa dilihat kembali dari satu akun.</p></div>
      </div>
      <div class="feature">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="6" width="19" height="13" rx="2"/><path d="M2.5 10h19"/><path d="M6 15h4"/></svg>
        <div><h3>Pembayaran fleksibel</h3><p>DP, pelunasan, atau deposit — sesuai kebutuhan transaksi Anda.</p></div>
      </div>
      <div class="feature">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 0 1 14.5-4.6M20 12a8 8 0 0 1-14.5 4.6"/><path d="M18.5 3v4.5H14M5.5 21v-4.5H10"/></svg>
        <div><h3>Jadwal real-time</h3><p>Sistem otomatis mencegah dua booking bentrok pada resource yang sama.</p></div>
      </div>
    </div>
  </div>
</section>

<section id="kategori">
  <div class="wrap">
    <div class="section-head">
      <h2>Kategori populer</h2>
      <p>Pilih jenis barang atau jasa yang ingin Anda sewa.</p>
    </div>
    <div class="card-grid">
      <?php if (empty($categories)): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          <h3>Kategori belum ditambahkan</h3>
          <p>Tambahkan kategori lewat menu Katalog di dashboard admin agar tampil di halaman ini.</p>
        </div>
      <?php else: ?>
        <?php foreach ($categories as $cat): ?>
          <div class="cat-card">
            <div class="icon">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7l8-4 8 4-8 4-8-4z"/><path d="M4 12l8 4 8-4M4 17l8 4 8-4"/></svg>
            </div>
            <h3><?= esc($cat['name']) ?></h3>
            <p><?= esc($cat['description'] ?? 'Lihat pilihan yang tersedia di kategori ini.') ?></p>
            <a class="link" href="<?= base_url('/katalog?kategori=' . $cat['slug']) ?>">Lihat kategori &rarr;</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section id="produk" style="background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line);">
  <div class="wrap">
    <div class="section-head">
      <h2>Produk & layanan pilihan</h2>
      <p>Beberapa item yang sedang tersedia untuk disewa.</p>
    </div>
    <div class="card-grid">
      <?php if (empty($catalogItems)): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg>
          <h3>Belum ada produk atau jasa</h3>
          <p>Tambahkan item pertama Anda lewat menu Katalog di dashboard admin.</p>
        </div>
      <?php else: ?>
        <?php foreach ($catalogItems as $item):
          $unit = $unitLabels[$item['pricing_unit']] ?? strtolower($item['pricing_unit']);
        ?>
          <div class="item-card">
            <div class="item-media">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7l1.5-3h5L16 7"/><circle cx="12" cy="13.5" r="3.2"/></svg>
            </div>
            <div class="item-body">
              <span class="item-type"><?= esc($item['item_type']) ?></span>
              <h3><?= esc($item['name']) ?></h3>
              <p><?= esc(mb_strimwidth($item['description'] ?? 'Detail lengkap tersedia di halaman produk.', 0, 90, '...')) ?></p>
              <div class="item-footer">
                <div class="item-price">Rp<?= number_format($item['base_price'], 0, ',', '.') ?> <small>/ <?= esc($unit) ?></small></div>
                <a href="<?= base_url('/katalog/' . $item['id']) ?>" class="btn btn-outline" style="padding:8px 14px;font-size:0.85rem;">Detail</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section id="cara-kerja" class="steps">
  <div class="wrap">
    <div class="section-head">
      <h2>Cara kerja booking</h2>
      <p>Empat langkah dari melihat katalog sampai transaksi tercatat di akun Anda.</p>
    </div>
    <div class="step-grid">
      <div class="step"><div class="num">01</div><h3>Pilih & cek ketersediaan</h3><p>Jelajahi katalog tanpa perlu login, lalu cek jadwal yang masih kosong.</p></div>
      <div class="step"><div class="num">02</div><h3>Verifikasi nomor HP</h3><p>Masukkan nomor HP, kode OTP dikirim lewat WhatsApp.</p></div>
      <div class="step"><div class="num">03</div><h3>Booking & bayar</h3><p>Lengkapi data pemesanan, lalu bayar DP atau pelunasan.</p></div>
      <div class="step"><div class="num">04</div><h3>Pantau di Akun Saya</h3><p>Semua status, invoice, dan riwayat transaksi tersimpan otomatis.</p></div>
    </div>
  </div>
</section>

<section>
  <div class="wrap">
    <div class="cta-banner">
      <h2>Sudah pernah booking di sini sebelumnya?</h2>
      <div class="cta-actions">
        <a href="/account/login" class="btn btn-primary">Masuk ke Akun Saya</a>
        <a href="<?= base_url('/katalog') ?>" class="btn btn-light">Lihat Katalog</a>
      </div>
    </div>
  </div>
</section>

<?= view('partials/footer') ?>