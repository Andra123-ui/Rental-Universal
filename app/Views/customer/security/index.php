<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$loginPhone = $account['login_phone'] ?? '-';
$status = $account['status'] ?? 'ACTIVE';
$verifiedAt = $account['phone_verified_at'] ?? null;
$lastLoginAt = $account['last_login_at'] ?? null;
$lastLoginIp = $account['last_login_ip'] ?? '-';
?>

<div class="row g-4">
    <!-- Left Column: Status, Change Phone, Logout -->
    <div class="col-lg-7">
        <!-- 1. Account Credentials & Status Card -->
        <div class="card-custom mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold">Status Kredensial Akun</span>
                <span class="badge <?= $status === 'ACTIVE' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger' ?> px-2 py-1">
                    <?= $status === 'ACTIVE' ? 'Akun Aktif' : 'Status: ' . esc($status) ?>
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small d-block">Nomor WhatsApp Login:</label>
                        <div class="fw-bold fs-5 text-dark font-monospace"><?= esc($loginPhone) ?></div>
                        <small class="text-success fw-medium">
                            <i class="bi bi-shield-check me-1"></i> Terverifikasi sejak <?= format_date_short($verifiedAt, false) ?>
                        </small>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small d-block">Login Terakhir:</label>
                        <div class="fw-bold text-dark small"><?= format_datetime($lastLoginAt) ?></div>
                        <small class="text-muted">Dari IP: <?= esc($lastLoginIp) ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Change Phone Flow (Step 1 or Step 2) -->
        <div class="card-custom mb-4">
            <div class="card-header fw-bold">
                <i class="bi bi-phone text-primary me-1"></i> Ubah Nomor WhatsApp Login
            </div>
            <div class="card-body p-4">
                <?php if (!empty($pendingPhone)): ?>
                    <!-- STEP 2: Input OTP Kode -->
                    <div class="alert alert-primary border-0 shadow-sm mb-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-chat-dots-fill fs-5"></i>
                            <strong>Verifikasi Nomor Baru</strong>
                        </div>
                        <p class="mb-0 small">
                            Kode OTP 6-digit telah dikirimkan ke nomor <strong><?= esc($pendingPhone) ?></strong>. Silakan periksa pesan WhatsApp Anda dan masukkan kodenya di bawah ini.
                        </p>
                    </div>

                    <form action="<?= site_url('account/security/phone/verify-otp') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="otp_code" class="form-label fw-semibold small">Kode OTP 6 Digit <span class="text-danger">*</span></label>
                            <input type="text" name="otp_code" id="otp_code" class="form-control form-control-lg font-monospace text-center fs-3 letter-spacing-2" 
                                   placeholder="------" maxlength="6" pattern="[0-9]{4,6}" required autofocus>
                            <small class="text-muted d-block text-center mt-2">Berlaku selama 5 menit. Jangan berikan kode ini kepada siapa pun.</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <button type="submit" formaction="<?= site_url('account/security/phone/cancel-otp') ?>" class="btn btn-outline-secondary btn-sm">
                                Batalkan
                            </button>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                Konfirmasi & Simpan Nomor
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- STEP 1: Input Nomor Baru -->
                    <p class="text-muted small mb-3">
                        Perubahan nomor telepon akan diverifikasi melalui pesan OTP ke nomor baru Anda. Nomor lama tidak akan dapat digunakan lagi untuk masuk setelah perubahan berhasil.
                    </p>

                    <form action="<?= site_url('account/security/phone/request-otp') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="new_phone" class="form-label fw-semibold small">Nomor WhatsApp Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-success"><i class="bi bi-whatsapp"></i></span>
                                <input type="text" name="new_phone" id="new_phone" class="form-control" 
                                       placeholder="Contoh: 08123456789 atau +628123456789" required minlength="8" maxlength="25">
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Pastikan nomor aktif dan dapat menerima pesan WhatsApp.</small>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-send me-1"></i> Kirim Kode OTP Verifikasi
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Keluar dari Akun (Logout) -->
        <div class="card-custom p-4 border-danger-subtle">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                <div>
                    <h6 class="fw-bold text-danger mb-1"><i class="bi bi-box-arrow-right me-1"></i> Keluar dari Sesi</h6>
                    <p class="text-muted small mb-0">Akhiri sesi login Anda di perangkat ini secara aman.</p>
                </div>
                <form action="<?= site_url('account/logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm text-nowrap px-3 py-2 fw-semibold">
                        <i class="bi bi-power me-1"></i> Keluar Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Security Logs & Support -->
    <div class="col-lg-5">
        <!-- Bantuan Pemulihan Akun -->
        <div class="card-custom p-4 bg-light-subtle mb-4">
            <h6 class="fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-life-preserver text-primary"></i> Bantuan Pemulihan Akun
            </h6>
            <p class="text-muted small mb-2" style="font-size: 0.825rem;">
                Jika nomor WhatsApp Anda hilang, tidak aktif, atau dicuri, hubungi tim dukungan resmi Rental Universal dengan menyertakan bukti identitas yang terdaftar.
            </p>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $business['phone'] ?? '') ?>?text=Halo%20Rental%20Universal,%20saya%20membutuhkan%20bantuan%20pemulihan%20akun%20rental%20saya." target="_blank" rel="noopener" class="btn btn-outline-success btn-sm w-100">
                <i class="bi bi-whatsapp me-1"></i> Hubungi Customer Support
            </a>
        </div>

        <!-- Riwayat Aktivitas Keamanan Akun -->
        <div class="card-custom">
            <div class="card-header fw-bold small text-muted text-uppercase">
                <i class="bi bi-shield-check me-1 text-primary"></i> Riwayat Aktivitas Akun
            </div>
            <div class="card-body p-0">
                <?php if (!empty($logs)): ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($logs as $log): ?>
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-semibold text-dark"><?= esc($log['description'] ?? $log['event_type']) ?></div>
                                    <small class="text-muted"><?= format_date_short($log['created_at'], true) ?></small>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    IP: <?= esc($log['ip_address'] ?: '-') ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small text-center p-3 mb-0">Belum ada catatan aktivitas keamanan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

