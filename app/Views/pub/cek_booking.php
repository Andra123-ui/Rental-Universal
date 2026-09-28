<?php
// Booking terakhir di sesi ini (disimpan oleh Checkout::proses), supaya invoice yang
// halamannya sudah tertutup tetap bisa dibuka lagi selama masa bayar / masa freeze.
$lastInvoice = session()->get('last_invoice');
$lastBooking = null;
$secondsLeft = null;
$freezeLeft = 0;

if ($lastInvoice) {
    (new \App\Models\AvailabilityModel())->expireStaleBookings();
    $lastBooking = model(\App\Models\BookingModel::class)->where('invoice_no', $lastInvoice)->first();

    if ($lastBooking && !empty($lastBooking['expires_at'])) {
        if ($lastBooking['status'] === 'PENDING' && $lastBooking['payment_status'] === 'UNPAID') {
            $secondsLeft = max(0, strtotime($lastBooking['expires_at']) - time());
        } elseif ($lastBooking['status'] === 'EXPIRED') {
            // 120 detik = samakan dengan Checkout::COOLDOWN_SECONDS
            $freezeLeft = max(0, strtotime($lastBooking['expires_at']) + 120 - time());
        }
    }
}
?>
<?= view('partials/header', ['title' => 'Cek Booking']) ?>

<div class="wrap">
    <?php if ($secondsLeft !== null): ?>
    <div class="alert-box alert-info" style="margin-bottom:20px;text-align:left;">
        Pembayaran booking <strong><?= esc($lastBooking['invoice_no']) ?></strong> belum selesai.
        Sisa waktu <strong id="pay-timer" data-left="<?= (int) $secondsLeft ?>">--:--</strong>.
        <div style="margin-top:12px;">
            <a href="<?= base_url('/checkout/berhasil/' . $lastBooking['invoice_no']) ?>"
                class="btn btn-primary">Lanjutkan
                Pembayaran</a>
        </div>
    </div>
    <?php elseif ($freezeLeft > 0): ?>
    <div class="alert-box" style="margin-bottom:20px;text-align:left;background:#fee2e2;color:#991b1b;">
        Booking <strong><?= esc($lastBooking['invoice_no']) ?></strong> dibatalkan karena waktu pembayaran habis.
        Anda bisa booking lagi dalam <strong id="freeze-timer" data-left="<?= (int) $freezeLeft ?>">--:--</strong>.
    </div>
    <?php endif; ?>

    <div class="success-box">
        <div class="icon-circle">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="6" y="2" width="12" height="20" rx="2" />
                <path d="M9 18h6" />
            </svg>
        </div>
        <h1>Cek Booking dengan Nomor Invoice Sudah Tidak Tersedia</h1>
        <p style="color:var(--muted);margin-top:10px;">Demi keamanan data Anda, seluruh riwayat transaksi kini hanya
            bisa dilihat setelah masuk ke akun dengan verifikasi nomor HP.</p>
        <div class="cta-actions" style="justify-content:center;">
            <a href="/account/login" class="btn btn-primary">Masuk ke Akun Saya</a>
            <a href="<?= base_url('/') ?>" class="btn btn-outline">Kembali ke Beranda</a>
        </div>
    </div>
</div>

<script>
function runCountdown(el, onDone) {
    if (!el) return;
    let left = parseInt(el.dataset.left, 10) || 0;
    (function tick() {
        if (left <= 0) {
            onDone();
            return;
        }
        el.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        left--;
        setTimeout(tick, 1000);
    })();
}
// Selesai -> reload, server menandai EXPIRED / menyembunyikan kartu
runCountdown(document.getElementById('pay-timer'), function() {
    location.reload();
});
runCountdown(document.getElementById('freeze-timer'), function() {
    location.reload();
});
</script>

<?= view('partials/footer') ?>