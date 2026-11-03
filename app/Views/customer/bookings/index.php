<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<!-- 1. STATUS TABS -->
<div class="card-custom mb-4 p-2 bg-white">
    <div class="nav nav-pills flex-nowrap overflow-x-auto text-nowrap gap-1" id="status-tabs" role="tablist">
        <a class="nav-link <?= empty($bookingStatus) || $bookingStatus === 'ALL' ? 'active' : '' ?> px-3 py-2 small" 
           href="<?= site_url('account/bookings') ?>">
            Semua <span class="badge <?= empty($bookingStatus) || $bookingStatus === 'ALL' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= $counts['ALL'] ?></span>
        </a>
        <a class="nav-link <?= $bookingStatus === 'PENDING' ? 'active' : '' ?> px-3 py-2 small" 
           href="<?= site_url('account/bookings?status=PENDING') ?>">
            Menunggu <span class="badge <?= $bookingStatus === 'PENDING' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= $counts['PENDING'] ?></span>
        </a>
        <a class="nav-link <?= $bookingStatus === 'CONFIRMED' ? 'active' : '' ?> px-3 py-2 small" 
           href="<?= site_url('account/bookings?status=CONFIRMED') ?>">
            Dikonfirmasi <span class="badge <?= $bookingStatus === 'CONFIRMED' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= $counts['CONFIRMED'] ?></span>
        </a>
        <a class="nav-link <?= $bookingStatus === 'ONGOING' ? 'active' : '' ?> px-3 py-2 small" 
           href="<?= site_url('account/bookings?status=ONGOING') ?>">
            Sedang Berjalan <span class="badge <?= $bookingStatus === 'ONGOING' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= $counts['ONGOING'] ?></span>
        </a>
        <a class="nav-link <?= $bookingStatus === 'COMPLETED' ? 'active' : '' ?> px-3 py-2 small" 
           href="<?= site_url('account/bookings?status=COMPLETED') ?>">
            Selesai <span class="badge <?= $bookingStatus === 'COMPLETED' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= $counts['COMPLETED'] ?></span>
        </a>
        <a class="nav-link <?= $bookingStatus === 'CANCELLED' ? 'active' : '' ?> px-3 py-2 small" 
           href="<?= site_url('account/bookings?status=CANCELLED') ?>">
            Dibatalkan <span class="badge <?= $bookingStatus === 'CANCELLED' ? 'bg-light text-primary' : 'bg-secondary' ?> ms-1"><?= $counts['CANCELLED'] ?></span>
        </a>
    </div>
</div>

<!-- 2. SEARCH & FILTER CARD -->
<div class="card-custom mb-4 p-3">
    <form action="<?= site_url('account/bookings') ?>" method="get" class="row g-2 align-items-center">
        <?php if (!empty($bookingStatus)): ?>
            <input type="hidden" name="status" value="<?= esc($bookingStatus) ?>">
        <?php endif; ?>

        <!-- Search Text -->
        <div class="col-md-5 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" 
                       placeholder="Cari nomor invoice / nama item..." 
                       value="<?= esc($search) ?>">
            </div>
        </div>

        <!-- Filter Status Pembayaran -->
        <div class="col-sm-6 col-md-3 col-lg-3">
            <select name="payment" class="form-select">
                <option value="">Semua Pembayaran</option>
                <option value="UNPAID" <?= $paymentStatus === 'UNPAID' ? 'selected' : '' ?>>Belum Lunas (Unpaid)</option>
                <option value="PAID" <?= $paymentStatus === 'PAID' ? 'selected' : '' ?>>Sudah Lunas (Paid)</option>
                <option value="PARTIAL" <?= $paymentStatus === 'PARTIAL' ? 'selected' : '' ?>>Sebagian (Partial)</option>
                <option value="REFUNDED" <?= $paymentStatus === 'REFUNDED' ? 'selected' : '' ?>>Dikembalikan (Refunded)</option>
            </select>
        </div>

        <!-- Filter Periode Tanggal -->
        <div class="col-sm-6 col-md-2 col-lg-3">
            <select name="date_range" class="form-select">
                <option value="">Semua Waktu</option>
                <option value="this_month" <?= $dateFilter === 'this_month' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="this_year" <?= $dateFilter === 'this_year' ? 'selected' : '' ?>>Tahun Ini</option>
            </select>
        </div>

        <!-- Submit & Reset Buttons -->
        <div class="col-md-2 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
                Filter
            </button>
            <?php if (!empty($search) || !empty($bookingStatus) || !empty($paymentStatus) || !empty($dateFilter)): ?>
                <a href="<?= site_url('account/bookings') ?>" class="btn btn-outline-secondary" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 3. BOOKINGS LIST TABLE / CARDS -->
