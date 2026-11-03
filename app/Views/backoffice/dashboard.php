<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
$rp  = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$dt  = static fn ($v) => $v ? date('d/m/Y H:i', strtotime($v)) : '-';
$map = [
    'CONFIRMED' => 'badge-info', 'ONGOING' => 'badge-info',
    'PAID' => 'badge-ok', 'COMPLETED' => 'badge-ok', 'VERIFIED' => 'badge-ok',
    'PENDING' => 'badge-warn', 'UNPAID' => 'badge-warn', 'PARTIAL' => 'badge-warn',
    'CANCELLED' => 'badge-danger', 'EXPIRED' => 'badge-off', 'REJECTED' => 'badge-danger',
];
$badge = static fn ($s) => $map[$s] ?? 'badge-off';
$timeLabel = static fn ($v) => substr((string) $v, 11, 5) === '00:00' ? 'Seharian' : substr((string) $v, 11, 5);
?>

<div class="page-head">
    <div>
        <h1>Halo, <?= esc(session()->get('admin_name')) ?></h1>
        <div class="muted"><?= esc(date('d/m/Y')) ?> · <?= esc($branchLabel) ?></div>
    </div>
    <?php if (!empty($quick)): ?>
    <div class="quick">
        <?php foreach ($quick as $q): ?>
        <a href="<?= esc($q['url'], 'attr') ?>" class="btn <?= $q['primary'] ? 'btn-primary' : 'btn-ghost' ?>"><?= esc($q['label']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!$canBooking && !$canFinance && !$canVerify && !$canStaff): ?>
<div class="panel">
    <p class="muted" style="margin:0;">Akun ini tidak memiliki ringkasan dashboard. Gunakan menu di samping sesuai hak akses Anda.</p>
</div>
<?php endif; ?>

