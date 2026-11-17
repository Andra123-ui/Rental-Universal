<?php
$fmt = static fn (?string $d) => $d ? date('d/m/Y H:i', strtotime($d)) : '-';
$typeBadge = ['DUP' => 'badge-warn', 'ORPHAN' => 'badge-info', 'CLAIM' => 'badge-danger'];
?>
<?= $this->extend('backoffice/layout') ?>

<?= $this->section('content') ?>

<div class="page-head">
    <div>
        <h1>Account Resolution</h1>
        <div class="muted">Duplicate phone, customer lama tanpa akun, dan claim akun yang perlu ditinjau manual.</div>
    </div>
    <a href="<?= site_url('backoffice/customers') ?>" class="btn btn-ghost btn-sm">&larr; Daftar customer</a>
</div>

<?php if (!$hasTable): ?>
<div class="alert alert-error">
    Tabel <code>customer_resolutions</code> belum dibuat, jadi keputusan belum bisa disimpan. Jalankan SQL pembuatan
    tabel terlebih dahulu.
</div>
<?php endif; ?>

<form method="get" class="filters">
    <div>
        <label for="type">Jenis kasus</label>
        <select id="type" name="type">
            <option value="">Semua</option>
            <?php foreach ($types as $k => $v): ?>
            <option value="<?= esc($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">Terbuka + ditahan</option>
            <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Belum ditangani</option>
            <option value="hold" <?= $status === 'hold' ? 'selected' : '' ?>>Ditahan</option>
            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Ditutup (ditolak)</option>
        </select>
    </div>
    <div class="f-actions">
        <button type="submit" class="btn btn-primary">Terapkan</button>
        <a href="<?= site_url('backoffice/customers/account-resolution') ?>" class="btn btn-ghost">Reset</a>
    </div>
</form>

<div class="stat-strip">
    <span class="stat-pill"><span class="num"><?= count($issues) ?></span> kasus</span>
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th>Nomor</th>
                    <th>Record terkait</th>
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$issues): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <p>Tidak ada kasus. Semua customer bersih.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($issues as $i): ?>
                <tr>
                    <td><span
                            class="badge <?= $typeBadge[$i['type']] ?? 'badge-off' ?>"><?= esc($i['type_label']) ?></span>
                    </td>
                    <td><?= esc($i['phone_view']) ?></td>
                    <td>
                        <?php foreach ($i['people'] as $p): ?>
                        <div>
                            <a
                                href="<?= site_url('backoffice/customers/' . id_encode((int) $p['id'])) ?>"><?= esc($p['name']) ?></a>
                            <span class="muted">· <?= (int) $p['bookings'] ?> booking ·
                                <?= $p['acc_status'] ? 'akun ' . esc($p['acc_status']) : 'tanpa akun' ?></span>
                        </div>
                        <?php endforeach; ?>
                    </td>
                    <td class="muted"><?= esc($i['note']) ?></td>
                    <td>
                        <?php if ($i['decision'] === 'HOLD'): ?>
                        <span class="badge badge-warn">Ditahan</span>
                        <div class="muted"><?= esc($fmt($i['decided_at'])) ?></div>
                        <?php elseif ($i['decision']): ?>
                        <span class="badge badge-off">Ditolak</span>
                        <?php else: ?>
                        <span class="badge badge-danger">Perlu review</span>
                        <?php endif; ?>
                    </td>
                    <td><a class="btn btn-primary btn-sm" href="<?= esc($i['case_url']) ?>">Tinjau</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>