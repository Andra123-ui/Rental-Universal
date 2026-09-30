<?php
/**
 * @var array $bookings
 * @var int $freezeLeft
 */
$statusMap = [
    'PENDING'   => ['Menunggu', 'warn'],
    'CONFIRMED' => ['Dikonfirmasi', 'ok'],
    'ONGOING'   => ['Berjalan', 'ok'],
    'COMPLETED' => ['Selesai', 'done'],
    'CANCELLED' => ['Dibatalkan', 'off'],
    'EXPIRED'   => ['Kedaluwarsa', 'off'],
];
$payMap = [
    'UNPAID'   => 'Belum dibayar',
    'PARTIAL'  => 'Dibayar sebagian',
    'PAID'     => 'Lunas',
    'REFUNDED' => 'Dikembalikan',
];
// Ubah di sini kalau route pembayaran/detail kamu berbeda
$payUrl    = fn($inv) => base_url('/track/' . rawurlencode($inv) . '/payment');
$detailUrl = fn($inv) => base_url('/track/' . rawurlencode($inv));

function bk_group(array $b): string
{
    if ($b['seconds_left'] !== null) return 'menunggu';
    if (in_array($b['status'], ['CANCELLED', 'EXPIRED'], true)) return 'batal';
    if ($b['status'] === 'COMPLETED') return 'selesai';
    return 'berjalan';
}
?>
<?= view('partials/header', ['title' => 'Booking Saya']) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Booking Saya</div>
        <h1>Booking Saya</h1>
    </div>
</div>

<section>
    <div class="wrap">
        <?php if ($freezeLeft > 0): ?>
        <div class="alert-box" id="freeze-box" style="background:#fee2e2;color:#991b1b;margin-bottom:16px;">
            Booking terakhir kedaluwarsa karena tidak dibayar. Anda bisa booking lagi dalam
            <strong class="js-freeze-timer" data-left="<?= (int) $freezeLeft ?>">--:--</strong>.
        </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <h3>Belum ada booking</h3>
            <p>Booking yang Anda buat akan muncul di sini.</p>
            <a href="<?= base_url('/catalog') ?>" class="btn btn-primary" style="margin-top:16px;">Lihat Katalog</a>
        </div>
        <?php else: ?>
        <div class="bk-filters">
            <button type="button" class="bk-chip active" data-filter="all">Semua</button>
            <button type="button" class="bk-chip" data-filter="menunggu">Menunggu Bayar</button>
            <button type="button" class="bk-chip" data-filter="berjalan">Berjalan</button>
            <button type="button" class="bk-chip" data-filter="selesai">Selesai</button>
            <button type="button" class="bk-chip" data-filter="batal">Batal</button>
        </div>

        <div class="bk-list">
            <?php foreach ($bookings as $b):
                    [$label, $tone] = $statusMap[$b['status']] ?? [$b['status'], 'off'];
                    $payText = $payMap[$b['payment_status']] ?? $b['payment_status'];

                    $parts = [];
                    foreach ($b['items'] as $it) {
                        $q = rtrim(rtrim(number_format((float) $it['quantity'], 2, '.', ''), '0'), '.');
                        $parts[] = $it['item_name_snapshot'] . ' ×' . $q;
                    }
                    $itemText = $parts ? implode(', ', $parts) : '-';

                    // end_at disimpan eksklusif (00:00 hari berikutnya), jadi dikurangi 1 detik untuk tampilan
                    $startText = date('d M Y', strtotime($b['start_at']));
                    $endText   = date('d M Y', strtotime($b['end_at']) - 1);
                    ?>
            <article class="bk-card" data-group="<?= bk_group($b) ?>">
                <div class="bk-head">
                    <div>
                        <div class="bk-inv"><?= esc($b['invoice_no']) ?></div>
                        <div class="bk-meta">Dibuat <?= esc(date('d M Y H:i', strtotime($b['created_at']))) ?></div>
                    </div>
                    <div class="bk-badges">
                        <span class="bk-badge bk-<?= $tone ?>"><?= esc($label) ?></span>
                        <span class="bk-badge bk-pay"><?= esc($payText) ?></span>
                    </div>
                </div>

                <div class="bk-body">
                    <div><?= esc($itemText) ?></div>
                    <div class="bk-meta"><?= esc($startText) ?> &ndash; <?= esc($endText) ?></div>
                    <div class="bk-total">Rp <?= number_format($b['grand_total'], 0, ',', '.') ?></div>
                </div>

                <?php if ($b['seconds_left'] !== null): ?>
                <div class="bk-timer">
                    Selesaikan pembayaran dalam
                    <strong class="js-pay-timer" data-left="<?= (int) $b['seconds_left'] ?>">--:--</strong>
                </div>
                <?php endif; ?>

                <div class="bk-actions">
                    <?php if ($b['seconds_left'] !== null): ?>
                    <a href="<?= $payUrl($b['invoice_no']) ?>" class="btn btn-primary">Lanjut Bayar</a>
                    <?php endif; ?>
                    <a href="<?= $detailUrl($b['invoice_no']) ?>" class="btn btn-outline">Detail</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.bk-filters {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.bk-chip {
    border: 1px solid var(--line);
    background: none;
    border-radius: 999px;
    padding: 6px 14px;
    font-size: .85rem;
    cursor: pointer;
}

.bk-chip.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}

.bk-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.bk-card {
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 16px 18px;
}

.bk-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.bk-inv {
    font-weight: 700;
    letter-spacing: .3px;
}

.bk-meta {
    font-size: .82rem;
    color: var(--muted);
    margin-top: 2px;
}

.bk-badges {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: flex-start;
}

.bk-badge {
    font-size: .74rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
    background: #e5e7eb;
    color: #374151;
}

.bk-ok {
    background: #dcfce7;
    color: #166534;
}

.bk-warn {
    background: #fef3c7;
    color: #92400e;
}

.bk-done {
    background: #dbeafe;
    color: #1e40af;
}

.bk-off {
    background: #fee2e2;
    color: #991b1b;
}

.bk-body {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.bk-total {
    font-weight: 700;
    margin-top: 4px;
}

.bk-timer {
    margin-top: 12px;
    padding: 10px 12px;
    border-radius: 8px;
    background: #fef3c7;
    color: #92400e;
    font-size: .9rem;
}

.bk-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
}
</style>

<script>
function runCountdown(el, onDone) {
    let left = parseInt(el.dataset.left, 10) || 0;
    (function tick() {
        if (left <= 0) {
            onDone(el);
            return;
        }
        el.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        left--;
        setTimeout(tick, 1000);
    })();
}

// Waktu bayar habis -> reload, server menandai EXPIRED dan stok dilepas
document.querySelectorAll('.js-pay-timer').forEach(el => runCountdown(el, () => location.reload()));

// Freeze selesai -> sembunyikan pesan
document.querySelectorAll('.js-freeze-timer').forEach(el =>
    runCountdown(el, () => {
        const box = document.getElementById('freeze-box');
        if (box) box.style.display = 'none';
    })
);

// Filter status
document.querySelectorAll('.bk-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.bk-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        const f = chip.dataset.filter;
        document.querySelectorAll('.bk-card').forEach(card => {
            card.style.display = (f === 'all' || card.dataset.group === f) ? '' : 'none';
        });
    });
});
</script>

<?= view('partials/footer') ?>