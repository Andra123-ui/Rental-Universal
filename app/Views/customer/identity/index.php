<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<div class="row g-4">
    <!-- Left Column: Upload Form & Existing Documents -->
    <div class="col-lg-8">
        <!-- 1. Existing Documents List -->
        <div class="card-custom mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold">Dokumen Identitas Tersimpan</span>
                <span class="badge bg-light text-dark border"><?= count($documents) ?> Berkas</span>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($documents)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($documents as $doc): ?>
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary fs-6 px-3 py-1"><?= esc($doc['document_type']) ?></span>
                                        <span class="font-monospace fw-bold fs-6 text-dark"><?= esc(mask_text($doc['document_number'], 4, 4)) ?></span>
                                    </div>
                                    <div>
                                        <?= verification_status_badge($doc['verification_status']) ?>
                                    </div>
                                </div>

                                <div class="row g-2 small text-muted mb-2">
                                    <div class="col-sm-6">
                                        <i class="bi bi-calendar-event me-1"></i> Masa Berlaku: 
                                        <strong><?= !empty($doc['expires_at']) ? format_date_short($doc['expires_at']) : 'Seumur Hidup / Tidak Ada' ?></strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <i class="bi bi-clock me-1"></i> Diunggah: 
                                        <span><?= format_datetime($doc['created_at']) ?></span>
                                    </div>
                                </div>

                                <?php if ($doc['verification_status'] === 'REJECTED' && !empty($doc['reject_reason'])): ?>
                                    <div class="alert alert-danger p-2 small mb-2 d-flex align-items-center gap-2">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <div><strong>Alasan Penolakan:</strong> <?= esc($doc['reject_reason']) ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($doc['notes'])): ?>
                                    <div class="small text-muted mb-2 fst-italic">
                                        Catatan: <?= esc($doc['notes']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center gap-2 mt-2 pt-2 border-top">
                                    <a href="<?= site_url('account/identity-documents/file/' . $doc['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-eye"></i>
                                        <span>Lihat File</span>
                                    </a>

                                    <form action="<?= site_url('account/identity-documents/delete/' . $doc['id']) ?>" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-trash"></i>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-card-checklist empty-state-icon text-muted"></i>
                        <h6 class="fw-bold mb-1">Belum Ada Dokumen Identitas</h6>
                        <p class="text-muted small mb-0">Silakan unggah foto KTP, SIM, atau Paspor menggunakan formulir di bawah ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Form Upload / Ganti Dokumen -->
        <div class="card-custom">
            <div class="card-header fw-bold">
                <i class="bi bi-cloud-arrow-up text-primary me-1"></i> Unggah / Perbarui Dokumen Identitas
            </div>
            <div class="card-body p-4">
                <form action="<?= site_url('account/identity-documents/upload') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="document_type" class="form-label fw-semibold small">Jenis Dokumen <span class="text-danger">*</span></label>
                            <select name="document_type" id="document_type" class="form-select <?= session('errors.document_type') ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Pilih Jenis Dokumen --</option>
                                <?php foreach ($docTypes as $key => $lbl): ?>
                                    <option value="<?= esc($key) ?>" <?= old('document_type') === $key ? 'selected' : '' ?>>
                                        <?= esc($lbl) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (session('errors.document_type')): ?>
                                <div class="invalid-feedback"><?= session('errors.document_type') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="document_number" class="form-label fw-semibold small">Nomor Identitas <span class="text-danger">*</span></label>
                            <input type="text" name="document_number" id="document_number" class="form-control <?= session('errors.document_number') ? 'is-invalid' : '' ?>" 
                                   value="<?= old('document_number') ?>" required minlength="5" maxlength="100" placeholder="Nomor NIK KTP / No. SIM / Paspor">
                            <?php if (session('errors.document_number')): ?>
                                <div class="invalid-feedback"><?= session('errors.document_number') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="expires_at" class="form-label fw-semibold small">Masa Berlaku (Kosongkan jika seumur hidup)</label>
                            <input type="date" name="expires_at" id="expires_at" class="form-control" value="<?= old('expires_at') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="doc_file" class="form-label fw-semibold small">File Dokumen (Foto / PDF) <span class="text-danger">*</span></label>
                            <input type="file" name="doc_file" id="doc_file" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Format: JPG, PNG, atau PDF. Maksimal 4 MB.</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="document_notes" class="form-label fw-semibold small">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="document_notes" id="document_notes" class="form-control" value="<?= old('document_notes') ?>" maxlength="255" placeholder="Contoh: KTP domisili Jakarta Selatan">
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                            <i class="bi bi-upload me-1"></i> Unggah Dokumen untuk Verifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Security Notice & Help -->
    <div class="col-lg-4">
        <div class="card-custom p-4 bg-light-subtle mb-4">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-primary"></i> Keamanan Dokumen Anda
            </h6>
            <p class="text-muted small mb-2">
                Kami sangat menjaga privasi dan kerahasiaan identitas Anda:
            </p>
            <ul class="ps-3 small text-muted mb-3" style="font-size: 0.825rem;">
                <li class="mb-1">Dokumen disimpan dalam penyimpanan privat server dan tidak dapat diakses publik.</li>
                <li class="mb-1">Nomor identitas ditampilkan secara tersamarkan (masked) di layar akun.</li>
                <li class="mb-1">Hanya staf verifikasi resmi yang memiliki hak akses untuk memeriksa keaslian identitas saat validasi sewa.</li>
                <li>Dokumen hanya digunakan untuk pemenuhan syarat operasional serah-terima unit rental.</li>
            </ul>
        </div>

        <div class="card-custom p-4">
            <h6 class="fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-question-circle text-primary"></i> Butuh Bantuan?
            </h6>
            <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                Jika verifikasi identitas Anda mengalami kendala atau membutuhkan percepatan sebelum jadwal rental, silakan hubungi tim kami.
            </p>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $business['phone'] ?? '') ?>?text=Halo%20Rental%20Universal,%20saya%20ingin%20menanyakan%20status%20verifikasi%20identitas%20saya." target="_blank" rel="noopener" class="btn btn-outline-success btn-sm w-100">
                <i class="bi bi-whatsapp me-1"></i> Hubungi Tim Verifikasi
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

