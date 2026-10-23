<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
$tabs = ['PENDING' => 'Menunggu', 'REJECTED' => 'Ditolak', 'VERIFIED' => 'Terverifikasi', 'UNVERIFIED' => 'Belum lengkap'];
$labels = ['PENDING' => 'Menunggu', 'REJECTED' => 'Ditolak', 'VERIFIED' => 'Terverifikasi', 'UNVERIFIED' => 'Belum lengkap'];
?>
<style>
.cv h1 {
    margin: 0 0 4px;
    font-size: 1.4rem;
}

.cv .sub {
    margin: 0 0 18px;
    color: #64748b;
    font-size: .9rem;
}

.cv-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.cv-tab {
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid #C5CEE0;
    background: #fff;
    color: #334155;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;
}

.cv-tab.active {
    background: #1B3A8C;
    border-color: #1B3A8C;
    color: #fff;
}

.cv-tab small {
    opacity: .75;
    margin-left: 4px;
}

.cv-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #E3E8F2;
    border-radius: 12px;
    overflow: hidden;
}

.cv-table th,
.cv-table td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #EEF1F7;
    font-size: .88rem;
    vertical-align: middle;
}

.cv-table th {
    background: #F1F4FA;
    color: #475569;
    font-size: .78rem;
}

.cv-who {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cv-ava {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #1B3A8C center/cover;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.cv-btn {
    display: inline-block;
    padding: 7px 14px;
    border-radius: 8px;
    background: #1B3A8C;
    color: #fff;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
}

.cv-flash {
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 14px;
    font-size: .85rem;
}

.cv-flash.ok {
    background: #ecfdf5;
    color: #047857;
}

.cv-flash.bad {
    background: #fef2f2;
    color: #b91c1c;
}

.cv-empty {
    padding: 36px;
    text-align: center;
    color: #64748b;
    background: #fff;
    border: 1px dashed #C5CEE0;
    border-radius: 12px;
}

.cv-scroll {
    overflow-x: auto;
}
</style>
<div class="cv">
    <h1>Verifikasi identitas</h1>
    <p class="sub">Periksa file identitas yang diunggah customer dan pastikan itu benar-benar dokumen identitas.</p>

    <div class="cv-tabs">
        <?php foreach ($tabs as $key => $label): ?>
        <a class="cv-tab<?= $status === $key ? ' active' : '' ?>"
            href="<?= base_url('backoffice/verifikasi-identitas?status=' . $key) ?>">
            <?= esc($label) ?><small><?= (int) ($counts[$key] ?? 0) ?></small>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (! $rows): ?>
    <div class="cv-empty">Tidak ada data pada status "<?= esc($labels[$status]) ?>".</div>
    <?php else: ?>
    <div class="cv-scroll">
        <table class="cv-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Identitas</th>
                    <th>Berlaku sampai</th>
                    <th>Diperbarui</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <div class="cv-who">
                            <span class="cv-ava"
                                <?= $r['profile_photo'] ? 'style="background-image:url(\'' . esc(base_url('backoffice/customer-photo/' . $r['id']), 'attr') . '\')"' : '' ?>>
                                <?= $r['profile_photo'] ? '' : esc(strtoupper(mb_substr($r['name'], 0, 1))) ?>
                            </span>
                            <div><strong><?= esc($r['name']) ?></strong><br><small
                                    style="color:#64748b"><?= esc($r['phone']) ?></small></div>
                        </div>
                    </td>
                    <td><?= esc($r['id_type'] ?? '-') ?><br><small
                            style="color:#64748b"><?= esc($r['id_number'] ?? '-') ?></small></td>
                    <td><?= $r['id_expires_at'] ? esc(date('d M Y', strtotime($r['id_expires_at']))) : '-' ?></td>
                    <td><?= esc(date('d M Y H:i', strtotime($r['updated_at']))) ?></td>
                    <td><a class="cv-btn"
                            href="<?= base_url('backoffice/verifikasi-identitas/' . id_encode($r['id'])) ?>">Tinjau</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>