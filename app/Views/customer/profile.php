<?= $this->extend('layouts/customer') ?>

<?= $this->section('content') ?>

<?php
helper('business');

$brand = business_name() ?: 'Rental Universal';

$customer = $customer ?? [];
$document = $document ?? null;

$docTypes = $docTypes ?? [
    'KTP',
    'SIM',
    'PASPOR',
];

$errors = session()->getFlashdata('errors') ?? [];
$phoneErrors = session()->getFlashdata('phone_errors') ?? [];
$success = session()->getFlashdata('success');
$error = session()->getFlashdata('error');

$phoneOtpSent = session()->getFlashdata('phone_otp_sent');
$phoneOtpError = session()->getFlashdata('phone_otp_error');

$fieldError = static function (string $key) use ($errors) {
    return $errors[$key] ?? null;
};

$isInvalid = static function (string $key) use ($errors) {
    return isset($errors[$key]) ? ' is-invalid' : '';
};

$documentType = old(
    'document_type',
    $document['document_type'] ?? ''
);

$documentNumber = old(
    'document_number',
    $document['document_number'] ?? ''
);

$expiresAt = old(
    'expires_at',
    $document['expires_at'] ?? ''
);

$documentNotes = old(
    'document_notes',
    $document['notes'] ?? ''
);

$customerName = trim((string) ($customer['name'] ?? ''));

$initial = $customerName !== ''
    ? strtoupper(mb_substr($customerName, 0, 1))
    : 'U';
?>

<style>
/* =========================================================
   FULL SCREEN PROFILE
========================================================= */

html:has(.profile-onboarding),
body:has(.profile-onboarding) {
    overflow: hidden;
}

.profile-onboarding {
    --brand: #1B3A8C;
    --brand-dark: #0d1f4d;
    --brand-light: #2563c9;
    --ring: rgba(27, 58, 140, .18);
    --border: #C5CEE0;
    --input-bg: #F1F4FA;

    position: fixed;
    inset: 0;
    z-index: 1000;

    overflow-y: auto;

    display: flex;
    flex-direction: column;

    padding: 64px 16px 30px;

    background-color: #0d1f4d;

    /*
     * GANTI DENGAN GAMBAR BACKGROUND KAMU
     */
    background-image:
        linear-gradient(135deg,
            rgba(8, 22, 60, .70),
            rgba(27, 58, 140, .45)),
        url("<?= base_url('assets/img/login-bg.jpg') ?>");

    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}


/* =========================================================
   BRAND
========================================================= */

.profile-onboarding .profile-brand {
    position: absolute;

    top: 22px;
    left: 32px;

    z-index: 3;

    display: flex;
    align-items: center;
    gap: 10px;

    color: #fff;

    font-size: 1.15rem;
    font-weight: 700;
}

.profile-onboarding .profile-brand-dot {
    width: 10px;
    height: 10px;

    border-radius: 50%;

    background: #29B6F6;

    box-shadow:
        0 0 14px rgba(41, 182, 246, .65);
}


/* =========================================================
   MAIN CARD
========================================================= */

.profile-onboarding .profile-card-main {

    position: relative;
    z-index: 2;

    width: 100%;
    max-width: 1120px;

    margin: auto;

    box-sizing: border-box;

    padding: 26px 30px 30px;

    background: rgba(255, 255, 255, .97);

    -webkit-backdrop-filter: blur(12px);
    backdrop-filter: blur(12px);

    border-radius: 18px;

    box-shadow:
        0 20px 60px rgba(4, 12, 40, .45);
}


/* =========================================================
   HEADER
========================================================= */

.profile-onboarding .profile-head {
    margin-bottom: 20px;
}

.profile-onboarding .profile-head h1 {
    margin: 0 0 5px;

    color: #111827;

    font-size: 1.55rem;
    line-height: 1.2;
    font-weight: 700;
}

