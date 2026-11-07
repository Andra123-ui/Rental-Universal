<?php
$rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$dt = static function ($v) {
    if (!$v) { return '-'; }
    $t = strtotime($v);
    return date('H:i', $t) === '00:00' ? date('d/m/Y', $t) : date('d/m/Y H:i', $t);
};
$qty = static fn ($v) => rtrim(rtrim((string) $v, '0'), '.');
$bizName = $business['business_name'] ?? 'Rental Universal';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice <?= esc($bk['invoice_no']) ?></title>
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 24px;
        background: #EEF2F7;
        color: #172554;
        font-family: 'Segoe UI', Roboto, Arial, sans-serif;
        font-size: 14px;
        line-height: 1.5;
    }

    .sheet {
        max-width: 780px;
        margin: 0 auto;
        padding: 36px 40px;
        background: #fff;
        border: 1px solid #D9E7F7;
    }

    .head {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 24px;
    }

    h1 {
        margin: 0;
        font-size: 22px;
    }

    .muted {
        color: #64748B;
        font-size: 12px;
    }

    .right {
        text-align: right;
    }

    .meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 20px;
        padding: 14px 16px;
        background: #F5F9FF;
        border-radius: 8px;
    }

    .meta h3 {
        margin: 0 0 4px;
        font-size: 11px;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #64748B;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    th,
    td {
        padding: 9px 8px;
        border-bottom: 1px solid #E2E8F0;
        text-align: left;
        vertical-align: top;
    }

    th {
        font-size: 11px;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #64748B;
    }

    .num {
        text-align: right;
        white-space: nowrap;
    }

    .totals {
        margin-left: auto;
        max-width: 320px;
    }

    .totals td {
        border: 0;
        padding: 4px 8px;
    }

    .totals .grand td {
        border-top: 2px solid #172554;
        font-weight: 700;
        padding-top: 8px;
    }

    .stamp {
        display: inline-block;
        padding: 3px 12px;
        border: 2px solid #166534;
        border-radius: 6px;
        color: #166534;
        font-weight: 700;
        letter-spacing: .06em;
    }

    .stamp.warn {
        border-color: #B45309;
        color: #B45309;
    }

    .stamp.off {
        border-color: #B91C1C;
        color: #B91C1C;
    }

    .foot {
        margin-top: 28px;
        font-size: 12px;
        color: #64748B;
    }

    .bar {
        max-width: 780px;
        margin: 0 auto 12px;
        display: flex;
        gap: 8px;
    }

    .bar button,
    .bar a {
        padding: 8px 16px;
        border: 1px solid #D9E7F7;
        border-radius: 8px;
        background: #fff;
        color: #172554;
        font: inherit;
        text-decoration: none;
        cursor: pointer;
    }

    @media print {
        body {
            padding: 0;
            background: #fff;
        }

        .sheet {
            border: 0;
            padding: 0;
            max-width: none;
        }

        .bar {
            display: none;
        }
    }
    </style>
</head>

<body>
    <div class="bar">
        <button type="button" onclick="window.print()">Cetak / simpan PDF</button>
        <a href="javascript:window.close()">Tutup</a>
    </div>

    <div class="sheet">
        <div class="head">
            <div>
                <h1><?= esc($bizName) ?></h1>
                <?php if ($branch): ?>
                <div class="muted">
                    <?= esc($branch['branch_name']) ?>
                    <?php if (!empty($branch['address'])): ?><br><?= esc($branch['address']) ?><?php endif; ?>
                    <?php if (!empty($branch['phone'])): ?><br><?= esc($branch['phone']) ?><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="right">
                <h1>INVOICE</h1>
                <div><strong><?= esc($bk['invoice_no']) ?></strong></div>
                <div class="muted">Dibuat <?= esc($dt($bk['created_at'])) ?></div>
                <?php
                    $stampClass = $bk['payment_status'] === 'PAID' ? '' : (in_array($bk['status'], ['CANCELLED', 'EXPIRED'], true) ? 'off' : 'warn');
                    $stampText  = in_array($bk['status'], ['CANCELLED', 'EXPIRED'], true) ? $bk['status'] : ($bk['payment_status'] === 'PAID' ? 'LUNAS' : 'BELUM LUNAS');
                ?>
                <div style="margin-top:8px;"><span
                        class="stamp <?= esc($stampClass, 'attr') ?>"><?= esc($stampText) ?></span></div>
            </div>
        </div>

        <div class="meta">
            <div>
                <h3>Customer</h3>
                <strong><?= esc($bk['customer_name_snapshot']) ?></strong><br>
                <?= esc($bk['customer_phone_snapshot']) ?>
                <?php if (!empty($bk['customer_email_snapshot'])): ?><br><?= esc($bk['customer_email_snapshot']) ?><?php endif; ?>
            </div>
            <div>
                <h3>Periode sewa</h3>
                <?= esc($dt($bk['start_at'])) ?> – <?= esc($dt($bk['end_at'])) ?><br>
                <span class="muted"><?= esc(str_replace('_', ' ', (string) $bk['fulfillment_method'])) ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num">Qty</th>
                    <th class="num">Harga</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                <tr>
                    <td><?= esc($i['item_name_snapshot']) ?></td>
                    <td class="num"><?= esc($qty($i['quantity'])) ?></td>
                    <td class="num"><?= esc($rp($i['unit_price'])) ?></td>
                    <td class="num"><?= esc($rp($i['subtotal'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php foreach ($charges as $c): ?>
                <tr>
                    <td><?= esc($c['description']) ?></td>
                    <td class="num"><?= esc($qty($c['quantity'])) ?></td>
                    <td class="num"><?= esc($rp($c['unit_price'])) ?></td>
                    <td class="num"><?= esc($rp($c['amount'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Subtotal</td>
                <td class="num"><?= esc($rp($bk['subtotal'])) ?></td>
            </tr>
            <?php if ((float) $bk['discount_total'] > 0): ?>
            <tr>
                <td>Diskon</td>
                <td class="num">- <?= esc($rp($bk['discount_total'])) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float) $bk['charge_total'] > 0): ?>
            <tr>
                <td>Biaya tambahan</td>
                <td class="num"><?= esc($rp($bk['charge_total'])) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float) $bk['deposit_total'] > 0): ?>
            <tr>
                <td>Deposit</td>
                <td class="num"><?= esc($rp($bk['deposit_total'])) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="grand">
                <td>Total</td>
                <td class="num"><?= esc($rp($bk['grand_total'])) ?></td>
            </tr>
            <tr>
                <td>Terbayar</td>
                <td class="num"><?= esc($rp($bk['paid_total'])) ?></td>
            </tr>
            <tr>
                <td><strong>Sisa tagihan</strong></td>
                <td class="num"><strong><?= esc($rp($bk['balance_due'])) ?></strong></td>
            </tr>
        </table>

        <div class="foot">
            Dokumen ini dicetak dari sistem pada <?= esc(date('d/m/Y H:i')) ?>.
            Deposit (jika ada) dikembalikan sesuai ketentuan setelah unit dikembalikan dalam kondisi baik.
        </div>
    </div>
</body>

</html>