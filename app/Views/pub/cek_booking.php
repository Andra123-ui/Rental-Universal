<?= view('partials/header', ['title' => 'Cek Booking']) ?>

<div class="wrap">
  <div class="success-box">
    <div class="icon-circle">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M9 18h6"/></svg>
    </div>
    <h1>Cek Booking dengan Nomor Invoice Sudah Tidak Tersedia</h1>
    <p style="color:var(--muted);margin-top:10px;">Demi keamanan data Anda, seluruh riwayat transaksi kini hanya bisa dilihat setelah masuk ke akun dengan verifikasi nomor HP.</p>
    <div class="cta-actions" style="justify-content:center;">
      <a href="/account/login" class="btn btn-primary">Masuk ke Akun Saya</a>
      <a href="<?= base_url('/') ?>" class="btn btn-outline">Kembali ke Beranda</a>
    </div>
  </div>
</div>

<?= view('partials/footer') ?>