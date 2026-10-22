<?= $this->extend('layouts/customer') ?>

<?= $this->section('content') ?>

<?php
helper('business');

$brand    = business_name() ?: 'Rental Universal';
$customer = $customer ?? [];
$document = $document ?? null;

// Normalisasi daftar tipe dokumen
$docTypes = $docTypes ?? ['KTP' => 'KTP', 'SIM' => 'SIM', 'PASPOR' => 'Paspor'];
if (array_is_list($docTypes)) {
    $docTypes = array_combine($docTypes, $docTypes);
}

// Flashdata
$errors        = session()->getFlashdata('errors') ?? [];
$phoneErrors   = session()->getFlashdata('phone_errors') ?? [];
$success       = session()->getFlashdata('success');
$error         = session()->getFlashdata('error');
$phoneOtpSent  = session()->getFlashdata('phone_otp_sent');
$phoneOtpError = session()->getFlashdata('phone_otp_error');

// Helper functions
$fieldError = static fn(string $k) => $errors[$k] ?? null;
$isInvalid  = static fn(string $k) => isset($errors[$k]) ? ' is-invalid' : '';

// Data Customer
$customerName = trim((string) ($customer['name'] ?? ''));
$initial      = $customerName !== '' ? strtoupper(mb_substr($customerName, 0, 1)) : 'U';
$photo        = $customer['profile_photo'] ?? null;
// Path foto profil sekarang diarahkan ke route controller yang aman
$photoUrl     = $photo ? base_url('account/profile/photo') : null;
$joined       = ! empty($customer['created_at']) ? date('d M Y', strtotime($customer['created_at'])) : '-';

// Data Dokumen
$docType   = old('document_type', $document['document_type'] ?? '');
$docNumber = old('document_number', $document['document_number'] ?? '');
$expiresAt = old('expires_at', $document['expires_at'] ?? '');
$hasFile   = ! empty($document['file_path']);
$status    = $document['verification_status'] ?? 'UNVERIFIED';

$statusMap = [
    'UNVERIFIED' => ['Belum diisi', 'is-neutral', 'Lengkapi jenis, nomor, dan file identitas jika ingin menyewa barang yang mensyaratkannya.'],
    'PENDING'    => ['Menunggu verifikasi', 'is-warn', 'Dokumenmu sedang kami periksa. Kamu akan melihat status terbaru di sini.'],
    'VERIFIED'   => ['Terverifikasi', 'is-ok', 'Identitasmu sudah diverifikasi. Mengubah jenis, nomor, atau file akan memulai verifikasi ulang.'],
    'REJECTED'   => ['Ditolak', 'is-bad', 'Dokumen belum bisa kami terima. Unggah ulang file yang lebih jelas.'],
];
[$statusLabel, $statusClass, $statusText] = $statusMap[$status] ?? $statusMap['UNVERIFIED'];
?>

<style>
html:has(.pf),
body:has(.pf) {
    overflow: hidden;
}

