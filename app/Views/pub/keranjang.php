<?php
/**
 * @var array $items
 * @var float $subtotal
 * @var array $unitLabels
 */
?>
<?= view('partials/header', ['title' => 'Keranjang']) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Keranjang</div>
    <h1>Keranjang Pilihan Anda</h1>
  </div>
</div>

<section>
  <div class="wrap">
    <div class="step-indicator">
      <div class="s-item active">1. Keranjang</div>
      <div class="s-item">2. Data Penyewa</div>
      <div class="s-item">3. Fulfillment</div>
      <div class="s-item">4. Review</div>
      <div class="s-item">5. Selesai</div>
    </div>

    <?php if (empty($items)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="6" width="18" height="15" rx="2" />
          <path d="M3 10h18" />
        </svg>
        <h3>Keranjang masih kosong</h3>
        <p>Yuk mulai pilih barang atau jasa yang ingin Anda sewa.</p>
        <a href="<?= base_url('/katalog') ?>" class="btn btn-primary" style="margin-top:16px;">Lihat Katalog</a>
      </div>
    <?php else: ?>
      <div class="detail-grid">
        <div>
          <table class="cart-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Jadwal</th>
                <th>Qty</th>
                <th>Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $line):
                $unit = $line['product']['unit_label'];
                ?>
                <tr>
                  <td>
                    <strong>
                      <?= esc($line['product']['name']) ?>
                    </strong><br>
                    <span style="color:var(--muted);font-size:0.82rem;">Rp
                      <?= number_format($line['product']['base_price'], 0, ',', '.') ?> /
                      <?= esc($unit) ?>
                    </span>
                  </td>
                  <td style="font-size:0.85rem;color:var(--muted);">
                    <?= esc(date('d M Y H:i', strtotime($line['start_at']))) ?><br>
                    s/d
                    <?= esc(date('d M Y H:i', strtotime($line['end_at']))) ?>
                  </td>
                  <td>
                    <form method="post" action="<?= base_url('/keranjang/update') ?>"
                      style="display:flex;gap:6px;align-items:center;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="key" value="<?= esc($line['key']) ?>">
                      <input type="number" name="qty" class="qty-input" min="1" value="<?= esc($line['qty']) ?>">
                      <button type="submit" class="btn btn-outline"
                        style="padding:6px 10px;font-size:0.78rem;">Ubah</button>
                    </form>
                  </td>
                  <td>Rp
                    <?= number_format($line['line_total'], 0, ',', '.') ?>
                  </td>
                  <td>
                    <a href="<?= base_url('/keranjang/hapus/' . $line['key']) ?>" class="cart-remove"
                      onclick="return confirm('Hapus item ini dari keranjang?');">Hapus</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="cart-summary">
          <h3 style="font-size:1.05rem;margin-bottom:16px;">Ringkasan</h3>
          <div class="summary-row">
            <span>Subtotal</span>
            <span>Rp
              <?= number_format($subtotal, 0, ',', '.') ?>
            </span>
          </div>
          <div class="summary-row" style="color:var(--muted);font-size:0.85rem;">
            <span>Biaya tambahan & deposit</span>
            <span>Dihitung di langkah berikutnya</span>
          </div>
          <div class="summary-row total">
            <span>Estimasi Total</span>
            <span>Rp
              <?= number_format($subtotal, 0, ',', '.') ?>
            </span>
          </div>
          <a href="<?= base_url('/checkout') ?>" class="btn btn-primary"
            style="width:100%;justify-content:center;margin-top:16px;">Lanjut ke Data Penyewa</a>
          <a href="<?= base_url('/katalog') ?>" class="btn btn-outline"
            style="width:100%;justify-content:center;margin-top:10px;">Tambah Item Lain</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?= view('partials/footer') ?>