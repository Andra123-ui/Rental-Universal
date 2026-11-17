<?php
$fmt = static fn (?string $d, bool $t = false) => $d ? date($t ? 'd/m/Y H:i' : 'd/m/Y', strtotime($d)) : '-';

$bkBadge = static function (string $s): string {
    $m = [
        'PENDING' => ['Menunggu', 'badge-warn'], 'CONFIRMED' => ['Dikonfirmasi', 'badge-info'],
        'ONGOING' => ['Berjalan', 'badge-info'], 'COMPLETED' => ['Selesai', 'badge-ok'],
        'CANCELLED' => ['Dibatalkan', 'badge-danger'], 'EXPIRED' => ['Kedaluwarsa', 'badge-off'],
    ];
    [$l, $c] = $m[$s] ?? [$s, 'badge-off'];
    return '<span class="badge ' . $c . '">' . esc($l) . '</span>';
};
$payBadge = static function (string $s): string {
    $m = [
        'PAID' => ['Lunas', 'badge-ok'], 'PARTIAL' => ['Sebagian', 'badge-warn'],
        'UNPAID' => ['Belum bayar', 'badge-danger'], 'REFUNDED' => ['Dikembalikan', 'badge-off'],
    ];
    [$l, $c] = $m[$s] ?? [$s, 'badge-off'];
    return '<span class="badge ' . $c . '">' . esc($l) . '</span>';
};
$docBadge = static function (string $s): string {
    $m = [
        'VERIFIED' => ['Terverifikasi', 'badge-ok'], 'PENDING' => ['Menunggu', 'badge-warn'],
        'REJECTED' => ['Ditolak', 'badge-danger'], 'UNVERIFIED' => ['Belum', 'badge-off'],
    ];
    [$l, $c] = $m[$s] ?? [$s !== '' ? $s : '-', 'badge-off'];
    return '<span class="badge ' . $c . '">' . esc($l) . '</span>';
};

$base = site_url('backoffice/customers/' . $seg);
?>
<?= $this->extend('backoffice/layout') ?>

<?= $this->section('content') ?>

<div class="page-head">
    <div>
        <h1><?= esc($c['name']) ?></h1>
        <div class="muted"><?= esc($code) ?> · terdaftar <?= esc($fmt($c['created_at'] ?? null)) ?></div>
    </div>
    <div class="quick">
        <a href="<?= site_url('backoffice/customers') ?>" class="btn btn-ghost btn-sm">&larr; Daftar</a>
        <?php if ($canManage): ?>
        <a href="<?= $base ?>?edit=1#edit" class="btn btn-ghost btn-sm">Edit profil</a>
        <?php endif; ?>
        <?php if ($canCreate): ?>
        <a href="<?= esc($bookingUrl) ?>" class="btn btn-primary btn-sm">Buat booking</a>
        <?php endif; ?>
        <a href="#riwayat" class="btn btn-ghost btn-sm">Riwayat</a>
    </div>
</div>

<?php if ($dups): ?>
<div class="alert alert-error">
    Nomor ini juga dipakai record customer lain:
    <?php foreach ($dups as $i => $d): ?>
    <?= $i ? ', ' : '' ?><a
        href="<?= site_url('backoffice/customers/' . id_encode((int) $d['id'])) ?>"><?= esc($d['name']) ?></a>
    <?php endforeach; ?>.
    <?php if ($resolveUrl): ?>
    <a href="<?= esc($resolveUrl) ?>">Buka Account Resolution</a>.
    <?php endif; ?>
    Jangan digabung hanya karena nama mirip.
</div>
<?php endif; ?>