.pf {
    --brand: #1B3A8C;
    --brand-2: #2563c9;
    --ink: #111827;
    --muted: #64748b;
    --line: #E3E8F2;
    --field: #F1F4FA;
    --field-line: #C5CEE0;
    --ring: rgba(27, 58, 140, .18);
    position: fixed;
    inset: 0;
    z-index: 1000;
    overflow-y: auto;
    padding: 64px 16px 30px;
    background-color: #0d1f4d;
    /* Background gradient murni */
    background-image: linear-gradient(135deg, #0d1f4d 0%, #1B3A8C 60%, #2563c9 100%);
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.pf * {
    box-sizing: border-box;
}

.pf-brand {
    position: absolute;
    top: 22px;
    left: 32px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-size: 1.15rem;
    font-weight: 700;
}

.pf-brand i {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #29B6F6;
    box-shadow: 0 0 14px rgba(41, 182, 246, .65);
}

.pf-card {
    max-width: 1120px;
    margin: 0 auto;
    background: rgba(255, 255, 255, .98);
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(4, 12, 40, .45);
}

/* ---------- hero ---------- */
.pf-hero {
    position: relative;
    border-radius: 20px 20px 0 0;
    padding: 28px 32px 26px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 22px;
    background: radial-gradient(circle at 85% 0%, rgba(41, 182, 246, .35), transparent 50%), linear-gradient(120deg, #0d1f4d, var(--brand) 60%, var(--brand-2));
}

.pf-avatar {
    position: relative;
    flex-shrink: 0;
    width: 96px;
    height: 96px;
    cursor: pointer;
    display: block;
}

.pf-avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff center/cover no-repeat;
    color: var(--brand);
    font-size: 36px;
    font-weight: 700;
    border: 4px solid rgba(255, 255, 255, .9);
    box-shadow: 0 10px 28px rgba(0, 0, 0, .3);
}

.pf-avatar-cam {
    position: absolute;
    right: -2px;
    bottom: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #29B6F6;
    color: #06214f;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #fff;
    transition: transform .15s;
}

.pf-avatar:hover .pf-avatar-cam {
    transform: scale(1.1);
}

.pf-avatar:focus-within .pf-avatar-img {
    outline: 3px solid #29B6F6;
    outline-offset: 3px;
}

.pf-avatar-cam svg {
    width: 15px;
    height: 15px;
}

.pf-hero-text {
    min-width: 0;
}

.pf-hero h1 {
    margin: 0;
    font-size: 1.6rem;
    line-height: 1.2;
    font-weight: 700;
    color: #fff;
    overflow-wrap: anywhere;
}

.pf-hero p {
    margin: 4px 0 0;
    color: rgba(255, 255, 255, .78);
    font-size: .9rem;
}

.pf-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.pf-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: .76rem;
    font-weight: 600;
    background: rgba(255, 255, 255, .16);
    border: 1px solid rgba(255, 255, 255, .28);
    color: #fff;
}

.pf-chip::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #34d399;
}

.pf-chip.is-warn::before {
    background: #fbbf24;
}

.pf-chip.is-bad::before {
    background: #f87171;
}

.pf-chip.is-neutral::before {
    background: #cbd5e1;
}

.pf-body {
    padding: 24px 32px 0;
}

.pf-alert {
    padding: 11px 14px;
    margin-bottom: 16px;
    border-radius: 10px;
    font-size: .84rem;
}

.pf-alert.success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
}

.pf-alert.error {
    background: #fff5f5;
    border: 1px solid #fecaca;
    color: #b91c1c;
}

.pf-grid {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 26px;
    align-items: start;
}

/* ---------- kolom kiri ---------- */
.pf-side {
    position: sticky;
    top: 0;
    background: var(--field);
    border: 1px solid #E1E7F1;
    border-radius: 14px;
    padding: 20px;
}

.pf-side h2 {
    margin: 0 0 14px;
    font-size: .78rem;
    letter-spacing: .02em;
    color: var(--muted);
    font-weight: 600;
}

.pf-info {
    margin-bottom: 14px;
}

.pf-info dt {
    color: #94a3b8;
    font-size: .74rem;
    font-weight: 600;
}

.pf-info dd {
    margin: 3px 0 0;
    color: #334155;
    font-size: .86rem;
    overflow-wrap: anywhere;
}

.pf-verify {
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid #DDE4F0;
}

.pf-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: .76rem;
    font-weight: 700;
}

.pf-badge.is-ok {
    background: #ecfdf5;
    color: #047857;
}

.pf-badge.is-warn {
    background: #fffbeb;
    color: #b45309;
}

.pf-badge.is-bad {
    background: #fef2f2;
    color: #b91c1c;
}

.pf-badge.is-neutral {
    background: #e8ecf4;
    color: #475569;
}

.pf-verify p {
    margin: 8px 0 0;
    font-size: .78rem;
    line-height: 1.5;
    color: var(--muted);
}

.pf-reason {
    margin-top: 8px;
    padding: 8px 10px;
    border-radius: 8px;
    background: #fef2f2;
    color: #b91c1c;
    font-size: .78rem;
}

