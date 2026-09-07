<?php /** @var array $booking */ ?>
<?= view('partials/header', ['title' => 'Booking Berhasil']) ?>

<section>
  <div class="wrap">
    <div class="success-box">
      <div class="icon-circle">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8.5 15l2 2 4-4" />
          <rect x="3" y="5" width="18" height="16" rx="2" />
        </svg>
      </div>
      <h1 style="font-size:1.6rem;">Booking Berhasil Dibuat!</h1>
      <p style="color:var(--muted);margin-top:10px;">
        Simpan nomor invoice di bawah ini untuk memantau status, pembayaran, dan riwayat booking Anda kapan saja.
      </p>

      <div class="invoice-box" id="invoice-no">
        <?= esc($booking['invoice_no']) ?>
      </div>
      <button type="button" onclick="copyInvoice()" class="btn btn-outline" style="margin-bottom:10px;">Salin Nomor
        Invoice</button>

      <div class="alert-box alert-info" style="text-align:left;margin-top:20px;">
        Status booking Anda saat ini: <strong>
          <?= esc($booking['status']) ?>
        </strong> &middot;
        Status pembayaran: <strong>
          <?= esc($booking['payment_status']) ?>
        </strong><br>
        Total yang perlu dibayar: <strong>Rp
          <?= number_format($booking['grand_total'], 0, ',', '.') ?>
        </strong>
        <?php if (!empty($booking['expires_at'])): ?>
          <br>Selesaikan pembayaran sebelum <strong>
            <?= esc(date('d M Y H:i', strtotime($booking['expires_at']))) ?>
          </strong> agar booking tidak otomatis dibatalkan.
        <?php endif; ?>
      </div>

      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px;">
        <a href="<?= base_url('/track/' . $booking['invoice_no'] . '/payment') ?>" class="btn btn-primary">Bayar
          Sekarang</a>
        <a href="<?= base_url('/track/' . $booking['invoice_no']) ?>" class="btn btn-outline">Lihat Detail Booking</a>
      </div>
      <a href="<?= base_url('/') ?>"
        style="display:inline-block;margin-top:20px;color:var(--muted);font-size:0.88rem;">&larr; Kembali ke Beranda</a>
    </div>
  </div>
</section>

<script>
  function copyInvoice() {
    const text = document.getElementById('invoice-no').innerText;
    navigator.clipboard.writeText(text).then(() => alert('Nomor invoice disalin: ' + text));
  }
</script>

<?= view('partials/footer') ?>