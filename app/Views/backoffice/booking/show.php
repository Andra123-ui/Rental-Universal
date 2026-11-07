<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
$rp  = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$dt  = static function ($v) {
    if (!$v) { return '-'; }
    $t = strtotime($v);
    return date('H:i', $t) === '00:00' ? date('d/m/Y', $t) : date('d/m/Y H:i', $t);
};
$map = [
    'CONFIRMED' => 'badge-info', 'ONGOING' => 'badge-info', 'PLANNED' => 'badge-info', 'IN_PROGRESS' => 'badge-info',
    'PAID' => 'badge-ok', 'COMPLETED' => 'badge-ok', 'ACTIVE' => 'badge-ok', 'VERIFIED' => 'badge-ok',
    'GOOD' => 'badge-ok', 'RESERVED' => 'badge-info', 'HELD' => 'badge-warn',
    'PENDING' => 'badge-warn', 'UNPAID' => 'badge-warn', 'PARTIAL' => 'badge-warn', 'REPORTED' => 'badge-warn',
    'REQUIRED' => 'badge-warn', 'MINOR_DAMAGE' => 'badge-warn', 'INVOICED' => 'badge-info', 'WAIVED' => 'badge-off',
    'CANCELLED' => 'badge-danger', 'REFUNDED' => 'badge-danger', 'VOID' => 'badge-danger', 'FAILED' => 'badge-danger',
    'REJECTED' => 'badge-danger', 'SUSPENDED' => 'badge-danger', 'BLOCKED' => 'badge-danger', 'LOCKED' => 'badge-danger',
    'DAMAGED' => 'badge-danger', 'MISSING' => 'badge-danger',
];
$badge = static fn ($s) => $map[$s] ?? 'badge-off';
$nowLocal = date('Y-m-d\TH:i');
$days = max(1, (int) ceil((strtotime($bk['end_at']) - strtotime($bk['start_at'])) / 86400));
$base = site_url('backoffice/bookings/' . $seg);
$closedStatus = in_array($bk['status'], ['CANCELLED', 'EXPIRED'], true);

$activeAssign = 0;
foreach ($assignments as $a) {
    if (in_array($a['status'], ['PLANNED', 'IN_PROGRESS'], true)) { $activeAssign++; }
}
$hasPickup = $hasReturn = false;
foreach ($handovers as $h) {
    $hasPickup = $hasPickup || $h['handover_type'] === 'PICKUP';
    $hasReturn = $hasReturn || $h['handover_type'] === 'RETURN';
}
$activeItems = array_values(array_filter($items, static fn ($i) => $i['status'] !== 'CANCELLED'));
?>

<div class="page-head">
    <div>
        <a href="<?= site_url('backoffice/bookings') ?>" class="muted">&larr; Daftar booking</a>
        <h1 style="margin-top:4px;"><?= esc($bk['invoice_no']) ?></h1>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">
            <span class="badge <?= esc($badge($bk['status']), 'attr') ?>"><?= esc($bk['status']) ?></span>
            <span
                class="badge <?= esc($badge($bk['payment_status']), 'attr') ?>"><?= esc($bk['payment_status']) ?></span>
            <span class="badge badge-off"><?= esc($bk['booking_source']) ?></span>
            <?php if ($branch): ?><span class="badge badge-off"><?= esc($branch['branch_name']) ?></span><?php endif; ?>
        </div>
    </div>

    <div class="quick">
        <a href="<?= esc($invoiceUrl, 'attr') ?>" target="_blank" rel="noopener" class="btn btn-ghost">Cetak invoice</a>

        <?php if ($rescheduleUrl): ?>
        <a href="<?= esc($rescheduleUrl, 'attr') ?>" class="btn btn-ghost">Reschedule</a>
        <?php endif; ?>

        <?php if ($can['confirm']): ?>
        <form method="post" action="<?= esc($base . '/confirm', 'attr') ?>" class="inline"
            onsubmit="return confirm('Konfirmasi booking ini?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Confirm</button>
        </form>
        <?php endif; ?>

        <?php if ($can['cancel']): ?>
        <form method="post" action="<?= esc($base . '/cancel', 'attr') ?>" class="inline"
            onsubmit="return askCancel(this, <?= (float) $bk['paid_total'] > 0 ? 'true' : 'false' ?>)">
            <?= csrf_field() ?>
            <input type="hidden" name="reason" value="">
            <input type="hidden" name="allow_paid" value="1">
            <button type="submit" class="btn btn-ghost btn-danger">Cancel booking</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($bk['status'] === 'PENDING' && !empty($bk['expires_at'])): ?>