/* ---------- section ---------- */
.pf-section {
    margin-bottom: 22px;
    padding: 20px 22px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
}

.pf-section h3 {
    margin: 0 0 2px;
    font-size: 1rem;
    color: var(--ink);
}

.pf-section>p {
    margin: 0 0 16px;
    color: #6b7280;
    font-size: .8rem;
}

.pf-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 16px;
}

.pf-group {
    margin-bottom: 14px;
}

.pf label {
    display: block;
    margin-bottom: 5px;
    color: #1f2937;
    font-size: .83rem;
    font-weight: 600;
}

.pf .req {
    color: #dc2626;
}

.pf-control {
    width: 100%;
    padding: 10px 12px;
    color: var(--ink);
    font: inherit;
    font-size: .92rem;
    background: var(--field);
    border: 1.5px solid var(--field-line);
    border-radius: 10px;
    outline: none;
    transition: background-color .15s, border-color .15s, box-shadow .15s;
}

.pf-control:hover:not([readonly]) {
    border-color: #9fb0d6;
}

.pf-control:focus {
    background: #fff;
    border-color: var(--brand);
    box-shadow: 0 0 0 4px var(--ring);
}

.pf-control[readonly] {
    background: #E8EBF2;
    color: #475569;
    cursor: default;
}

.pf-control.is-invalid {
    border-color: #dc2626;
    background: #fff5f5;
}

textarea.pf-control {
    min-height: 76px;
    resize: vertical;
}

select.pf-control {
    appearance: none;
    padding-right: 38px;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%231B3A8C' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}

.pf-help {
    margin-top: 5px;
    color: #6b7280;
    font-size: .76rem;
    line-height: 1.4;
}

.pf-err {
    margin-top: 5px;
    color: #dc2626;
    font-size: .78rem;
    font-weight: 600;
}

.pf-phone {
    display: flex;
    gap: 8px;
}

.pf-phone .pf-control {
    flex: 1;
    min-width: 0;
}

.pf-btn-ghost {
    flex-shrink: 0;
    padding: 0 16px;
    border: 1px solid #B8C9E8;
    border-radius: 10px;
    background: #EEF4FF;
    color: var(--brand);
    font: inherit;
    font-size: .83rem;
    font-weight: 600;
    cursor: pointer;
}

.pf-btn-ghost:hover {
    background: #E1EBFF;
}

/* dropzone file identitas */
.pf-drop {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1.5px dashed var(--field-line);
    border-radius: 10px;
    background: var(--field);
    cursor: pointer;
    transition: border-color .15s, background-color .15s;
}

.pf-drop:hover,
.pf-drop:focus-within {
    border-color: var(--brand);
    background: #fff;
}

.pf-drop.is-invalid {
    border-color: #dc2626;
    background: #fff5f5;
}

.pf-drop svg {
    width: 22px;
    height: 22px;
    color: var(--brand);
    flex-shrink: 0;
}

.pf-drop b {
    display: block;
    font-size: .85rem;
    color: var(--ink);
}

.pf-drop span {
    display: block;
    font-size: .76rem;
    color: var(--muted);
    overflow-wrap: anywhere;
}

.pf-drop input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
}

.pf-file-saved {
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 8px 11px;
    border-radius: 9px;
    background: #F1F5F9;
    color: #475569;
    font-size: .78rem;
}

.pf-file-saved a {
    color: var(--brand);
    font-weight: 600;
    text-decoration: underline;
}

/* ---------- aksi ---------- */
.pf-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin: 0 -32px;
    /* Menempel ke pinggir card */
    padding: 20px 32px;
    /* Padding bawah agar tidak terlalu mepet */
    background: #fff;
    border-top: 1px solid var(--line);
    border-radius: 0 0 20px 20px;
    /* Mengikuti lengkungan card di bawah */
}

.pf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 200px;
    padding: 11px 20px;
    border-radius: 10px;
    font: inherit;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.pf-btn-primary {
    border: 0;
    color: #fff;
    background: linear-gradient(135deg, var(--brand), var(--brand-2));
    box-shadow: 0 8px 18px rgba(27, 58, 140, .2);
}

