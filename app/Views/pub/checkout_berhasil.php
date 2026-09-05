<?= view('partials/header', ['title' => 'Booking Berhasil']) ?>

<div class="wrap">
  <div class="success-box">
    <div class="icon-circle">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h1>Booking Berhasil Dibuat</h1>
    <p style="color:var(--muted);margin-top:10px;">Terima kasih, pesanan Anda sudah tercatat. Nomor invoice Anda:</p>
    <div class="invoice-box"><?= esc($booking['invoice_no']) ?></div>
    <div class="cta-actions" style="justify-content:center;">
      <a href="/account/bookings/<?= esc($booking['invoice_no']) ?>" class="btn btn-primary">Lihat di Akun Saya</a>
      <a href="<?= base_url('/katalog') ?>" class="btn btn-outline">Kembali ke Katalog</a>
    </div>
  </div>
</div>

<?= view('partials/footer') ?>