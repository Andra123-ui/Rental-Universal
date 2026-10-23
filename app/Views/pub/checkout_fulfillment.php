<?php
/**
 * @var array $cartItems
 * @var float $subtotal
 * @var float $grandTotal
 */
?>
<?= view('partials/header', ['title' => 'Metode Pemenuhan']) ?>

<style>
/* Tema Khusus Halaman Fulfillment */
.fulfillment-wrap {
    --brand-blue: #1B3A8C;
    --brand-blue-light: #EEF4FF;
    --border-color: #E2E8F0;
    --text-main: #1E293B;
    --text-muted: #64748B;
    --bg-body: #F8FAFC;
}

/* Card Accordion */
.fulfill-card {
    border: 1px solid var(--border-color);
    border-radius: 14px;
    margin-bottom: 20px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    transition: box-shadow 0.3s;
}

.fulfill-card:hover {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
}

.fulfill-header {
    padding: 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border-bottom: 1px solid transparent;
    transition: background 0.2s;
}

.fulfill-header.active {
    border-bottom-color: var(--border-color);
    background: #FAFAFC;
}

/* ITEM BADGE (BIRU) */
.item-badge {
    display: inline-block;
    background: var(--brand-blue-light);
    color: var(--brand-blue);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

/* HARGA (BIRU) */
.price-strong {
    color: var(--brand-blue);
    font-weight: 700;
}

/* Custom Radio Buttons */
.method-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 12px;
}

.method-card {
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
    background: #fff;
}

.method-card:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
}

.method-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

/* State Dipilih (BIRU) */
.method-card:has(input[type="radio"]:checked) {
    border-color: var(--brand-blue);
    background-color: var(--brand-blue-light);
    box-shadow: 0 4px 12px rgba(27, 58, 140, 0.1);
}

.method-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.radio-circle {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #CBD5E1;
    flex-shrink: 0;
    margin-top: 2px;
    position: relative;
    transition: all 0.2s;
}

.method-card:has(input:checked) .radio-circle {
    border-color: var(--brand-blue);
    background: var(--brand-blue);
}

.method-card:has(input:checked) .radio-circle::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: #fff;
    border-radius: 50%;
}

.method-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--text-main);
    margin-bottom: 4px;
}

.method-desc {
    font-size: 0.78rem;
    color: var(--text-muted);
    line-height: 1.4;
    margin: 0;
}

/* Input Fields */
.fulfill-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.9rem;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
    resize: vertical;
}

.fulfill-input:focus {
    outline: none;
    border-color: var(--brand-blue);
    box-shadow: 0 0 0 3px rgba(27, 58, 140, 0.1);
}

/* Info Box Cabang */
.branch-info-box {
    margin-top: 16px;
    padding: 16px;
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    border-radius: 10px;
    display: flex;
    gap: 12px;
}

.branch-info-box svg {
    color: #16A34A;
    flex-shrink: 0;
}

.branch-info-box h4 {
    margin: 0 0 4px;
    font-size: 0.9rem;
    color: #166534;
}

.branch-info-box p {
    margin: 0;
    font-size: 0.82rem;
    color: #15803D;
    line-height: 1.4;
}

/* Total Box */
.total-box {
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 24px;
    margin-top: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
}

/* Tombol Utama Biru */
.btn-primary {
    background-color: var(--brand-blue) !important;
    border-color: var(--brand-blue) !important;
}

.btn-primary:hover {
    background-color: #142d70 !important;
}

