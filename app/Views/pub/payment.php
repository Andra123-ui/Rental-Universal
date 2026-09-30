<?php
/**
 * @var array $booking
 * @var int $secondsLeft
 */
?>

<?= view('partials/header', ['title' => 'Pembayaran']) ?>

<section class="payment-page">
    <div class="wrap">

        <div class="payment-container">

            <div class="payment-header">
                <span class="payment-label">PEMBAYARAN</span>

                <h1>Selesaikan Pembayaran</h1>

                <p>
                    Booking Anda sudah berhasil dibuat.
                    Selesaikan pembayaran sebelum waktu habis.
                </p>
            </div>

            <!-- COUNTDOWN -->
            <div class="payment-countdown">
                <span>Bayar sebelum</span>

                <strong id="pay-timer" data-left="<?= (int) $secondsLeft ?>">
                    --:--
                </strong>
            </div>

            <!-- INVOICE -->
            <div class="payment-card">

                <div class="payment-row">
                    <span>Nomor Invoice</span>
                    <strong>
                        <?= esc($booking['invoice_no']) ?>
                    </strong>
                </div>

                <div class="payment-row">
                    <span>Status Booking</span>
                    <strong>
                        <?= esc($booking['status']) ?>
                    </strong>
                </div>

                <div class="payment-row">
                    <span>Status Pembayaran</span>
                    <strong>
                        <?= esc($booking['payment_status']) ?>
                    </strong>
                </div>

                <div class="payment-divider"></div>

                <div class="payment-total">
                    <span>Total Pembayaran</span>

                    <strong>
                        Rp <?= number_format(
                            $booking['grand_total'],
                            0,
                            ',',
                            '.'
                        ) ?>
                    </strong>
                </div>

            </div>

            <!-- FORM PAYMENT -->
            <form action="<?= base_url('/track/' . $booking['invoice_no'] . '/payment/process') ?>" method="post"
                id="payment-form">

                <?= csrf_field() ?>

                <div class="payment-card">

                    <h3>Metode Pembayaran</h3>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="BANK_TRANSFER" checked>

                        <div>
                            <strong>Transfer Bank</strong>
                            <small>
                                Pembayaran melalui transfer bank
                            </small>
                        </div>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="QRIS">

                        <div>
                            <strong>QRIS</strong>
                            <small>
                                Bayar menggunakan QRIS
                            </small>
                        </div>
                    </label>

                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="EWALLET">

                        <div>
                            <strong>E-Wallet</strong>
                            <small>
                                GoPay, OVO, DANA, dan lainnya
                            </small>
                        </div>
                    </label>

                </div>

                <button type="submit" class="btn btn-primary payment-button">
                    Bayar Sekarang
                    <span>
                        Rp <?= number_format(
                            $booking['grand_total'],
                            0,
                            ',',
                            '.'
                        ) ?>
                    </span>
                </button>

            </form>

            <a href="<?= base_url('/track/' . $booking['invoice_no']) ?>" class="back-link">
                &larr; Kembali ke Detail Booking
            </a>

        </div>

    </div>
</section>

<style>
.payment-page {
    padding: 50px 0 80px;
}

.payment-container {
    max-width: 680px;
    margin: auto;
}

.payment-header {
    text-align: center;
    margin-bottom: 28px;
}

.payment-label {
    display: inline-block;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .12em;
    color: #2563eb;
    margin-bottom: 10px;
}

.payment-header h1 {
    margin: 0;
    font-size: 2rem;
}

.payment-header p {
    color: var(--muted);
    margin-top: 10px;
}

.payment-countdown {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    margin-bottom: 16px;
    border-radius: 14px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
}

.payment-countdown span {
    color: #475569;
}

.payment-countdown strong {
    font-size: 1.5rem;
    color: #dc2626;
    font-variant-numeric: tabular-nums;
}

.payment-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 16px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 9px 0;
}

.payment-row span {
    color: #64748b;
}

.payment-row strong {
    text-align: right;
}

.payment-divider {
    border-top: 1px solid #e5e7eb;
    margin: 14px 0;
}

.payment-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.payment-total span {
    font-weight: 600;
}

.payment-total strong {
    font-size: 1.35rem;
    color: #2563eb;
}

.payment-card h3 {
    margin-top: 0;
    margin-bottom: 16px;
}

.payment-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 15px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-top: 10px;
    cursor: pointer;
}

.payment-option:hover {
    border-color: #2563eb;
    background: #f8fbff;
}

.payment-option input {
    width: 18px;
    height: 18px;
}

.payment-option div {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.payment-option small {
    color: #64748b;
}

.payment-button {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 20px;
    border: 0;
    cursor: pointer;
}

.payment-button span {
    font-weight: 700;
}

.back-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: var(--muted);
    text-decoration: none;
}

.back-link:hover {
    color: #2563eb;
}
</style>

<script>
(function() {

    const timer = document.getElementById('pay-timer');
    const form = document.getElementById('payment-form');

    if (!timer) return;

    let left = parseInt(timer.dataset.left, 10) || 0;

    function tick() {

        if (left <= 0) {
            timer.textContent = '00:00';

            // Jangan izinkan submit setelah waktu habis
            if (form) {
                form.style.pointerEvents = 'none';
                form.style.opacity = '.6';
            }

            setTimeout(function() {
                location.reload();
            }, 1000);

            return;
        }

        const minutes = Math.floor(left / 60);
        const seconds = left % 60;

        timer.textContent =
            String(minutes).padStart(2, '0') +
            ':' +
            String(seconds).padStart(2, '0');

        left--;

        setTimeout(tick, 1000);
    }

    tick();

})();
</script>

<?= view('partials/footer') ?>