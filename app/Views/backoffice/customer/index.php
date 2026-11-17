<?php
/**
 * @var array $rows
 * @var array $f
 * @var int $page
 * @var int $pages
 * @var int $total
 */
$fmt = static fn (?string $d, bool $t = false) => $d ? date($t ? 'd/m/Y H:i' : 'd/m/Y', strtotime($d)) : '-';

$accBadge = static function (array $r): string {
    if (empty($r['acc_owner'])) {
        return '<span class="badge badge-off">Belum punya akun</span>';
    }
    $s = (string) $r['acc_status'];
    if ($s === 'ACTIVE') {
        return '<span class="badge badge-ok">Aktif</span>';
    }
    if ($s === 'SUSPENDED') {
        return '<span class="badge badge-danger">Suspended</span>';
    }
    return '<span class="badge badge-warn">' . esc($s !== '' ? $s : '-') . '</span>';
};

$clean   = array_filter($f, static fn ($v) => $v !== '');
$pageUrl = static fn (int $p) => site_url('backoffice/customers') . '?' . http_build_query(array_merge($clean, ['page' => $p]));
?>
<?= $this->extend('backoffice/layout') ?>

<?= $this->section('content') ?>

<div class="page-head">
    <h1>Customer</h1>
    <div class="quick">
        <?php if ($resolveUrl): ?>
        <a href="<?= esc($resolveUrl) ?>" class="btn btn-ghost btn-sm">Account Resolution</a>
        <?php endif; ?>
    </div>
</div>

<form method="get" action="<?= site_url('backoffice/customers') ?>" class="filters">
    <div class="f-wide">
        <label for="q">Cari</label>
        <input type="text" id="q" name="q" value="<?= esc($f['q']) ?>"
            placeholder="Nama, email, ID / kode<?= $canSeePhone ? ', nomor HP' : '' ?>">
    </div>
    <div>
        <label for="account">Akun</label>
        <select id="account" name="account">
            <option value="">Semua</option>
            <option value="ACTIVE" <?= $f['account'] === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option>
            <option value="SUSPENDED" <?= $f['account'] === 'SUSPENDED' ? 'selected' : '' ?>>Suspended</option>
            <option value="OTHER" <?= $f['account'] === 'OTHER' ? 'selected' : '' ?>>Status lain</option>
            <option value="NONE" <?= $f['account'] === 'NONE' ? 'selected' : '' ?>>Belum punya akun</option>
        </select>
    </div>
    <div>
        <label for="verified">Verified</label>
        <select id="verified" name="verified">
            <option value="">Semua</option>
            <option value="yes" <?= $f['verified'] === 'yes' ? 'selected' : '' ?>>Terverifikasi</option>
            <option value="no" <?= $f['verified'] === 'no' ? 'selected' : '' ?>>Belum terverifikasi</option>
        </select>
    </div>
    <div>
        <label for="flag">Indikator</label>
        <select id="flag" name="flag">
            <option value="">Semua</option>
            <option value="dup" <?= $f['flag'] === 'dup' ? 'selected' : '' ?>>Duplikat nomor</option>
            <option value="noacc" <?= $f['flag'] === 'noacc' ? 'selected' : '' ?>>Tanpa akun</option>
        </select>
    </div>
    <div>
        <label for="from">Dibuat dari</label>
        <input type="date" id="from" name="from" value="<?= esc($f['from']) ?>">
    </div>
    <div>
        <label for="to">Sampai</label>
        <input type="date" id="to" name="to" value="<?= esc($f['to']) ?>">
    </div>
    <div class="f-actions">
        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="<?= site_url('backoffice/customers') ?>" class="btn btn-ghost">Reset</a>
    </div>
</form>

<div class="stat-strip">
    <span class="stat-pill"><span class="num"><?= number_format($total, 0, ',', '.') ?></span> customer ditemukan</span>
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Customer</th>
                    <th>Nomor HP</th>
                    <th>Akun</th>
                    <th>Verified</th>
                    <th>Login terakhir</th>
                    <th>Booking</th>
                    <th>Indikator</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <p>Tidak ada customer yang cocok dengan filter.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><span class="muted"><?= esc($r['code']) ?></span></td>
                    <td>
                        <a class="inv"
                            href="<?= site_url('backoffice/customers/' . $r['enc']) ?>"><?= esc($r['name']) ?></a>
                        <div class="muted"><?= esc($r['email'] ?: '-') ?></div>
                    </td>
                    <td><?= esc($r['phone_view']) ?></td>
                    <td><?= $accBadge($r) ?></td>
                    <td>
                        <?php if (empty($r['acc_owner'])): ?>
                        <span class="muted">-</span>
                        <?php elseif (!empty($r['acc_verified_at'])): ?>
                        <span class="badge badge-ok">Ya</span>
                        <div class="muted"><?= esc($fmt($r['acc_verified_at'])) ?></div>
                        <?php else: ?>
                        <span class="badge badge-warn">Belum</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($fmt($r['acc_last_login'] ?? null, true)) ?></td>
                    <td>
                        <?= (int) $r['booking_count'] ?>
                        <div class="muted"><?= esc($fmt($r['last_booking_at'] ?? null)) ?></div>
                    </td>
                    <td>
                        <div class="badge-group">
                            <?php if ((int) $r['dup_count'] > 0): ?>
                            <span class="badge badge-warn"
                                title="Nomor sama dipakai <?= (int) $r['dup_count'] ?> record lain">Duplikat
                                nomor</span>
                            <?php endif; ?>
                            <?php if (empty($r['acc_owner'])): ?>
                            <span class="badge badge-info">Tanpa akun</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-ghost btn-sm"
                                href="<?= site_url('backoffice/customers/' . $r['enc']) ?>">Detail</a>
                            <?php if ($canManage): ?>
                            <a class="btn btn-ghost btn-sm"
                                href="<?= site_url('backoffice/customers/' . $r['enc']) ?>?edit=1#edit">Edit</a>
                            <?php endif; ?>
                            <?php if ($canCreate): ?>
                            <a class="btn btn-ghost btn-sm"
                                href="<?= site_url('backoffice/bookings/baru?customer_id=' . (int) $r['id']) ?>">Booking</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pager">
        <?php if ($page > 1): ?>
        <a class="btn btn-ghost btn-sm" href="<?= esc($pageUrl($page - 1)) ?>">&lsaquo; Sebelumnya</a>
        <?php endif; ?>
        <span>Halaman <?= $page ?> dari <?= $pages ?></span>
        <?php if ($page < $pages): ?>
        <a class="btn btn-ghost btn-sm" href="<?= esc($pageUrl($page + 1)) ?>">Berikutnya &rsaquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>