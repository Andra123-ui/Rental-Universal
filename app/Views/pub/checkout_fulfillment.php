<?= view('partials/header', ['title' => 'Checkout']) ?>

<div class="page-banner">
  <div class="wrap">
    <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Checkout</div>
    <h1>Checkout</h1>
  </div>
</div>

<div class="wrap" style="padding:50px 0;max-width:720px;">
  <div class="step-indicator">
    <div class="s-item active">1. Fulfillment</div>
    <div class="s-item">2. Review</div>
    <div class="s-item">3. Selesai</div>
  </div>

  <form method="post" action="<?= base_url('/checkout/fulfillment') ?>">
    <?= csrf_field() ?>
    <div class="field-group">
      <label>Metode Pengambilan / Pelaksanaan</label>
      <select name="fulfillment_method" required>
        <option value="SELF_PICKUP">Ambil sendiri</option>
        <option value="DELIVERY">Diantar</option>
        <option value="ONSITE">Dilaksanakan di lokasi</option>
        <option value="SERVICE">Jasa terjadwal</option>
      </select>
    </div>
    <div class="field-group">
      <label>Alamat Pengambilan / Pelaksanaan</label>
      <textarea name="pickup_address" rows="3" placeholder="Alamat lengkap"></textarea>
    </div>
    <div class="field-group">
      <label>Alamat Pengembalian (jika berbeda)</label>
      <textarea name="return_address" rows="3" placeholder="Opsional"></textarea>
    </div>
    <div class="field-group">
      <label>Catatan untuk Admin</label>
      <textarea name="customer_notes" rows="3" placeholder="Opsional"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Lanjut ke Review</button>
  </form>
</div>

<?= view('partials/footer') ?>