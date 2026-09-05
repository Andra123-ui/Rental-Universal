<?= view('partials/header', ['title' => 'Keranjang']) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Keranjang</div>
    <h1>Keranjang Saya</h1>
  </div>
</div>

<div class="wrap" style="padding:50px 0;">
  <?php if (empty($cart)): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/><path d="M2 3h2l2.4 12.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L21 7H6"/></svg>
      <h3>Keranjang masih kosong</h3>
      <p>Yuk mulai jelajahi katalog dan tambahkan barang atau jasa yang ingin Anda sewa.</p>
      <a href="<?= base_url('/katalog') ?>" class="btn btn-primary" style="margin-top:16px;">Lihat Katalog</a>
    </div>
  <?php else: ?>
    <form method="post" action="<?= base_url('/keranjang/update') ?>">
      <?= csrf_field() ?>
      <div style="overflow-x:auto;margin-bottom:30px;">
        <table class="cart-table">
          <thead><tr><th>Produk</th><th>Jadwal</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($cart as $id => $line):
              $unit = $unitLabels[$line['pricing_unit']] ?? strtolower($line['pricing_unit']);
            ?>
              <tr>
                <td><?= esc($line['name']) ?></td>
                <td><?= esc($line['start_at']) ?> &rarr; <?= esc($line['end_at']) ?></td>
                <td>Rp<?= number_format($line['unit_price'], 0, ',', '.') ?>/<?= esc($unit) ?></td>
                <td><input class="qty-input" type="number" name="qty[<?= esc($id) ?>]" min="1" value="<?= esc($line['qty']) ?>"></td>
                <td>Rp<?= number_format($line['subtotal'], 0, ',', '.') ?></td>
                <td><a class="cart-remove" href="<?= base_url('/keranjang/hapus/' . $id) ?>">Hapus</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <button type="submit" class="btn btn-outline">Perbarui Keranjang</button>
    </form>

    <div style="max-width:360px;margin-left:auto;margin-top:30px;">
      <div class="cart-summary">
        <?php $total = array_sum(array_column($cart, 'subtotal')); ?>
        <div class="summary-row"><span>Subtotal</span><span>Rp<?= number_format($total, 0, ',', '.') ?></span></div>
        <div class="summary-row total"><span>Total</span><span>Rp<?= number_format($total, 0, ',', '.') ?></span></div>
        <a href="<?= base_url('/checkout') ?>" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:16px;">Lanjut Checkout</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?= view('partials/footer') ?>