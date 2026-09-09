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
      <div class="eyebrow-free reveal">
        <span class="eyebrow">Rental Universal</span>
        <h1>Sewa barang dan jasa apa saja, dalam satu platform.</h1>
        <p class="lead">Dari kendaraan, kamera, ruang acara, sampai jasa teknisi dan guide — cek ketersediaan, booking,
          dan pantau transaksi tanpa perlu bikin akun.</p>
      </div>

      <div class="hero-collage reveal reveal-delay-1">
        <div class="ph ph1" style="background-image:url('<?= esc(catFoto('ruang-villa', $categoryPhotos)) ?>');"></div>
        <div class="ph ph2" style="background-image:url('<?= esc(catFoto('kendaraan', $categoryPhotos)) ?>');"></div>
        <div class="ph ph3" style="background-image:url('<?= esc(catFoto('kamera-alat', $categoryPhotos)) ?>');"></div>
      </div>
    </div>

    <form class="search-card reveal reveal-delay-2" method="get" action="<?= base_url('/katalog') ?>">
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

<!-- COUNTER -->
<section style="padding:40px 0;">
  <div class="wrap">
    <div class="counter-row">
      <div class="counter-item reveal">
        <div class="num" data-target="500" data-suffix="+">0</div>
        <div class="label">Produk & Jasa</div>
      </div>
      <div class="counter-item reveal reveal-delay-1">
        <div class="num" data-target="2000" data-suffix="+">0</div>
        <div class="label">Booking Selesai</div>
      </div>
      <div class="counter-item reveal reveal-delay-2">
        <div class="num" data-target="6" data-suffix="">0</div>
        <div class="label">Kategori Layanan</div>
      </div>
      <div class="counter-item reveal reveal-delay-3">
        <div class="num" data-target="24" data-suffix="/7">0</div>
        <div class="label">Cek Status Invoice</div>
      </div>
    </div>
  </div>
</section>

<!-- KATEGORI POPULER dengan foto asli sesuai isi -->
<section id="kategori">
  <div class="wrap">
    <div class="section-head-row reveal">
      <div class="section-head">
        <span class="eyebrow">Jelajahi</span>
        <h2>Kategori Populer</h2>
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
          <p>Tambahkan kategori lewat menu Katalog di dashboard admin.</p>
        </div>
      <?php else: ?>
        <?php foreach ($categories as $cat):
          $idx++;
          $d = min($idx, 4); ?>
          <div class="cat-card reveal reveal-delay-<?= $d ?>">
            <div class="cat-card-img"
              style="background-image:url('<?= esc(catFoto($cat['slug'], $categoryPhotos)) ?>');height:160px;border-radius:8px 8px 0 0;background-size:cover;background-position:center;margin:-26px -26px 16px;">
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

<!-- ITEM UNGGULAN -->
<section id="produk"
  style="background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line);">
  <div class="wrap">
    <div class="section-head-row reveal">
      <div class="section-head">
        <span class="eyebrow">Pilihan Terbaik</span>
        <h2>Produk & Layanan Pilihan</h2>
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
          <div class="item-card reveal reveal-delay-<?= $d ?>">
            <?php if ($i === 0): ?><span class="item-ribbon">Terbaru</span><?php endif; ?>
            <div class="item-media"
              style="background-image:url('<?= esc($imgUrl) ?>');background-size:cover;background-position:center;"></div>
            <div class="item-body">
              <span class="item-type"><?= esc($item['item_type']) ?></span>
              <h3><?= esc($item['name']) ?></h3>
              <p>
                <?= esc(mb_strimwidth($item['description'] ?? 'Detail lengkap tersedia di halaman produk.', 0, 90, '...')) ?>
              </p>
              <div class="item-footer">
                <div class="item-price">Rp<?= number_format($item['base_price'], 0, ',', '.') ?> <small>/
                    <?= esc($item['unit_label']) ?></small></div>
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

<!-- CARA BOOKING -->
<section id="cara-kerja" class="steps">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow" style="color:var(--accent2);">Alur Sederhana</span>
      <h2>Cara Kerja Booking</h2>
      <p>Empat langkah dari melihat katalog sampai transaksi selesai.</p>
    </div>
    <div class="step-grid">
      <div class="step reveal">
        <div class="num">01</div>
        <h3>Pilih & cek ketersediaan</h3>
        <p>Jelajahi katalog tanpa login, lalu cek jadwal yang masih kosong.</p>
      </div>
      <div class="step reveal reveal-delay-1">
        <div class="num">02</div>
        <h3>Isi data / login OTP</h3>
        <p>Lanjut sebagai tamu, atau login OTP WhatsApp agar riwayat tersimpan.</p>
      </div>
      <div class="step reveal reveal-delay-2">
        <div class="num">03</div>
        <h3>Booking & bayar</h3>
        <p>Lengkapi data pemesanan, lalu bayar DP atau pelunasan.</p>
      </div>
      <div class="step reveal reveal-delay-3">
        <div class="num">04</div>
        <h3>Cek status via invoice</h3>
        <p>Simpan nomor invoice untuk memantau status kapan saja.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section>
  <div class="wrap">
    <div class="cta-banner reveal">
      <h2>Sudah pernah booking di sini sebelumnya?</h2>
      <div class="cta-actions">
        <a href="/account/login" class="btn btn-primary">Masuk / Akun Saya</a>
        <a href="<?= base_url('/cek-booking') ?>" class="btn btn-light">Cek Booking Tamu</a>
      </div>
    </div>
  </div>
</section>

<?= view('partials/footer') ?>