<div class="grid-2">
    <!-- PROFIL -->
    <div class="panel">
        <h3 class="panel-title">Profil</h3>
        <dl class="dl">
            <dt>Kode</dt>
            <dd><?= esc($code) ?> <span class="muted">(ID <?= (int) $c['id'] ?>)</span></dd>
            <dt>Nama</dt>
            <dd class="strong"><?= esc($c['name']) ?></dd>
            <dt>Nomor HP</dt>
            <dd><?= esc($phoneView) ?></dd>
            <dt>Email</dt>
            <dd><?= esc($c['email'] ?: '-') ?></dd>
            <?php if ($address !== null): ?>
            <dt>Alamat</dt>
            <dd><?= esc($address !== '' ? $address : '-') ?></dd>
            <?php endif; ?>
            <dt>Total booking</dt>
            <dd><?= (int) $stat['n'] ?> <span class="muted">(terakhir <?= esc($fmt($stat['last_at'] ?? null)) ?>)</span>
            </dd>
            <dt>Nilai transaksi</dt>
            <dd>Rp <?= number_format((float) $stat['total'], 0, ',', '.') ?></dd>
        </dl>

        <?php if ($canManage): ?>
        <details class="form-box" id="edit" style="margin-top:18px;" <?= $edit ? 'open' : '' ?>>
            <summary>Edit profil</summary>
            <form method="post" action="<?= $base ?>/update">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="full">
                        <label for="e-name">Nama lengkap</label>
                        <input type="text" id="e-name" name="name" required minlength="2" maxlength="150"
                            value="<?= esc($c['name']) ?>">
                    </div>
                    <div class="full">
                        <label for="e-email">Email</label>
                        <input type="email" id="e-email" name="email" maxlength="150"
                            value="<?= esc($c['email'] ?? '') ?>">
                    </div>
                    <?php if ($address !== null): ?>
                    <div class="full">
                        <label for="e-addr">Alamat</label>
                        <textarea id="e-addr" name="address" rows="2" maxlength="500"><?= esc($address) ?></textarea>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="hint">Nomor HP tidak bisa diubah di sini karena terkait akun login. Perubahan nomor harus
                    lewat alur verifikasi.</div>
                <div class="actions"><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
            </form>
        </details>
        <?php endif; ?>
    </div>

    <!-- AKUN -->
    <div class="panel" id="akun">
        <h3 class="panel-title">Akun login</h3>
        <?php if (!$account): ?>
        <p class="muted">Customer ini belum punya akun login (booking sebagai tamu / customer lama).</p>
        <?php else: ?>
        <dl class="dl">
            <dt>Nomor login</dt>
            <dd><?= esc($account['_phone_view']) ?></dd>
            <dt>Status</dt>
            <dd>
                <?php if ($account['status'] === 'ACTIVE'): ?><span class="badge badge-ok">Aktif</span>
                <?php elseif ($account['status'] === 'SUSPENDED'): ?><span class="badge badge-danger">Suspended</span>
                <?php else: ?><span class="badge badge-warn"><?= esc($account['status']) ?></span><?php endif; ?>
            </dd>
            <dt>Verified</dt>
            <dd><?= !empty($account['verified_at']) ? esc($fmt($account['verified_at'], true)) : '<span class="badge badge-warn">Belum</span>' ?>
            </dd>
            <dt>Login terakhir</dt>
            <dd><?= esc($fmt($account['_last_login'] ?? null, true)) ?></dd>
        </dl>
        <p class="hint">Kode OTP aktif tidak ditampilkan kepada staf. Pemulihan akun tetap lewat alur verifikasi.</p>

        <?php if ($canManage && in_array($account['status'], ['ACTIVE', 'SUSPENDED'], true)): ?>
        <?php $susp = $account['status'] === 'ACTIVE'; ?>
        <details class="form-box" style="margin-top:14px;">
            <summary><?= $susp ? 'Suspend akun' : 'Aktifkan kembali akun' ?></summary>
            <form method="post" action="<?= $base ?>/account-status"
                onsubmit="return confirm('<?= $susp ? 'Suspend akun ini? Customer tidak bisa login, booking tetap berjalan.' : 'Aktifkan kembali akun ini?' ?>');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $susp ? 'suspend' : 'reactivate' ?>">
                <label for="a-reason">Alasan</label>
                <input type="text" id="a-reason" name="reason" required minlength="3" maxlength="300">
                <div class="hint">Suspend tidak membatalkan booking yang sudah ada.</div>
                <div class="actions">
                    <button type="submit" class="btn btn-sm <?= $susp ? 'btn-ghost btn-danger' : 'btn-primary' ?>">
                        <?= $susp ? 'Suspend' : 'Aktifkan' ?>
                    </button>
                </div>
            </form>
        </details>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- DOKUMEN -->
