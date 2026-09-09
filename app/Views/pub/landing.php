<?php
helper('image');
/**
 * @var array $categories
 * @var array $catalogItems
 * @var array $imageMap
 */

// Foto asli Unsplash — dicocokkan isi per kategori (License bebas pakai komersial)
$categoryPhotos = [
  'kendaraan' => 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?auto=format&fit=crop&w=900&q=80', // mobil sport
  'kamera-alat' => 'https://images.unsplash.com/photo-1512790182412-b19e6d62bc39?auto=format&fit=crop&w=900&q=80', // DSLR
  'ruang-villa' => 'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=900&q=80', // villa pool
  'sound-system' => 'https://images.unsplash.com/photo-1561314105-e6ac04c2984a?auto=format&fit=crop&w=900&q=80', // PA system
  'jasa-personel' => 'https://images.unsplash.com/photo-1519689950823-0a2251441815?auto=format&fit=crop&w=900&q=80', // fotografer motret
  'perlengkapan-acara' => 'https://images.unsplash.com/photo-1650713293709-8b9efcf0190b?auto=format&fit=crop&w=900&q=80', // tenda+kursi
];
function catFoto($slug, $map)
{
  return $map[$slug] ?? ('https://picsum.photos/seed/' . $slug . '/900/600');
}
$idx = 0;
?>
<?= view('partials/header', ['title' => 'Beranda']) ?>

<!-- HERO -->
<section class="hero">
  <div class="wrap">
    <div class="hero-grid">
      <div class="eyebrow-free">
        <span class="eyebrow">Rental Universal</span>
        <h1>Sewa barang dan jasa apa saja, dalam satu platform.</h1>
        <p class="lead">Dari kendaraan, kamera, ruang acara, sampai jasa teknisi dan guide — cek ketersediaan, booking,
          dan pantau transaksi lewat nomor invoice, tanpa perlu membuat akun.</p>
        <div class="hero-ctas">
          <a href="<?= base_url('/katalog') ?>" class="btn btn-primary">Lihat Katalog</a>
          <a href="#cara-kerja" class="btn btn-outline">Cara Kerja</a>
        </div>
      </div>

      <div class="hero-art">
        <div class="art-tile">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 13l1.5-5A2 2 0 0 1 6.4 6.5h11.2A2 2 0 0 1 19.5 8l1.5 5" />
            <rect x="2.5" y="13" width="19" height="5.5" rx="1.2" />
            <circle cx="7" cy="18.5" r="1.4" />
            <circle cx="17" cy="18.5" r="1.4" />
          </svg>
          <span>Kendaraan</span>
        </div>
        <div class="art-tile">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="7" width="18" height="13" rx="2" />
            <path d="M8 7l1.5-3h5L16 7" />
            <circle cx="12" cy="13.5" r="3.4" />
          </svg>
          <span>Kamera & Alat</span>
        </div>
        <div class="art-tile tall">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 21V10l8-6 8 6v11" />
            <path d="M9 21v-6h6v6" />
          </svg>
          <strong>Ruang &amp; Villa</strong>
        </div>
        <div class="art-tile">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="10" width="4" height="9" />
            <rect x="10" y="5" width="4" height="14" />
            <rect x="16" y="8" width="4" height="11" />
          </svg>
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
            <option value="<?= esc($cat['slug']) ?>">
              <?= esc($cat['name']) ?>
            </option>
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