<div class="card-custom">
    <div class="table-responsive">
        <?php if (!empty($bookings)): ?>
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>No. Invoice & Tanggal</th>
                        <th>Item Rental</th>
                        <th>Periode Sewa</th>
                        <th>Total & Sisa Tagihan</th>
                        <th>Status Booking</th>
                        <th>Status Bayar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('account/bookings/' . $b['invoice_no']) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                    <?= esc($b['invoice_no']) ?>
                                </a>
                                <div class="text-muted small"><?= format_date_short($b['created_at'], true) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($b['items'])): ?>
                                    <div class="fw-semibold text-dark"><?= esc($b['items'][0]['item_name_snapshot']) ?></div>
                                    <?php if (count($b['items']) > 1): ?>
                                        <div class="text-muted small">+<?= count($b['items']) - 1 ?> item lainnya</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-medium text-dark">
                                    <i class="bi bi-calendar3 me-1 text-muted"></i><?= format_date_short($b['start_at']) ?> - <?= format_date_short($b['end_at']) ?>
                                </div>
                                <div class="text-muted small text-uppercase">
                                    <?= esc($b['fulfillment_method'] ?? 'SELF_PICKUP') ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= rupiah($b['grand_total']) ?></div>
                                <?php if ((float)$b['balance_due'] > 0 && !in_array($b['status'], ['CANCELLED', 'EXPIRED'])): ?>
                                    <div class="text-danger small fw-semibold">
                                        Sisa: <?= rupiah($b['balance_due']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= booking_status_badge($b['status']) ?>
                            </td>
                            <td>
                                <?= payment_status_badge($b['payment_status']) ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Opsi
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('account/bookings/' . $b['invoice_no']) ?>">
                                                <i class="bi bi-eye text-primary"></i> Detail Lengkap
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('account/bookings/' . $b['invoice_no'] . '/print') ?>" target="_blank">
                                                <i class="bi bi-printer text-muted"></i> Cetak Invoice
                                            </a>
                                        </li>
                                        <?php if ((float)$b['balance_due'] > 0 && !in_array($b['status'], ['CANCELLED', 'EXPIRED'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 text-warning fw-semibold" href="<?= site_url('track/' . $b['invoice_no'] . '/payment') ?>">
                                                    <i class="bi bi-credit-card"></i> Bayar Sekarang
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox empty-state-icon"></i>
                <h5 class="fw-bold mb-1">Tidak Ada Riwayat Transaksi Ditemukan</h5>
                <p class="text-muted small mb-3">
                    <?php if (!empty($search) || !empty($bookingStatus) || !empty($paymentStatus)): ?>
                        Tidak ada transaksi yang cocok dengan kata kunci atau filter yang Anda terapkan.
                    <?php else: ?>
                        Anda belum memiliki pesanan rental pada akun ini.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($bookingStatus) || !empty($paymentStatus)): ?>
                    <a href="<?= site_url('account/bookings') ?>" class="btn btn-outline-primary btn-sm px-3">
                        Reset Filter
                    </a>
                <?php else: ?>
                    <a href="<?= site_url('catalog') ?>" class="btn btn-primary btn-sm px-3 py-2">
                        <i class="bi bi-plus-circle me-1"></i> Mulai Sewa Sekarang
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 4. PAGINATION -->
    <?php if ($pager && $pager->getPageCount('bookings') > 1): ?>
        <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Menampilkan halaman <?= $pager->getCurrentPage('bookings') ?> dari <?= $pager->getPageCount('bookings') ?>
            </span>
            <div>
                <?= $pager->links('bookings', 'default_bootstrap') ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

