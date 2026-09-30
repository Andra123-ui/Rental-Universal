<?php
helper('business');
/**
 * @var array $items
 * @var float $subtotal
 */
?>
<?= view('partials/header', ['title' => 'Keranjang']) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Keranjang</div>
        <h1>Keranjang Pilihan Anda</h1>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="step-indicator">
            <div class="s-item active">1. Keranjang</div>
            <div class="s-item">2. Data Penyewa</div>
            <div class="s-item">3. Fulfillment</div>
            <div class="s-item">4. Review</div>
            <div class="s-item">5. Selesai</div>
        </div>

        <?php if (empty($items)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="6" width="18" height="15" rx="2" />
                <path d="M3 10h18" />
            </svg>
            <h3>Keranjang masih kosong</h3>
            <p>Yuk mulai pilih barang atau jasa yang ingin Anda sewa.</p>
            <a href="<?= base_url('/catalog') ?>" class="btn btn-primary" style="margin-top:16px;">Lihat Katalog</a>
        </div>
        <?php else: ?>
        <div class="detail-grid">
            <div>
                <table class="cart-table" id="cartTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Jadwal</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $line):
                                $unit = $line['product']['unit_label'];
                                ?>
                        <?php
    $lineUnits = $line['price_detail']['units'] ?? max(1, ceil((strtotime($line['end_at']) - strtotime($line['start_at'])) / 86400));
    ?>
                        <tr data-key="<?= esc($line['key']) ?>" data-price="<?= esc($line['product']['base_price']) ?>"
                            data-units="<?= esc($lineUnits) ?>">
                            <td>
                                <strong>
                                    <?= esc($line['product']['name']) ?>
                                </strong><br>
                                <span style="color:var(--muted);font-size:0.82rem;">
                                    Rp
                                    <?= number_format($line['product']['base_price'], 0, ',', '.') ?> /
                                    <?= esc($unit) ?>
                                </span>

                                <?php
                                $bizName = business_name($line['product']['business_id'] ?? null);
                                $brName  = branch_name(isset($line['branch_id']) ? (int) $line['branch_id'] : null);
                                $desc    = trim((string) ($line['product']['description'] ?? ''));
                                ?>
                                <div class="cart-meta">
                                    <?php if ($bizName !== ''): ?>
                                    <div><span>Bisnis</span> <?= esc($bizName) ?></div>
                                    <?php endif; ?>
                                    <div><span>Cabang</span> <?= $brName !== '' ? esc($brName) : 'Semua cabang' ?></div>
                                </div>
                                <?php if ($desc !== ''): ?>
                                <p class="cart-desc"><?= esc(mb_strimwidth($desc, 0, 120, '…')) ?></p>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;color:var(--muted);">
                                <?= esc(date('d M Y H:i', strtotime($line['start_at']))) ?><br>
                                s/d
                                <?= esc(date('d M Y H:i', strtotime($line['end_at']))) ?>
                            </td>
                            <td>
                                <div class="qty-stepper">
                                    <button type="button" class="qty-btn qty-minus"
                                        aria-label="Kurangi">&minus;</button>
                                    <input type="number" class="qty-input js-qty" min="1"
                                        value="<?= esc($line['qty']) ?>" inputmode="numeric">
                                    <button type="button" class="qty-btn qty-plus" aria-label="Tambah">&plus;</button>
                                </div>
                            </td>
                            <td>
                                <span class="js-line-total">Rp
                                    <?= number_format($line['line_total'], 0, ',', '.') ?>
                                </span>
                                <span class="qty-saving-indicator" style="display:none;">Menyimpan...</span>
                            </td>
                            <td>
                                <a href="<?= base_url('/cart/hapus/' . $line['key']) ?>" class="cart-remove"
                                    onclick="return confirm('Hapus item ini dari keranjang?');">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary">
                <h3 style="font-size:1.05rem;margin-bottom:16px;">Ringkasan</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="js-subtotal">Rp
                        <?= number_format($subtotal, 0, ',', '.') ?>
                    </span>
                </div>
                <div class="summary-row" style="color:var(--muted);font-size:0.85rem;">
                    <span>Biaya tambahan & deposit</span>
                    <span>Dihitung di langkah berikutnya</span>
                </div>
                <div class="summary-row total">
                    <span>Estimasi Total</span>
                    <span class="js-grand-total">Rp
                        <?= number_format($subtotal, 0, ',', '.') ?>
                    </span>
                </div>
                <a href="<?= base_url('/checkout') ?>" class="btn btn-primary"
                    style="width:100%;justify-content:center;margin-top:16px;">Checkout</a>
                <a href="<?= base_url('/catalog') ?>" class="btn btn-outline"
                    style="width:100%;justify-content:center;margin-top:10px;">Tambah Item Lain</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.cart-meta {
    margin-top: 6px;
    font-size: .8rem;
    color: var(--muted);
    line-height: 1.5;
}

.cart-meta span {
    display: inline-block;
    min-width: 46px;
    font-weight: 600;
}

.cart-desc {
    margin: 6px 0 0;
    font-size: .8rem;
    color: var(--muted);
    line-height: 1.5;
    max-width: 320px;
}

.qty-stepper {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--line);
    border-radius: 8px;
    overflow: hidden;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: var(--paper);
    color: var(--ink);
    font-size: 1.1rem;
    line-height: 1;
    cursor: pointer;
    transition: background .15s ease;
}

