<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
$rp   = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$tgl  = static function ($v) {
    if (!$v) { return '-'; }
    $t = strtotime($v);
    return date('H:i', $t) === '00:00' ? date('d/m/Y', $t) : date('d/m/Y H:i', $t);
};
$map = [
    'CONFIRMED' => 'badge-info', 'ONGOING' => 'badge-info',
    'PAID' => 'badge-ok', 'COMPLETED' => 'badge-ok', 'ACTIVE' => 'badge-ok',
    'PENDING' => 'badge-warn', 'UNPAID' => 'badge-warn', 'PARTIAL' => 'badge-warn',
    'CANCELLED' => 'badge-danger', 'REFUNDED' => 'badge-danger', 'SUSPENDED' => 'badge-danger',
    'BLOCKED' => 'badge-danger', 'LOCKED' => 'badge-danger',
];
$badge = static fn ($s) => $map[$s] ?? 'badge-off';

$clean = array_filter($filters, static fn ($v) => $v !== '' && $v !== null);
$qs    = static fn (array $extra = []) => ($p = http_build_query(array_merge($clean, $extra))) ? '?' . $p : '';
$base  = site_url('backoffice/bookings');
$from  = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$to    = min($total, $page * $perPage);
?>

<div class="page-head">
    <h1>Daftar Booking</h1>
    <div class="quick">
        <?php if ($canManage): ?>
        <a href="<?= esc($base . '/export' . $qs(), 'attr') ?>" class="btn btn-ghost">Export CSV</a>
        <?php endif; ?>
        <?php if ($createUrl): ?>
        <a href="<?= esc($createUrl, 'attr') ?>" class="btn btn-primary">+ Booking baru</a>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <form method="get" action="<?= esc($base, 'attr') ?>" class="filters">
        <div class="f-wide">
            <label for="q">Cari</label>
            <input type="text" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>"
                placeholder="Invoice, nama, atau no. HP">
        </div>
        <div>
            <label for="from">Sewa dari</label>
            <input type="date" id="from" name="from" value="<?= esc($filters['from'], 'attr') ?>">
        </div>
        <div>
            <label for="to">Sampai</label>
            <input type="date" id="to" name="to" value="<?= esc($filters['to'], 'attr') ?>">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="payment">Pembayaran</label>
            <select id="payment" name="payment">
                <option value="">Semua</option>
                <?php foreach ($payments as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $filters['payment'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="source">Sumber</label>
            <select id="source" name="source">
                <option value="">Semua</option>
                <?php foreach ($sources as $s): ?>
                <option value="<?= esc($s, 'attr') ?>" <?= $filters['source'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!$lockBranch): ?>
        <div>
            <label for="branch">Cabang</label>
            <select id="branch" name="branch">
                <option value="">Semua</option>
                <?php foreach ($branches as $br): ?>
                <option value="<?= (int) $br['id'] ?>" <?= $filters['branch'] === (string) $br['id'] ? 'selected' : '' ?>><?= esc($br['branch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="f-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="<?= esc($base, 'attr') ?>" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Sumber</th>
                    <th>Periode sewa</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Cabang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="muted" style="text-align:center;padding:28px;">Tidak ada booking yang cocok dengan filter.</td></tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                <?php
                    $canConfirm = $canManage && $r['status'] === 'PENDING';
                    $canCancel  = $canManage && in_array($r['status'], ['PENDING', 'CONFIRMED'], true);
                    $hasPaid    = (float) $r['paid_total'] > 0;
                ?>
                <tr>
                    <td>
                        <?php if ($r['detail_url']): ?>
                        <a href="<?= esc($r['detail_url'], 'attr') ?>" class="inv"><?= esc($r['invoice_no']) ?></a>
                        <?php else: ?>
                        <span class="inv"><?= esc($r['invoice_no']) ?></span>
                        <?php endif; ?>
                        <div class="muted"><?= esc($tgl($r['created_at'])) ?></div>
                    </td>
                    <td>
                        <?= esc($r['customer_name_snapshot']) ?>
                        <div class="muted"><?= esc($r['customer_phone_snapshot']) ?></div>
                        <?php if ($r['account_status']): ?>
                        <span class="badge <?= esc($badge($r['account_status']), 'attr') ?>">Akun <?= esc(strtolower($r['account_status'])) ?></span>
                        <?php else: ?>
                        <span class="badge badge-off">Tanpa akun</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($r['booking_source']) ?></td>
                    <td>
                        <?= esc($tgl($r['start_at'])) ?>
                        <div class="muted">s/d <?= esc($tgl($r['end_at'])) ?></div>
                    </td>
                    <td>
                        <span class="badge <?= esc($badge($r['status']), 'attr') ?>"><?= esc($r['status']) ?></span>
                        <span class="badge <?= esc($badge($r['payment_status']), 'attr') ?>"><?= esc($r['payment_status']) ?></span>
                    </td>
                    <td>
                        <?= esc($rp($r['grand_total'])) ?>
                        <?php if ((float) $r['balance_due'] > 0 && !in_array($r['status'], ['CANCELLED', 'EXPIRED'], true)): ?>
                        <div class="muted">sisa <?= esc($rp($r['balance_due'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($r['branch_name'] ?? '-') ?></td>
                    <td>
                        <div class="row-actions">
                            <?php if ($r['detail_url']): ?>
                            <a href="<?= esc($r['detail_url'], 'attr') ?>" class="btn btn-ghost btn-sm">Detail</a>
                            <?php endif; ?>

                            <?php if ($canConfirm): ?>
                            <form method="post" action="<?= site_url('backoffice/bookings/' . $r['enc'] . '/confirm') ?>" class="inline"
                                onsubmit="return confirm('Konfirmasi booking <?= esc($r['invoice_no'], 'js') ?>?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                            </form>
                            <?php endif; ?>

                            <?php if ($canCancel): ?>
                            <?php if ($hasPaid): ?>
                            <button type="button" class="btn btn-ghost btn-sm" disabled
                                title="Sudah ada pembayaran. Batalkan lewat halaman detail agar refund tercatat.">Cancel</button>
                            <?php else: ?>
                            <form method="post" action="<?= site_url('backoffice/bookings/' . $r['enc'] . '/cancel') ?>" class="inline"
                                onsubmit="return askCancel(this, '<?= esc($r['invoice_no'], 'js') ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="reason" value="">
                                <button type="submit" class="btn btn-ghost btn-sm btn-danger">Cancel</button>
                            </form>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span>Menampilkan <?= (int) $from ?>–<?= (int) $to ?> dari <?= (int) $total ?> booking</span>
        <?php if ($page > 1): ?>
        <a href="<?= esc($base . $qs(['page' => $page - 1]), 'attr') ?>" class="btn btn-ghost btn-sm">&larr; Sebelumnya</a>
        <?php endif; ?>
        <span>Hal. <?= (int) $page ?> / <?= (int) $pages ?></span>
        <?php if ($page < $pages): ?>
        <a href="<?= esc($base . $qs(['page' => $page + 1]), 'attr') ?>" class="btn btn-ghost btn-sm">Berikutnya &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<script>
function askCancel(form, invoice) {
    var reason = prompt('Alasan membatalkan ' + invoice + ':');
    if (reason === null) { return false; }
    reason = reason.trim();
    if (reason.length < 3) { alert('Alasan minimal 3 karakter.'); return false; }
    form.querySelector('[name=reason]').value = reason;
    return true;
}
</script>

<?= $this->endSection() ?>