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
$hour = (int) date('H');
$greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
?>

<div class="page-head">
    <div>
        <h1><?= esc($greet) ?>, <?= esc(session()->get('admin_name')) ?></h1>
        <div class="muted"><?= esc(date('d/m/Y')) ?> · <?= esc($branchLabel) ?></div>
    </div>
    <?php if (!empty($quick)): ?>
    <div class="quick">
        <?php foreach ($quick as $q): ?>
        <a href="<?= esc($q['url'], 'attr') ?>"
            class="btn <?= $q['primary'] ? 'btn-primary' : 'btn-ghost' ?>"><?= esc($q['label']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!$canBooking && !$canFinance && !$canVerify && !$canStaff): ?>
<div class="panel">
    <p class="muted" style="margin:0;">Akun ini tidak memiliki ringkasan dashboard. Gunakan menu di samping sesuai hak
        akses Anda.</p>
</div>
<?php endif; ?>

<?php if ($canBooking): ?>
<!-- ===== STATS BAR ===== -->
<div class="stats-bar">
    <div class="stat highlight">
        <div class="stat-label">Booking hari ini</div>
        <div class="stat-value"><?= (int) $kpi['today'] ?></div>
        <div class="stat-sub">dibuat hari ini</div>
    </div>
    <div class="stat">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?= (int) $kpi['pending'] ?></div>
        <div class="stat-sub">menunggu pembayaran</div>
    </div>
    <div class="stat">
        <div class="stat-label">Sedang berjalan</div>
        <div class="stat-value"><?= (int) $kpi['ongoing'] ?></div>
        <div class="stat-sub">booking aktif sekarang</div>
    </div>
    <div class="stat">
        <div class="stat-label">Utilization</div>
        <div class="stat-value"><?= (int) $kpi['utilization'] ?><small>%</small></div>
        <div class="util-track"><span style="width:<?= min(100, (int) $kpi['utilization']) ?>%"></span></div>
        <div class="stat-sub"><?= (int) $kpi['res_used'] ?> dari <?= (int) $kpi['res_total'] ?> unit</div>
    </div>
</div>
<?php endif; ?>

<?php if ($canFinance): ?>
<!-- ===== FINANCE STRIP ===== -->
<div class="finance-strip">
    <div class="fin-cell">
        <div class="stat-label">Revenue bulan ini</div>
        <div class="fin-amount"><?= esc($rp($finance['revenue'])) ?></div>
        <div class="fin-note">total terbayar</div>
    </div>
    <div class="fin-cell">
        <div class="stat-label">Belum lunas</div>
        <div class="fin-amount is-danger"><?= esc($rp($finance['unpaid_total'])) ?></div>
        <div class="fin-note"><?= (int) $finance['unpaid_count'] ?> booking</div>
    </div>
</div>
<?php endif; ?>

<div class="grid-2">

    <?php if ($canBooking): ?>
    <div class="panel">
        <h2 class="panel-title">Jadwal hari ini</h2>
        <?php if (empty($schedule)): ?>
        <div class="empty-state">
            <p>Tidak ada pickup, return, assignment, atau service hari ini.</p>
        </div>
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
        <div class="empty-state">
            <p>Tidak ada maintenance, blackout, atau unit yang tidak tersedia.</p>
        </div>
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
        <div class="stat-strip">
            <span class="stat-pill"><span class="num"><?= (int) $payAttention['pending'] ?></span> menunggu
                verifikasi</span>
        </div>
        <?php if (empty($payAttention['overdue'])): ?>
        <div class="empty-state">
            <p>Tidak ada booking yang jadwalnya sudah lewat dan belum lunas.</p>
        </div>
        <?php else: ?>
        <p class="muted" style="margin:0 0 8px;">Jadwal sudah mulai, belum lunas:</p>
        <ul class="list">
            <?php foreach ($payAttention['overdue'] as $o): ?>
            <li>
                <span class="list-main">
                    <?php if ($o['url']): ?><a href="<?= esc($o['url'], 'attr') ?>"><?= esc($o['invoice_no']) ?></a>
                    <?php else: ?><strong><?= esc($o['invoice_no']) ?></strong><?php endif; ?>
                    <span class="muted"><?= esc($o['customer_name_snapshot']) ?> · mulai
                        <?= esc($dt($o['start_at'])) ?></span>
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
                    <?php if ($identityUrl): ?><a href="<?= esc($identityUrl, 'attr') ?>">Dokumen identitas menunggu
                        verifikasi</a>
                    <?php else: ?><strong>Dokumen identitas menunggu verifikasi</strong><?php endif; ?>
                </span>
                <span
                    class="badge <?= $custAttention['docs'] > 0 ? 'badge-warn' : 'badge-off' ?>"><?= (int) $custAttention['docs'] ?></span>
            </li>
            <li>
                <span class="list-main"><strong>Akun customer suspended / terkunci</strong></span>
                <span
                    class="badge <?= $custAttention['locked'] > 0 ? 'badge-danger' : 'badge-off' ?>"><?= (int) $custAttention['locked'] ?></span>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($canBooking): ?>
    <div class="panel">
        <h2 class="panel-title">Booking terbaru</h2>
        <?php if (empty($recentBookings)): ?>
        <div class="empty-state">
            <p>Belum ada booking.</p>
        </div>
        <?php else: ?>
        <ul class="list">
            <?php foreach ($recentBookings as $b): ?>
            <li>
                <span class="list-main">
                    <?php if ($b['url']): ?><a href="<?= esc($b['url'], 'attr') ?>"><?= esc($b['invoice_no']) ?></a>
                    <?php else: ?><strong><?= esc($b['invoice_no']) ?></strong><?php endif; ?>
                    <span class="muted"><?= esc($b['customer_name_snapshot']) ?> ·
                        <?= esc($dt($b['created_at'])) ?></span>
                </span>
                <span class="badge-group">
                    <span class="badge <?= esc($badge($b['status']), 'attr') ?>"><?= esc($b['status']) ?></span>
                    <span
                        class="badge <?= esc($badge($b['payment_status']), 'attr') ?>"><?= esc($b['payment_status']) ?></span>
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
        <div class="empty-state">
            <p>Belum ada log.</p>
        </div>
        <?php else: ?>
        <ul class="list">
            <?php foreach ($recentLogs as $l): ?>
            <li>
                <span class="list-main"><?= esc($l['description'] ?: $l['event_type']) ?>
                    <span class="muted"><?= esc($l['user_name'] ?: 'Tanpa user') ?> ·
                        <?= esc($dt($l['created_at'])) ?></span></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<div class="role-strip">
    <div class="role-label">Masuk sebagai <strong><?= esc(session()->get('admin_role')) ?></strong></div>
    <div class="role-badges">
        <?php if (session()->get('admin_role') === 'owner'): ?>
        <span class="badge badge-ok">semua hak akses (owner)</span>
        <?php else: ?>
        <?php foreach ((array) session()->get('admin_permissions') as $code): ?>
        <span class="badge badge-off"><?= esc($code) ?></span>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>