.qty-btn:hover {
    background: var(--accent-soft);
}

.qty-stepper .qty-input {
    width: 44px;
    text-align: center;
    border: none;
    border-left: 1px solid var(--line);
    border-right: 1px solid var(--line);
    border-radius: 0;
    padding: 6px 4px;
    appearance: textfield;
    -moz-appearance: textfield;
}

.qty-stepper .qty-input::-webkit-outer-spin-button,
.qty-stepper .qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.qty-saving-indicator {
    display: block;
    font-size: 0.72rem;
    color: var(--accent);
    margin-top: 2px;
}

.qty-saving-indicator.qty-error {
    color: #B91C1C;
    font-weight: 600;
}

.js-line-total {
    transition: color .2s ease;
}

.js-line-total.flash {
    color: var(--accent);
}
</style>

<script>
(function() {
    const table = document.getElementById('cartTable');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    const subtotalEl = document.querySelector('.js-subtotal');
    const grandTotalEl = document.querySelector('.js-grand-total');
    const rupiah = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');

    // Token CSRF dinamis: mulai dari nilai yang di-embed saat halaman dimuat,
    // lalu selalu diperbarui dari respons server setiap request AJAX berhasil/gagal.
    // Ini WAJIB karena CI4 meregenerasi CSRF token setiap request POST -> kalau
    // pakai token statis, request kedua dan seterusnya akan selalu ditolak.
    let csrfTokenName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    function recalcAll() {
        let subtotal = 0;
        rows.forEach(row => {
            const price = parseFloat(row.dataset.price);
            const units = parseFloat(row.dataset.units) || 1;
            const qty = parseInt(row.querySelector('.js-qty').value, 10) || 1;
            const lineTotal = price * units * qty;
            subtotal += lineTotal;

            const lineTotalEl = row.querySelector('.js-line-total');
            lineTotalEl.textContent = rupiah(lineTotal);
            lineTotalEl.classList.add('flash');
            setTimeout(() => lineTotalEl.classList.remove('flash'), 350);
        });
        subtotalEl.textContent = rupiah(subtotal);
        grandTotalEl.textContent = rupiah(subtotal);
    }

    let debounceTimer = null;

    function persistQty(row) {
        const key = row.dataset.key;
        const qtyInput = row.querySelector('.js-qty');
        const qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
        const previousQty = row.dataset.lastConfirmedQty || qty;
        qtyInput.value = qty;

        const indicator = row.querySelector('.qty-saving-indicator');
        indicator.style.display = 'block';
        indicator.classList.remove('qty-error');
        indicator.textContent = 'Menyimpan...';

        const formData = new FormData();
        formData.append('key', key);
        formData.append('qty', qty);
        formData.append(csrfTokenName, csrfHash);

        fetch('<?= base_url('/cart/update') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async res => {
                const data = await res.json();
                // Selalu sinkronkan token terbaru dari server, apa pun hasilnya (sukses/gagal),
                // supaya request AJAX berikutnya tidak ditolak CSRF.
                if (data.csrf_token_name && data.csrf_hash) {
                    csrfTokenName = data.csrf_token_name;
                    csrfHash = data.csrf_hash;
                }
                if (!res.ok || !data.success) {
                    throw data;
                }
                return data;
            })
            .then(() => {
                row.dataset.lastConfirmedQty = qty;
                indicator.style.display = 'none';
            })
            .catch((err) => {
                // Stok tidak cukup / gagal disimpan -> kembalikan qty ke nilai terakhir yang valid
                const maxAvail = err && err.max_available ? err.max_available : previousQty;
                qtyInput.value = maxAvail;
                row.dataset.lastConfirmedQty = maxAvail;
                recalcAll();

                indicator.classList.add('qty-error');
                indicator.textContent = (err && err.message) ? err.message : 'Stok tidak mencukupi';
                indicator.style.display = 'block';
                setTimeout(() => {
                    indicator.style.display = 'none';
                    indicator.classList.remove('qty-error');
                }, 3500);
            });
    }

    rows.forEach(row => {
        const qtyInput = row.querySelector('.js-qty');
        const minusBtn = row.querySelector('.qty-minus');
        const plusBtn = row.querySelector('.qty-plus');

        function onQtyChanged() {
            let v = parseInt(qtyInput.value, 10);
            if (isNaN(v) || v < 1) v = 1;
            qtyInput.value = v;

            recalcAll(); // update tampilan instan

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => persistQty(row),
                500); // simpan ke server setelah 0.5 detik jeda
        }

        minusBtn.addEventListener('click', () => {
            qtyInput.value = Math.max(1, (parseInt(qtyInput.value, 10) || 1) - 1);
            onQtyChanged();
        });
        plusBtn.addEventListener('click', () => {
            qtyInput.value = (parseInt(qtyInput.value, 10) || 1) + 1;
            onQtyChanged();
        });
        qtyInput.addEventListener('input', onQtyChanged);
    });
})();
</script>

<?= view('partials/footer') ?>