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
    <title>Kwitansi <?= esc($payment['payment_no']) ?> — <?= esc($bizName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            padding: 40px;
        }
        .receipt-box {
            max-width: 750px;
            margin: auto;
            border: 2px solid #cbd5e1;
            padding: 35px;
            border-radius: 8px;
            position: relative;
        }
        .watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 80px;
            color: rgba(22, 163, 74, 0.12);
            font-weight: 900;
            text-transform: uppercase;
            pointer-events: none;
            letter-spacing: 10px;
        }
        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a8a;
        }
        @media print {
            body { padding: 0; }
            .receipt-box { border: 1px solid #94a3b8; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="receipt-box">
        <div class="watermark">LUNAS</div>

        <!-- Action Buttons -->
        <div class="no-print d-flex justify-content-between mb-4 pb-2 border-bottom">
            <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm">
                &larr; Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                Cetak Kwitansi / Simpan PDF
            </button>
        </div>

        <!-- Header -->
        <div class="row align-items-center mb-4 pb-3 border-bottom">
            <div class="col-7">
                <div class="header-title"><?= esc($bizName) ?></div>
                <div class="text-muted small"><?= esc($bizAddress) ?></div>
                <div class="text-muted small">Telp: <?= esc($bizPhone) ?> | Email: <?= esc($bizEmail) ?></div>
            </div>
            <div class="col-5 text-end">
                <h4 class="fw-bold mb-1 text-success">KWITANSI RESMI</h4>
                <div class="font-monospace fw-bold fs-6"><?= esc($payment['payment_no']) ?></div>
                <div class="small text-muted">Tanggal: <?= format_datetime($payment['paid_at'] ?? $payment['created_at'], false) ?></div>
            </div>
        </div>

        <!-- Detail Pembayaran Table -->
        <table class="table table-borderless my-4">
            <tbody>
                <tr>
                    <td class="text-muted" style="width: 200px;">Telah Diterima Dari:</td>
                    <td class="fw-bold fs-6">: <?= esc($payment['customer_name_snapshot'] ?? $customer['name']) ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Nomor WhatsApp:</td>
                    <td>: <?= esc($payment['customer_phone_snapshot'] ?? $customer['phone']) ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Untuk Pembayaran:</td>
                    <td>: Pelunasan Rental — Nomor Invoice <strong><?= esc($payment['invoice_no']) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Metode Pembayaran:</td>
                    <td>: <?= esc($payment['method']) ?> (<?= esc($payment['provider'] ?: 'Transfer') ?>)</td>
                </tr>
                <tr>
                    <td class="text-muted">Nominal Pembayaran:</td>
                    <td>
                        : <span class="badge bg-success-subtle text-success fs-5 px-3 py-1 fw-bold"><?= rupiah($payment['amount']) ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Status Transaksi:</td>
                    <td>: <span class="text-success fw-bold">BERHASIL / LUNAS (PAID)</span></td>
                </tr>
            </tbody>
        </table>

        <!-- Summary & Signatures -->
        <div class="row mt-5 pt-3 border-top">
            <div class="col-8">
                <div class="small text-muted">
                    <strong>Keterangan:</strong><br>
                    Kwitansi ini adalah bukti pembayaran yang sah yang dikeluarkan secara elektronik oleh sistem <?= esc($bizName) ?>.
                </div>
            </div>
            <div class="col-4 text-center">
                <div class="small text-muted mb-4"><?= esc($bizName) ?> Finance</div>
                <div class="fw-bold mt-4 pt-3 border-top d-inline-block px-3">Tanda Terima Sah</div>
            </div>
        </div>
    </div>

</body>
</html>

