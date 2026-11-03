<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<!-- 1. FILTER & SEARCH CARD -->
<div class="card-custom mb-4 p-3">
    <form action="<?= site_url('account/documents') ?>" method="get" class="row g-2 align-items-center">
        <div class="col-md-6 col-lg-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" 
                       placeholder="Cari berdasarkan nomor invoice atau dokumen..." 
                       value="<?= esc($search) ?>">
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-4">
            <select name="type" class="form-select">
                <option value="">Semua Jenis Dokumen</option>
                <option value="INVOICE" <?= $typeFilter === 'INVOICE' ? 'selected' : '' ?>>Invoice Rental Digital</option>
                <option value="RECEIPT" <?= $typeFilter === 'RECEIPT' ? 'selected' : '' ?>>Kwitansi Pembayaran Resmi</option>
                <option value="KONTRAK" <?= $typeFilter === 'KONTRAK' ? 'selected' : '' ?>>Kontrak Perjanjian Sewa</option>
                <option value="SURAT_JALAN" <?= $typeFilter === 'SURAT_JALAN' ? 'selected' : '' ?>>Surat Jalan / Serah Terima</option>
            </select>
        </div>
        <div class="col-sm-6 col-md-2 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
                Terapkan
            </button>
            <?php if (!empty($search) || !empty($typeFilter)): ?>
                <a href="<?= site_url('account/documents') ?>" class="btn btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 2. DOCUMENTS LIST -->
<div class="card-custom">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-bold">Arsip Dokumen Pelanggan</span>
        <span class="badge bg-light text-dark border"><?= count($documents) ?> Berkas</span>
    </div>
    <div class="table-responsive">
        <?php if (!empty($documents)): ?>
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>Jenis Dokumen</th>
                        <th>Nomor & Judul</th>
                        <th>No. Invoice Terkait</th>
                        <th>Tanggal Terbit</th>
                        <th>Nominal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td>
                                <?php if ($doc['type'] === 'INVOICE'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-file-earmark-text me-1"></i> Invoice
                                    </span>
                                <?php elseif ($doc['type'] === 'RECEIPT'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-file-earmark-check me-1"></i> Kwitansi
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> <?= esc($doc['type_label']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark font-monospace"><?= esc($doc['number']) ?></div>
                                <small class="text-muted"><?= esc($doc['title']) ?></small>
                            </td>
                            <td>
                                <a href="<?= site_url('account/bookings/' . $doc['invoice_no']) ?>" class="font-monospace text-decoration-none">
                                    <?= esc($doc['invoice_no']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="small"><?= format_datetime($doc['date'], false) ?></div>
                            </td>
                            <td>
                                <?php if ($doc['amount'] !== null): ?>
                                    <span class="fw-semibold text-dark"><?= rupiah($doc['amount']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="<?= esc($doc['print_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak / Buka PDF">
                                        <i class="bi bi-printer me-1"></i> Cetak / PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-file-earmark-x empty-state-icon"></i>
                <h5 class="fw-bold mb-1">Tidak Ada Dokumen Ditemukan</h5>
                <p class="text-muted small mb-0">Dokumen seperti invoice dan kwitansi akan muncul di sini setelah Anda memiliki riwayat transaksi aktif.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

