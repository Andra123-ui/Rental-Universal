<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
$st = $customer['id_verification_status'];
$stLabel = ['PENDING' => 'Menunggu', 'VERIFIED' => 'Terverifikasi', 'REJECTED' => 'Ditolak', 'UNVERIFIED' => 'Belum lengkap'][$st] ?? $st;
$expired = ! empty($customer['id_expires_at']) && strtotime($customer['id_expires_at']) < strtotime('today');
$fileUrl = base_url('backoffice/verifikasi-identitas/' . $enc . '/file');
?>
<style>
.cvs h1 {
    margin: 0 0 4px;
    font-size: 1.4rem;
}

.cvs-back {
    color: #1B3A8C;
    font-size: .85rem;
    text-decoration: none;
    font-weight: 600;
}

.cvs-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
    gap: 20px;
    margin-top: 16px;
    align-items: start;
}

.cvs-box {
    background: #fff;
    border: 1px solid #E3E8F2;
    border-radius: 14px;
    padding: 18px;
}

.cvs-box h2 {
    margin: 0 0 12px;
    font-size: 1rem;
}

.cvs-viewer {
    width: 100%;
    min-height: 420px;
    border: 1px solid #E3E8F2;
    border-radius: 10px;
    background: #F1F4FA;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.cvs-viewer img {
    max-width: 100%;
    max-height: 640px;
    display: block;
}

.cvs-viewer iframe {
    width: 100%;
    height: 640px;
    border: 0;
}

.cvs-dl {
    display: grid;
    grid-template-columns: 130px 1fr;
    gap: 8px 10px;
    margin: 0;
    font-size: .88rem;
}

.cvs-dl dt {
    color: #64748b;
}