.pf-btn-primary:hover {
    background: linear-gradient(135deg, #142d70, var(--brand));
}

.pf-btn-primary:disabled {
    opacity: .65;
    cursor: not-allowed;
}

.pf-btn-secondary {
    color: var(--brand);
    background: #fff;
    border: 1.5px solid #B8C9E8;
}

.pf-btn-secondary:hover {
    background: #F4F8FF;
}

.pf :focus-visible {
    outline: 3px solid #29B6F6;
    outline-offset: 2px;
}

/* ---------- modal nomor ---------- */
.pf-modal {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(8, 22, 60, .65);
    backdrop-filter: blur(8px);
}

.pf-modal.active {
    display: flex;
}

.pf-modal,
.pf-modal * {
    box-sizing: border-box;
}

.pf-modal label {
    display: block;
    margin-bottom: 5px;
    color: #1f2937;
    font-size: .83rem;
    font-weight: 600;
}

.pf-modal :focus-visible {
    outline: 3px solid #29B6F6;
    outline-offset: 2px;
}

.pf-modal-box {
    position: relative;
    width: min(440px, 100%);
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 25px 70px rgba(0, 0, 0, .35);
    overflow: hidden;
    animation: pfIn .2s ease-out;
}

@keyframes pfIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(.97);
    }

    to {
        opacity: 1;
        transform: none;
    }
}

.pf-modal-head {
    padding: 22px 24px;
    border-bottom: 1px solid var(--line);
}

.pf-modal-head h3 {
    margin: 0;
    padding-right: 36px;
    font-size: 1.1rem;
    color: var(--ink);
}

.pf-modal-head p {
    margin: 6px 0 0;
    color: var(--muted);
    font-size: .84rem;
}

.pf-modal-body {
    padding: 24px;
}

.pf-close {
    position: absolute;
    top: 14px;
    right: 16px;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--muted);
    font-size: 25px;
    line-height: 1;
    cursor: pointer;
}

.pf-close:hover {
    background: var(--field);
    color: var(--ink);
}

.pf-otp {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 20px 0;
}

.pf-otp input {
    width: 46px;
    height: 54px;
    text-align: center;
    border: 1.5px solid var(--field-line);
    border-radius: 10px;
    background: var(--field);
    color: var(--ink);
    font-size: 21px;
    font-weight: 700;
    outline: none;
}

.pf-otp input:focus {
    background: #fff;
    border-color: var(--brand);
    box-shadow: 0 0 0 4px var(--ring);
}

.pf-otp-error {
    padding: 10px 12px;
    margin: 10px 0;
    border-radius: 9px;
    background: #fff5f5;
    color: #b91c1c;
    font-size: .84rem;
}

.pf-otp-links {
    display: flex;
    justify-content: space-between;
    margin-top: 14px;
}

.pf-link {
    padding: 0;
    border: 0;
    background: none;
    color: var(--brand);
    font: inherit;
    font-size: .84rem;
    cursor: pointer;
}

.pf-link:disabled {
    color: #94a3b8;
    cursor: not-allowed;
}

@media (max-width: 900px) {
    .pf-grid {
        grid-template-columns: 1fr;
    }

    .pf-side {
        position: static;
    }
}