<div class="alert alert-error">Hold unit berakhir pada <?= esc($dt($bk['expires_at'])) ?>. Setelah itu booking otomatis
    EXPIRED dan unit dilepas.</div>
<?php endif; ?>
<?php if ($bk['status'] === 'PENDING' && $activeAlloc === 0): ?>
<div class="alert alert-error">Semua alokasi unit sudah dilepas, jadi booking ini tidak bisa dikonfirmasi.</div>
<?php endif; ?>
<?php if ($bk['status'] === 'CANCELLED' && (float) $bk['paid_total'] > 0): ?>
<div class="alert alert-error">Booking dibatalkan tetapi pembayaran <?= esc($rp($bk['paid_total'])) ?> belum
    dikembalikan. Catat refund di tab Pembayaran.</div>
<?php endif; ?>

<!-- ============ Ringkasan ============ -->
<div class="detail-grid">

    <div class="panel">
        <h2 class="panel-title">Customer</h2>
        <dl class="dl">
            <dt>Nama</dt>
            <dd><?= esc($bk['customer_name_snapshot']) ?></dd>
            <dt>Telepon</dt>
            <dd><?= esc($bk['customer_phone_snapshot']) ?></dd>
            <?php if (!empty($bk['customer_email_snapshot'])): ?>
            <dt>Email</dt>
            <dd><?= esc($bk['customer_email_snapshot']) ?></dd>
            <?php endif; ?>
            <?php if ($customer && !empty($customer['customer_code'])): ?>
            <dt>Kode</dt>
            <dd><?= esc($customer['customer_code']) ?></dd>
            <?php endif; ?>
            <dt>Akun</dt>
            <dd>
                <?php if ($account): ?>
                <span class="badge <?= esc($badge($account['status']), 'attr') ?>"><?= esc($account['status']) ?></span>
                <?php else: ?>
                <span class="badge badge-off">Tanpa akun</span>
                <?php endif; ?>
            </dd>
            <dt>Identitas</dt>
            <dd>
                <?php if ($doc): ?>
                <span
                    class="badge <?= esc($badge($doc['verification_status']), 'attr') ?>"><?= esc($doc['verification_status']) ?></span>
                <?php else: ?>
                <span class="badge badge-off">Belum ada dokumen</span>
                <?php endif; ?>
            </dd>
        </dl>
    </div>

    <div class="panel">
        <h2 class="panel-title">Ringkasan keuangan</h2>
        <dl class="dl dl-money">
            <dt>Subtotal</dt>
            <dd><?= esc($rp($bk['subtotal'])) ?></dd>
            <?php if ((float) $bk['discount_total'] > 0): ?>
            <dt>Diskon</dt>
            <dd>- <?= esc($rp($bk['discount_total'])) ?></dd>
            <?php endif; ?>
            <?php if ((float) $bk['charge_total'] > 0): ?>
            <dt>Biaya tambahan</dt>
            <dd><?= esc($rp($bk['charge_total'])) ?></dd>
            <?php endif; ?>
            <?php if ((float) $bk['deposit_total'] > 0): ?>
            <dt>Deposit</dt>
            <dd><?= esc($rp($bk['deposit_total'])) ?></dd>
            <?php endif; ?>
            <dt class="strong">Grand total</dt>
            <dd class="strong"><?= esc($rp($bk['grand_total'])) ?></dd>
            <dt>Terbayar</dt>
            <dd><?= esc($rp($bk['paid_total'])) ?></dd>
            <dt class="strong">Sisa tagihan</dt>
            <dd class="strong <?= (float) $bk['balance_due'] > 0 && !$closedStatus ? 'text-danger' : '' ?>">
                <?= esc($rp($bk['balance_due'])) ?></dd>
        </dl>
    </div>

    <div class="panel">
        <h2 class="panel-title">Ringkasan operasional</h2>
        <dl class="dl">
            <dt>Mulai</dt>
            <dd><?= esc($dt($bk['start_at'])) ?></dd>
            <dt>Selesai</dt>
            <dd><?= esc($dt($bk['end_at'])) ?> <span class="muted">(<?= (int) $days ?> hari)</span></dd>
            <dt>Pengambilan</dt>
            <dd><?= esc(str_replace('_', ' ', (string) ($bk['fulfillment_method'] ?? '-'))) ?></dd>
            <dt>Unit</dt>
            <dd><?= (int) $activeAlloc ?> teralokasi / <?= count($activeItems) ?> item</dd>
            <dt>Penugasan</dt>
            <dd><?= (int) $activeAssign ?> aktif</dd>
            <dt>Handover</dt>
            <dd>
                <span class="badge <?= $hasPickup ? 'badge-ok' : 'badge-off' ?>">Pickup
                    <?= $hasPickup ? '✓' : '–' ?></span>
                <span class="badge <?= $hasReturn ? 'badge-ok' : 'badge-off' ?>">Return
                    <?= $hasReturn ? '✓' : '–' ?></span>
            </dd>
        </dl>
    </div>
