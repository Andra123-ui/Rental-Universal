<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$custName = trim((string)($customer['name'] ?? 'Pelanggan'));
$docStatus = $latestDocument['verification_status'] ?? 'UNVERIFIED';
?>

<!-- 1. GREETING & ACCOUNT OVERVIEW BANNER -->
<div class="card-custom mb-4 overflow-hidden border-0" style="background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 100%); color: #FFFFFF;">
    <div class="card-body p-4 p-md-5 position-relative">
        <div class="row align-items-center position-relative z-1">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-white text-primary px-3 py-1 fw-semibold">
                        <i class="bi bi-person-check-fill me-1"></i> Customer Terdaftar
                    </span>
                    <span class="badge bg-white bg-opacity-25 text-white px-3 py-1">
                        ID: <?= esc($customer['customer_code'] ?? ('CUST-' . str_pad($customer['id'], 4, '0', STR_PAD_LEFT))) ?>
                    </span>
                </div>
                <h1 class="h2 fw-bold mb-2 text-white">Selamat Datang, <?= esc($custName) ?>!</h1>
                <p class="text-white-50 mb-0 fs-6" style="max-width: 600px;">
                    Kelola seluruh pesanan rental, lacak pembayaran, unduh invoice resmi, dan pantau status serah-terima unit dengan aman dalam satu portal.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="<?= site_url('catalog') ?>" class="btn btn-light text-primary px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <span>Buat Booking Baru</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 2. STATS OVERVIEW CARDS -->
<div class="row g-3 mb-4">
    <!-- Active Bookings -->
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom h-100 p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Booking Aktif</span>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="bi bi-calendar2-range"></i>
                </div>
            </div>
            <div class="h3 fw-bold mb-1"><?= (int) $activeCount ?></div>
            <div class="small text-muted">
                <?php if ($activeCount > 0): ?>
                    <span class="text-primary fw-medium"><i class="bi bi-info-circle me-1"></i>Pesanan berjalan</span>
                <?php else: ?>
                    <span>Tidak ada booking berjalan</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Outstanding Payments -->
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom h-100 p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Tagihan Tertunda</span>
                <div class="stat-icon-wrapper <?= $unpaidCount > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                    <i class="bi bi-credit-card-2-front"></i>
                </div>
            </div>
            <div class="h3 fw-bold mb-1 <?= $unpaidCount > 0 ? 'text-danger' : 'text-success' ?>">
                <?= rupiah($totalBalanceDue) ?>
            </div>
            <div class="small text-muted">
                <?php if ($unpaidCount > 0): ?>
                    <span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i><?= $unpaidCount ?> invoice perlu dibayar</span>
                <?php else: ?>
                    <span class="text-success"><i class="bi bi-check2-circle me-1"></i>Semua pembayaran lunas</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Verification Status -->
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom h-100 p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Verifikasi Identitas</span>
                <div class="stat-icon-wrapper bg-info-subtle text-info">
                    <i class="bi bi-person-badge"></i>
                </div>
            </div>
            <div class="h5 fw-bold mb-1">
                <?= verification_status_badge($docStatus) ?>
            </div>
            <div class="small text-muted mt-2">
                <a href="<?= site_url('account/identity-documents') ?>" class="text-decoration-none">
                    <?= $docStatus === 'VERIFIED' ? 'Lihat dokumen identitas' : 'Lengkapi dokumen identitas' ?> &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Account Safety -->
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom h-100 p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small fw-semibold">Keamanan Akun</span>
                <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                    <i class="bi bi-shield-lock"></i>
                </div>
            </div>
            <div class="h5 fw-bold mb-1 text-dark">
                <?= esc($customerAccount['status'] ?? 'ACTIVE') ?>
            </div>
            <div class="small text-muted mt-2">
                <a href="<?= site_url('account/security') ?>" class="text-decoration-none">
                    Kelola nomor WhatsApp &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 3. PAYMENT ATTENTION ALERT (IF BALANCE DUE > 0) -->
<?php if ($unpaidCount > 0): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: #FFFBEB; border-left: 5px solid #F59E0B !important;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="bg-warning text-dark p-2 rounded-3 fs-4 d-inline-flex">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Perhatian: Ada Pembayaran Tertunda</h5>
                        <p class="text-muted mb-0 small">
                            Anda memiliki <strong><?= $unpaidCount ?> pesanan</strong> dengan sisa tagihan sebesar <strong><?= rupiah($totalBalanceDue) ?></strong>. Harap segera selesaikan sebelum batas waktu agar proses booking tidak dibatalkan otomatis.
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= site_url('account/payments') ?>" class="btn btn-warning text-dark fw-bold px-3 py-2 text-nowrap shadow-sm">
                        <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 4. SCHEDULE & QUICK ACTIONS -->