.cvs-dl dd {
    margin: 0;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.cvs-warn {
    padding: 10px 12px;
    border-radius: 10px;
    background: #fffbeb;
    color: #92400e;
    font-size: .84rem;
    margin-bottom: 12px;
}

.cvs-bad {
    padding: 10px 12px;
    border-radius: 10px;
    background: #fef2f2;
    color: #b91c1c;
    font-size: .84rem;
    margin-bottom: 12px;
}

.cvs-check input {
    width: auto;
    height: auto;
    margin-top: 3px;
    flex-shrink: 0;
}

.cvs-check {
    display: flex;
    color: inherit;
    font-weight: 400;
    gap: 8px;
    align-items: flex-start;
    margin-bottom: 9px;
    font-size: .88rem;
}

.cvs-btn {
    width: 100%;
    padding: 11px;
    border: 0;
    border-radius: 10px;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.cvs-ok {
    background: #047857;
    color: #fff;
}

.cvs-ok:disabled {
    opacity: .45;
    cursor: not-allowed;
}

.cvs-no {
    background: #fff;
    color: #b91c1c;
    border: 1.5px solid #fca5a5;
}

.cvs textarea {
    width: 100%;
    background: #fff;
    padding: 10px;
    border: 1.5px solid #C5CEE0;
    border-radius: 10px;
    font: inherit;
    min-height: 70px;
    margin-bottom: 8px;
}

.cvs-flash {
    padding: 10px 14px;
    border-radius: 10px;
    margin: 12px 0;
    font-size: .85rem;
    background: #fef2f2;
    color: #b91c1c;
}

@media (max-width: 900px) {
    .cvs-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="cvs">
    <a class="cvs-back" href="<?= base_url('backoffice/verifikasi-identitas') ?>">&larr; Kembali ke daftar</a>
    <h1><?= esc($customer['name']) ?></h1>

    <div class="cvs-grid">
        <div class="cvs-box">
            <h2>File identitas</h2>
            <?php if ($fileExists): ?>
            <div class="cvs-viewer">
                <?php if ($fileMime === 'application/pdf'): ?>
                <iframe src="<?= esc($fileUrl, 'attr') ?>" title="File identitas"></iframe>
                <?php else: ?>
                <img src="<?= esc($fileUrl, 'attr') ?>" alt="File identitas <?= esc($customer['name'], 'attr') ?>">
                <?php endif; ?>
            </div>
            <p style="margin:10px 0 0"><a href="<?= esc($fileUrl, 'attr') ?>" target="_blank" rel="noopener"
                    style="color:#1B3A8C;font-weight:600">Buka di tab baru</a></p>
            <?php else: ?>
            <div class="cvs-bad">File identitas tidak ditemukan di server. Minta customer mengunggah ulang.</div>
            <?php endif; ?>
        </div>

        <div>
            <div class="cvs-box" style="margin-bottom:16px">
                <h2>Data yang diisi customer</h2>
                <?php if ($duplicates): ?>
                <div class="cvs-warn">Nomor identitas ini juga dipakai akun lain:
                    <?= esc(implode(', ', array_map(static fn($d) => $d['name'] . ' (' . $d['phone'] . ')', $duplicates))) ?>.
                </div>
                <?php endif; ?>
                <?php if ($expired): ?><div class="cvs-warn">Dokumen sudah melewati tanggal berlaku.</div>
                <?php endif; ?>
                <dl class="cvs-dl">
                    <dt>Nama akun</dt>
                    <dd><?= esc($customer['name']) ?></dd>
                    <dt>Nomor WhatsApp</dt>
                    <dd><?= esc($customer['phone']) ?></dd>
                    <dt>Jenis identitas</dt>
                    <dd><?= esc($customer['id_type'] ?? '-') ?></dd>
                    <dt>Nomor identitas</dt>
                    <dd><?= esc($customer['id_number'] ?? '-') ?></dd>
                    <dt>Berlaku sampai</dt>
                    <dd><?= $customer['id_expires_at'] ? esc(date('d M Y', strtotime($customer['id_expires_at']))) : '-' ?>
                    </dd>
                    <dt>Status</dt>
                    <dd><?= esc($stLabel) ?></dd>
                    <?php if ($st === 'REJECTED' && $customer['id_reject_reason']): ?>
                    <dt>Alasan ditolak</dt>
                    <dd><?= esc($customer['id_reject_reason']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>

            <?php if (in_array($st, ['PENDING', 'VERIFIED'], true)): ?>
            <div class="cvs-box">
                <?php if ($st === 'PENDING'): ?>
                <h2>Pemeriksaan</h2>
                <form action="<?= base_url('backoffice/verifikasi-identitas/' . $enc . '/approve') ?>" method="post"
                    id="approve-form">
                    <?= csrf_field() ?>
                    <?php foreach ([
                            'Dokumen adalah identitas asli (KTP/SIM/Paspor), bukan screenshot, fotokopi buram, atau hasil edit',
                            'Jenis dokumen sesuai dengan yang dipilih customer',
                            'Nomor di dokumen sama dengan nomor yang diisi',
                            'Nama di dokumen cocok dengan nama akun dan belum kedaluwarsa',
                        ] as $text): ?>
                    <label class="cvs-check"><input type="checkbox" name="checks[]" value="1" class="chk">
                        <span><?= esc($text) ?></span></label>
                    <?php endforeach; ?>
                    <button type="submit" class="cvs-btn cvs-ok" id="approve-btn" disabled>Setujui &amp;
                        verifikasi</button>
                </form>
                <hr style="border:0;border-top:1px solid #E3E8F2;margin:16px 0">
                <?php endif; ?>

                <h2>Tolak dokumen</h2>
                <form action="<?= base_url('backoffice/verifikasi-identitas/' . $enc . '/reject') ?>" method="post">
                    <?= csrf_field() ?>
                    <textarea name="reason" maxlength="255" required
                        placeholder="Contoh: foto buram, nomor tidak terbaca"></textarea>
                    <button type="submit" class="cvs-btn cvs-no">Tolak dokumen</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    var checks = document.querySelectorAll('.chk');
    var btn = document.getElementById('approve-btn');
    if (!btn) return;

    function sync() {
        btn.disabled = !Array.prototype.every.call(checks, function(c) {
            return c.checked;
        });
    }
    checks.forEach(function(c) {
        c.addEventListener('change', sync);
    });
})();
</script>
<?= $this->endSection() ?>