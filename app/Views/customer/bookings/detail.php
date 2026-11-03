<?= $this->extend('customer/layouts/main') ?>

<?= $this->section('content') ?>

<?php
$bStatus = $booking['status'];
$pStatus = $booking['payment_status'];
$balance = (float) $booking['balance_due'];
$grandTotal = (float) $booking['grand_total'];
$isPaid = $pStatus === 'PAID';
$isCancelled = in_array($bStatus, ['CANCELLED', 'EXPIRED']);

$bizPhone = $business['phone'] ?? '';
$waMessage = urlencode("Halo {$business['business_name']}, saya ingin menanyakan tentang booking invoice {$booking['invoice_no']}.");
$waBookingUrl = !empty($bizPhone) ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $bizPhone) . "?text={$waMessage}" : "#";

// Timeline status calculation
$steps = [
    'PENDING'   => ['label' => 'Pesanan Dibuat', 'icon' => 'bi-receipt', 'desc' => 'Menunggu verifikasi / pembayaran'],
    'CONFIRMED' => ['label' => 'Terkonfirmasi', 'icon' => 'bi-check-circle', 'desc' => 'Unit siap diserahkan'],
    'ONGOING'   => ['label' => 'Sedang Berjalan', 'icon' => 'bi-arrow-repeat', 'desc' => 'Unit sedang disewa'],
    'COMPLETED' => ['label' => 'Selesai', 'icon' => 'bi-trophy', 'desc' => 'Unit telah dikembalikan'],
];

$stepKeys = ['PENDING', 'CONFIRMED', 'ONGOING', 'COMPLETED'];
$currentStepIdx = array_search($bStatus, $stepKeys);
if ($currentStepIdx === false) {
    $currentStepIdx = -1; // CANCELLED or EXPIRED
}
?>

<!-- 1. HEADER ACTION BAR -->
<div class="card-custom mb-4 p-3 p-md-4">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="h4 fw-bold font-monospace text-primary mb-0"><?= esc($booking['invoice_no']) ?></h3>
                <?= booking_status_badge($bStatus) ?>
                <?= payment_status_badge($pStatus) ?>
            </div>
            <p class="text-muted small mb-0">
                Dibuat pada <?= format_datetime($booking['created_at']) ?> 
                <?php if (!empty($branch)): ?>
                    • Cabang: <strong><?= esc($branch['branch_name']) ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="<?= site_url('account/bookings/' . $booking['invoice_no'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-printer"></i>
                <span>Cetak Invoice</span>
            </a>
            <a href="<?= esc($waBookingUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-whatsapp"></i>
                <span>Tanya Admin</span>
            </a>
            <?php if ($balance > 0 && !$isCancelled): ?>
                <a href="<?= site_url('track/' . $booking['invoice_no'] . '/payment') ?>" class="btn btn-warning text-dark fw-bold btn-sm shadow-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-credit-card"></i>
                    <span>Bayar Tagihan</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 2. BOOKING LIFECYCLE TIMELINE -->
