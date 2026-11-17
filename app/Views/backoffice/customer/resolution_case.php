<?php
$fmt = static fn (?string $d, bool $t = false) => $d ? date($t ? 'd/m/Y H:i' : 'd/m/Y', strtotime($d)) : '-';
$actLabel = ['MERGE' => 'Merge', 'LINK' => 'Link account', 'REJECT' => 'Reject', 'HOLD' => 'Hold'];

$withAcc    = array_values(array_filter($people, static fn ($p) => $p['has_account']));
$withoutAcc = array_values(array_filter($people, static fn ($p) => !$p['has_account']));
?>
<?= $this->extend('backoffice/layout') ?>

<?= $this->section('content') ?>

<style>
.cand-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 22px;
}

.cand-grid .dl {
    grid-template-columns: 96px 1fr;
}

.chk {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin: 10px 0;
    font-size: .86rem;
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
    color: var(--ink-soft);
}

.chk input {
    width: auto;
    height: auto;
    margin-top: 3px;
}
</style>

<div class="page-head">
    <div>
        <h1>Tinjau Kasus</h1>
        <div class="muted"><?= esc($typeLabel) ?> · <code><?= esc($key) ?></code></div>
    </div>
    <a href="<?= esc($queueUrl) ?>" class="btn btn-ghost btn-sm">&larr; Antrean</a>
</div>

<div class="alert alert-error">
    Merge dan link mengubah data permanen (booking, dokumen, akun). Disarankan backup database dulu.
    Jangan menggabungkan record hanya karena namanya mirip: nomor harus sama dan terverifikasi.
</div>

<!-- KANDIDAT -->
<div class="cand-grid">
    <?php foreach ($people as $p): ?>
    <div class="panel">
        <h3 class="panel-title"><?= esc($p['name']) ?></h3>
        <dl class="dl">
            <dt>Kode</dt>
            <dd><?= esc($p['code']) ?> <span class="muted">(ID <?= (int) $p['id'] ?>)</span></dd>
            <dt>Nomor HP</dt>
            <dd><?= esc($p['phone_view']) ?></dd>
            <dt>Email</dt>
            <dd><?= esc($p['email'] ?: '-') ?></dd>
            <dt>Dibuat</dt>
            <dd><?= esc($fmt($p['created_at'], true)) ?></dd>
            <dt>Akun</dt>
            <dd>
                <?php if ($p['acc']): ?>
                <?= esc($p['acc']['_phone_view']) ?><br>
                <span
                    class="badge <?= $p['acc']['status'] === 'ACTIVE' ? 'badge-ok' : 'badge-warn' ?>"><?= esc($p['acc']['status']) ?></span>
                <?= !empty($p['acc']['verified_at']) ? '<span class="badge badge-ok">Verified</span>' : '<span class="badge badge-danger">Belum verified</span>' ?>
                <div class="muted">Login terakhir <?= esc($fmt($p['acc']['_last_login'] ?? null, true)) ?></div>
                <?php else: ?>
                <span class="muted">Tidak punya akun</span>
                <?php endif; ?>
            </dd>
            <dt>Dokumen</dt>
            <dd><?= (int) $p['doc_count'] ?> file</dd>
            <dt>Dampak booking</dt>
            <dd><strong><?= (int) $p['bk_count'] ?></strong> booking · Rp
                <?= number_format($p['bk_total'], 0, ',', '.') ?></dd>
        </dl>

        <?php if ($p['bookings']): ?>
        <ul class="list" style="margin-top:12px;">
            <?php foreach ($p['bookings'] as $b): ?>
            <li>
                <div class="list-main">
                    <a href="<?= esc($b['url']) ?>"><?= esc($b['invoice_no']) ?></a>
                    <span class="muted"><?= esc($fmt($b['created_at'])) ?> · <?= esc($b['status']) ?> · Rp
                        <?= number_format((float) $b['grand_total'], 0, ',', '.') ?></span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($p['bk_count'] > count($p['bookings'])): ?>
        <div class="muted">+ <?= $p['bk_count'] - count($p['bookings']) ?> booking lainnya</div>
        <?php endif; ?>
        <?php endif; ?>

        <p style="margin-top:12px;">
            <a class="btn btn-ghost btn-sm" href="<?= site_url('backoffice/customers/' . $p['enc']) ?>">Buka
                customer</a>
        </p>
    </div>
    <?php endforeach; ?>
</div>

<!-- RESOLUSI -->
<?php if (!$hasTable): ?>
<div class="alert alert-error">Tabel <code>customer_resolutions</code> belum dibuat. Aksi resolusi dinonaktifkan sampai
    tabel dibuat.</div>