@media (max-width: 700px) {
    .pf {
        padding: 0;
    }

    .pf-hero {
        border-radius: 0;
        flex-direction: column;
        text-align: center;
        padding: 26px 18px 22px;
    }

    .pf-brand {
        display: none;
    }

    .pf-card {
        border-radius: 0;
        min-height: 100%;
    }

    .pf-chips {
        justify-content: center;
    }

    .pf-body {
        padding: 18px 16px 0;
    }

    .pf-section {
        padding: 16px;
    }

    .pf-row {
        grid-template-columns: 1fr;
    }

    .pf-phone {
        flex-direction: column;
    }

    .pf-btn-ghost {
        height: 42px;
    }

    .pf-actions {
        margin: 20px -16px 0;
        /* Sesuaikan margin untuk layar kecil */
        padding: 16px;
    }

    .pf-btn {
        min-width: 0;
        flex: 1;
    }

    .pf-otp input {
        width: 42px;
        height: 50px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .pf-modal-box {
        animation: none;
    }
}
</style>

<div class="pf">
    <div class="pf-brand"><i></i><?= esc($brand) ?></div>

    <div class="pf-card">
        <form action="<?= base_url('account/profile/update') ?>" method="post" enctype="multipart/form-data"
            id="profile-form" novalidate>
            <?= csrf_field() ?>

            <!-- HERO -->
            <div class="pf-hero">
                <label class="pf-avatar" for="profile_photo" title="Ganti foto profil">
                    <span class="pf-avatar-img" id="avatar-preview"
                        <?= $photoUrl ? 'style="background-image:url(\'' . esc($photoUrl, 'attr') . '\')"' : '' ?>><?= $photoUrl ? '' : esc($initial) ?></span>
                    <span class="pf-avatar-cam" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                            <circle cx="12" cy="13" r="4" />
                        </svg>
                    </span>
                </label>
                <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" hidden>

                <div class="pf-hero-text">
                    <h1><?= esc($customerName !== '' ? $customerName : 'Pengguna') ?></h1>
                    <p><?= esc($customer['phone'] ?? '-') ?></p>
                    <div class="pf-chips">
                        <span class="pf-chip">Akun aktif</span>
                        <span class="pf-chip <?= $statusClass ?>">Identitas: <?= esc($statusLabel) ?></span>
                    </div>
                    <?php if ($msg = $fieldError('profile_photo')): ?>
                    <div class="pf-err" style="color:#fecaca"><?= esc($msg) ?></div>
                    <?php else: ?>
                    <div class="pf-help" style="color:rgba(255,255,255,.65)" id="photo-help">Klik foto untuk
                        menggantinya. JPG, PNG, atau WEBP, maksimal 2 MB.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pf-body">
                <?php if ($success): ?><div class="pf-alert success"><?= esc($success) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="pf-alert error"><?= esc($error) ?></div><?php endif; ?>

                <div class="pf-grid">

                    <!-- KIRI -->
                    <aside class="pf-side">
                        <h2>Ringkasan akun</h2>
                        <dl class="pf-info">
                            <dt>Email</dt>
                            <dd><?= esc($customer['email'] ?? '-') ?: '-' ?></dd>
                        </dl>
                        <dl class="pf-info">
                            <dt>Alamat</dt>
                            <dd><?= nl2br(esc($customer['address'] ?? '-')) ?></dd>
                        </dl>
                        <dl class="pf-info">
                            <dt>Bergabung sejak</dt>
                            <dd><?= esc($joined) ?></dd>
                        </dl>

                        <div class="pf-verify">
                            <h2>Verifikasi identitas</h2>
                            <span class="pf-badge <?= $statusClass ?>"><?= esc($statusLabel) ?></span>
                            <p><?= esc($statusText) ?></p>
                            <?php if ($status === 'REJECTED' && ! empty($document['reject_reason'])): ?>
                            <div class="pf-reason">Alasan: <?= esc($document['reject_reason']) ?></div>
                            <?php endif; ?>
                        </div>
                    </aside>

                    <!-- KANAN -->
                    <div>
                        <section class="pf-section">
                            <h3>Data diri</h3>
                            <p>Informasi dasar yang dipakai untuk kebutuhan penyewaan.</p>

                            <div class="pf-group">
                                <label for="name">Nama lengkap <span class="req">*</span></label>
                                <input type="text" id="name" name="name" maxlength="150"
                                    class="pf-control<?= $isInvalid('name') ?>"
                                    value="<?= esc(old('name', $customer['name'] ?? '')) ?>">
                                <?php if ($msg = $fieldError('name')): ?><div class="pf-err"><?= esc($msg) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="pf-row">
                                <div class="pf-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" maxlength="150"
                                        class="pf-control<?= $isInvalid('email') ?>"
                                        value="<?= esc(old('email', $customer['email'] ?? '')) ?>">
                                    <?php if ($msg = $fieldError('email')): ?><div class="pf-err"><?= esc($msg) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="pf-group">
                                    <label for="phone-view">Nomor WhatsApp</label>
                                    <div class="pf-phone">
                                        <input type="text" id="phone-view" class="pf-control"
                                            value="<?= esc($customer['phone'] ?? '') ?>" readonly>
                                        <button type="button" class="pf-btn-ghost" id="change-phone-btn">Ganti
                                            nomor</button>
                                    </div>
                                    <div class="pf-help">Nomor ini dipakai untuk login.</div>
                                </div>
                            </div>

                            <div class="pf-group" style="margin-bottom:0">
                                <label for="address">Alamat</label>
                                <textarea id="address" name="address" maxlength="500"
                                    class="pf-control<?= $isInvalid('address') ?>"><?= esc(old('address', $customer['address'] ?? '')) ?></textarea>
                                <?php if ($msg = $fieldError('address')): ?><div class="pf-err"><?= esc($msg) ?></div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="pf-section">
                            <h3>Dokumen identitas</h3>
                            <p>Dipakai untuk rental tertentu. Setiap dokumen diperiksa tim kami sebelum berstatus
                                terverifikasi.</p>

                            <div class="pf-row">
                                <div class="pf-group">
                                    <label for="document_type">Jenis identitas</label>
                                    <select id="document_type" name="document_type"
                                        class="pf-control<?= $isInvalid('document_type') ?>">
                                        <option value="">Pilih jenis</option>
                                        <?php foreach ($docTypes as $val => $label): ?>
                                        <option value="<?= esc($val) ?>"
                                            <?= $docType === (string) $val ? 'selected' : '' ?>><?= esc($label) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($msg = $fieldError('document_type')): ?><div class="pf-err">
                                        <?= esc($msg) ?></div><?php endif; ?>
                                </div>
                                <div class="pf-group">
                                    <label for="document_number">Nomor identitas</label>
                                    <input type="text" id="document_number" name="document_number" maxlength="80"
                                        class="pf-control<?= $isInvalid('document_number') ?>"
                                        value="<?= esc($docNumber) ?>">
                                    <?php if ($msg = $fieldError('document_number')): ?><div class="pf-err">
                                        <?= esc($msg) ?></div><?php endif; ?>
                                </div>
                            </div>

                            <div class="pf-row">
                                <div class="pf-group">
                                    <label for="id_file">Foto / file identitas</label>
                                    <label class="pf-drop<?= $isInvalid('id_file') ?>" for="id_file"
                                        style="margin-bottom:0">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <path d="M17 8l-5-5-5 5" />
                                            <path d="M12 3v12" />
                                        </svg>
                                        <div>
                                            <b id="id-file-title"><?= $hasFile ? 'Ganti file' : 'Pilih file' ?></b>
                                            <span id="id-file-name">JPG, PNG, atau PDF. Maksimal 4 MB.</span>
                                        </div>
                                        <input type="file" id="id_file" name="id_file" accept=".jpg,.jpeg,.png,.pdf">
                                    </label>
                                    <?php if ($hasFile): ?>
                                    <div class="pf-file-saved">
                                        Dokumen sudah tersimpan.
                                        <!-- Mengarah ke route ProfileController::identityFile() -->
                                        <a href="<?= base_url('account/profile/identity-file') ?>" target="_blank"
                                            rel="noopener">Lihat dokumen</a>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($msg = $fieldError('id_file')): ?><div class="pf-err"><?= esc($msg) ?>
                                    </div><?php endif; ?>
                                </div>
                                <div class="pf-group">
                                    <label for="expires_at">Berlaku sampai</label>
                                    <input type="date" id="expires_at" name="expires_at"
                                        class="pf-control<?= $isInvalid('expires_at') ?>"
                                        value="<?= esc($expiresAt) ?>">
                                    <?php if ($msg = $fieldError('expires_at')): ?><div class="pf-err"><?= esc($msg) ?>
                                    </div><?php endif; ?>
                                    <div class="pf-help">Kosongkan jika dokumen berlaku seumur hidup.</div>
                                </div>
                            </div>
                        </section>

                        <section class="pf-section">
                            <h3>Kontak darurat</h3>
                            <p>Orang yang bisa kami hubungi jika diperlukan.</p>

                            <div class="pf-row">
                                <div class="pf-group">
                                    <label for="emergency_contact_name">Nama</label>
                                    <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                        maxlength="150" class="pf-control<?= $isInvalid('emergency_contact_name') ?>"
                                        value="<?= esc(old('emergency_contact_name', $customer['emergency_contact_name'] ?? '')) ?>">
                                    <?php if ($msg = $fieldError('emergency_contact_name')): ?><div class="pf-err">
                                        <?= esc($msg) ?></div><?php endif; ?>
                                </div>
                                <div class="pf-group">
                                    <label for="emergency_contact_phone">Nomor HP</label>
                                    <input type="tel" inputmode="numeric" id="emergency_contact_phone"
                                        name="emergency_contact_phone" maxlength="30"
                                        class="pf-control<?= $isInvalid('emergency_contact_phone') ?>"
                                        value="<?= esc(old('emergency_contact_phone', $customer['emergency_contact_phone'] ?? '')) ?>">
                                    <?php if ($msg = $fieldError('emergency_contact_phone')): ?><div class="pf-err">
                                        <?= esc($msg) ?></div><?php endif; ?>
                                </div>
                            </div>

                            <div class="pf-group" style="margin-bottom:0">
                                <label for="notes">Catatan</label>
                                <textarea id="notes" name="notes" maxlength="500"
                                    class="pf-control<?= $isInvalid('notes') ?>"><?= esc(old('notes', $customer['notes'] ?? '')) ?></textarea>
                                <?php if ($msg = $fieldError('notes')): ?><div class="pf-err"><?= esc($msg) ?></div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="pf-actions">
                    <a href="<?= base_url('/') ?>" class="pf-btn pf-btn-secondary">Kembali</a>
                    <button type="submit" class="pf-btn pf-btn-primary" id="save-profile-btn">Simpan perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL GANTI NOMOR (di luar form utama) -->
<div class="pf-modal" id="phone-modal" role="dialog" aria-modal="true" aria-labelledby="phone-modal-title">
    <div class="pf-modal-box">
        <div class="pf-modal-head">
            <button type="button" class="pf-close" id="close-phone-modal" aria-label="Tutup">&times;</button>
            <h3 id="phone-modal-title">Ganti nomor WhatsApp</h3>
            <p>Kami kirim kode OTP ke nomor baru untuk memastikan nomor itu milikmu.</p>
        </div>
        <div class="pf-modal-body">
            <?php if (! $phoneOtpSent): ?>
            <form action="<?= base_url('account/profile/phone/send-otp') ?>" method="post">
                <?= csrf_field() ?>
                <div class="pf-group">
                    <label for="new-phone">Nomor WhatsApp baru</label>
                    <input type="text" id="new-phone" name="phone" class="pf-control" placeholder="08xxxxxxxxxx"
                        maxlength="20" required>
                    <?php if (! empty($phoneErrors['phone'])): ?><div class="pf-err"><?= esc($phoneErrors['phone']) ?>
                    </div><?php endif; ?>
                </div>
                <button type="submit" class="pf-btn pf-btn-primary" style="width:100%">Kirim kode OTP</button>
            </form>
            <?php else: ?>
            <form action="<?= base_url('account/profile/phone/verify') ?>" method="post" id="otp-form">
                <?= csrf_field() ?>
                <p style="text-align:center;color:#64748b;font-size:.84rem;margin:0">Masukkan kode OTP 6 digit.</p>
                <?php if ($phoneOtpError): ?><div class="pf-otp-error"><?= esc($phoneOtpError) ?></div><?php endif; ?>
                <div class="pf-otp">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input"
                        aria-label="Digit <?= $i + 1 ?>">
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="otp" id="otp-value">
                <button type="submit" class="pf-btn pf-btn-primary" style="width:100%">Verifikasi nomor</button>
                <div class="pf-otp-links">
                    <button type="button" class="pf-link" disabled>Kirim ulang</button>
                    <button type="button" class="pf-link"
                        onclick="location.href='<?= base_url('account/profile') ?>'">Ganti nomor</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('phone-modal');
    var open = document.getElementById('change-phone-btn');
    var close = document.getElementById('close-phone-modal');

    function setModal(on) {
        if (modal) modal.classList.toggle('active', on);
    }
    if (open) open.addEventListener('click', function(e) {
        e.preventDefault();
        setModal(true);
    });
    if (close) close.addEventListener('click', function(e) {
        e.preventDefault();
        setModal(false);
    });
    if (modal) modal.addEventListener('click', function(e) {
        if (e.target === modal) setModal(false);
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') setModal(false);
    });

    <?php if ($phoneOtpSent || ! empty($phoneErrors) || ! empty($phoneOtpError)): ?>
    setModal(true);
    <?php endif; ?>

    /* ---- OTP ---- */
    var otpInputs = document.querySelectorAll('.otp-input');
    var otpHidden = document.getElementById('otp-value');

    function updateOtp() {
        if (!otpHidden) return;
        var v = '';
        otpInputs.forEach(function(i) {
            v += i.value;
        });
        otpHidden.value = v;
    }
    otpInputs.forEach(function(input, idx) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value && otpInputs[idx + 1]) otpInputs[idx + 1].focus();
            updateOtp();
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && otpInputs[idx - 1]) otpInputs[idx - 1]
                .focus();
        });
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            var txt = ((e.clipboardData || window.clipboardData).getData('text') || '').replace(
                /\D/g, '').substring(0, 6);
            txt.split('').forEach(function(d, i) {
                if (otpInputs[i]) otpInputs[i].value = d;
            });
            updateOtp();
            if (otpInputs[txt.length - 1]) otpInputs[txt.length - 1].focus();
        });
    });
    var otpForm = document.getElementById('otp-form');
    if (otpForm) otpForm.addEventListener('submit', updateOtp);

    /* ---- Preview foto profil ---- */
    var photo = document.getElementById('profile_photo');
    var preview = document.getElementById('avatar-preview');
    var photoHelp = document.getElementById('photo-help');
    if (photo && preview) {
        photo.addEventListener('change', function() {
            var f = photo.files && photo.files[0];
            if (!f) return;
            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1 || f.size > 2 * 1024 *
                1024) {
                photo.value = '';
                if (photoHelp) {
                    photoHelp.textContent = 'Foto harus JPG, PNG, atau WEBP dan maksimal 2 MB.';
                    photoHelp.style.color = '#fecaca';
                }
                return;
            }
            var url = URL.createObjectURL(f);
            preview.style.backgroundImage = 'url("' + url + '")';
            preview.textContent = '';
            if (photoHelp) {
                photoHelp.textContent =
                    'Foto baru akan tersimpan setelah kamu menekan "Simpan perubahan".';
                photoHelp.style.color = 'rgba(255,255,255,.8)';
            }
        });
    }

    /* ---- Nama file identitas ---- */
    var idFile = document.getElementById('id_file');
    var idName = document.getElementById('id-file-name');
    if (idFile && idName) {
        idFile.addEventListener('change', function() {
            var f = idFile.files && idFile.files[0];
            if (!f) return;
            if (f.size > 4 * 1024 * 1024) {
                idFile.value = '';
                idName.textContent = 'Ukuran file lebih dari 4 MB. Pilih file lain.';
                return;
            }
            idName.textContent = f.name;
        });
    }

    /* ---- Simpan ---- */
    var form = document.getElementById('profile-form');
    var save = document.getElementById('save-profile-btn');
    if (form && save) {
        form.addEventListener('submit', function() {
            save.disabled = true;
            save.textContent = 'Menyimpan...';
        });
    }
});
</script>

<?= $this->endSection() ?>