<div class="panel" id="dokumen" style="margin-top:22px;">
    <h3 class="panel-title">Dokumen identitas</h3>
    <?php if (!$docs): ?>
    <p class="muted">Belum ada dokumen.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th>Nomor</th>
                    <th>Status</th>
                    <th>Diunggah</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docs as $d): ?>
                <tr>
                    <td><?= esc($d['type']) ?></td>
                    <td><?= esc($d['number'] !== '' ? $d['number'] : '-') ?></td>
                    <td><?= $docBadge($d['status']) ?></td>
                    <td><?= esc($fmt($d['created'], true)) ?></td>
                    <td>
                        <?php if ($d['url']): ?>
                        <a class="btn btn-ghost btn-sm" href="<?= esc($d['url']) ?>">Tinjau / verifikasi</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($canManage): ?>
    <details class="form-box" style="margin-top:16px;">
        <summary>Tambah dokumen</summary>
        <form method="post" action="<?= $base ?>/document" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div>
                    <label for="d-type">Jenis</label>
                    <select id="d-type" name="document_type" required>
                        <?php foreach ($docTypes as $k => $v): ?>
                        <option value="<?= esc($k) ?>"><?= esc($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="d-num">Nomor dokumen</label>
                    <input type="text" id="d-num" name="document_number" maxlength="60">
                </div>
                <div class="full">
                    <label for="d-file">File (JPG / PNG / PDF, maks 5 MB)</label>
                    <input type="file" id="d-file" name="document" required accept=".jpg,.jpeg,.png,.pdf"
                        style="height:auto;padding:8px;">
                </div>
            </div>
            <div class="hint">Dokumen baru berstatus Menunggu dan perlu diverifikasi di menu Verifikasi Identitas.</div>
            <div class="actions"><button type="submit" class="btn btn-primary btn-sm">Unggah</button></div>
        </form>
    </details>
    <?php endif; ?>
</div>

<!-- RIWAYAT -->
<div class="panel" id="riwayat" style="margin-top:22px;">
    <h3 class="panel-title">Riwayat booking</h3>
    <?php if (!$bookings): ?>
    <p class="muted">Belum ada booking.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Dibuat</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><a class="inv" href="<?= esc($b['url']) ?>"><?= esc($b['invoice_no']) ?></a></td>
                    <td><?= esc($fmt($b['created_at'], true)) ?></td>
                    <td><?= esc($fmt($b['start_at'])) ?> – <?= esc(date('d/m/Y', strtotime($b['end_at']) - 1)) ?></td>
                    <td><?= $bkBadge((string) $b['status']) ?></td>
                    <td><?= $payBadge((string) $b['payment_status']) ?></td>
                    <td style="text-align:right;">Rp <?= number_format((float) $b['grand_total'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$showAll && (int) $stat['n'] > count($bookings)): ?>
    <p style="margin-top:12px;"><a href="<?= $base ?>?all=1#riwayat" class="btn btn-ghost btn-sm">Tampilkan semua
            (<?= (int) $stat['n'] ?>)</a></p>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="grid-2" style="margin-top:22px;">
    <!-- CATATAN -->
    <div class="panel" id="catatan">
        <h3 class="panel-title">Catatan internal</h3>
        <?php if ($notes === null): ?>
        <p class="muted">Kolom catatan tidak tersedia.</p>
        <?php else: ?>
        <div class="notes-box">
            <?= $notes !== '' ? nl2br(esc($notes)) : '<span class="muted">Belum ada catatan.</span>' ?></div>
        <?php if ($canManage): ?>
        <form method="post" action="<?= $base ?>/note" style="margin-top:14px;">
            <?= csrf_field() ?>
            <label for="n-note">Tambah catatan</label>
            <textarea id="n-note" name="note" rows="3" required maxlength="1000"></textarea>
            <div class="actions"><button type="submit" class="btn btn-primary btn-sm">Simpan catatan</button></div>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- AKTIVITAS -->
    <div class="panel">
        <h3 class="panel-title">Aktivitas staf</h3>
        <?php if (!$log): ?>
        <p class="muted">Belum ada aktivitas tercatat.</p>
        <?php else: ?>
        <ul class="timeline">
            <?php foreach ($log as $l): ?>
            <li>
                <div class="tl-time"><?= esc($fmt($l['created_at'], true)) ?></div>
                <div>
                    <?= esc($l['description']) ?>
                    <div class="muted"><?= esc($l['who'] ?: 'Sistem') ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>