<?php if ($canBooking): ?>
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label">Booking hari ini</div>
        <div class="kpi-value"><?= (int) $kpi['today'] ?></div>
        <div class="kpi-sub">dibuat hari ini</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Pending</div>
        <div class="kpi-value"><?= (int) $kpi['pending'] ?></div>
        <div class="kpi-sub">menunggu pembayaran</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Sedang berjalan</div>
        <div class="kpi-value"><?= (int) $kpi['ongoing'] ?></div>
        <div class="kpi-sub">booking aktif sekarang</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Utilization</div>
        <div class="kpi-value"><?= (int) $kpi['utilization'] ?>%</div>
        <div class="kpi-sub"><?= (int) $kpi['res_used'] ?> dari <?= (int) $kpi['res_total'] ?> unit terpakai hari ini</div>
    </div>
    <?php if ($canFinance): ?>
    <div class="kpi">
        <div class="kpi-label">Revenue bulan ini</div>
        <div class="kpi-value kpi-money"><?= esc($rp($finance['revenue'])) ?></div>
        <div class="kpi-sub">total terbayar</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Belum lunas</div>
        <div class="kpi-value kpi-money"><?= esc($rp($finance['unpaid_total'])) ?></div>
        <div class="kpi-sub"><?= (int) $finance['unpaid_count'] ?> booking</div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid-2">

    <?php if ($canBooking): ?>
    <div class="panel">
        <h2 class="panel-title">Jadwal hari ini</h2>
        <?php if (empty($schedule)): ?>
        <p class="muted">Tidak ada pickup, return, assignment, atau service hari ini.</p>
        <?php else: ?>
        <ul class="list">
            <?php foreach ($schedule as $e): ?>
            <li>
                <span class="list-time"><?= esc($timeLabel($e['time'])) ?></span>
                <span class="list-main">
                    <?php if ($e['url']): ?><a href="<?= esc($e['url'], 'attr') ?>"><?= esc($e['title']) ?></a>
                    <?php else: ?><strong><?= esc($e['title']) ?></strong><?php endif; ?>
                    <span class="muted"><?= esc($e['sub']) ?></span>
                </span>
                <span class="badge <?= esc($e['badge'], 'attr') ?>"><?= esc($e['type']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2 class="panel-title">Perlu perhatian: resource</h2>
        <?php if (empty($resAttention)): ?>
        <p class="muted">Tidak ada maintenance, blackout, atau unit yang tidak tersedia.</p>
        <?php else: ?>
        <ul class="list">
            <?php foreach ($resAttention as $r): ?>
            <li>
                <span class="list-main"><strong><?= esc($r['title']) ?></strong>
                    <span class="muted"><?= esc($r['sub']) ?></span></span>
                <span class="badge <?= esc($r['badge'], 'attr') ?>"><?= esc($r['type']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($canFinance): ?>
    <div class="panel">
        <h2 class="panel-title">Perlu perhatian: pembayaran</h2>
        <p style="margin:0 0 10px;">
            Menunggu verifikasi: <strong><?= (int) $payAttention['pending'] ?></strong>
        </p>
        <?php if (empty($payAttention['overdue'])): ?>
        <p class="muted">Tidak ada booking yang jadwalnya sudah lewat dan belum lunas.</p>
        <?php else: ?>
        <p class="muted" style="margin:0 0 6px;">Jadwal sudah mulai, belum lunas:</p>
        <ul class="list">
            <?php foreach ($payAttention['overdue'] as $o): ?>
            <li>
                <span class="list-main">
                    <?php if ($o['url']): ?><a href="<?= esc($o['url'], 'attr') ?>"><?= esc($o['invoice_no']) ?></a>
                    <?php else: ?><strong><?= esc($o['invoice_no']) ?></strong><?php endif; ?>
                    <span class="muted"><?= esc($o['customer_name_snapshot']) ?> · mulai <?= esc($dt($o['start_at'])) ?></span>
                </span>
                <span class="badge badge-danger"><?= esc($rp($o['balance_due'])) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($canVerify): ?>
    <div class="panel">
        <h2 class="panel-title">Perlu perhatian: akun customer</h2>
        <ul class="list">
            <li>
                <span class="list-main">
                    <?php if ($identityUrl): ?><a href="<?= esc($identityUrl, 'attr') ?>">Dokumen identitas menunggu verifikasi</a>
                    <?php else: ?><strong>Dokumen identitas menunggu verifikasi</strong><?php endif; ?>
                </span>
                <span class="badge <?= $custAttention['docs'] > 0 ? 'badge-warn' : 'badge-off' ?>"><?= (int) $custAttention['docs'] ?></span>
            </li>
            <li>
                <span class="list-main"><strong>Akun customer suspended / terkunci</strong></span>
                <span class="badge <?= $custAttention['locked'] > 0 ? 'badge-danger' : 'badge-off' ?>"><?= (int) $custAttention['locked'] ?></span>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($canBooking): ?>
    <div class="panel">
        <h2 class="panel-title">Booking terbaru</h2>
        <?php if (empty($recentBookings)): ?>
        <p class="muted">Belum ada booking.</p>
        <?php else: ?>
        <ul class="list">
            <?php foreach ($recentBookings as $b): ?>
            <li>
                <span class="list-main">
                    <?php if ($b['url']): ?><a href="<?= esc($b['url'], 'attr') ?>"><?= esc($b['invoice_no']) ?></a>
                    <?php else: ?><strong><?= esc($b['invoice_no']) ?></strong><?php endif; ?>
                    <span class="muted"><?= esc($b['customer_name_snapshot']) ?> · <?= esc($dt($b['created_at'])) ?></span>
                </span>
                <span>
                    <span class="badge <?= esc($badge($b['status']), 'attr') ?>"><?= esc($b['status']) ?></span>
                    <span class="badge <?= esc($badge($b['payment_status']), 'attr') ?>"><?= esc($b['payment_status']) ?></span>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($canStaff): ?>
    <div class="panel">
        <h2 class="panel-title">Log aktivitas</h2>
        <?php if (empty($recentLogs)): ?>
        <p class="muted">Belum ada log.</p>
        <?php else: ?>
        <ul class="list">
            <?php foreach ($recentLogs as $l): ?>
            <li>
                <span class="list-main"><?= esc($l['description'] ?: $l['event_type']) ?>
                    <span class="muted"><?= esc($l['user_name'] ?: 'Tanpa user') ?> · <?= esc($dt($l['created_at'])) ?></span></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<div class="panel" style="margin-top:20px;">
    <p class="muted" style="margin-top:0;">Role: <strong><?= esc(session()->get('admin_role')) ?></strong></p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php if (session()->get('admin_role') === 'owner'): ?>
        <span class="badge badge-ok">semua hak akses (owner)</span>
        <?php else: ?>
        <?php foreach ((array) session()->get('admin_permissions') as $code): ?>
        <span class="badge badge-ok"><?= esc($code) ?></span>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>