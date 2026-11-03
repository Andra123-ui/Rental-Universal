<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$custName = trim((string)($customer['name'] ?? ''));
$custPhone = $customerAccount['login_phone'] ?? ($customer['phone'] ?? '-');
$custEmail = $customer['email'] ?? '';
$custAddress = $customer['address'] ?? '';
$emergencyName = $customer['emergency_contact_name'] ?? '';
$emergencyPhone = $customer['emergency_contact_phone'] ?? '';
$notes = $customer['notes'] ?? '';
$photoUrl = !empty($customer['profile_photo']) ? base_url('account/profile/photo') : null;
$docStatus = $latestDocument['verification_status'] ?? 'UNVERIFIED';
?>

<div class="row g-4">
    <!-- Left Column: Profile Form -->
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold">Informasi Data Pribadi</span>
                <span class="badge bg-light text-primary border">Profil Pelanggan</span>
            </div>
            <div class="card-body p-4">
                <form action="<?= site_url('account/profile/update') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- Foto Profil -->
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="position-relative">
                            <?php if ($photoUrl): ?>
                                <img src="<?= $photoUrl ?>" alt="<?= esc($custName) ?>" class="rounded-circle border" style="width: 72px; height: 72px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-3 border" style="width: 72px; height: 72px;">
                                    <?= esc(strtoupper(mb_substr($custName ?: 'U', 0, 1))) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="profile_photo" class="form-label fw-semibold small mb-1">Ganti Foto Profil</label>
                            <input type="file" name="profile_photo" id="profile_photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Format: JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                        </div>
                    </div>

                    <!-- Nomor WhatsApp Login (READ-ONLY) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Nomor WhatsApp Terverifikasi (Kredensial Login)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-success"><i class="bi bi-whatsapp"></i></span>
                            <input type="text" class="form-control bg-light" value="<?= esc($custPhone) ?>" readonly>
                            <span class="input-group-text bg-success-subtle text-success small">
                                <i class="bi bi-check-circle-fill me-1"></i> Terverifikasi
                            </span>
                            <a href="<?= site_url('account/security') ?>" class="btn btn-outline-primary">
                                Ubah Nomor
                            </a>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Nomor telepon login tidak dapat diedit langsung demi keamanan. Gunakan menu Keamanan Akun untuk verifikasi OTP nomor baru.
                        </small>
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                               value="<?= old('name', $custName) ?>" required minlength="3" maxlength="150" placeholder="Masukkan nama lengkap Anda">
                        <?php if (session('errors.name')): ?>
                            <div class="invalid-feedback"><?= session('errors.name') ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Alamat Email</label>
                        <input type="email" name="email" id="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" 
                               value="<?= old('email', $custEmail) ?>" maxlength="150" placeholder="nama@email.com">
                        <small class="text-muted" style="font-size: 0.75rem;">Digunakan untuk pengiriman invoice dan bukti transaksi digital.</small>
                        <?php if (session('errors.email')): ?>
                            <div class="invalid-feedback"><?= session('errors.email') ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Alamat Utama -->
                    <div class="mb-4">
                        <label for="address" class="form-label fw-semibold small">Alamat Lengkap Domisili</label>
                        <textarea name="address" id="address" rows="3" class="form-control <?= session('errors.address') ? 'is-invalid' : '' ?>" 
                                  maxlength="500" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos"><?= old('address', $custAddress) ?></textarea>
                        <?php if (session('errors.address')): ?>
                            <div class="invalid-feedback"><?= session('errors.address') ?></div>
                        <?php endif; ?>
                    </div>

                    <h6 class="fw-bold mb-3 pt-3 border-top d-flex align-items-center gap-2">
                        <i class="bi bi-telephone-plus text-primary"></i> Kontak Darurat (Opsional)
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="emergency_contact_name" class="form-label fw-semibold small">Nama Kontak Darurat</label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control <?= session('errors.emergency_contact_name') ? 'is-invalid' : '' ?>" 
                                   value="<?= old('emergency_contact_name', $emergencyName) ?>" placeholder="Contoh: Budi Santoso (Saudara)">
                            <?php if (session('errors.emergency_contact_name')): ?>
                                <div class="invalid-feedback"><?= session('errors.emergency_contact_name') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="emergency_contact_phone" class="form-label fw-semibold small">Nomor HP Kontak Darurat</label>
                            <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control <?= session('errors.emergency_contact_phone') ? 'is-invalid' : '' ?>" 
                                   value="<?= old('emergency_contact_phone', $emergencyPhone) ?>" placeholder="08123456789">
                            <?php if (session('errors.emergency_contact_phone')): ?>
                                <div class="invalid-feedback"><?= session('errors.emergency_contact_phone') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label fw-semibold small">Catatan Tambahan</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control" maxlength="500" placeholder="Keterangan khusus profil bila ada"><?= old('notes', $notes) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Verification & Security Overview -->
    <div class="col-lg-4">
        <!-- Verification Card -->
        <div class="card-custom mb-4 p-4">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-patch-check text-primary"></i> Status Identitas
            </h6>
            <div class="p-3 bg-light rounded-3 border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Status:</span>
                    <?= verification_status_badge($docStatus) ?>
                </div>
                <div class="small">
                    <span class="text-muted">Dokumen:</span> 
                    <strong><?= esc($latestDocument['document_type'] ?? 'Belum diunggah') ?></strong>
                </div>
                <?php if (!empty($latestDocument['document_number'])): ?>
                    <div class="small mt-1">
                        <span class="text-muted">Nomor ID:</span> 
                        <span class="font-monospace fw-semibold"><?= esc(mask_text($latestDocument['document_number'], 4, 4)) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                Dokumen identitas (KTP/SIM/Paspor) diperlukan saat menyewa peralatan bernilai tinggi atau kendaraan bermotor.
            </p>

            <a href="<?= site_url('account/identity-documents') ?>" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-card-heading me-1"></i> Kelola Dokumen Identitas &rarr;
            </a>
        </div>

        <!-- Security Quick Link -->
        <div class="card-custom p-4 bg-light-subtle">
            <h6 class="fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock text-primary"></i> Keamanan Akun
            </h6>
            <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                Kelola status login, riwayat aktivitas perangkat, dan ubah nomor WhatsApp login dengan verifikasi OTP yang aman.
            </p>
            <a href="<?= site_url('account/security') ?>" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-gear me-1"></i> Pengaturan Keamanan
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

