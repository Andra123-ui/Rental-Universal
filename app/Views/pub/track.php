<?php
/**
 * @var array $booking
 * @var int|null $secondsLeft
 */
$secondsLeft = $secondsLeft ?? null;
$items       = $items ?? ($booking['items'] ?? []);
$canPay = !in_array($booking['status'], ['CANCELLED', 'EXPIRED', 'COMPLETED'], true)
    && $booking['payment_status'] !== 'PAID'
    && (float) ($booking['balance_due'] ?? $booking['grand_total']) > 0;
?>
<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <!-- HEADER -->
        <div class="card-custom mb-4 p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="text-muted small">Nomor Invoice</div>
                    <div class="fw-bold font-monospace fs-5"><?= esc($booking['invoice_no']) ?></div>
                    <?php if (!empty($booking['created_at'])): ?>
                    <div class="text-muted small">Dibuat <?= format_date_short($booking['created_at'], true) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <?= booking_status_badge($booking['status']) ?>
                    <?= payment_status_badge($booking['payment_status']) ?>
                </div>
            </div>

            <?php if ($canPay && $secondsLeft !== null): ?>
            <div class="alert alert-warning mt-3 mb-0 small">
                Selesaikan pembayaran dalam
                <strong id="pay-timer" data-left="<?= (int) $secondsLeft ?>">--:--</strong>.
                Lewat dari itu booking dibatalkan otomatis.
            </div>
            <?php endif; ?>
        </div>

        <!-- CUSTOMER -->
        <div class="card-custom mb-4 p-4">
            <h6 class="fw-bold mb-3">Data Pemesan</h6>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">Nama</span>
                <span class="fw-semibold text-end"><?= esc($booking['customer_name_snapshot'] ?? '-') ?></span>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span class="text-muted">No. HP</span>
                <span class="fw-semibold text-end"><?= esc($booking['customer_phone_snapshot'] ?? '-') ?></span>
            </div>
        </div>

        <!-- ITEM & PERIODE -->
        <div class="card-custom mb-4 p-4">
            <h6 class="fw-bold mb-3">Rincian Sewa</h6>

            <?php if (!empty($booking['start_at']) && !empty($booking['end_at'])): ?>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">Periode</span>
                <span class="fw-semibold text-end">
                    <?= format_date_short($booking['start_at']) ?> - <?= format_date_short($booking['end_at']) ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if (!empty($items)): ?>
            <?php foreach ($items as $it):
                    $q = rtrim(rtrim(number_format((float) ($it['quantity'] ?? 1), 2, '.', ''), '0'), '.');
                ?>
            <div class="d-flex justify-content-between py-2 border-bottom">
                <span><?= esc($it['item_name_snapshot']) ?></span>
                <span class="text-muted">×<?= esc($q) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center pt-3">
                <span class="fw-semibold">Total</span>
                <span class="fw-bold fs-5 text-primary"><?= rupiah($booking['grand_total']) ?></span>
            </div>

            <?php if ((float) ($booking['balance_due'] ?? 0) > 0 && !in_array($booking['status'], ['CANCELLED', 'EXPIRED'], true)): ?>
            <div class="d-flex justify-content-between pt-1">
                <span class="text-danger small fw-semibold">Sisa tagihan</span>
                <span class="text-danger small fw-semibold"><?= rupiah($booking['balance_due']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($booking['payment_status'] === 'PAID'): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle me-1"></i> Pembayaran berhasil
        </div>
        <?php endif; ?>

        <!-- AKSI -->
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($canPay): ?>
            <a href="<?= base_url('/track/' . $booking['invoice_no'] . '/payment') ?>" class="btn btn-primary">
                Bayar Sekarang
            </a>
            <?php endif; ?>
            <a href="<?= site_url('account/bookings') ?>" class="btn btn-outline-secondary">
                &larr; Riwayat Booking
            </a>
        </div>

    </div>
</div>

<script>
(function() {
    const el = document.getElementById('pay-timer');
    if (!el) return;
    let left = parseInt(el.dataset.left, 10) || 0;
    (function tick() {
        if (left <= 0) {
            location.reload();
            return;
        }
        el.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2,
            '0');
        left--;
        setTimeout(tick, 1000);
    })();
})();
</script>

<?= $this->endSection() ?>