.profile-onboarding .profile-head p {
    margin: 0;

    color: #64748b;

    font-size: .9rem;
}


/* =========================================================
   ALERT
========================================================= */

.profile-onboarding .profile-alert {
    padding: 11px 14px;

    margin-bottom: 18px;

    border-radius: 10px;

    font-size: .83rem;
}

.profile-onboarding .profile-alert.success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
}

.profile-onboarding .profile-alert.error {
    background: #fff5f5;
    border: 1px solid #fecaca;
    color: #b91c1c;
}


/* =========================================================
   PROFILE GRID
========================================================= */

.profile-onboarding .profile-grid {
    display: grid;

    grid-template-columns:
        290px minmax(0, 1fr);

    gap: 24px;

    align-items: start;
}


/* =========================================================
   LEFT ACCOUNT CARD
========================================================= */

.profile-onboarding .account-card {

    background: #F1F4FA;

    border: 1px solid #E1E7F1;

    border-radius: 14px;

    padding: 22px;

    position: sticky;
    top: 20px;
}

.profile-onboarding .profile-avatar {

    width: 76px;
    height: 76px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background:
        linear-gradient(135deg,
            #1B3A8C,
            #2563c9);

    color: #fff;

    font-size: 28px;
    font-weight: 700;

    box-shadow:
        0 10px 25px rgba(27, 58, 140, .25);
}

.profile-onboarding .account-name {
    margin-top: 15px;

    color: #111827;

    font-size: 20px;
    font-weight: 700;
}

.profile-onboarding .account-phone {
    margin-top: 5px;

    color: #64748b;

    font-size: 14px;
}

.profile-onboarding .account-status {
    display: inline-flex;

    margin-top: 13px;

    padding: 6px 11px;

    border-radius: 999px;

    background: #ecfdf5;
    color: #047857;

    font-size: 12px;
    font-weight: 600;
}


/* =========================================================
   ACCOUNT INFO
========================================================= */

.profile-onboarding .account-info {

    margin-top: 22px;

    padding-top: 18px;

    border-top: 1px solid #DDE4F0;
}

.profile-onboarding .account-info-item {
    margin-bottom: 13px;
}

.profile-onboarding .account-info-label {

    color: #94a3b8;

    font-size: 11px;
    font-weight: 600;

    text-transform: uppercase;
    letter-spacing: .04em;
}

.profile-onboarding .account-info-value {

    margin-top: 3px;

    color: #334155;

    font-size: 13px;
}


/* =========================================================
   FORM AREA
========================================================= */

.profile-onboarding .form-area {

    min-width: 0;
}

.profile-onboarding .form-section {

    margin-bottom: 24px;

    padding-bottom: 22px;

    border-bottom: 1px solid #E3E8F2;
}

.profile-onboarding .form-section:last-child {
    border-bottom: 0;
    margin-bottom: 0;
    padding-bottom: 0;
}

.profile-onboarding .form-section-title {

    margin: 0 0 4px;

    padding-left: 10px;

    border-left: 4px solid var(--brand);

    color: #111827;

    font-size: .98rem;
    line-height: 1.3;
}

.profile-onboarding .form-section-description {

    margin: 0 0 14px;

    color: #6b7280;

    font-size: .76rem;
}


/* =========================================================
   FORM
========================================================= */

.profile-onboarding .form-group {
    margin-bottom: 13px;
}

.profile-onboarding .form-row {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 15px;
}

.profile-onboarding label {

    display: block;

    margin-bottom: 5px;

    color: #1f2937;

    font-size: .83rem;
    font-weight: 600;
}

.profile-onboarding .required {
    color: #dc2626;
}

.profile-onboarding .optional {
    color: #6b7280;

    font-size: .76rem;
    font-weight: 400;
}

.profile-onboarding .form-control,
.profile-onboarding .form-select,
.profile-onboarding .form-textarea {

    width: 100%;

    box-sizing: border-box;

    padding: 9px 12px;

    color: #111827;

    background: var(--input-bg);

    border: 1.5px solid var(--border);

    border-radius: 10px;

    outline: none;

    font-family: inherit;
    font-size: .92rem;

    transition:
        background-color .15s,
        border-color .15s,
        box-shadow .15s;
}

.profile-onboarding .form-control::placeholder {
    color: #9aa5bd;
}

.profile-onboarding .form-control:hover,
.profile-onboarding .form-select:hover,
.profile-onboarding .form-textarea:hover {
    border-color: #9fb0d6;
}

.profile-onboarding .form-control:focus,
.profile-onboarding .form-select:focus,
.profile-onboarding .form-textarea:focus {

    background: #fff;

    border-color: var(--brand);

    box-shadow:
        0 0 0 4px var(--ring);
}

.profile-onboarding .form-control.is-invalid,
.profile-onboarding .form-select.is-invalid,
.profile-onboarding .form-textarea.is-invalid {

    border-color: #dc2626;

    background: #fff5f5;
}

.profile-onboarding .form-textarea {

    min-height: 72px;

    resize: vertical;
}

.profile-onboarding .field-help {

    margin-top: 4px;

    color: #6b7280;

    font-size: .76rem;
    line-height: 1.4;
}

.profile-onboarding .field-error {

    margin-top: 4px;

    color: #dc2626;

    font-size: .78rem;
    font-weight: 600;
}


/* =========================================================
   PHONE
========================================================= */

.profile-onboarding .phone-box {

    display: flex;

    gap: 9px;
}

.profile-onboarding .phone-box .form-control {
    flex: 1;
}

.profile-onboarding .btn-change-phone {

    flex-shrink: 0;

    padding: 0 16px;

    border: 1px solid #B8C9E8;

    border-radius: 10px;

    background: #EEF4FF;

    color: #1B3A8C;

    font-size: .83rem;
    font-weight: 600;

    cursor: pointer;
}

.profile-onboarding .btn-change-phone:hover {
    background: #E1EBFF;
}


/* =========================================================
   FILE
========================================================= */

.profile-onboarding input[type="file"] {

    padding: 7px 10px;

    border-style: dashed;

    cursor: pointer;
}

.profile-onboarding input[type="file"]::file-selector-button {

    margin-right: 10px;

    padding: 6px 12px;

    border: 0;

    border-radius: 8px;

    background: var(--brand);

    color: #fff;

    font-weight: 600;

    cursor: pointer;
}


/* =========================================================
   DOCUMENT STATUS
========================================================= */

.profile-onboarding .document-status {

    margin-top: 7px;

    padding: 9px 11px;

    border-radius: 9px;

    background: #F1F5F9;

    color: #64748b;

    font-size: .76rem;
}

.profile-onboarding .document-status.verified {

    background: #ecfdf5;

    color: #047857;
}


/* =========================================================
   ACTION
========================================================= */

.profile-onboarding .form-actions {

    display: flex;

    justify-content: flex-end;

    margin-top: 4px;

    padding-top: 17px;

    border-top: 1px solid #E3E8F2;
}

.profile-onboarding .btn-primary {

    min-width: 220px;

    padding: 11px 20px;

    border: 0;

    border-radius: 10px;

    background:
        linear-gradient(135deg,
            #1B3A8C,
            #2563c9);

    color: #fff;

    font-size: .88rem;
    font-weight: 600;

    cursor: pointer;

    box-shadow:
        0 8px 18px rgba(27, 58, 140, .20);
}

.profile-onboarding .btn-primary:hover {
    background:
        linear-gradient(135deg,
            #142d70,
            #1B3A8C);
}

.profile-onboarding .btn-primary:disabled {
    opacity: .65;
    cursor: not-allowed;
}


/* =========================================================
   MODAL OTP
========================================================= */

/* =========================================================
   MODAL GANTI NOMOR WHATSAPP
========================================================= */

.modal-overlay {
    position: fixed;
    inset: 0;

    z-index: 99999;

    display: none;

    align-items: center;
    justify-content: center;

    padding: 20px;

    background: rgba(8, 22, 60, .65);

    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.modal-overlay.active {
    display: flex;
}

.phone-modal {
    position: relative;

    width: min(440px, calc(100% - 40px));

    background: #ffffff;

    border-radius: 18px;

    box-shadow:
        0 25px 70px rgba(0, 0, 0, .35);

    overflow: hidden;

    animation: phoneModalIn .2s ease-out;
}

@keyframes phoneModalIn {

    from {
        opacity: 0;
        transform: translateY(12px) scale(.97);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

}

.phone-modal-header {
    position: relative;

    padding: 22px 24px;

    border-bottom: 1px solid #E3E8F2;
}

.phone-modal-header h3 {
    margin: 0;

    padding-right: 35px;

    color: #111827;

    font-size: 18px;
    font-weight: 700;
}

.phone-modal-header p {
    margin: 6px 0 0;

    color: #64748b;

    font-size: 13px;
}

.phone-modal-body {
    padding: 24px;
}

.modal-close {
    position: absolute;

    top: 14px;
    right: 16px;

    width: 34px;
    height: 34px;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 0;

    border: 0;

    border-radius: 8px;

    background: transparent;

    color: #64748b;

    font-size: 25px;
    line-height: 1;

    cursor: pointer;

    transition:
        background .15s ease,
        color .15s ease;
}

.modal-close:hover {
    background: #F1F4FA;
    color: #111827;
}


/* =========================================================
   OTP
========================================================= */

.otp-group {

    display: flex;

    justify-content: center;

    gap: 8px;

    margin: 22px 0;
}

.otp-input {

    width: 48px;
    height: 54px;

    box-sizing: border-box;

    text-align: center;

    border: 1.5px solid #C5CEE0;

    border-radius: 10px;

    background: #F1F4FA;

    color: #111827;

    font-size: 21px;
    font-weight: 700;

    outline: none;

    transition:
        background .15s,
        border-color .15s,
        box-shadow .15s;
}

.otp-input:focus {

    background: #fff;

    border-color: #1B3A8C;

    box-shadow:
        0 0 0 4px rgba(27, 58, 140, .18);
}

.otp-error {

    padding: 10px 12px;

    margin-bottom: 10px;

    border-radius: 9px;

    background: #fff5f5;

    color: #b91c1c;

    font-size: 13px;
}

.otp-actions {

    display: flex;

    justify-content: space-between;

    margin-top: 15px;
}

.resend-btn {

    padding: 0;

    border: 0;

    background: none;

    color: #1B3A8C;

    font-size: 13px;

    cursor: pointer;
}

.resend-btn:disabled {

    color: #94a3b8;

    cursor: not-allowed;
}


/* =========================================================
   MOBILE MODAL
========================================================= */

@media(max-width: 700px) {

    .phone-modal {

        width: calc(100% - 30px);

        border-radius: 16px;

    }

    .phone-modal-header {

        padding: 20px;

    }

    .phone-modal-body {

        padding: 20px;

    }

    .otp-input {

        width: 42px;
        height: 50px;

    }

}


/* =========================================================
   TABLET
========================================================= */

@media(max-width: 900px) {

    .profile-onboarding .profile-grid {
        grid-template-columns: 1fr;
    }

    .profile-onboarding .account-card {
        position: static;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width: 700px) {

    .profile-onboarding {

        padding: 0;

        background-position: center;
    }

    .profile-onboarding .profile-brand {
        display: none;
    }

    .profile-onboarding .profile-card-main {

        max-width: none;

        min-height: 100%;

        border-radius: 0;

        padding: 25px 18px 30px;

        box-shadow: none;
    }

    .profile-onboarding .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .profile-onboarding .phone-box {
        flex-direction: column;
    }

    .profile-onboarding .btn-change-phone {
        height: 42px;
    }

    .profile-onboarding .form-actions {
        justify-content: stretch;
    }

    .profile-onboarding .btn-primary {
        width: 100%;
    }

    .profile-onboarding .otp-input {

        width: 42px;
        height: 50px;
    }

}
</style>


<div class="profile-onboarding">

    <!-- BRAND -->

    <div class="profile-brand">

        <span class="profile-brand-dot"></span>

        <?= esc($brand) ?>

    </div>


    <!-- MAIN CARD -->

    <div class="profile-card-main">

        <!-- HEADER -->

        <div class="profile-head">

            <h1>Profil Saya</h1>

            <p>
                Kelola informasi pribadi dan dokumen identitas kamu.
            </p>

        </div>


        <!-- ALERT -->

        <?php if ($success): ?>

        <div class="profile-alert success">
            <?= esc($success) ?>
        </div>

        <?php endif; ?>


        <?php if ($error): ?>

        <div class="profile-alert error">
            <?= esc($error) ?>
        </div>

        <?php endif; ?>


        <!-- GRID -->

        <div class="profile-grid">


            <!-- =================================================
                 ACCOUNT
            ================================================== -->

            <div class="account-card">

                <div class="profile-avatar">
                    <?= esc($initial) ?>
                </div>

                <div class="account-name">

                    <?= esc(
                        $customerName !== ''
                            ? $customerName
                            : 'Pengguna'
                    ) ?>

                </div>

                <div class="account-phone">

                    <?= esc(
                        $customer['phone'] ?? '-'
                    ) ?>

                </div>

                <div class="account-status">
                    Akun Aktif
                </div>


                <div class="account-info">

                    <div class="account-info-item">

                        <div class="account-info-label">
                            Email
                        </div>

                        <div class="account-info-value">

                            <?= esc(
                                $customer['email']
                                ?? '-'
                            ) ?>

                        </div>

                    </div>


                    <div class="account-info-item">

                        <div class="account-info-label">
                            Alamat
                        </div>

                        <div class="account-info-value">

                            <?= nl2br(
                                esc(
                                    $customer['address']
                                    ?? '-'
                                )
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 FORM
            ================================================== -->

            <div class="form-area">

                <form action="<?= base_url('account/profile/update') ?>" method="post" enctype="multipart/form-data"
                    id="profile-form">

                    <?= csrf_field() ?>


                    <!-- DATA DIRI -->

                    <div class="form-section">

                        <h3 class="form-section-title">
                            Data Diri
                        </h3>

                        <p class="form-section-description">
                            Informasi dasar yang digunakan untuk kebutuhan penyewaan.
                        </p>


                        <div class="form-group">

                            <label>
                                Nama Lengkap
                                <span class="required">*</span>
                            </label>

                            <input type="text" name="name" maxlength="150" class="form-control<?= $isInvalid('name') ?>"
                                value="<?= esc(
                                    old(
                                        'name',
                                        $customer['name'] ?? ''
                                    )
                                ) ?>">

                            <?php if ($msg = $fieldError('name')): ?>

                            <div class="field-error">
                                <?= esc($msg) ?>
                            </div>

                            <?php endif; ?>

                        </div>


                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Email
                                </label>

                                <input type="email" name="email" maxlength="150"
                                    class="form-control<?= $isInvalid('email') ?>" value="<?= esc(
                                        old(
                                            'email',
                                            $customer['email'] ?? ''
                                        )
                                    ) ?>">

                                <?php if ($msg = $fieldError('email')): ?>

                                <div class="field-error">
                                    <?= esc($msg) ?>
                                </div>

                                <?php endif; ?>

                            </div>


                            <div class="form-group">

                                <label>
                                    Nomor WhatsApp
                                </label>

                                <div class="phone-box">

                                    <input type="text" class="form-control" value="<?= esc($customer['phone'] ?? '') ?>"
                                        readonly>

                                    <button type="button" class="btn-change-phone" id="change-phone-btn">
                                        Ganti
                                    </button>

                                </div>

                                <div class="field-help">
                                    Nomor WhatsApp digunakan untuk login.
                                </div>

                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Alamat
                            </label>

                            <textarea name="address" class="form-textarea<?= $isInvalid('address') ?>" maxlength="500"><?= esc(
                                old(
                                    'address',
                                    $customer['address'] ?? ''
                                )
                            ) ?></textarea>

                            <?php if ($msg = $fieldError('address')): ?>

                            <div class="field-error">
                                <?= esc($msg) ?>
                            </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- IDENTITAS -->

                    <div class="form-section">

                        <h3 class="form-section-title">
                            Dokumen Identitas
                        </h3>

                        <p class="form-section-description">
                            Data identitas digunakan untuk kebutuhan rental tertentu.
                        </p>


                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Jenis Identitas
                                </label>

                                <select name="document_type" class="form-select<?= $isInvalid('document_type') ?>">

                                    <option value="">
                                        Pilih jenis
                                    </option>

                                    <?php foreach ($docTypes as $type): ?>

                                    <option value="<?= esc($type) ?>" <?= $documentType === $type
                                            ? 'selected'
                                            : '' ?>>
                                        <?= esc($type) ?>
                                    </option>

                                    <?php endforeach; ?>

                                </select>

                                <?php if ($msg = $fieldError('document_type')): ?>

                                <div class="field-error">
                                    <?= esc($msg) ?>
                                </div>

                                <?php endif; ?>

                            </div>


                            <div class="form-group">

                                <label>
                                    Nomor Identitas
                                </label>

                                <input type="text" name="document_number" maxlength="100"
                                    class="form-control<?= $isInvalid('document_number') ?>"
                                    value="<?= esc($documentNumber) ?>">

                                <?php if ($msg = $fieldError('document_number')): ?>

                                <div class="field-error">
                                    <?= esc($msg) ?>
                                </div>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    File Identitas
                                </label>

                                <input type="file" name="document_file"
                                    class="form-control<?= $isInvalid('document_file') ?>"
                                    accept=".jpg,.jpeg,.png,.pdf">

                                <div class="field-help">
                                    JPG, JPEG, PNG atau PDF. Maksimal 4 MB.
                                </div>

                                <?php if (
                                    $document &&
                                    !empty($document['file_path'])
                                ): ?>

                                <div class="document-status">
                                    Dokumen sudah tersimpan. Upload file baru jika ingin menggantinya.
                                </div>

                                <?php endif; ?>

                                <?php if ($msg = $fieldError('document_file')): ?>

                                <div class="field-error">
                                    <?= esc($msg) ?>
                                </div>

                                <?php endif; ?>

                            </div>


                            <div class="form-group">

                                <label>
                                    Berlaku Sampai
                                </label>

                                <input type="date" name="expires_at" class="form-control<?= $isInvalid('expires_at') ?>"
                                    value="<?= esc($expiresAt) ?>">

                                <?php if ($msg = $fieldError('expires_at')): ?>

                                <div class="field-error">
                                    <?= esc($msg) ?>
                                </div>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Catatan Dokumen
                            </label>

                            <textarea name="document_notes" class="form-textarea<?= $isInvalid('document_notes') ?>"
                                maxlength="255"><?= esc($documentNotes) ?></textarea>

                            <?php if ($msg = $fieldError('document_notes')): ?>

                            <div class="field-error">
                                <?= esc($msg) ?>
                            </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- KONTAK DARURAT -->

                    <div class="form-section">

                        <h3 class="form-section-title">
                            Kontak Darurat
                        </h3>

                        <p class="form-section-description">
                            Kontak yang dapat dihubungi apabila diperlukan.
                        </p>


                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Nama Kontak Darurat
                                </label>

                                <input type="text" name="emergency_contact_name" maxlength="150"
                                    class="form-control<?= $isInvalid('emergency_contact_name') ?>" value="<?= esc(
                                        old(
                                            'emergency_contact_name',
                                            $customer['emergency_contact_name'] ?? ''
                                        )
                                    ) ?>">

                                <?php if ($msg = $fieldError('emergency_contact_name')): ?>

                                <div class="field-error">
                                    <?= esc($msg) ?>
                                </div>

                                <?php endif; ?>

                            </div>


                            <div class="form-group">

                                <label>
                                    Nomor Kontak Darurat
                                </label>

                                <input type="text" name="emergency_contact_phone" maxlength="30"
                                    class="form-control<?= $isInvalid('emergency_contact_phone') ?>" value="<?= esc(
                                        old(
                                            'emergency_contact_phone',
                                            $customer['emergency_contact_phone'] ?? ''
                                        )
                                    ) ?>">

                                <?php if ($msg = $fieldError('emergency_contact_phone')): ?>

                                <div class="field-error">
                                    <?= esc($msg) ?>
                                </div>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Catatan
                            </label>

                            <textarea name="notes" maxlength="500" class="form-textarea<?= $isInvalid('notes') ?>"><?= esc(
                                old(
                                    'notes',
                                    $customer['notes'] ?? ''
                                )
                            ) ?></textarea>

                            <?php if ($msg = $fieldError('notes')): ?>

                            <div class="field-error">
                                <?= esc($msg) ?>
                            </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- ACTION -->

                    <div class="form-actions">

                        <button type="submit" class="btn-primary" id="save-profile-btn">
                            Simpan Perubahan
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     MODAL GANTI NOMOR
========================================================= -->

<div class="modal-overlay" id="phone-modal">

    <div class="phone-modal">

        <div class="phone-modal-header">

            <button type="button" class="modal-close" id="close-phone-modal">
                ×
            </button>

            <h3>
                Ganti Nomor WhatsApp
            </h3>

            <p>
                Masukkan nomor WhatsApp baru.
            </p>

        </div>


        <div class="phone-modal-body">

            <?php if (!$phoneOtpSent): ?>

            <form action="<?= base_url('account/profile/phone/send-otp') ?>" method="post">

                <?= csrf_field() ?>

                <div class="form-group">

                    <label>
                        Nomor WhatsApp Baru
                    </label>

                    <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" maxlength="20"
                        required>

                    <?php if (!empty($phoneErrors['phone'])): ?>

                    <div class="field-error">
                        <?= esc($phoneErrors['phone']) ?>
                    </div>

                    <?php endif; ?>

                </div>

                <button type="submit" class="btn-primary" style="width:100%;">
                    Kirim Kode OTP
                </button>

            </form>

            <?php else: ?>

            <form action="<?= base_url('account/profile/phone/verify') ?>" method="post" id="otp-form">

                <?= csrf_field() ?>

                <p style="
                    text-align:center;
                    color:#64748b;
                    font-size:13px;
                ">
                    Masukkan kode OTP 6 digit.
                </p>

                <?php if ($phoneOtpError): ?>

                <div class="otp-error">
                    <?= esc($phoneOtpError) ?>
                </div>

                <?php endif; ?>


                <div class="otp-group">

                    <?php for ($i = 0; $i < 6; $i++): ?>

                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">

                    <?php endfor; ?>

                </div>


                <input type="hidden" name="otp" id="otp-value">


                <button type="submit" class="btn-primary" style="width:100%;">
                    Verifikasi Nomor
                </button>


                <div class="otp-actions">

                    <button type="button" class="resend-btn" disabled>
                        Kirim ulang
                    </button>

                    <button type="button" class="resend-btn"
                        onclick="location.href='<?= base_url('account/profile') ?>'">
                        Ganti nomor
                    </button>

                </div>

            </form>

            <?php endif; ?>

        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {

    /* =====================================================
       MODAL GANTI NOMOR
    ===================================================== */

    const modal = document.getElementById('phone-modal');
    const openButton = document.getElementById('change-phone-btn');
    const closeButton = document.getElementById('close-phone-modal');


    /*
     * DEBUG
     * Bisa dihapus nanti.
     */
    console.log('Profile Phone Modal:', {
        modal: modal,
        openButton: openButton,
        closeButton: closeButton
    });


    /* =====================================================
       BUKA MODAL
    ===================================================== */

    if (openButton && modal) {

        openButton.addEventListener('click', function(event) {

            event.preventDefault();
            event.stopPropagation();

            modal.classList.add('active');

        });

    }


    /* =====================================================
       TUTUP MODAL
    ===================================================== */

    if (closeButton && modal) {

        closeButton.addEventListener('click', function(event) {

            event.preventDefault();
            event.stopPropagation();

            modal.classList.remove('active');

        });

    }


    /* =====================================================
       KLIK AREA GELAP
    ===================================================== */

    if (modal) {

        modal.addEventListener('click', function(event) {

            if (event.target === modal) {

                modal.classList.remove('active');

            }

        });

    }


    /* =====================================================
       ESC UNTUK MENUTUP
    ===================================================== */

    document.addEventListener('keydown', function(event) {

        if (event.key === 'Escape' && modal) {

            modal.classList.remove('active');

        }

    });


    /* =====================================================
       AUTO OPEN SETELAH SEND OTP
    ===================================================== */

    <?php if (
        $phoneOtpSent ||
        !empty($phoneErrors) ||
        !empty($phoneOtpError)
    ): ?>

    if (modal) {

        modal.classList.add('active');

    }

    <?php endif; ?>


    /* =====================================================
       OTP INPUT
    ===================================================== */

    const otpInputs =
        document.querySelectorAll('.otp-input');

    const otpHidden =
        document.getElementById('otp-value');


    function updateOtp() {

        if (!otpHidden) {
            return;
        }

        let otp = '';

        otpInputs.forEach(function(input) {

            otp += input.value;

        });

        otpHidden.value = otp;

    }


    otpInputs.forEach(function(input, index) {


        /* INPUT */

        input.addEventListener('input', function() {

            this.value =
                this.value.replace(/\D/g, '');

            if (
                this.value &&
                otpInputs[index + 1]
            ) {

                otpInputs[index + 1].focus();

            }

            updateOtp();

        });


        /* BACKSPACE */

        input.addEventListener('keydown', function(event) {

            if (
                event.key === 'Backspace' &&
                !this.value &&
                otpInputs[index - 1]
            ) {

                otpInputs[index - 1].focus();

            }

        });


        /* PASTE OTP */

        input.addEventListener('paste', function(event) {

            event.preventDefault();

            const pasted =
                (event.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .substring(0, 6);


            pasted.split('').forEach(function(digit, i) {

                if (otpInputs[i]) {

                    otpInputs[i].value = digit;

                }

            });

            updateOtp();


            if (otpInputs[pasted.length - 1]) {

                otpInputs[pasted.length - 1].focus();

            }

        });

    });


    /* =====================================================
       FORM OTP
    ===================================================== */

    const otpForm =
        document.getElementById('otp-form');


    if (otpForm) {

        otpForm.addEventListener('submit', function() {

            updateOtp();

        });

    }


    /* =====================================================
       SAVE PROFILE
    ===================================================== */

    const profileForm =
        document.getElementById('profile-form');

    const saveButton =
        document.getElementById('save-profile-btn');


    if (
        profileForm &&
        saveButton
    ) {

        profileForm.addEventListener('submit', function() {

            saveButton.disabled = true;

            saveButton.textContent =
                'Menyimpan...';

        });

    }

});
</script>

<?= $this->endSection() ?>