<div class="row g-4 mb-4">
    <!-- Upcoming Schedules -->
    <div class="col-lg-6">
        <div class="card-custom h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span>Jadwal Operasional Terdekat</span>
                </span>
                <span class="badge bg-light text-muted border"><?= count($upcomingSchedules) ?> Jadwal</span>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($upcomingSchedules)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingSchedules as $schedule): ?>
                            <div class="list-group-item px-0 py-3 border-bottom d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-light p-2 rounded-3 text-primary fs-5">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-1"><?= esc($schedule['type']) ?></div>
                                        <div class="small text-muted mb-1">
                                            <i class="bi bi-geo-alt me-1"></i><?= esc($schedule['location']) ?>
                                        </div>
                                        <div class="small fw-semibold text-primary">
                                            <i class="bi bi-alarm me-1"></i><?= format_datetime($schedule['date']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?= site_url('account/bookings/' . $schedule['invoice_no']) ?>" class="btn btn-sm btn-outline-primary text-nowrap">
                                        Detail
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-calendar-x empty-state-icon fs-2"></i>
                        <h6 class="fw-bold mb-1">Tidak Ada Jadwal Mendatang</h6>
                        <p class="text-muted small mb-0">Belum ada serah-terima atau pengembalian unit dalam waktu dekat.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Shortcuts -->
    <div class="col-lg-6">
        <div class="card-custom h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-lightning-charge text-primary"></i>
                <span>Akses Cepat Portal</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="<?= site_url('account/bookings') ?>" class="p-3 border rounded-3 d-block text-decoration-none bg-light-subtle h-100 text-dark hover-shadow">
                            <i class="bi bi-receipt fs-4 text-primary d-block mb-1"></i>
                            <div class="fw-bold small">Riwayat Booking</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Semua riwayat sewa Anda</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= site_url('account/payments') ?>" class="p-3 border rounded-3 d-block text-decoration-none bg-light-subtle h-100 text-dark hover-shadow">
                            <i class="bi bi-wallet2 fs-4 text-success d-block mb-1"></i>
                            <div class="fw-bold small">Pembayaran</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Riwayat & tagihan sewa</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= site_url('account/documents') ?>" class="p-3 border rounded-3 d-block text-decoration-none bg-light-subtle h-100 text-dark hover-shadow">
                            <i class="bi bi-file-earmark-pdf fs-4 text-danger d-block mb-1"></i>
                            <div class="fw-bold small">Invoice & Kwitansi</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Unduh dokumen resmi</div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= site_url('account/profile') ?>" class="p-3 border rounded-3 d-block text-decoration-none bg-light-subtle h-100 text-dark hover-shadow">
                            <i class="bi bi-person-gear fs-4 text-info d-block mb-1"></i>
                            <div class="fw-bold small">Profil & Kontak</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Perbarui data diri Anda</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. RECENT BOOKING TRANSACTIONS -->
<div class="card-custom">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i>
            <span>Transaksi Booking Terbaru</span>
        </span>
        <a href="<?= site_url('account/bookings') ?>" class="btn btn-sm btn-outline-primary">
            Lihat Semua Riwayat &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <?php if (!empty($recentBookings)): ?>
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Item Sewa</th>
                        <th>Periode Sewa</th>
                        <th>Total Biaya</th>
                        <th>Status Booking</th>
                        <th>Status Bayar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark font-monospace"><?= esc($b['invoice_no']) ?></div>
                                <small class="text-muted"><?= format_date_short($b['created_at']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($b['items'])): ?>
                                    <div class="fw-medium text-dark"><?= esc($b['items'][0]['item_name_snapshot']) ?></div>
                                    <?php if (count($b['items']) > 1): ?>
                                        <small class="text-muted">+<?= count($b['items']) - 1 ?> item lainnya</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-medium">
                                    <?= format_date_short($b['start_at']) ?> - <?= format_date_short($b['end_at']) ?>
                                </div>
                                <small class="text-muted text-uppercase"><?= esc($b['fulfillment_method'] ?? 'SELF_PICKUP') ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= rupiah($b['grand_total']) ?></div>
                                <?php if ((float)$b['balance_due'] > 0): ?>
                                    <small class="text-danger">Sisa: <?= rupiah($b['balance_due']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= booking_status_badge($b['status']) ?>
                            </td>
                            <td>
                                <?= payment_status_badge($b['payment_status']) ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('account/bookings/' . $b['invoice_no']) ?>" class="btn btn-sm btn-light border px-2 py-1" title="Lihat Detail Transaksi">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x empty-state-icon"></i>
                <h5 class="fw-bold mb-1">Belum Ada Transaksi Rental</h5>
                <p class="text-muted small mb-3">Anda belum pernah melakukan pemesanan rental barang di Rental Universal.</p>
                <a href="<?= site_url('catalog') ?>" class="btn btn-primary btn-sm px-3 py-2">
                    <i class="bi bi-grid me-1"></i> Telusuri Katalog Rental
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