<!-- Baris keunggulan bergaya ikon -->
<section class="trust-row">
  <div class="wrap">
    <div class="trust-grid">
      <div class="trust-item">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round">
            <rect x="6" y="2" width="12" height="20" rx="2" />
            <path d="M9 18h6" />
          </svg></div>
        <span>Tanpa Perlu Akun</span>
      </div>
      <div class="trust-item">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3.5 2" />
          </svg></div>
        <span>Ketersediaan Real-time</span>
      </div>
      <div class="trust-item">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round">
            <rect x="3" y="3" width="8" height="8" rx="1.2" />
            <rect x="13" y="3" width="8" height="8" rx="1.2" />
            <rect x="3" y="13" width="8" height="8" rx="1.2" />
            <rect x="13" y="13" width="8" height="8" rx="1.2" />
          </svg></div>
        <span>Aneka Kategori</span>
      </div>
      <div class="trust-item">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round">
            <rect x="2.5" y="6" width="19" height="13" rx="2" />
            <path d="M2.5 10h19" />
          </svg></div>
        <span>Pembayaran Fleksibel</span>
      </div>
      <div class="trust-item">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round">
            <path d="M8.5 15l2 2 4-4" />
            <rect x="3" y="5" width="18" height="16" rx="2" />
          </svg></div>
        <span>Invoice Tersimpan</span>
      </div>
      <div class="trust-item">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"
            stroke-linejoin="round">
            <rect x="4" y="10" width="4" height="9" />
            <rect x="10" y="5" width="4" height="14" />
            <rect x="16" y="8" width="4" height="11" />
          </svg></div>
        <span>Dukungan Cepat</span>
      </div>
    </div>
  </div>
</section>

<!-- KATEGORI POPULER dengan foto asli sesuai isi -->
<section id="kategori">
  <div class="wrap">
    <div class="section-head-row">
      <div class="section-head">
        <span class="eyebrow">Jelajahi</span>
        <h2>Kategori populer</h2>
        <p>Pilih jenis barang atau jasa yang ingin Anda sewa.</p>
      </div>
      <a href="<?= base_url('/katalog') ?>" class="btn btn-outline">Lihat semua kategori</a>
    </div>
    <div class="card-grid">
      <?php if (empty($categories)): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
          </svg>
          <h3>Kategori belum ditambahkan</h3>
          <p>Tambahkan kategori lewat menu Katalog di dashboard admin agar tampil di halaman ini.</p>
        </div>
      <?php else: ?>
        <?php foreach ($categories as $cat): ?>
          <div class="cat-card">
            <div class="cat-card-img"
              style="background-image:url('<?= esc(category_image_url($cat['slug'])) ?>');height:150px;border-radius:4px 4px 0 0;background-size:cover;background-position:center;margin:-26px -26px 16px;">
            </div>
            <h3>
              <?= esc($cat['name']) ?>
            </h3>
            <p>
              <?= esc($cat['description'] ?? 'Lihat pilihan yang tersedia di kategori ini.') ?>
            </p>
            <a class="link" href="<?= base_url('/katalog?kategori=' . $cat['slug']) ?>">Lihat kategori &rarr;</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ITEM UNGGULAN -->
<section id="produk"
  style="background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line);">
  <div class="wrap">
    <div class="section-head-row">
      <div class="section-head">
        <span class="eyebrow">Pilihan Terbaik</span>
        <h2>Produk & layanan pilihan</h2>
        <p>Beberapa item yang sedang tersedia untuk disewa.</p>
      </div>
      <a href="<?= base_url('/katalog') ?>" class="btn btn-outline">Lihat semua produk</a>
    </div>
    <div class="card-grid">
      <?php if (empty($catalogItems)): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8l-9-5-9 5 9 5 9-5z" />
            <path d="M3 8v8l9 5 9-5V8" />
          </svg>
          <h3>Belum ada produk atau jasa</h3>
          <p>Tambahkan item pertama Anda lewat menu Katalog di dashboard admin.</p>
        </div>
      <?php else: ?>
        <?php foreach ($catalogItems as $i => $item):
          $imgUrl = item_image_url($imageMap[$item['id']] ?? null, 'item-' . $item['id']);
          $d = min($i + 1, 4);
          ?>
          <div class="item-card">
            <?php if ($i === 0): ?><span class="item-ribbon">Terbaru</span>
            <?php endif; ?>
            <div class="item-media"
              style="background-image:url('<?= esc($imgUrl) ?>');background-size:cover;background-position:center;">
            </div>
            <div class="item-body">
              <span class="item-type">
                <?= esc($item['item_type']) ?>
              </span>
              <h3>
                <?= esc($item['name']) ?>
              </h3>
              <p>
                <?= esc(mb_strimwidth($item['description'] ?? 'Detail lengkap tersedia di halaman produk.', 0, 90, '...')) ?>
              </p>
              <div class="item-meta-row">
                <span><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3.5 2" />
                  </svg> per
                  <?= esc($item['unit_label']) ?>
                </span>
              </div>
              <div class="item-footer">
                <div class="item-price">Rp
                  <?= number_format($item['base_price'], 0, ',', '.') ?> <small>/
                    <?= esc($item['unit_label']) ?>
                  </small>
                </div>
                <a href="<?= base_url('/katalog/' . $item['id']) ?>" class="btn btn-outline"
                  style="padding:8px 14px;font-size:0.85rem;">Detail</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Promo / kebijakan booking, konten statis -->
