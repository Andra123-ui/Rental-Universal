<?php
/**
 * @var array $booking
 * @var int|null $secondsLeft  sisa detik batas bayar (null bila bukan PENDING+UNPAID)
 * @var int $freezeLeft        sisa detik freeze booking setelah booking EXPIRED
 */
$secondsLeft = $secondsLeft ?? null;
$freezeLeft = $freezeLeft ?? 0;
$isExpired = $booking['status'] === 'EXPIRED';
?>
<?= view('partials/header', ['title' => $isExpired ? 'Waktu Pembayaran Habis' : 'Booking Berhasil']) ?>

<section>
    <div class="wrap">
        <div class="success-box">
            <div class="icon-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8.5 15l2 2 4-4" />
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                </svg>
            </div>

            <?php if ($isExpired): ?>
            <h1 style="font-size:1.6rem;">Waktu Pembayaran Habis</h1>
            <p style="color:var(--muted);margin-top:10px;">
                Booking ini dibatalkan otomatis dan stok sudah dilepas kembali.
            </p>
            <?php else: ?>
            <h1 style="font-size:1.6rem;">Booking Berhasil Dibuat!</h1>
            <p style="color:var(--muted);margin-top:10px;">
                Simpan nomor invoice di bawah ini untuk memantau status, pembayaran, dan riwayat booking Anda kapan
                saja.
            </p>
            <?php endif; ?>

            <div class="invoice-box" id="invoice-no">
                <?= esc($booking['invoice_no']) ?>
            </div>
            <button type="button" onclick="copyInvoice()" class="btn btn-outline" style="margin-bottom:10px;">Salin
                Nomor
                Invoice</button>

            <?php if ($isExpired): ?>
            <div class="alert-box" style="text-align:left;margin-top:20px;background:#fee2e2;color:#991b1b;">
                Status booking: <strong><?= esc($booking['status']) ?></strong> &middot;
                Status pembayaran: <strong><?= esc($booking['payment_status']) ?></strong><br>
                <span id="freeze-msg" <?= $freezeLeft > 0 ? '' : 'style="display:none;"' ?>>
                    Anda bisa booking lagi dalam <strong id="freeze-timer"
                        data-left="<?= (int) $freezeLeft ?>">--:--</strong>.
                </span>
                <span id="freeze-done" <?= $freezeLeft > 0 ? 'style="display:none;"' : '' ?>>
                    Anda sudah bisa membuat booking baru.
                </span>
            </div>
            <?php else: ?>
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
                <?php if ($secondsLeft !== null): ?>
                <br>Selesaikan pembayaran dalam <strong id="pay-timer"
                    data-left="<?= (int) $secondsLeft ?>">--:--</strong>.
                Stok dikunci untuk Anda selama waktu ini, lewat dari itu booking dibatalkan otomatis.
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px;">
                <?php if (!$isExpired): ?>
                <a href="<?= base_url('/track/' . $booking['invoice_no'] . '/payment') ?>" class="btn btn-primary">Bayar
                    Sekarang</a>
                <?php endif; ?>
                <a href="<?= base_url('/track/' . $booking['invoice_no']) ?>" class="btn btn-outline">Lihat Detail
                    Booking</a>
                <?php if ($isExpired): ?>
                <a href="<?= base_url('/catalog') ?>" class="btn btn-primary">Booking Ulang</a>
                <?php endif; ?>
            </div>
            <a href="<?= base_url('/') ?>"
                style="display:inline-block;margin-top:20px;color:var(--muted);font-size:0.88rem;">&larr; Kembali ke
                Beranda</a>
        </div>
    </div>
</section>

<script>
function copyInvoice() {
    const text = document.getElementById('invoice-no').innerText;
    navigator.clipboard.writeText(text).then(() => alert('Nomor invoice disalin: ' + text));
}

// Hitung mundur dari sisa detik yang dihitung server (bukan jam browser)
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

// Batas bayar habis -> reload, server menandai booking EXPIRED dan melepas stok
runCountdown(document.getElementById('pay-timer'), function() {
    location.reload();
});

// Freeze selesai -> tampilkan pesan boleh booking lagi
runCountdown(document.getElementById('freeze-timer'), function() {
    document.getElementById('freeze-msg').style.display = 'none';
    document.getElementById('freeze-done').style.display = '';
});
</script>

<?= view('partials/footer') ?>