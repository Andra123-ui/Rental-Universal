<?php
/**
 * @var array $booking
 * @var int $secondsLeft
 */
?>
<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="mb-4">
            <h4 class="fw-bold mb-1">Selesaikan Pembayaran</h4>
            <p class="text-muted small mb-0">Selesaikan pembayaran sebelum waktu habis.</p>
        </div>

        <?php if (!empty($secondsLeft)): ?>
        <!-- COUNTDOWN -->
        <div class="card-custom mb-3 p-3 d-flex justify-content-between align-items-center bg-white">
            <span class="text-muted">Bayar sebelum</span>
            <strong id="pay-timer" class="fs-4 text-danger" style="font-variant-numeric:tabular-nums;"
                data-left="<?= (int) $secondsLeft ?>">--:--</strong>
        </div>
        <?php endif; ?>

        <!-- RINGKASAN -->
        <div class="card-custom mb-3 p-4">
            <div class="d-flex justify-content-between py-2">
                <span class="text-muted">Nomor Invoice</span>
                <strong class="font-monospace"><?= esc($booking['invoice_no']) ?></strong>
            </div>
            <div class="d-flex justify-content-between py-2 align-items-center">
                <span class="text-muted">Status Booking</span>
                <?= booking_status_badge($booking['status']) ?>
            </div>
            <div class="d-flex justify-content-between py-2 align-items-center">
                <span class="text-muted">Status Pembayaran</span>
                <?= payment_status_badge($booking['payment_status']) ?>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Total Pembayaran</span>
                <strong class="fs-5 text-primary"><?= rupiah($booking['grand_total']) ?></strong>
            </div>
        </div>

        <!-- FORM -->
        <form action="<?= base_url('/track/' . $booking['invoice_no'] . '/payment/process') ?>" method="post"
            id="payment-form">
            <?= csrf_field() ?>

            <div class="card-custom mb-3 p-4">
                <h6 class="fw-bold mb-3">Metode Pembayaran</h6>

                <div class="list-group">
                    <label class="list-group-item d-flex align-items-center gap-3 py-3">
                        <input class="form-check-input mt-0" type="radio" name="payment_method" value="BANK_TRANSFER"
                            checked>
                        <div>
                            <div class="fw-semibold">Transfer Bank</div>
                            <div class="text-muted small">Pembayaran melalui transfer bank</div>
                        </div>
                    </label>
                    <label class="list-group-item d-flex align-items-center gap-3 py-3">
                        <input class="form-check-input mt-0" type="radio" name="payment_method" value="QRIS">
                        <div>
                            <div class="fw-semibold">QRIS</div>
                            <div class="text-muted small">Bayar menggunakan QRIS</div>
                        </div>
                    </label>
                    <label class="list-group-item d-flex align-items-center gap-3 py-3">
                        <input class="form-check-input mt-0" type="radio" name="payment_method" value="EWALLET">
                        <div>
                            <div class="fw-semibold">E-Wallet</div>
                            <div class="text-muted small">GoPay, OVO, DANA, dan lainnya</div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit"
                class="btn btn-primary w-100 d-flex justify-content-between align-items-center py-3 px-4">
                <span>Bayar Sekarang</span>
                <span class="fw-bold"><?= rupiah($booking['grand_total']) ?></span>
            </button>
        </form>

        <a href="<?= base_url('/track/' . $booking['invoice_no']) ?>"
            class="d-block text-center text-muted text-decoration-none mt-4 small">
            &larr; Kembali ke Detail Booking
        </a>

    </div>
</div>

<script>
(function() {
    const timer = document.getElementById('pay-timer');
    const form = document.getElementById('payment-form');
    if (!timer) return;

    let left = parseInt(timer.dataset.left, 10) || 0;

    (function tick() {
        if (left <= 0) {
            timer.textContent = '00:00';
            if (form) {
                form.style.pointerEvents = 'none';
                form.style.opacity = '.6';
            }
            setTimeout(() => location.reload(), 1000);
            return;
        }
        timer.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2,
            '0');
        left--;
        setTimeout(tick, 1000);
    })();
})();
</script>

<?= $this->endSection() ?>