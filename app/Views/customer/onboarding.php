<?= $this->extend('layouts/customer') ?>

<?= $this->section('content') ?>

<?php
helper('business');
$brand = business_name() ?: 'Rental Universal';

// Kalau deteksi otomatis gagal, isi path gambar di sini, contoh:
// $bgImage = base_url('assets/img/login-bg.jpg');
$bgImage = null;

$errs = session()->getFlashdata('errors') ?? [];
$fieldKeys = ['name', 'email', 'address', 'document_type', 'document_number', 'id_file',
    'emergency_contact_name', 'emergency_contact_phone', 'notes'];
$hasFieldError = (bool) array_intersect(array_keys($errs), $fieldKeys);
$otherErrors = array_diff_key($errs, array_flip($fieldKeys));
$cls = fn(string $k) => isset($errs[$k]) ? ' is-invalid' : '';
?>

<div class="onb" <?= $bgImage ? ' data-bg="1" style="background-image:url(\'' . esc($bgImage, 'attr') . '\')"' : '' ?>>

    <div class="onb-brand"><span class="onb-dot"></span><?= esc($brand) ?></div>

    <div class="onb-card">

        <div class="onb-head">
            <h1 class="auth-title">Lengkapi Data Anda</h1>
            <p class="auth-subtitle">
                Cukup diisi sekali agar proses sewa Anda lebih cepat. Kolom bertanda
                <span class="req">*</span> wajib diisi.
            </p>
        </div>

        <?php if ($hasFieldError || $otherErrors): ?>
        <div class="alert alert-error">
            <ul>
                <?php if ($hasFieldError): ?>
                <li>Ada isian yang perlu diperbaiki, lihat kolom yang bertanda merah.</li>
                <?php endif; ?>
                <?php foreach ($otherErrors as $error): ?>
                <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="<?= site_url('account/onboarding') ?>" method="post" enctype="multipart/form-data" id="onbForm"
            novalidate>
            <?= csrf_field() ?>

            <div class="onb-grid">

                <!-- KOLOM 1: DATA DIRI -->
                <div class="onb-col">
                    <h3 class="onb-section">Data Diri</h3>

                    <div class="form-group">
                        <label>Nomor HP</label>
                        <input type="text" class="form-control" value="<?= esc($customer['phone'] ?? '') ?>" disabled>
                        <p class="onb-hint">Dipakai untuk login OTP WhatsApp.</p>
                    </div>

                    <div class="form-group">
                        <label for="name">Nama Lengkap <span class="req">*</span></label>
                        <input type="text" id="name" name="name" class="form-control<?= $cls('name') ?>" maxlength="150"
                            placeholder="Nama sesuai identitas" value="<?= old('name', $prefillName ?? '') ?>" required
                            autofocus>
                        <?php if (isset($errs['name'])): ?><p class="field-error"><?= esc($errs['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="opt">(opsional)</span></label>
                        <input type="email" id="email" name="email" class="form-control<?= $cls('email') ?>"
                            maxlength="150" placeholder="nama@email.com"
                            value="<?= old('email', $customer['email'] ?? '') ?>">
                        <?php if (isset($errs['email'])): ?><p class="field-error"><?= esc($errs['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat <span class="opt">(disarankan)</span></label>
                        <textarea id="address" name="address" class="form-control<?= $cls('address') ?>" rows="3"
                            maxlength="500"
                            placeholder="Jalan, nomor, kelurahan, kota"><?= old('address', $customer['address'] ?? '') ?></textarea>
                        <?php if (isset($errs['address'])): ?><p class="field-error"><?= esc($errs['address']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KOLOM 2: IDENTITAS -->
                <div class="onb-col">
                    <h3 class="onb-section">Identitas <span class="opt">(untuk rental tertentu)</span></h3>

                    <div class="form-group">
                        <label for="document_type">Jenis Identitas</label>
                        <select id="document_type" name="document_type"
                            class="form-control<?= $cls('document_type') ?>">
                            <option value="">Pilih jenis identitas</option>
                            <?php foreach ($docTypes as $val => $label): ?>
                            <option value="<?= esc($val) ?>"
                                <?= old('document_type', $customer['id_type'] ?? '') === $val ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errs['document_type'])): ?><p class="field-error">
                            <?= esc($errs['document_type']) ?></p><?php endif; ?>
                        <p class="onb-hint" id="id-hint"></p>
                    </div>

                    <div class="form-group">
                        <label for="document_number">Nomor Identitas</label>
                        <input type="text" id="document_number" name="document_number"
                            class="form-control<?= $cls('document_number') ?>" maxlength="80"
                            placeholder="Nomor sesuai dokumen"
                            value="<?= old('document_number', $customer['id_number'] ?? '') ?>">
                        <?php if (isset($errs['document_number'])): ?><p class="field-error">
                            <?= esc($errs['document_number']) ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="id_file">Foto/Dokumen Identitas</label>
                        <input type="file" id="id_file" name="id_file" class="form-control<?= $cls('id_file') ?>"
                            accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (isset($errs['id_file'])): ?>
                        <p class="field-error"><?= esc($errs['id_file']) ?> Pilih ulang file Anda.</p>
                        <?php else: ?>
                        <p class="onb-hint">JPG, PNG, atau PDF, maksimal 4 MB.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KOLOM 3: KONTAK DARURAT & CATATAN -->
                <div class="onb-col">
                    <h3 class="onb-section">Kontak Darurat <span class="opt">(opsional)</span></h3>

                    <div class="form-group">
                        <label for="emergency_contact_name">Nama Kontak Darurat</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                            class="form-control<?= $cls('emergency_contact_name') ?>" maxlength="150"
                            placeholder="Nama keluarga atau teman"
                            value="<?= old('emergency_contact_name', $customer['emergency_contact_name'] ?? '') ?>">
                        <?php if (isset($errs['emergency_contact_name'])): ?><p class="field-error">
                            <?= esc($errs['emergency_contact_name']) ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="emergency_contact_phone">No. HP Kontak Darurat</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                            class="form-control<?= $cls('emergency_contact_phone') ?>" maxlength="30"
                            inputmode="numeric" placeholder="08xxxxxxxxxx"
                            value="<?= old('emergency_contact_phone', $customer['emergency_contact_phone'] ?? '') ?>">
                        <?php if (isset($errs['emergency_contact_phone'])): ?><p class="field-error">
                            <?= esc($errs['emergency_contact_phone']) ?></p><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan <span class="opt">(opsional)</span></label>
                        <input type="text" id="notes" name="notes" class="form-control<?= $cls('notes') ?>"
                            maxlength="500" placeholder="Kebutuhan khusus yang perlu kami ketahui"
                            value="<?= old('notes', $customer['notes'] ?? '') ?>">
                        <?php if (isset($errs['notes'])): ?><p class="field-error"><?= esc($errs['notes']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="onb-actions">
                <button type="submit" class="btn btn-primary" id="onbBtn">
                    <span id="onbBtnText">Simpan &amp; Lanjutkan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Halaman onboarding menutupi seluruh layar; gambar berada di belakang kartu */
html:has(.onb),
body:has(.onb) {
    overflow: hidden;
}

.onb {
    --onb-brand: #1B3A8C;
    --onb-ring: rgba(27, 58, 140, .18);
    --onb-border: #C5CEE0;
    --onb-bg: #F1F4FA;

    position: fixed;
    inset: 0;
    z-index: 1000;
    overflow-y: auto;
    display: flex;
    padding: 64px 16px 24px;
    background-color: #0d1f4d;
    background-image:
        radial-gradient(circle at 20% 15%, rgba(41, 182, 246, .28), transparent 45%),
        linear-gradient(135deg, #0d1f4d 0%, #1B3A8C 60%, #2563c9 100%);
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

/* Lapisan gelap tipis supaya kartu dan tulisan tetap kontras di atas foto */
.onb::before {
    content: "";
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(8, 22, 60, .70), rgba(27, 58, 140, .45));
    pointer-events: none;
}

.onb.onb--soft::before {
    background: linear-gradient(135deg, rgba(8, 22, 60, .35), rgba(27, 58, 140, .20));
}

.onb .onb-brand {
    position: absolute;
    top: 22px;
    left: 32px;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
}

.onb .onb-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #29B6F6;
}

.onb .onb-card {
    position: relative;
    z-index: 1;
    margin: auto;
    width: 100%;
    max-width: 1120px;
    box-sizing: border-box;
    padding: 22px 30px 24px;
    background: rgba(255, 255, 255, .97);
    -webkit-backdrop-filter: blur(12px);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(4, 12, 40, .45);
}

.onb .onb-head {
    margin-bottom: 16px;
}

.onb .auth-title {
    margin: 0 0 4px;
    font-size: 1.55rem;
    line-height: 1.2;
}

.onb .auth-subtitle {
    margin: 0;
    font-size: .9rem;
}

.onb .req {
    color: #dc2626;
}

.onb .opt {
    font-weight: 400;
    color: #6b7280;
    font-size: .78rem;
}

/* Tiga kolom supaya muat dalam satu layar */
.onb .onb-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0 32px;
}

.onb .onb-section {
    margin: 0 0 12px;
    padding-left: 10px;
    border-left: 4px solid var(--onb-brand);
    font-size: .98rem;
    line-height: 1.3;
}

.onb .form-group {
    margin-bottom: 12px;
}

.onb label {
    display: block;
    margin-bottom: 5px;
    font-size: .83rem;
    font-weight: 600;
    color: #1f2937;
    transition: color .15s;
}

.onb .form-group:focus-within>label {
    color: var(--onb-brand);
}

/* Kolom isian: border + background, menyala saat diklik */
.onb .form-control {
    width: 100%;
    box-sizing: border-box;
    padding: 9px 12px;
    font-size: .92rem;
    color: #111827;
    background-color: var(--onb-bg);
    border: 1.5px solid var(--onb-border);
    border-radius: 10px;
    outline: none;
    transition: background-color .15s, border-color .15s, box-shadow .15s;
}

.onb .form-control::placeholder {
    color: #9aa5bd;
}

.onb .form-control:hover:not(:disabled) {
    border-color: #9fb0d6;
}

.onb .form-control:focus {
    background-color: #fff;
    border-color: var(--onb-brand);
    box-shadow: 0 0 0 4px var(--onb-ring);
}

.onb .form-control:disabled {
    background-color: #E8EBF2;
    color: #6b7280;
    border-style: dashed;
    cursor: not-allowed;
}

.onb .form-control.is-invalid {
    border-color: #dc2626;
    background-color: #fff5f5;
}

.onb .form-control.is-invalid:focus {
    box-shadow: 0 0 0 4px rgba(220, 38, 38, .15);
}

.onb textarea.form-control {
    resize: vertical;
    min-height: 72px;
}

.onb select.form-control {
    appearance: none;
    -webkit-appearance: none;
    padding-right: 38px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%231B3A8C' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    cursor: pointer;
}

.onb input[type="file"].form-control {
    padding: 7px 10px;
    border-style: dashed;
    cursor: pointer;
}

.onb input[type="file"]::file-selector-button {
    margin-right: 10px;
    padding: 6px 12px;
    border: 0;
    border-radius: 8px;
    background: var(--onb-brand);
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}

.onb input[type="file"]:disabled::file-selector-button {
    background: #9aa3b5;
    cursor: not-allowed;
}

.onb .onb-hint {
    margin: 4px 0 0;
    font-size: .76rem;
    color: #6b7280;
    line-height: 1.4;
}

.onb .field-error {
    margin: 4px 0 0;
    font-size: .8rem;
    font-weight: 600;
    color: #dc2626;
}

.onb .onb-actions {
    margin-top: 6px;
    padding-top: 14px;
    border-top: 1px solid #E3E8F2;
    display: flex;
    justify-content: flex-end;
}

.onb .onb-actions .btn {
    min-width: 260px;
    justify-content: center;
}

@media (max-width: 1024px) {
    .onb .onb-grid {
        grid-template-columns: 1fr 1fr;
    }

    .onb .onb-col:nth-child(3) {
        grid-column: 1 / -1;
    }
}

@media (max-width: 700px) {
    .onb {
        padding: 0;
    }

    .onb .onb-brand {
        display: none;
    }

    .onb .onb-card {
        border-radius: 0;
        padding: 20px 18px 24px;
        min-height: 100%;
    }

    .onb .onb-grid {
        grid-template-columns: 1fr;
    }

    .onb .onb-col:nth-child(3) {
        grid-column: auto;
    }

    .onb .onb-actions .btn {
        width: 100%;
    }
}
</style>

<script>
(function() {
    var root = document.querySelector('.onb');

    // Ambil gambar dari panel kiri layout (di bawah overlay ini) dan pakai sebagai latar.
    // Dilewati kalau $bgImage sudah diisi manual di atas.
    if (root && !root.dataset.bg) {
        var pts = [
            [window.innerWidth * 0.10, window.innerHeight * 0.50],
            [window.innerWidth * 0.20, window.innerHeight * 0.30],
            [window.innerWidth * 0.30, window.innerHeight * 0.70]
        ];
        var found = null;

        pts.some(function(p) {
            return document.elementsFromPoint(p[0], p[1]).some(function(el) {
                if (root.contains(el) || el === document.documentElement || el === document.body)
                    return false;

                if (el.tagName === 'IMG' && (el.currentSrc || el.src)) {
                    found = 'url("' + (el.currentSrc || el.src) + '")';
                    return true;
                }
                var bg = getComputedStyle(el).backgroundImage;
                if (bg && bg.indexOf('url(') !== -1) {
                    found = bg;
                    if (bg.indexOf('gradient(') !== -1) root.classList.add(
                        'onb--soft'); // layout sudah punya overlay sendiri
                    return true;
                }
                return false;
            });
        });

        if (found) root.style.backgroundImage = found;
    }

    // Nomor & foto identitas aktif setelah jenis identitas dipilih
    var sel = document.getElementById('document_type');
    var num = document.getElementById('document_number');
    var file = document.getElementById('id_file');
    var hint = document.getElementById('id-hint');

    function sync() {
        var on = !!sel.value;
        num.disabled = !on;
        file.disabled = !on;
        num.required = on;
        file.required = on;
        hint.textContent = on ?
            'Nomor dan foto identitas wajib diisi.' :
            'Pilih jenis identitas dulu untuk mengisi nomor dan foto.';
    }
    sel.addEventListener('change', sync);
    sync();

    document.getElementById('onbForm').addEventListener('submit', function() {
        document.getElementById('onbBtn').disabled = true;
        document.getElementById('onbBtnText').textContent = 'Menyimpan...';
    });
})();
</script>

<?= $this->endSection() ?>