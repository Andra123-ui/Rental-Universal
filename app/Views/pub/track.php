<?= view('partials/header', ['title' => 'Detail Booking']) ?>

<section>
    <div class="wrap">

        <div class="success-box">

            <div class="icon-circle">
                ✓
            </div>

            <h1>Detail Booking</h1>

            <p style="color:var(--muted);margin-top:10px;">
                Booking Anda berhasil ditemukan.
            </p>

            <div class="invoice-box">
                <?= esc($booking['invoice_no']) ?>
            </div>

            <div class="alert-box alert-info" style="text-align:left;margin-top:20px;">

                <p>
                    <strong>Status Booking:</strong>
                    <?= esc($booking['status']) ?>
                </p>

                <p>
                    <strong>Status Pembayaran:</strong>
                    <?= esc($booking['payment_status']) ?>
                </p>

                <p>
                    <strong>Nama:</strong>
                    <?= esc($booking['customer_name_snapshot']) ?>
                </p>

                <p>
                    <strong>No. HP:</strong>
                    <?= esc($booking['customer_phone_snapshot']) ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    Rp <?= number_format(
                        $booking['grand_total'],
                        0,
                        ',',
                        '.'
                    ) ?>
                </p>

            </div>

            <?php if (
                $booking['status'] === 'PENDING' &&
                $booking['payment_status'] === 'UNPAID'
            ): ?>

            <div style="margin-top:24px;">

                <a href="<?= base_url('/track/' . $booking['invoice_no'] . '/payment') ?>" class="btn btn-primary">
                    Bayar Sekarang
                </a>

            </div>

            <?php elseif ($booking['payment_status'] === 'PAID'): ?>

            <div style="
                        margin-top:24px;
                        padding:14px;
                        border-radius:10px;
                        background:#dcfce7;
                        color:#166534;
                    ">
                ✓ Pembayaran berhasil
            </div>

            <?php endif; ?>

            <div style="margin-top:20px;">

                <a href="<?= base_url('/account/booking') ?>" class="btn btn-outline">
                    Lihat History Booking
                </a>

            </div>

        </div>

    </div>
</section>

<?= view('partials/footer') ?>