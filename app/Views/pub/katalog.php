<?= view('partials/header', ['title' => 'Katalog']) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Katalog</div>
    <h1>Katalog Sewa</h1>
  </div>
</div>

<div class="wrap">
  <form method="get" action="<?= base_url('/katalog') ?>" class="filters-bar">
    <div class="search-field">
      <label>Cari</label>
      <input type="text" name="q" value="<?= esc($keyword ?? '') ?>" placeholder="Nama produk atau jasa">
    </div>
    <div class="search-field">
      <label>Kategori</label>
      <select name="kategori">
        <option value="">Semua kategori</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= esc($cat['slug']) ?>" <?= $kategori === $cat['slug'] ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="search-field">
      <label>Urutkan</label>
      <select name="sort">
        <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
        <option value="harga-terendah" <?= $sort === 'harga-terendah' ? 'selected' : '' ?>>Harga terendah</option>
        <option value="harga-tertinggi" <?= $sort === 'harga-tertinggi' ? 'selected' : '' ?>>Harga tertinggi</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Terapkan</button>
  </form>

  <div class="card-grid" style="margin-bottom:40px;">
    <?php if (empty($items)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg>
        <h3>Tidak ada produk ditemukan</h3>
        <p>Coba ubah kata kunci/kategori, atau tambahkan produk lewat dashboard admin.</p>
      </div>
    <?php else: ?>
      <?php foreach ($items as $item):
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

  <?php if (! empty($items)): ?>
    <div style="display:flex;justify-content:center;margin-bottom:60px;"><?= $pager->links() ?></div>
  <?php endif; ?>
</div>

<?= view('partials/footer') ?>