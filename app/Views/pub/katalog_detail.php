<?= view('partials/header', ['title' => $item['name']]) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / <a href="<?= base_url('/katalog') ?>">Katalog</a> / <?= esc($item['name']) ?></div>
    <h1><?= esc($item['name']) ?></h1>
  </div>
</div>

<div class="wrap" style="padding:50px 0;">
  <div class="detail-grid">
    <div>
      <div class="detail-media">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7l1.5-3h5L16 7"/><circle cx="12" cy="13.5" r="3.4"/></svg>
      </div>
      <div class="detail-info" style="margin-top:26px;">
        <span class="badge"><?= esc($item['item_type']) ?></span>
        <p style="color:var(--muted);"><?= esc($item['description'] ?? 'Deskripsi produk akan tampil di sini setelah dilengkapi lewat dashboard admin.') ?></p>
      </div>
    </div>

    <div class="booking-card">
      <h3>Cek & Tambah ke Keranjang</h3>
      <?php $unit = $unitLabels[$item['pricing_unit']] ?? strtolower($item['pricing_unit']); ?>
      <div class="detail-price">Rp<?= number_format($item['base_price'], 0, ',', '.') ?> <small>/ <?= esc($unit) ?></small></div>

      <form method="get" action="<?= base_url('/katalog/' . $item['id']) ?>" style="margin-top:20px;">
        <div class="field-group">
          <label>Tanggal mulai</label>
          <input type="date" name="start_at" value="<?= esc($startAt ?? '') ?>">
        </div>
        <div class="field-group">
          <label>Tanggal selesai</label>
          <input type="date" name="end_at" value="<?= esc($endAt ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center;">Cek Ketersediaan</button>
      </form>

      <?php if ($availability !== null): ?>
        <?php if ($availability['tersedia']): ?>
          <div class="avail-note avail-ok">Tersedia untuk tanggal yang Anda pilih.</div>
        <?php else: ?>
          <div class="avail-note avail-warn">Sudah penuh untuk tanggal ini, coba tanggal lain.</div>
        <?php endif; ?>
      <?php endif; ?>

      <form method="post" action="<?= base_url('/keranjang/tambah') ?>" style="margin-top:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="catalog_item_id" value="<?= esc($item['id']) ?>">
        <input type="hidden" name="start_at" value="<?= esc($startAt ?? '') ?>">
        <input type="hidden" name="end_at" value="<?= esc($endAt ?? '') ?>">
        <div class="field-group">
          <label>Jumlah</label>
          <input type="number" name="qty" min="1" value="1">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Tambah ke Keranjang</button>
      </form>
    </div>
  </div>
</div>

<?= view('partials/footer') ?>