</div>

<!-- ============ Tab ============ -->
<div class="panel" style="margin-top:20px;">
    <div class="tabs" role="tablist">
        <button type="button" class="tab" data-tab="items">Item &amp; unit</button>
        <button type="button" class="tab" data-tab="payments">Pembayaran <span
                class="tab-n"><?= count($payments) ?></span></button>
        <button type="button" class="tab" data-tab="charges">Biaya <span
                class="tab-n"><?= count($charges) ?></span></button>
        <button type="button" class="tab" data-tab="deposit">Deposit</button>
        <button type="button" class="tab" data-tab="assignment">Penugasan <span
                class="tab-n"><?= count($assignments) ?></span></button>
        <button type="button" class="tab" data-tab="handover">Handover <span
                class="tab-n"><?= count($handovers) ?></span></button>
        <button type="button" class="tab" data-tab="documents">Dokumen</button>
        <button type="button" class="tab" data-tab="history">Riwayat</button>
    </div>

    <!-- ---------- Item & unit ---------- -->
    <section data-pane="items">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i): ?>
                    <tr>
                        <td><?= esc($i['item_name_snapshot']) ?></td>
                        <td><?= esc(rtrim(rtrim((string) $i['quantity'], '0'), '.')) ?></td>
                        <td><?= esc($rp($i['unit_price'])) ?></td>
                        <td><?= esc($rp($i['subtotal'])) ?></td>
                        <td><span class="badge <?= esc($badge($i['status']), 'attr') ?>"><?= esc($i['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="sub-title">Alokasi unit</h3>
        <?php if (empty($allocs)): ?>
        <p class="muted">Belum ada alokasi unit.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Unit</th>
                        <th>Periode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allocs as $a): ?>
                    <tr>
                        <td><?= esc($a['item_name_snapshot']) ?></td>
                        <td><?= esc($a['resource_name'] ?? '-') ?> <span
                                class="muted"><?= esc($a['resource_code'] ?? '') ?></span></td>
                        <td><?= esc($dt($a['start_at'])) ?> <span class="muted">s/d <?= esc($dt($a['end_at'])) ?></span>
                        </td>
                        <td><span
                                class="badge <?= esc($badge($a['allocation_status']), 'attr') ?>"><?= esc($a['allocation_status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ---------- Pembayaran ---------- -->
    <section data-pane="payments">
        <?php if ($legacyPaid): ?>
        <div class="alert alert-success">Pembayaran <?= esc($rp($bk['paid_total'])) ?> tercatat di booking, tetapi belum
            punya riwayat transaksi. Riwayatnya dibuat otomatis saat pembayaran atau biaya pertama dicatat.</div>
        <?php endif; ?>

        <div class="action-row">
            <?php if ($can['pay']): ?>
            <details class="form-box">
                <summary class="btn btn-primary btn-sm">+ Catat pembayaran</summary>
                <form method="post" action="<?= esc($base . '/payment', 'attr') ?>" class="form-grid">
                    <?= csrf_field() ?>
                    <input type="hidden" name="direction" value="IN">
                    <div>
                        <label>Nominal (maks <?= esc($rp($bk['balance_due'])) ?>)</label>
                        <input type="number" name="amount" min="1" max="<?= (int) $bk['balance_due'] ?>" step="1"
                            value="<?= (int) $bk['balance_due'] ?>" required>
                    </div>
                    <div>
                        <label>Jenis</label>
                        <select name="payment_type">
                            <?php foreach ($payTypes as $k => $v): ?><option value="<?= esc($k, 'attr') ?>">
                                <?= esc($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Metode</label>
                        <select name="method" required>
                            <?php foreach ($payMethods as $k => $v): ?><option value="<?= esc($k, 'attr') ?>">
                                <?= esc($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Waktu bayar</label>
                        <input type="datetime-local" name="paid_at" value="<?= esc($nowLocal, 'attr') ?>">
                    </div>
                    <div>
                        <label>No. referensi</label>
                        <input type="text" name="reference_no" maxlength="120">
                    </div>
                    <div>
                        <label>Catatan</label>
                        <input type="text" name="notes" maxlength="255">
                    </div>
                    <div class="full"><button type="submit" class="btn btn-primary">Simpan pembayaran</button></div>
                </form>
            </details>
            <?php endif; ?>

            <?php if ($can['refund']): ?>
            <details class="form-box">
                <summary class="btn btn-ghost btn-sm">Catat refund</summary>
                <form method="post" action="<?= esc($base . '/payment', 'attr') ?>" class="form-grid"
                    onsubmit="return confirm('Catat refund? Total terbayar akan berkurang.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="direction" value="OUT">
                    <div>
                        <label>Nominal (maks <?= esc($rp($bk['paid_total'])) ?>)</label>
                        <input type="number" name="amount" min="1" max="<?= (int) $bk['paid_total'] ?>" step="1"
                            required>
                    </div>
                    <div>
                        <label>Metode</label>
                        <select name="method" required>
                            <?php foreach ($payMethods as $k => $v): ?><option value="<?= esc($k, 'attr') ?>">
                                <?= esc($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Waktu</label>
                        <input type="datetime-local" name="paid_at" value="<?= esc($nowLocal, 'attr') ?>">
                    </div>
                    <div>
                        <label>No. referensi</label>
                        <input type="text" name="reference_no" maxlength="120">
                    </div>
                    <div class="full">
                        <label>Alasan / catatan</label>
                        <input type="text" name="notes" maxlength="255">
                    </div>
                    <div class="full"><button type="submit" class="btn btn-ghost btn-danger">Simpan refund</button>
                    </div>
                </form>
            </details>
            <?php endif; ?>
        </div>

        <?php if (empty($payments)): ?>
        <p class="muted">Belum ada transaksi pembayaran.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th>Metode</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= esc($p['payment_no']) ?>
                            <?php if ($p['reference_no']): ?><div class="muted">Ref: <?= esc($p['reference_no']) ?>
                            </div><?php endif; ?>
                            <?php if ($p['notes']): ?><div class="muted"><?= esc($p['notes']) ?></div><?php endif; ?>
                        </td>
                        <td><?= esc($dt($p['paid_at'] ?: $p['created_at'])) ?>
                            <?php if ($p['received_by']): ?><div class="muted"><?= esc($p['received_by']) ?></div>
                            <?php endif; ?></td>
                        <td><?= esc($p['payment_type']) ?></td>
                        <td><?= esc($payMethods[$p['method']] ?? $p['method']) ?></td>
                        <td class="<?= $p['direction'] === 'OUT' ? 'text-danger' : '' ?>">
                            <?= $p['direction'] === 'OUT' ? '- ' : '' ?><?= esc($rp($p['amount'])) ?></td>
                        <td><span class="badge <?= esc($badge($p['status']), 'attr') ?>"><?= esc($p['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($can['verify'] && $p['status'] === 'PENDING'): ?>
                            <div class="row-actions">
                                <form method="post"
                                    action="<?= esc($base . '/payment/' . $p['enc'] . '/verify', 'attr') ?>"
                                    class="inline">
                                    <?= csrf_field() ?><button class="btn btn-primary btn-sm"
                                        type="submit">Verifikasi</button>
                                </form>
                                <form method="post"
                                    action="<?= esc($base . '/payment/' . $p['enc'] . '/reject', 'attr') ?>"
                                    class="inline" onsubmit="return confirm('Tolak pembayaran ini?')">
                                    <?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-danger"
                                        type="submit">Tolak</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ---------- Biaya ---------- -->
    <section data-pane="charges">
        <?php if ($can['charge']): ?>
        <div class="action-row">
            <details class="form-box">
                <summary class="btn btn-primary btn-sm">+ Tambah biaya</summary>
                <form method="post" action="<?= esc($base . '/charge', 'attr') ?>" class="form-grid">
                    <?= csrf_field() ?>
                    <div>
                        <label>Jenis</label>
                        <select name="charge_type" required>
                            <?php foreach ($chargeTypes as $k => $v): ?><option value="<?= esc($k, 'attr') ?>">
                                <?= esc($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Keterangan</label>
                        <input type="text" name="description" maxlength="255" required>
                    </div>
                    <div>
                        <label>Jumlah</label>
                        <input type="number" name="quantity" min="0.01" step="0.01" value="1" required>
                    </div>
                    <div>
                        <label>Harga satuan</label>
                        <input type="number" name="unit_price" min="1" step="1" required>
                    </div>
                    <div class="full"><button type="submit" class="btn btn-primary">Simpan biaya</button></div>
                </form>
            </details>
        </div>
        <?php endif; ?>

        <?php if (empty($charges)): ?>
        <p class="muted">Belum ada biaya tambahan.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Keterangan</th>
                        <th>Qty × harga</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($charges as $c): ?>
                    <tr>
                        <td><?= esc($chargeTypes[$c['charge_type']] ?? $c['charge_type']) ?></td>
                        <td><?= esc($c['description']) ?><div class="muted"><?= esc($dt($c['created_at'])) ?></div>
                        </td>
                        <td><?= esc(rtrim(rtrim((string) $c['quantity'], '0'), '.')) ?> ×
                            <?= esc($rp($c['unit_price'])) ?></td>
                        <td><?= esc($rp($c['amount'])) ?></td>
                        <td><span class="badge <?= esc($badge($c['status']), 'attr') ?>"><?= esc($c['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($can['manage'] && !in_array($c['status'], ['WAIVED', 'VOID'], true)): ?>
                            <form method="post" action="<?= esc($base . '/charge/' . $c['enc'] . '/void', 'attr') ?>"
                                class="inline" onsubmit="return confirm('Batalkan biaya ini?')">
                                <?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-danger"
                                    type="submit">Batalkan</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($damages)): ?>
        <h3 class="sub-title">Laporan kerusakan</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Deskripsi</th>
                        <th>Estimasi</th>
                        <th>Dibebankan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($damages as $d): ?>
                    <tr>
                        <td><?= esc($conditions[$d['damage_type']] ?? $d['damage_type']) ?></td>
                        <td><?= esc($d['description']) ?></td>
                        <td><?= esc($rp($d['estimated_cost'])) ?></td>
                        <td><?= esc($rp($d['charged_amount'])) ?></td>
                        <td><span class="badge <?= esc($badge($d['status']), 'attr') ?>"><?= esc($d['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ---------- Deposit ---------- -->
    <section data-pane="deposit">
        <?php if ($deposit): ?>
        <dl class="dl dl-money" style="max-width:420px;">
            <dt>Status</dt>
            <dd><span class="badge <?= esc($badge($deposit['status']), 'attr') ?>"><?= esc($deposit['status']) ?></span>
            </dd>
            <dt>Diwajibkan</dt>
            <dd><?= esc($rp($deposit['amount_required'])) ?></dd>
            <dt>Diterima</dt>
            <dd><?= esc($rp($deposit['amount_received'])) ?></dd>
            <dt>Dipotong</dt>
            <dd><?= esc($rp($deposit['amount_deducted'])) ?></dd>
            <dt>Dikembalikan</dt>
            <dd><?= esc($rp($deposit['amount_refunded'])) ?></dd>
            <?php if ($deposit['notes']): ?><dt>Catatan</dt>
            <dd><?= esc($deposit['notes']) ?></dd><?php endif; ?>
        </dl>
        <?php elseif ((float) $bk['deposit_total'] > 0): ?>
        <p>Deposit <strong><?= esc($rp($bk['deposit_total'])) ?></strong> termasuk di grand total booking ini.</p>
        <p class="muted">Belum ada catatan deposit terpisah (penerimaan, potongan, dan pengembalian belum diproses di
            halaman ini).</p>
        <?php else: ?>
        <p class="muted">Booking ini tidak mewajibkan deposit.</p>
        <?php endif; ?>
    </section>

    <!-- ---------- Penugasan ---------- -->
    <section data-pane="assignment">
        <?php if ($can['assign']): ?>
        <div class="action-row">
            <details class="form-box">
                <summary class="btn btn-primary btn-sm">+ Tugaskan petugas</summary>
                <form method="post" action="<?= esc($base . '/assign', 'attr') ?>" class="form-grid">
                    <?= csrf_field() ?>
                    <div>
                        <label>Jenis tugas</label>
                        <select name="assignment_type" required>
                            <?php foreach ($assignTypes as $k => $v): ?><option value="<?= esc($k, 'attr') ?>">
                                <?= esc($v) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Petugas</label>
                        <select name="user_id" required>
                            <option value="">Pilih…</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= (int) $u['id'] ?>"><?= esc($u['name']) ?> (<?= esc($u['role_name']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Mulai</label>
                        <input type="datetime-local" name="start_at"
                            value="<?= esc(date('Y-m-d\TH:i', strtotime($bk['start_at'])), 'attr') ?>">
                    </div>
                    <div>
                        <label>Selesai</label>
                        <input type="datetime-local" name="end_at">
                    </div>
                    <div class="full">
                        <label>Catatan</label>
                        <input type="text" name="notes" maxlength="1000">
                    </div>
                    <div class="full"><button type="submit" class="btn btn-primary">Simpan penugasan</button></div>
                </form>
            </details>
        </div>
        <?php endif; ?>

        <?php if (empty($assignments)): ?>
        <p class="muted">Belum ada penugasan.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Petugas</th>
                        <th>Tugas</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                    <tr>
                        <td><?= esc($a['assignee_name_snapshot']) ?>
                            <?php if ($a['notes']): ?><div class="muted"><?= esc($a['notes']) ?></div><?php endif; ?>
                        </td>
                        <td><?= esc($assignTypes[$a['assignment_type']] ?? $a['assignment_type']) ?></td>
                        <td><?= esc($dt($a['start_at'])) ?> <span class="muted">s/d <?= esc($dt($a['end_at'])) ?></span>
                        </td>
                        <td><span class="badge <?= esc($badge($a['status']), 'attr') ?>"><?= esc($a['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($can['manage'] && in_array($a['status'], ['PLANNED', 'IN_PROGRESS'], true)): ?>
                            <form method="post" action="<?= esc($base . '/assign/' . $a['enc'] . '/cancel', 'attr') ?>"
                                class="inline" onsubmit="return confirm('Batalkan penugasan ini?')">
                                <?= csrf_field() ?><button class="btn btn-ghost btn-sm btn-danger"
                                    type="submit">Batalkan</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ---------- Handover ---------- -->
    <section data-pane="handover">
        <?php if ($can['pickup'] || $can['return']): ?>
        <?php if ($can['pickup'] && (float) $bk['balance_due'] > 0): ?>
        <div class="alert alert-error">Masih ada sisa tagihan <?= esc($rp($bk['balance_due'])) ?>. Pastikan sudah
            disepakati sebelum serah terima.</div>
        <?php endif; ?>
        <?php if ($can['pickup'] && (!$doc || $doc['verification_status'] !== 'VERIFIED')): ?>
        <div class="alert alert-error">Identitas customer belum berstatus VERIFIED.</div>
        <?php endif; ?>

        <div class="action-row">
            <details class="form-box" style="flex:1 1 100%;">
                <summary class="btn btn-primary btn-sm">
                    <?= $can['pickup'] ? '+ Catat serah terima (pickup)' : '+ Catat pengembalian (return)' ?>
                </summary>
                <form method="post" action="<?= esc($base . '/handover', 'attr') ?>"
                    onsubmit="return confirm('Simpan handover? Status booking akan berubah menjadi <?= $can['pickup'] ? 'ONGOING' : 'COMPLETED' ?>.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="handover_type" value="<?= $can['pickup'] ? 'PICKUP' : 'RETURN' ?>">
                    <div class="form-grid">
                        <div>
                            <label>Waktu aktual</label>
                            <input type="datetime-local" name="actual_at" value="<?= esc($nowLocal, 'attr') ?>">
                        </div>
                        <div>
                            <label>Nama penerima / pengembali</label>
                            <input type="text" name="receiver_name" maxlength="150"
                                value="<?= esc($bk['customer_name_snapshot'], 'attr') ?>">
                        </div>
                        <div class="full">
                            <label>Lokasi</label>
                            <input type="text" name="location" maxlength="255">
                        </div>
                    </div>

                    <h3 class="sub-title">Kondisi per item</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Kondisi</th>
                                    <th>Catatan kondisi</th>
                                    <th>Meter</th>
                                    <th>Aksesori</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeItems as $it): ?>
                                <tr>
                                    <td><?= esc($it['item_name_snapshot']) ?></td>
                                    <td>
                                        <select name="items[<?= (int) $it['id'] ?>][condition]">
                                            <?php foreach ($conditions as $k => $v): ?><option
                                                value="<?= esc($k, 'attr') ?>"><?= esc($v) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[<?= (int) $it['id'] ?>][notes]" maxlength="1000">
                                    </td>
                                    <td><input type="number" step="0.01" name="items[<?= (int) $it['id'] ?>][meter]"
                                            style="width:90px;"></td>
                                    <td><input type="text" name="items[<?= (int) $it['id'] ?>][accessory]"
                                            maxlength="1000"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-grid" style="margin-top:12px;">
                        <div class="full">
                            <label>Catatan handover</label>
                            <textarea name="notes" rows="2" maxlength="2000"></textarea>
                        </div>
                        <div class="full"><button type="submit" class="btn btn-primary">Simpan handover</button></div>
                    </div>
                </form>
            </details>
        </div>
        <?php endif; ?>

        <?php if (empty($handovers)): ?>
        <p class="muted">Belum ada handover.</p>
        <?php else: ?>
        <?php foreach ($handovers as $h): ?>
        <div class="handover-card">
            <div class="handover-head">
                <strong><?= $h['handover_type'] === 'PICKUP' ? 'Serah terima (Pickup)' : 'Pengembalian (Return)' ?></strong>
                <span
                    class="muted"><?= esc($dt($h['actual_at'])) ?><?= $h['handled_by'] ? ' · ' . esc($h['handled_by']) : '' ?></span>
            </div>
            <?php if ($h['customer_receiver_name'] || $h['location']): ?>
            <div class="muted">
                <?= esc($h['customer_receiver_name'] ?? '') ?><?= $h['location'] ? ' · ' . esc($h['location']) : '' ?>
            </div>
            <?php endif; ?>
            <?php if ($h['notes']): ?><p style="margin:6px 0;"><?= nl2br(esc($h['notes'])) ?></p><?php endif; ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Kondisi</th>
                            <th>Catatan</th>
                            <th>Meter</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($h['details'] as $d): ?>
                        <tr>
                            <td><?= esc($d['item_name_snapshot']) ?></td>
                            <td><?= esc($d['resource_name'] ?? '-') ?></td>
                            <td><span
                                    class="badge <?= esc($badge($d['condition_status']), 'attr') ?>"><?= esc($conditions[$d['condition_status']] ?? $d['condition_status']) ?></span>
                            </td>
                            <td><?= esc((string) $d['condition_notes']) ?><?= $d['accessory_notes'] ? '<div class="muted">Aksesori: ' . esc($d['accessory_notes']) . '</div>' : '' ?>
                            </td>
                            <td><?= $d['meter_value'] !== null ? esc((string) $d['meter_value']) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- ---------- Dokumen ---------- -->
    <section data-pane="documents">
        <?php if (empty($documents)): ?>
        <p class="muted">Belum ada dokumen yang tersimpan untuk booking ini. Invoice dapat dicetak dari tombol “Cetak
            invoice” di atas.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>No.</th>
                        <th>Catatan</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $d): ?>
                    <tr>
                        <td><?= esc($d['document_type']) ?></td>
                        <td><?= esc((string) $d['document_no']) ?></td>
                        <td><?= esc((string) $d['notes']) ?></td>
                        <td><?= esc($dt($d['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ---------- Riwayat ---------- -->
    <section data-pane="history">
        <ul class="timeline">
            <?php foreach ($timeline as $t): ?>
            <li>
                <div class="tl-time"><?= esc($dt($t['at'])) ?></div>
                <div>
                    <strong><?= esc($t['title']) ?></strong>
                    <?php if ($t['sub'] !== ''): ?><div class="muted"><?= esc($t['sub']) ?></div><?php endif; ?>
                    <div class="muted">oleh <?= esc($t['who']) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<!-- ============ Catatan internal + audit ============ -->
<div class="grid-2" style="margin-top:20px;">
    <div class="panel">
        <h2 class="panel-title">Catatan internal</h2>
        <?php if (trim((string) $bk['internal_notes']) !== ''): ?>
        <div class="notes-box"><?= nl2br(esc($bk['internal_notes'])) ?></div>
        <?php else: ?>
        <p class="muted">Belum ada catatan.</p>
        <?php endif; ?>

        <?php if ($can['manage']): ?>
        <form method="post" action="<?= esc($base . '/note', 'attr') ?>" style="margin-top:12px;">
            <?= csrf_field() ?>
            <label for="note">Tambah catatan (tidak terlihat oleh customer)</label>
            <textarea id="note" name="note" rows="2" maxlength="1000" required></textarea>
            <div class="actions"><button type="submit" class="btn btn-ghost btn-sm">Simpan catatan</button></div>
        </form>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2 class="panel-title">Audit</h2>
        <dl class="dl">
            <dt>Dibuat</dt>
            <dd><?= esc($dt($bk['created_at'])) ?></dd>
            <dt>Dibuat oleh</dt>
            <dd><?= esc($createdBy ?: 'Customer (web)') ?></dd>
            <dt>Diubah terakhir</dt>
            <dd><?= esc($dt($bk['updated_at'])) ?></dd>
            <?php if (!empty($timeline)): ?>
            <dt>Aktivitas terakhir</dt>
            <dd><?= esc($timeline[0]['title']) ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<script>
function askCancel(form, hasPaid) {
    var msg = 'Alasan membatalkan booking ini:';
    if (hasPaid) {
        msg = 'Booking ini sudah ada pembayaran (tidak otomatis direfund).\n' + msg;
    }
    var reason = prompt(msg);
    if (reason === null) {
        return false;
    }
    reason = reason.trim();
    if (reason.length < 3) {
        alert('Alasan minimal 3 karakter.');
        return false;
    }
    form.querySelector('[name=reason]').value = reason;
    return true;
}

(function() {
    var tabs = document.querySelectorAll('[data-tab]');
    var panes = document.querySelectorAll('[data-pane]');

    function show(name) {
        tabs.forEach(function(t) {
            t.classList.toggle('active', t.dataset.tab === name);
        });
        panes.forEach(function(p) {
            p.hidden = p.dataset.pane !== name;
        });
    }
    var q = new URLSearchParams(location.search).get('tab');
    var ok = Array.prototype.some.call(tabs, function(t) {
        return t.dataset.tab === q;
    });
    show(ok ? q : 'items');
    tabs.forEach(function(t) {
        t.addEventListener('click', function() {
            show(t.dataset.tab);
            history.replaceState(null, '', '?tab=' + t.dataset.tab);
        });
    });
})();
</script>

<?= $this->endSection() ?>