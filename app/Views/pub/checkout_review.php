<?php
/**
 * @var array $items
 * @var float $subtotal
 * @var float $depositTotal
 * @var float $grandTotal
 * @var array $customer
 * @var array $fulfillment
 */
$fulfillmentLabels = [
  'SELF_PICKUP' => 'Ambil Sendiri',
  'DELIVERY' => 'Diantar',
  'ONSITE' => 'Di Lokasi Anda',
  'SERVICE' => 'Jasa/Personel',
];
?>
<?= view('partials/header', ['title' => 'Review Booking']) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Review Booking</div>
    <h1>Review Booking & Harga</h1>
  </div>
</div>

<section>
  <div class="wrap">
    <div class="step-indicator">
      <div class="s-item done">1. Keranjang</div>
      <div class="s-item done">2. Data Penyewa</div>
      <div class="s-item done">3. Fulfillment</div>
      <div class="s-item active">4. Review</div>
      <div class="s-item">5. Selesai</div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert-box alert-warn">
        <?= esc(session()->getFlashdata('error')) ?>
      </div>
    <?php endif; ?>

    <div class="detail-grid">
      <div>
        <h3 style="font-size:1.05rem;margin-bottom:14px;">Data Penyewa</h3>
        <div class="cart-summary" style="margin-bottom:24px;">
          <div class="summary-row"><span>Nama</span><span>
              <?= esc($customer['name']) ?>
            </span></div>
          <div class="summary-row"><span>No. HP</span><span>
              <?= esc($customer['phone']) ?>
            </span></div>
          <div class="summary-row"><span>Email</span><span>
              <?= esc($customer['email'] ?: '-') ?>
            </span></div>
          <div class="summary-row" style="border-bottom:none;">
            <span>Fulfillment</span><span>
              <?= esc($fulfillmentLabels[$fulfillment['fulfillment_method']] ?? $fulfillment['fulfillment_method']) ?>
            </span>
          </div>
        </div>

        <h3 style="font-size:1.05rem;margin-bottom:14px;">Item Booking</h3>
        <table class="cart-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Jadwal</th>
              <th>Qty</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $line): ?>
              <tr>
                <td><strong>
                    <?= esc($line['product']['name']) ?>
                  </strong></td>
                <td style="font-size:0.85rem;color:var(--muted);">
                  <?= esc(date('d M Y H:i', strtotime($line['start_at']))) ?> s/d
                  <?= esc(date('d M Y H:i', strtotime($line['end_at']))) ?>
                </td>
                <td>
                  <?= esc($line['qty']) ?>
                </td>
                <td>Rp
                  <?= number_format($line['line_total'], 0, ',', '.') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="cart-summary">
        <h3 style="font-size:1.05rem;margin-bottom:16px;">Rincian Biaya</h3>
        <div class="summary-row"><span>Subtotal Sewa</span><span>Rp
            <?= number_format($subtotal, 0, ',', '.') ?>
          </span></div>
        <?php if ($depositTotal > 0): ?>
          <div class="summary-row"><span>Deposit/Jaminan</span><span>Rp
              <?= number_format($depositTotal, 0, ',', '.') ?>
            </span></div>
        <?php endif; ?>
        <div class="summary-row total"><span>Grand Total</span><span>Rp
            <?= number_format($grandTotal, 0, ',', '.') ?>
          </span></div>

        <?php if ($depositTotal > 0): ?>
          <p style="font-size:0.78rem;color:var(--muted);margin-top:10px;">
            *Deposit bersifat refundable dan akan dikembalikan sesuai syarat & ketentuan setelah barang dikembalikan dalam
            kondisi baik.
          </p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('/checkout/proses') ?>" style="margin-top:18px;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Konfirmasi
            Booking</button>
        </form>
        <a href="<?= base_url('/checkout/fulfillment') ?>" class="btn btn-outline"
          style="width:100%;justify-content:center;margin-top:10px;">Kembali</a>
      </div>
    </div>
  </div>
</section>

<?= view('partials/footer') ?>