<section>
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow">Penawaran</span>
      <h2>Kenapa booking lebih untung di sini</h2>
    </div>
    <div class="promo-grid">
      <div class="promo-card">
        <span class="promo-tag">Booking Awal</span>
        <h3>Pesan lebih cepat, jadwal lebih pasti</h3>
        <p>Ketersediaan dicek langsung saat itu juga, tidak perlu menunggu konfirmasi lama.</p>
        <a href="<?= base_url('/katalog') ?>" class="btn btn-light">Cek Katalog</a>
      </div>
      <div class="promo-card">
        <span class="promo-tag">Pembayaran</span>
        <h3>DP, pelunasan, atau deposit</h3>
        <p>Pilih metode pembayaran sesuai kebutuhan transaksi Anda.</p>
        <a href="<?= base_url('/help') ?>" class="btn btn-light">Pelajari Kebijakan</a>
      </div>
      <div class="promo-card">
        <span class="promo-tag">Riwayat Aman</span>
        <h3>Semua transaksi tersimpan di invoice</h3>
        <p>Cukup simpan nomor invoice untuk melihat status booking kapan saja.</p>
        <a href="<?= base_url('/cek-booking') ?>" class="btn btn-light">Cek Booking</a>
      </div>
    </div>
  </div>
</section>

<section id="cara-kerja" class="steps">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow" style="color:var(--brass);">Alur Sederhana</span>
      <h2>Cara kerja booking</h2>
      <p>Empat langkah dari melihat katalog sampai transaksi selesai.</p>
    </div>
    <div class="step-grid">
      <div class="step">
        <div class="num">01</div>
        <h3>Pilih & cek ketersediaan</h3>
        <p>Jelajahi katalog tanpa perlu login, lalu cek jadwal yang masih kosong.</p>
      </div>
      <div class="step">
        <div class="num">02</div>
        <h3>Isi data penyewa</h3>
        <p>Masukkan nama dan nomor HP, tanpa perlu membuat akun.</p>
      </div>
      <div class="step">
        <div class="num">03</div>
        <h3>Booking & bayar</h3>
        <p>Lengkapi data pemesanan, lalu bayar DP atau pelunasan.</p>
      </div>
      <div class="step">
        <div class="num">04</div>
        <h3>Cek status via invoice</h3>
        <p>Simpan nomor invoice untuk memantau status dan riwayat transaksi.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section>
  <div class="wrap">
    <div class="cta-banner">
      <h2>Sudah pernah booking di sini sebelumnya?</h2>
      <div class="cta-actions">
        <a href="<?= base_url('/cek-booking') ?>" class="btn btn-primary">Cek Booking Saya</a>
        <a href="<?= base_url('/katalog') ?>" class="btn btn-light">Lihat Katalog</a>
      </div>
    </div>
  </div>
</section>

<?= view('partials/footer') ?>