@media (max-width: 600px) {
    .method-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / <a href="<?= base_url('/checkout') ?>">Data
                Penyewa</a> / Fulfillment</div>
        <h1>Metode Pemenuhan & Lokasi</h1>
    </div>
</div>

<section class="fulfillment-wrap"
    style="background: var(--bg-body); padding-top: 30px; padding-bottom: 60px; min-height: 80vh;">
    <div class="wrap">

        <!-- STEP INDICATOR (SAMA DENGAN DATA PENYEWA) -->
        <div class="step-indicator">
            <div class="s-item done">1. Keranjang</div>
            <div class="s-item done">2. Data Penyewa</div>
            <div class="s-item active">3. Fulfillment</div>
            <div class="s-item">4. Review</div>
            <div class="s-item">5. Selesai</div>
        </div>

        <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert-box alert-warn"
            style="margin-bottom: 20px; border-left: 4px solid #EF4444; background: #FEF2F2; color: #991B1B; padding: 16px; border-radius: 8px;">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
            <div style="font-size: 0.9rem; margin-bottom: 4px;"><?= esc($err) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('/checkout/fulfillment') ?>" style="max-width:850px; margin: 0 auto;">
            <?= csrf_field() ?>

            <!-- LOOPING PER ITEM -->
            <?php foreach ($cartItems as $index => $item): ?>
            <?php
        $branchName = $item['branch_name'] ?? 'Cabang Terpilih';
        $branchAddress = $item['branch_address'] ?? 'Alamat cabang tidak tersedia. Silakan hubungi admin.';
        ?>

            <div class="fulfill-card">

                <!-- HEADER ACCORDION -->
                <div class="fulfill-header <?= $index === 0 ? 'active' : '' ?>"
                    onclick="toggleAccordion(<?= $index ?>)">
                    <div>
                        <span class="item-badge"><?= esc($item['item_type']) ?></span>
                        <h3 style="margin:0 0 4px; font-size:1.05rem; color: var(--text-main);">
                            <?= esc($item['name']) ?>
                        </h3>
                        <p style="margin:0; font-size:0.85rem; color:var(--text-muted);">
                            Jumlah: <strong><?= esc($item['qty']) ?></strong> &nbsp;|&nbsp;
                            <span class="price-strong">Rp <?= number_format($item['line_total'], 0, ',', '.') ?></span>
                        </p>
                    </div>
                    <div class="accordion-icon" id="icon-<?= $index ?>"
                        style="color: var(--text-muted); transition: transform 0.3s; <?= $index === 0 ? 'transform: rotate(180deg);' : '' ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                <!-- BODY ACCORDION -->
                <div class="accordion-body" id="body-<?= $index ?>"
                    style="display: <?= $index === 0 ? 'block' : 'none' ?>; padding: 20px;">

                    <!-- Pilihan Metode -->
                    <div style="margin-bottom: 24px;">
                        <label
                            style="font-weight:700; font-size:0.9rem; color: var(--text-main); display:block; margin-bottom:8px;">
                            Pilih Metode Pemenuhan
                        </label>
                        <div class="method-grid">
                            <?php
                $methods = [
                  'SELF_PICKUP' => ['label' => 'Ambil Sendiri', 'desc' => 'Ambil & kembalikan ke lokasi kami.'],
                  'DELIVERY' => ['label' => 'Diantar (Driver)', 'desc' => 'Kami antar ke alamat Anda.'],
                ];
                foreach ($methods as $val => $m):
                  $inputId = "method_{$index}_{$val}";
                ?>
                            <label class="method-card" for="<?= $inputId ?>">
                                <input type="radio" id="<?= $inputId ?>" name="items[<?= $index ?>][fulfillment_method]"
                                    value="<?= $val ?>" onchange="handleMethodChange(<?= $index ?>)" required>
                                <div class="method-content">
                                    <div class="radio-circle"></div>
                                    <div>
                                        <div class="method-title"><?= esc($m['label']) ?></div>
                                        <p class="method-desc"><?= esc($m['desc']) ?></p>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Info Lokasi Cabang (Tampil jika Ambil Sendiri) -->
                    <div id="pickup_info_<?= $index ?>" class="branch-info-box" style="display: none;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <div>
                            <h4>Lokasi Pengambilan: <?= esc($branchName) ?></h4>
                            <p><?= esc($branchAddress) ?></p>
                        </div>
                    </div>

                    <!-- Alamat Pengantaran (Tampil jika Diantar) -->
                    <div id="delivery_wrap_<?= $index ?>" style="display: none;">
                        <label for="pickup_<?= $index ?>"
                            style="font-weight:600; font-size:0.85rem; color: var(--text-main); margin-bottom:6px; display:block;">
                            Alamat Pengantaran
                        </label>
                        <textarea id="pickup_<?= $index ?>" name="items[<?= $index ?>][pickup_address]"
                            class="fulfill-input" rows="3"
                            placeholder="Isi alamat lengkap pengantaran (Jalan, No, Kelurahan, Kota)"></textarea>
                    </div>

                    <!-- Hidden input -->
                    <input type="hidden" name="items[<?= $index ?>][cart_key]" value="<?= esc($item['key']) ?>">
                    <input type="hidden" name="items[<?= $index ?>][catalog_item_id]"
                        value="<?= esc($item['catalog_item_id']) ?>">

                </div>
            </div>
            <?php endforeach; ?>

            <!-- TOTAL BOX -->
            <div class="total-box">
                <div
                    style="display:flex; justify-content:space-between; margin-bottom:12px; color: var(--text-muted); font-size:0.95rem;">
                    <span>Subtotal</span>
                    <span class="price-strong">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                </div>
                <div
                    style="display:flex; justify-content:space-between; border-top:1px dashed var(--border-color); padding-top:16px; margin-top:16px; align-items:center;">
                    <span style="font-size:1.1rem; font-weight:700; color: var(--text-main);">Total Tagihan</span>
                    <strong style="font-size:1.4rem; color: var(--brand-blue);">Rp
                        <?= number_format($grandTotal, 0, ',', '.') ?></strong>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div style="display:flex; gap:16px; margin-top:30px;">
                <a href="<?= base_url('/checkout') ?>" class="btn btn-outline"
                    style="padding: 14px 24px; border-radius: 8px;">
                    Kembali
                </a>
                <button type="submit" class="btn btn-primary"
                    style="flex:1; justify-content:center; padding: 14px 24px; border-radius: 8px; font-weight: 600; font-size: 1rem;">
                    Lanjut ke Review &rarr;
                </button>
            </div>
        </form>
    </div>
</section>

<!-- JAVASCRIPT -->
<script>
function toggleAccordion(index) {
    const body = document.getElementById('body-' + index);
    const icon = document.getElementById('icon-' + index);
    const header = body.previousElementSibling;

    if (body.style.display === 'none' || body.style.display === '') {
        body.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
        header.classList.add('active');
    } else {
        body.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
        header.classList.remove('active');
    }
}

function handleMethodChange(index) {
    const methodPickup = document.getElementById('method_' + index + '_SELF_PICKUP');
    const infoCabang = document.getElementById('pickup_info_' + index);
    const wrapDelivery = document.getElementById('delivery_wrap_' + index);
    const textarea = wrapDelivery.querySelector('textarea');

    if (methodPickup && methodPickup.checked) {
        infoCabang.style.display = 'flex';
        wrapDelivery.style.display = 'none';
        if (textarea) textarea.value = '';
    } else {
        infoCabang.style.display = 'none';
        wrapDelivery.style.display = 'block';
    }
}
</script>

<?= view('partials/footer') ?>