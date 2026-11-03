<?php
$bizName = $business['business_name'] ?? 'Rental Universal';
$bizAddress = $business['address'] ?? '';
$bizPhone = $business['phone'] ?? '';
$bizEmail = $business['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= esc($booking['invoice_no']) ?> — <?= esc($bizName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            padding: 30px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            border: 1px solid #e2e8f0;
            padding: 30px;
            border-radius: 8px;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .table-invoice th {
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            font-size: 13px;
            text-transform: uppercase;
        }
        .table-invoice td {
            font-size: 14px;
            padding: 10px 8px;
        }
        @media print {
            body { padding: 0; }
            .invoice-box { border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="invoice-box">
        <!-- Action Buttons -->
        <div class="no-print d-flex justify-content-between mb-4 pb-2 border-bottom">
            <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                Cetak Dokumen / Simpan PDF
            </button>
        </div>

        <!-- Header -->
        <div class="row align-items-center mb-4">
            <div class="col-7">
                <div class="header-title"><?= esc($bizName) ?></div>
                <div class="text-muted small"><?= esc($bizAddress) ?></div>
                <div class="text-muted small">Telp: <?= esc($bizPhone) ?> | Email: <?= esc($bizEmail) ?></div>
            </div>
            <div class="col-5 text-end">
                <h3 class="fw-bold mb-1 text-primary">INVOICE</h3>
                <div class="font-monospace fw-bold fs-5"><?= esc($booking['invoice_no']) ?></div>
                <div class="small text-muted">Tanggal: <?= format_datetime($booking['created_at'], false) ?></div>
                <div class="mt-1">
                    <?= booking_status_badge($booking['status']) ?>
                    <?= payment_status_badge($booking['payment_status']) ?>
                </div>
            </div>
        </div>

        <hr class="my-3">

        <!-- Info Penerima & Sewa -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="fw-bold text-muted small text-uppercase mb-1">Ditagihkan Kepada:</div>
                <div class="fw-bold fs-6"><?= esc($booking['customer_name_snapshot']) ?></div>
                <div class="small text-muted"><?= esc($booking['customer_phone_snapshot']) ?></div>
                <?php if (!empty($booking['customer_email_snapshot'])): ?>
                    <div class="small text-muted"><?= esc($booking['customer_email_snapshot']) ?></div>
                <?php endif; ?>
                <?php if (!empty($booking['pickup_address'])): ?>
                    <div class="small mt-1 text-muted">Alamat: <?= esc($booking['pickup_address']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <div class="fw-bold text-muted small text-uppercase mb-1">Periode Rental:</div>
                <div class="fw-bold small"><?= format_datetime($booking['start_at']) ?></div>
                <div class="small text-muted">s/d</div>
                <div class="fw-bold small"><?= format_datetime($booking['end_at']) ?></div>
                <div class="small text-muted mt-1">Metode: <strong><?= esc($booking['fulfillment_method'] ?? 'SELF_PICKUP') ?></strong></div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table table-invoice mb-4">
            <thead>
                <tr>
                    <th>Item Rental</th>
                    <th>Durasi</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= esc($it['item_name_snapshot']) ?></div>
                            <small class="text-muted"><?= esc($it['item_type_snapshot']) ?></small>
                        </td>
                        <td><?= (int)$it['duration_value'] ?> <?= esc($it['duration_unit']) ?></td>
                        <td class="text-end"><?= rupiah($it['unit_price']) ?></td>
                        <td class="text-center"><?= (int)$it['quantity'] ?></td>
                        <td class="text-end fw-bold"><?= rupiah($it['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="row justify-content-end mb-4">
            <div class="col-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Subtotal:</td>
                        <td class="text-end"><?= rupiah($booking['subtotal']) ?></td>
                    </tr>
                    <?php if ((float)$booking['discount_total'] > 0): ?>
                        <tr>
                            <td class="text-success">Diskon:</td>
                            <td class="text-end text-success">- <?= rupiah($booking['discount_total']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ((float)$booking['charge_total'] > 0): ?>
                        <tr>
                            <td class="text-muted">Biaya Tambahan:</td>
                            <td class="text-end"><?= rupiah($booking['charge_total']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ((float)$booking['deposit_total'] > 0): ?>
                        <tr>
                            <td class="text-muted">Uang Jaminan:</td>
                            <td class="text-end"><?= rupiah($booking['deposit_total']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="border-top">
                        <td class="fw-bold fs-6">Grand Total:</td>
                        <td class="text-end fw-bold fs-6 text-primary"><?= rupiah($booking['grand_total']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Sudah Dibayar:</td>
                        <td class="text-end text-success fw-bold"><?= rupiah($booking['paid_total']) ?></td>
                    </tr>
                    <tr class="border-top">
                        <td class="fw-bold">Sisa Tagihan:</td>
                        <td class="text-end fw-bold text-danger"><?= rupiah($booking['balance_due']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes & Signature -->
        <div class="row pt-4 border-top">
            <div class="col-8">
                <div class="small text-muted">
                    <strong>Catatan:</strong>
                    <ul class="mb-0 ps-3">
                        <li>Bukti pembayaran sah ini diterbitkan secara elektronik oleh sistem Rental Universal.</li>
                        <li>Pastikan unit dikembalikan tepat waktu dan dalam kondisi bersih sesuai ketentuan.</li>
                    </ul>
                </div>
            </div>
            <div class="col-4 text-center">
                <div class="small text-muted mb-4"><?= esc($bizName) ?></div>
                <div class="fw-bold mt-4 pt-3 border-top d-inline-block px-3">Management</div>
            </div>
        </div>
    </div>

</body>
</html>