<?php else: ?>
<div class="panel">
    <h3 class="panel-title">Resolusi</h3>
    <div class="action-row">

        <!-- MERGE -->
        <?php if (count($people) >= 2): ?>
        <details class="form-box">
            <summary>Merge customer</summary>
            <form method="post" action="<?= esc($formUrl) ?>"
                onsubmit="return confirm('Merge tidak bisa dibatalkan otomatis. Lanjutkan?');">
                <?= csrf_field() ?>
                <input type="hidden" name="key" value="<?= esc($key) ?>">
                <input type="hidden" name="action" value="MERGE">

                <div class="form-grid">
                    <div>
                        <label for="m-target">Record utama (dipertahankan)</label>
                        <select id="m-target" name="target_id" required>
                            <?php foreach ($people as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= $p['id'] === $suggestTarget ? 'selected' : '' ?>>
                                <?= esc($p['code'] . ' — ' . $p['name'] . ' (' . $p['bk_count'] . ' booking)') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="m-source">Record yang digabung (dinonaktifkan)</label>
                        <select id="m-source" name="source_id" required>
                            <?php foreach ($people as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= $p['id'] === $suggestSource ? 'selected' : '' ?>>
                                <?= esc($p['code'] . ' — ' . $p['name'] . ' (' . $p['bk_count'] . ' booking)') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="chk"><input type="checkbox" name="confirm_same" value="1">Saya sudah memastikan kedua
                    record adalah orang yang sama, bukan hanya namanya yang mirip.</label>
                <label class="chk"><input type="checkbox" name="confirm_verified" value="1">Kepemilikan nomor sudah
                    diverifikasi (OTP / dihubungi langsung).</label>
                <label class="chk"><input type="checkbox" name="confirm_backup" value="1">Backup database sudah dibuat
                    atau saya menerima risikonya.</label>

                <label for="m-reason">Alasan (audit)</label>
                <textarea id="m-reason" name="reason" rows="3" required minlength="10" maxlength="900"></textarea>
                <div class="hint">Booking, dokumen, dan akun dipindah ke record utama. Record yang digabung
                    di-soft-delete, tidak dihapus permanen.</div>
                <div class="actions"><button type="submit" class="btn btn-primary btn-sm">Merge</button></div>
            </form>
        </details>
        <?php endif; ?>

        <!-- LINK -->
        <details class="form-box">
            <summary>Link account</summary>
            <?php if (!$withAcc || !$withoutAcc): ?>
            <p class="muted" style="margin-top:12px;">Link butuh satu record yang punya akun dan satu record tujuan yang
                belum punya akun.</p>
            <?php else: ?>
            <form method="post" action="<?= esc($formUrl) ?>"
                onsubmit="return confirm('Akun login akan dipindahkan ke record tujuan. Lanjutkan?');">
                <?= csrf_field() ?>
                <input type="hidden" name="key" value="<?= esc($key) ?>">
                <input type="hidden" name="action" value="LINK">

                <div class="form-grid">
                    <div>
                        <label for="l-owner">Record pemilik akun saat ini</label>
                        <select id="l-owner" name="owner_id" required>
                            <?php foreach ($withAcc as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= esc($p['code'] . ' — ' . $p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="l-target">Pindahkan akun ke record (tanpa akun)</label>
                        <select id="l-target" name="target_id" required>
                            <?php foreach ($withoutAcc as $p): ?>
                            <option value="<?= (int) $p['id'] ?>">
                                <?= esc($p['code'] . ' — ' . $p['name'] . ' (' . $p['bk_count'] . ' booking)') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="chk"><input type="checkbox" name="confirm_verified" value="1">Nomor login sudah
                    terverifikasi dan sama dengan nomor record tujuan.</label>

                <label for="l-reason">Alasan (audit)</label>
                <textarea id="l-reason" name="reason" rows="3" required minlength="10" maxlength="900"></textarea>
                <div class="actions"><button type="submit" class="btn btn-primary btn-sm">Link account</button></div>
            </form>
            <?php endif; ?>
        </details>

        <!-- REJECT / HOLD -->
        <details class="form-box">
            <summary>Reject / Hold</summary>
            <form method="post" action="<?= esc($formUrl) ?>">
                <?= csrf_f  ield() ?>
                <input type="hidden" name="key" value="<?= esc($key) ?>">

                <label for="r-action">Keputusan</label>
                <select id="r-action" name="action" required>
                    <option value="HOLD">Hold: tahan, tinjau lagi nanti</option>
                    <option value="REJECT">Reject: tolak dan tutup kasus</option>
                </select>

                <label for="r-reason" style="margin-top:14px;">Alasan (audit)</label>
                <textarea id="r-reason" name="reason" rows="3" required minlength="10" maxlength="900"></textarea>
                <div class="hint">Reject tidak mengubah data customer; kasus hanya ditutup dan dicatat.</div>
                <div class="actions"><button type="submit" class="btn btn-primary btn-sm">Simpan keputusan</button>
                </div>
            </form>
        </details>
    </div>
</div>
<?php endif; ?>

<!-- RIWAYAT KEPUTUSAN -->
<div class="panel" style="margin-top:22px;">
    <h3 class="panel-title">Riwayat keputusan</h3>
    <?php if (!$history): ?>
    <p class="muted">Belum ada keputusan untuk kasus ini.</p>
    <?php else: ?>
    <ul class="timeline">
        <?php foreach ($history as $h): ?>
        <li>
            <div class="tl-time"><?= esc($fmt($h['created_at'], true)) ?></div>
            <div>
                <strong><?= esc($actLabel[$h['action']] ?? $h['action']) ?></strong>
                <?= $h['primary_customer_id'] ? ' · utama #' . (int) $h['primary_customer_id'] : '' ?>
                <?= $h['secondary_customer_id'] ? ' · kedua #' . (int) $h['secondary_customer_id'] : '' ?>
                <div><?= esc($h['reason']) ?></div>
                <div class="muted">oleh <?= esc($h['operator'] ?: 'Sistem') ?></div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>