<?php if (!$isCancelled): ?>
    <div class="card-custom mb-4 p-4">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-diagram-3 text-primary"></i> Progres Transaksi
        </h6>
        <div class="position-relative py-2">
            <div class="row text-center g-2">
                <?php foreach ($stepKeys as $idx => $sKey): ?>
                    <?php 
                    $info = $steps[$sKey];
                    $isDone = $currentStepIdx >= $idx;
                    $isCurrent = $currentStepIdx === $idx;
                    ?>
                    <div class="col-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mb-2 shadow-sm <?= $isDone ? 'bg-primary text-white' : 'bg-light text-muted border' ?>" style="width: 44px; height: 44px;">
                                <i class="bi <?= $info['icon'] ?> fs-5"></i>
                            </div>
                            <div class="fw-bold small <?= $isCurrent ? 'text-primary' : ($isDone ? 'text-dark' : 'text-muted') ?>">
                                <?= esc($info['label']) ?>
                            </div>
                            <div class="text-muted d-none d-md-block" style="font-size: 0.72rem;">
                                <?= esc($info['desc']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger mb-4 d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
        <i class="bi bi-x-octagon-fill fs-4"></i>
        <div>
            <strong>Status Booking: <?= esc($bStatus) ?></strong> — Transaksi ini telah dibatalkan atau melewati batas waktu pembayaran.
        </div>
    </div>
<?php endif; ?>

<!-- 3. MAIN DETAILS GRID -->
<div class="row g-4">
    <!-- Left Column: Items & Fulfillment -->
    <div class="col-lg-8">
        <!-- Rincian Item Sewa -->
        <div class="card-custom mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam text-primary"></i>
                    <span>Daftar Item Rental</span>
                </span>
                <span class="badge bg-light text-dark border"><?= count($items) ?> Unit / Layanan</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Item Rental</th>
                            <th>Periode Sewa</th>
                            <th>Harga Satuan</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($item['item_name_snapshot']) ?></div>
                                    <small class="text-muted text-uppercase"><?= esc($item['item_type_snapshot']) ?></small>
                                </td>
                                <td>
                                    <div class="small fw-medium"><?= format_date_short($item['start_at']) ?> - <?= format_date_short($item['end_at']) ?></div>
                                    <small class="text-muted"><?= (int)$item['duration_value'] ?> <?= esc($item['duration_unit']) ?></small>
                                </td>
                                <td><?= rupiah($item['unit_price']) ?></td>
                                <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                <td class="text-end fw-bold text-dark"><?= rupiah($item['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Serah Terima / Pengembalian (Handovers) -->
        <?php if (!empty($handovers)): ?>
            <div class="card-custom mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-right text-primary"></i>
                    <span>Riwayat Serah Terima & Kondisi Unit</span>
                </div>
                <div class="card-body p-3">
                    <?php foreach ($handovers as $h): ?>
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary"><?= esc($h['handover_type']) ?></span>
                                <small class="text-muted"><?= format_datetime($h['actual_at'] ?? $h['scheduled_at']) ?></small>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-md-6">
                                    <span class="text-muted">Penerima:</span> <strong><?= esc($h['customer_receiver_name'] ?: $booking['customer_name_snapshot']) ?></strong>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted">Lokasi:</span> <strong><?= esc($h['location'] ?: 'Lokasi Rental') ?></strong>
                                </div>
                                <?php if (!empty($h['notes'])): ?>
                                    <div class="col-12 mt-2">
                                        <div class="text-muted">Catatan Operasional:</div>
                                        <div class="p-2 bg-white rounded border mt-1"><?= esc($h['notes']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Detail Unit Kondisi -->
                            <?php if (!empty($h['details'])): ?>
                                <div class="mt-3 pt-2 border-top">
                                    <div class="fw-semibold small mb-2">Pemeriksaan Unit:</div>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($h['details'] as $det): ?>
                                            <div class="list-group-item bg-transparent px-0 py-1 d-flex justify-content-between align-items-center small">
                                                <span>Status Kondisi: <strong><?= esc($det['condition_status']) ?></strong></span>
                                                <span class="text-muted"><?= esc($det['condition_notes'] ?: 'Baik / Lengkap') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Histori Status Booking (Customer Safe) -->
        <?php if (!empty($statusHistory)): ?>
            <div class="card-custom mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span>Catatan Status Booking</span>
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($statusHistory as $hist): ?>
                            <li class="d-flex align-items-start gap-3 py-2 border-bottom">
                                <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                                <div>
                                    <div class="fw-semibold text-dark">
                                        Perubahan Status: <?= esc($hist['new_status']) ?>
                                    </div>
                                    <?php if (!empty($hist['note'])): ?>
                                        <div class="small text-muted"><?= esc($hist['note']) ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?= format_datetime($hist['changed_at']) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Financial Summary, Customer & Actions -->
    <div class="col-lg-4">
        <!-- Rincian Finansial -->
        <div class="card-custom mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary"></i>
                <span>Rincian Pembayaran</span>
            </div>
            <div class="card-body p-3">
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Subtotal Item</span>
                    <span class="fw-medium"><?= rupiah($booking['subtotal']) ?></span>
                </div>
                <?php if ((float)$booking['discount_total'] > 0): ?>
                    <div class="d-flex justify-content-between py-1 small text-success">
                        <span>Diskon</span>
                        <span>- <?= rupiah($booking['discount_total']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((float)$booking['charge_total'] > 0): ?>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Biaya Tambahan</span>
                        <span>+ <?= rupiah($booking['charge_total']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((float)$booking['deposit_total'] > 0): ?>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Uang Jaminan (Deposit)</span>
                        <span>+ <?= rupiah($booking['deposit_total']) ?></span>
                    </div>
                <?php endif; ?>
                
                <hr class="my-2">
                
                <div class="d-flex justify-content-between py-1">
                    <span class="fw-bold text-dark">Grand Total</span>
                    <span class="fw-bold text-dark h5 mb-0"><?= rupiah($grandTotal) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Sudah Dibayar</span>
                    <span class="text-success fw-semibold"><?= rupiah($booking['paid_total']) ?></span>
                </div>
                
                <div class="p-3 rounded-3 mt-2 <?= $balance > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold small">Sisa Tagihan:</span>
                        <span class="h5 fw-bold mb-0"><?= rupiah($balance) ?></span>
                    </div>
                </div>

                <?php if ($balance > 0 && !$isCancelled): ?>
                    <div class="mt-3">
                        <a href="<?= site_url('track/' . $booking['invoice_no'] . '/payment') ?>" class="btn btn-warning text-dark fw-bold w-100 py-2 shadow-sm">
                            <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detail Pemesan & Penyerahan -->
        <div class="card-custom mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt text-primary"></i>
                <span>Instruksi Penyerahan</span>
            </div>
            <div class="card-body p-3 small">
                <div class="mb-3">
                    <div class="text-muted small">Nama Pemesan:</div>
                    <div class="fw-bold text-dark"><?= esc($booking['customer_name_snapshot']) ?></div>
                    <div class="text-muted"><?= esc($booking['customer_phone_snapshot']) ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Metode Penyerahan:</div>
                    <span class="badge bg-light text-dark border text-uppercase">
                        <?= esc($booking['fulfillment_method'] ?? 'SELF_PICKUP') ?>
                    </span>
                </div>
                <?php if (!empty($booking['pickup_address'])): ?>
                    <div class="mb-2">
                        <div class="text-muted small">Lokasi Pengambilan:</div>
                        <div class="p-2 bg-light rounded border"><?= nl2br(esc($booking['pickup_address'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($booking['return_address'])): ?>
                    <div class="mb-2">
                        <div class="text-muted small">Lokasi Pengembalian:</div>
                        <div class="p-2 bg-light rounded border"><?= nl2br(esc($booking['return_address'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($booking['customer_notes'])): ?>
                    <div class="mb-2">
                        <div class="text-muted small">Catatan Anda:</div>
                        <div class="p-2 bg-light rounded border fst-italic"><?= nl2br(esc($booking['customer_notes'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Riwayat Pembayaran -->
        <div class="card-custom mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-credit-card text-primary"></i>
                    <span>Riwayat Pembayaran</span>
                </span>
                <span class="badge bg-light text-dark border"><?= count($payments) ?></span>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($payments)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($payments as $p): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold small font-monospace"><?= esc($p['payment_no']) ?></div>
                                        <small class="text-muted"><?= esc($p['method']) ?> • <?= format_date_short($p['paid_at'] ?? $p['created_at']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold small text-dark"><?= rupiah($p['amount']) ?></div>
                                        <?= payment_status_badge($p['status']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0 text-center py-2">Belum ada riwayat pembayaran tercatat.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

