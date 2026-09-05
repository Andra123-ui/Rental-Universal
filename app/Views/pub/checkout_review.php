<?= view('partials/header', ['title' => 'Review Pesanan']) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Checkout</div>
    <h1>Review Pesanan</h1>
  </div>
</div>

<div class="wrap" style="padding:50px 0;max-width:760px;">
  <div class="step-indicator">
    <div class="s-item done">1. Fulfillment</div>
    <div class="s-item active">2. Review</div>
    <div class="s-item">3. Selesai</div>
  </div>

  <div style="overflow-x:auto;margin-bottom:26px;">
    <table class="cart-table">
      <thead><tr><th>Produk</th><th>Jadwal</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
      <tbody>
        <?php foreach ($cart as $line): ?>
          <tr>
            <td><?= esc($line['name']) ?></td>
            <td><?= esc($line['start_at']) ?> &rarr; <?= esc($line['end_at']) ?></td>
            <td><?= esc($line['qty']) ?></td>
            <td>Rp<?= number_format($line['subtotal'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="cart-summary" style="max-width:340px;margin-left:auto;margin-bottom:30px;">
    <div class="summary-row total"><span>Total</span><span>Rp<?= number_format($subtotal, 0, ',', '.') ?></span></div>
  </div>

  <div class="alert-box alert-info">
    Metode: <?= esc($fulfillment['fulfillment_method']) ?><br>
    Alamat: <?= esc($fulfillment['pickup_address'] ?: '-') ?>
  </div>

  <form method="post" action="<?= base_url('/checkout/proses') ?>">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-primary">Konfirmasi & Buat Booking</button>
  </form>
</div>

<?= view('partials/footer') ?>