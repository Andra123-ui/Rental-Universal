<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<!-- 1. SUMMARY STATS -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card-custom p-3 d-flex flex-row align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold">Total Tagihan Tertunda</span>
                <div class="h3 fw-bold mb-0 <?= $totalOutstanding > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= rupiah($totalOutstanding) ?>
                </div>
                <small class="text-muted"><?= count($outstanding) ?> pesanan membutuhkan pembayaran</small>
            </div>
            <div class="stat-icon-wrapper <?= $totalOutstanding > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-custom p-3 d-flex flex-row align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-semibold">Total Transaksi Selesai</span>
                <div class="h3 fw-bold mb-0 text-primary">
                    <?= count($history) ?> Transaksi
                </div>
                <small class="text-muted">Kwitansi pembayaran dapat dicetak sewaktu-waktu</small>
            </div>
            <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                <i class="bi bi-shield-check"></i>
            </div>
        </div>
    </div>
</div>

<!-- 2. NAVIGATION TABS (OUTSTANDING VS RIWAYAT) -->
<ul class="nav nav-tabs mb-4 border-bottom" id="paymentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= ($tab ?? 'outstanding') === 'outstanding' ? 'active fw-bold' : '' ?>" 
                id="outstanding-tab" data-bs-toggle="tab" data-bs-target="#outstanding-pane" type="button" role="tab">
            <i class="bi bi-exclamation-circle me-1 text-danger"></i> Tagihan Tertunda 
            <span class="badge bg-danger ms-1"><?= count($outstanding) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= ($tab ?? 'outstanding') === 'history' ? 'active fw-bold' : '' ?>" 
                id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab">
            <i class="bi bi-clock-history me-1 text-primary"></i> Riwayat Pembayaran 
            <span class="badge bg-secondary ms-1"><?= count($history) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="paymentTabsContent">
    <!-- TAB 1: OUTSTANDING BOOKINGS -->
    <div class="tab-pane fade <?= ($tab ?? 'outstanding') === 'outstanding' ? 'show active' : '' ?>" id="outstanding-pane" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-custom">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span class="fw-bold">Daftar Tagihan Belum Lunas</span>
                        <span class="badge bg-light text-dark border"><?= count($outstanding) ?> Booking</span>
                    </div>
                    <div class="table-responsive">
                        <?php if (!empty($outstanding)): ?>
                            <table class="table table-custom align-middle">
                                <thead>
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Periode Rental</th>
                                        <th>Total Biaya</th>
                                        <th>Sisa Tagihan</th>
                                        <th>Batas Waktu</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outstanding as $b): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= site_url('account/bookings/' . $b['invoice_no']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                                    <?= esc($b['invoice_no']) ?>
                                                </a>
                                                <div class="text-muted small"><?= format_date_short($b['created_at']) ?></div>
                                            </td>
                                            <td>
                                                <div class="small fw-medium"><?= format_date_short($b['start_at']) ?> - <?= format_date_short($b['end_at']) ?></div>
                                            </td>
                                            <td><?= rupiah($b['grand_total']) ?></td>
                                            <td>
                                                <span class="fw-bold text-danger"><?= rupiah($b['balance_due']) ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($b['expires_at'])): ?>
                                                    <div class="small text-danger fw-semibold">
                                                        <i class="bi bi-alarm me-1"></i><?= format_datetime($b['expires_at']) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= site_url('track/' . $b['invoice_no'] . '/payment') ?>" class="btn btn-sm btn-warning text-dark fw-bold text-nowrap">
                                                    <i class="bi bi-credit-card me-1"></i> Bayar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle empty-state-icon text-success"></i>
                                <h5 class="fw-bold mb-1">Semua Pembayaran Lunas</h5>
                                <p class="text-muted small mb-0">Hebat! Anda tidak memiliki tagihan rental yang tertunda saat ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Pembayaran / Bank -->
            <div class="col-lg-4">
                <div class="card-custom p-3 bg-light-subtle">
                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-bank text-primary"></i> Rekening Pembayaran Resmi
                    </h6>
                    <div class="p-3 bg-white rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-primary">Bank BCA</span>
                            <small class="text-muted">Transfer Otomatis</small>
                        </div>
                        <div class="font-monospace fs-5 fw-bold text-dark my-1">8920 1829 3810</div>
                        <div class="small text-muted">a.n. <strong>PT Rental Universal Indonesia</strong></div>
                    </div>

                    <div class="p-3 bg-white rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-success">Bank Mandiri</span>
                            <small class="text-muted">Transfer Manual</small>
                        </div>
                        <div class="font-monospace fs-5 fw-bold text-dark my-1">1370 0982 7162 1</div>
                        <div class="small text-muted">a.n. <strong>PT Rental Universal Indonesia</strong></div>
                    </div>

                    <div class="small text-muted">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle me-1"></i>Catatan Penting:</div>
                        <ul class="ps-3 mb-0" style="font-size: 0.8rem;">
                            <li>Gunakan nomor invoice sebagai berita transfer saat membayar.</li>
                            <li>Upload bukti transfer setelah melakukan pembayaran manual agar segera diverifikasi oleh tim kami.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: PAYMENT HISTORY -->
    <div class="tab-pane fade <?= ($tab ?? 'outstanding') === 'history' ? 'show active' : '' ?>" id="history-pane" role="tabpanel">
        <div class="card-custom">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold">Riwayat Pembayaran & Kwitansi</span>
                <span class="badge bg-light text-dark border"><?= count($history) ?> Transaksi</span>
            </div>
            <div class="table-responsive">
                <?php if (!empty($history)): ?>
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>No. Pembayaran</th>
                                <th>No. Invoice</th>
                                <th>Metode & Provider</th>
                                <th>Nominal</th>
                                <th>Tanggal Bayar</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold font-monospace text-dark"><?= esc($p['payment_no']) ?></div>
                                        <small class="text-muted text-uppercase"><?= esc($p['payment_type']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('account/bookings/' . $p['invoice_no']) ?>" class="fw-medium text-decoration-none">
                                            <?= esc($p['invoice_no']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= esc($p['method']) ?></div>
                                        <small class="text-muted"><?= esc($p['provider'] ?: 'Sistem') ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= rupiah($p['amount']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small"><?= format_datetime($p['paid_at'] ?? $p['created_at'], true) ?></div>
                                    </td>
                                    <td>
                                        <?= payment_status_badge($p['status']) ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <?php if ($p['status'] === 'PAID'): ?>
                                                <a href="<?= site_url('account/payments/receipt/' . $p['payment_no']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Kwitansi">
                                                    <i class="bi bi-file-earmark-check"></i> Kwitansi
                                                </a>
                                            <?php elseif ($p['status'] === 'PENDING' && empty($p['proof_path'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning text-dark" data-bs-toggle="modal" data-bs-target="#modalProof<?= $p['id'] ?>">
                                                    <i class="bi bi-upload"></i> Upload Bukti
                                                </button>
                                            <?php elseif (!empty($p['proof_path'])): ?>
                                                <span class="badge bg-light text-muted border">Bukti Terunggah</span>
                                            <?php endif; ?>
                                            
                                            <a href="<?= site_url('account/bookings/' . $p['invoice_no']) ?>" class="btn btn-sm btn-light border" title="Lihat Booking">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>

                                        <!-- Modal Upload Bukti Transfer -->
                                        <?php if ($p['status'] === 'PENDING' && empty($p['proof_path'])): ?>
                                            <div class="modal fade text-start" id="modalProof<?= $p['id'] ?>" tabindex="-1" aria-labelledby="modalProofLabel<?= $p['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="<?= site_url('account/payments/upload-proof/' . $p['payment_no']) ?>" method="post" enctype="multipart/form-data">
                                                            <?= csrf_field() ?>
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold" id="modalProofLabel<?= $p['id'] ?>">Upload Bukti Transfer</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">No. Pembayaran:</label>
                                                                    <div class="fw-bold font-monospace"><?= esc($p['payment_no']) ?></div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">Nominal Transfer:</label>
                                                                    <div class="fw-bold text-primary fs-5"><?= rupiah($p['amount']) ?></div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="proof_file_<?= $p['id'] ?>" class="form-label fw-semibold">Pilih File Bukti Pembayaran <span class="text-danger">*</span></label>
                                                                    <input type="file" name="proof_file" id="proof_file_<?= $p['id'] ?>" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                                                    <small class="text-muted d-block mt-1">Format: JPG, PNG, atau PDF. Ukuran maks: 4 MB.</small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-primary btn-sm">Unggah Bukti</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-clock-history empty-state-icon"></i>
                        <h5 class="fw-bold mb-1">Belum Ada Riwayat Pembayaran</h5>
                        <p class="text-muted small mb-0">Kuitansi dan riwayat bayar akan tercatat otomatis saat Anda menyelesaikan transaksi.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

