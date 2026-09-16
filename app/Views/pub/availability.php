<?php
helper('image');
/**
 * @var array|null $items
 * @var array|null $selectedItem
 * @var array $gallery
 * @var array $branches
 * @var string|null $prefStartAt
 * @var string|null $prefEndAt
 * @var int $prefQty
 */
$mainImg = $selectedItem
    ? (!empty($gallery) ? item_image_url($gallery[0]['file_path'], 'item-' . $selectedItem['id']) : item_image_url(null, 'item-' . $selectedItem['id']))
    : null;
?>
<?= view('partials/header', ['title' => 'Cek Ketersediaan']) ?>

<style>
/* CSS Ringkasan Booking taruh di sini */

.booking-summary-card {
    margin-top: 20px;
    padding: 18px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--surface, #fff);
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}

.summary-label {
    display: block;
    margin-bottom: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: .06em;
    color: var(--muted);
}

.summary-header h4 {
    margin: 0;
    font-size: 1rem;
}

.summary-status {
    padding: 5px 10px;
    border-radius: 999px;
    background: var(--line);
    color: var(--muted);
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
}

.summary-time {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 10px;
    background: var(--bg, #f7f7f7);
}

.summary-title {
    display: block;
    margin-bottom: 5px;
    font-size: 0.75rem;
    color: var(--muted);
}

.summary-time-item strong {
    display: block;
    font-size: 0.9rem;
}

.summary-arrow {
    color: var(--muted);
}

.summary-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 10px;
}

.summary-detail {
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 9px;
}

.summary-detail strong {
    font-size: 0.9rem;
}

@media (max-width: 600px) {
    .summary-time {
        grid-template-columns: 1fr;
    }

    .summary-arrow {
        display: none;
    }

    .summary-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb">
            <a href="<?= base_url('/') ?>">Beranda</a> /
            <?php if ($selectedItem): ?>
            <a href="<?= base_url('/item/' . $selectedItem['id']) ?>"><?= esc($selectedItem['name']) ?></a> /
            <?php endif; ?>
            Cek Ketersediaan
        </div>
        <h1>Cek Ketersediaan &amp; Estimasi Harga</h1>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="detail-grid">
            <div>
                <?php if ($selectedItem): ?>
                <div class="detail-media"
                    style="background-image:url('<?= esc($mainImg) ?>');background-size:cover;background-position:center;">
                </div>

                <?php if (count($gallery) > 1): ?>
                <div style="display:flex;gap:10px;margin-top:12px;">
                    <?php foreach ($gallery as $g): ?>
                    <div
                        style="width:70px;height:56px;border-radius:4px;background-image:url('<?= esc(item_image_url($g['file_path'])) ?>');background-size:cover;background-position:center;border:1px solid var(--line);">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="detail-info" style="margin-top:24px;">
                    <span class="badge"><?= esc($selectedItem['item_type']) ?></span>
                    <h2 style="font-size:1.3rem;"><?= esc($selectedItem['name']) ?></h2>
                    <div class="detail-price">
                        Rp <?= number_format($selectedItem['base_price'], 0, ',', '.') ?>
                        <small>/ <?= esc($selectedItem['unit_label']) ?></small>
                    </div>
                </div>
                <?php else: ?>
                <p style="color:var(--muted);">Pilih item dari form di samping untuk mulai cek ketersediaan.</p>
                <?php endif; ?>
            </div>

            <div class="booking-card">
                <h3>Isi Detail Booking</h3>
                <form id="form-availability" onsubmit="return false;">

                    <?php if (!$selectedItem): ?>
                    <div class="field-group">
                        <label for="item_id">Item</label>
                        <select id="item_id" name="item_id" required
                            onchange="window.location.href='<?= base_url('/availability') ?>?item_id=' + this.value">
                            <option value="">-- Pilih Item --</option>
                            <?php foreach ($items as $it): ?>
                            <option value="<?= esc($it['id']) ?>"><?= esc($it['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" id="item_id" value="<?= esc($selectedItem['id']) ?>">

                    <div class="field-group">
                        <label for="start_at">Tanggal/Jam Mulai</label>
                        <input type="datetime-local" id="start_at" name="start_at"
                            value="<?= esc($prefStartAt ?? '') ?>" required>
                    </div>
                    <div class="field-group">
                        <label for="end_at">Tanggal/Jam Selesai</label>
                        <input type="datetime-local" id="end_at" name="end_at" value="<?= esc($prefEndAt ?? '') ?>"
                            required>
                    </div>
                    <div class="field-group">
                        <label for="qty">Jumlah</label>
                        <input type="number" id="qty" name="qty" min="1" value="<?= esc($prefQty ?: 1) ?>">
                    </div>

                    <div class="field-group">
                        <label for="branch_id">Cabang</label>
                        <select id="branch_id" name="branch_id" required>
                            <option value="">-- Pilih Cabang --</option>

                            <?php foreach ($branches as $branch): ?>
                            <option value="<?= esc($branch['id']) ?>">
                                <?= esc($branch['branch_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Ringkasan Booking -->
                    <div class="booking-summary-card">

                        <div class="summary-header">
                            <div>
                                <span class="summary-label">RINGKASAN BOOKING</span>
                                <h4>Detail Pilihan Anda</h4>
                            </div>

                            <span id="availability-status" class="summary-status">
                                Belum dicek
                            </span>
                        </div>

                        <div class="summary-time">

                            <div class="summary-time-item">
                                <span class="summary-title">Mulai</span>
                                <strong id="summary-start">—</strong>
                            </div>

                            <div class="summary-arrow">→</div>

                            <div class="summary-time-item">
                                <span class="summary-title">Selesai</span>
                                <strong id="summary-end">—</strong>
                            </div>

                        </div>

                        <div class="summary-details">

                            <div class="summary-detail">
                                <span class="summary-title">Jumlah</span>
                                <strong id="summary-qty">1 unit</strong>
                            </div>

                            <div class="summary-detail">
                                <span class="summary-title">Cabang</span>
                                <strong id="summary-branch">—</strong>
                            </div>

                        </div>

                        <div id="avail-result" class="summary-result"></div>

                        <div id="branch-list" class="summary-extra"></div>

                        <div id="buffer-info" class="summary-extra"></div>

                        <div id="alt-slots" class="summary-extra"></div>

                        <div id="price-estimate" class="summary-price"></div>

                    </div>

                    <div id="avail-result"></div>
                    <div id="branch-list"></div>
                    <div id="buffer-info"></div>
                    <div id="alt-slots"></div>
                    <div id="price-estimate"></div>

                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px;">
                        <button type="button" id="btn-cek" class="btn btn-outline"
                            style="width:100%;justify-content:center;">Cek Ketersediaan</button>
                        <button type="button" id="btn-tambah" class="btn btn-primary" disabled
                            style="width:100%;justify-content:center;">Tambah ke Keranjang</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</section>

<?php if ($selectedItem): ?>
<script>
const ITEM_ID = document.getElementById('item_id').value;
const CEK_URL = '<?= base_url('/availability/cek/') ?>' + ITEM_ID;
const CART_URL = '<?= base_url('/cart/tambah') ?>';
const CSRF_NAME = '<?= csrf_token() ?>';
const CSRF_HASH = '<?= csrf_hash() ?>';

const elStart = document.getElementById('start_at');
const elEnd = document.getElementById('end_at');
const elQty = document.getElementById('qty');
const elBranch = document.getElementById('branch_id');

const resultBox = document.getElementById('avail-result');
const branchListBox = document.getElementById('branch-list');
const bufferBox = document.getElementById('buffer-info');
const altBox = document.getElementById('alt-slots');
const priceBox = document.getElementById('price-estimate');
const btnTambah = document.getElementById('btn-tambah');

const statusBox = document.getElementById('availability-status');
const summaryStart = document.getElementById('summary-start');
const summaryEnd = document.getElementById('summary-end');
const summaryQty = document.getElementById('summary-qty');
const summaryBranch = document.getElementById('summary-branch');

/*
|--------------------------------------------------------------------------
| Simpan jadwal awal
|--------------------------------------------------------------------------
*/
let originalStartAt = elStart.value;
let originalEndAt = elEnd.value;

let selectedAlternative = false;
let isChecking = false;


/*
|--------------------------------------------------------------------------
| Format tanggal & waktu
|--------------------------------------------------------------------------
*/
function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}


/*
|--------------------------------------------------------------------------
| Update Ringkasan Booking
|--------------------------------------------------------------------------
*/
function updateBookingSummary() {

    // Mulai
    summaryStart.textContent = formatDateTime(elStart.value);

    // Selesai
    summaryEnd.textContent = formatDateTime(elEnd.value);

    // Jumlah
    const qty = Math.max(1, Number(elQty.value) || 1);
    summaryQty.textContent = qty + ' unit';

    // Cabang
    const selectedBranch =
        elBranch.options[elBranch.selectedIndex];

    if (selectedBranch && selectedBranch.value) {
        summaryBranch.textContent =
            selectedBranch.textContent.trim();
    } else {
        summaryBranch.textContent = '—';
    }
}


/*
|--------------------------------------------------------------------------
| Update status Ringkasan
|--------------------------------------------------------------------------
*/
function updateSummaryStatus(status) {

    if (!statusBox) {
        return;
    }

    statusBox.className = 'summary-status';

    if (status === 'available') {

        statusBox.textContent = 'Tersedia';
        statusBox.classList.add('status-available');

    } else if (status === 'limited') {

        statusBox.textContent = 'Terbatas';
        statusBox.classList.add('status-limited');

    } else if (status === 'unavailable') {

        statusBox.textContent = 'Tidak tersedia';
        statusBox.classList.add('status-unavailable');

    } else {

        statusBox.textContent = 'Belum dicek';
    }
}


/*
|--------------------------------------------------------------------------
| Reset hasil pengecekan
|--------------------------------------------------------------------------
*/
function resetAvailabilityResult() {

    resultBox.innerHTML = '';
    branchListBox.innerHTML = '';
    bufferBox.innerHTML = '';
    altBox.innerHTML = '';
    priceBox.innerHTML = '';

    btnTambah.disabled = true;

    updateSummaryStatus(null);
}


/*
|--------------------------------------------------------------------------
| Cek Ketersediaan
|--------------------------------------------------------------------------
*/
async function checkAvailability() {

    const startAt = elStart.value;
    const endAt = elEnd.value;
    const qty = Math.max(1, Number(elQty.value) || 1);
    const branchId = elBranch.value;

    // Update ringkasan setiap kali pengecekan
    updateBookingSummary();

    // Reset hasil sebelumnya
    resultBox.innerHTML = '';
    branchListBox.innerHTML = '';
    bufferBox.innerHTML = '';
    altBox.innerHTML = '';
    priceBox.innerHTML = '';

    btnTambah.disabled = true;

    updateSummaryStatus(null);

    /*
    |--------------------------------------------------------------------------
    | Validasi tanggal
    |--------------------------------------------------------------------------
    */

    if (!startAt || !endAt) {

        resultBox.innerHTML =
            '<div class="avail-note avail-warn">' +
            'Isi tanggal mulai dan selesai terlebih dahulu.' +
            '</div>';

        return;
    }

    if (new Date(startAt) >= new Date(endAt)) {

        resultBox.innerHTML =
            '<div class="avail-note avail-warn">' +
            'Tanggal selesai harus setelah tanggal mulai.' +
            '</div>';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi cabang
    |--------------------------------------------------------------------------
    */

    if (!branchId) {

        resultBox.innerHTML =
            '<div class="avail-note avail-warn">' +
            'Pilih cabang terlebih dahulu.' +
            '</div>';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Cegah request ganda
    |--------------------------------------------------------------------------
    */

    if (isChecking) {
        return;
    }

    isChecking = true;

    resultBox.innerHTML =
        '<div class="avail-note">' +
        'Memeriksa ketersediaan...' +
        '</div>';

    try {

        const params = new URLSearchParams({
            start_at: startAt,
            end_at: endAt,
            qty: qty,
            branch_id: branchId
        });

        const res = await fetch(
            CEK_URL + '?' + params.toString()
        );

        const data = await res.json();

        /*
        |--------------------------------------------------------------------------
        | Status utama
        |--------------------------------------------------------------------------
        */

        const clsMap = {
            available: 'avail-ok',
            limited: 'avail-warn',
            unavailable: 'avail-warn'
        };

        resultBox.innerHTML =
            `<div class="avail-note ${
                clsMap[data.status] || 'avail-warn'
            }">${data.message || 'Status ketersediaan tidak diketahui.'}</div>`;

        /*
        |--------------------------------------------------------------------------
        | Status di Ringkasan Booking
        |--------------------------------------------------------------------------
        */

        updateSummaryStatus(data.status);

        /*
        |--------------------------------------------------------------------------
        | Buffer
        |--------------------------------------------------------------------------
        */

        if (data.buffer_note) {

            bufferBox.innerHTML =
                `<div class="avail-note">${data.buffer_note}</div>`;
        }

        /*
        |--------------------------------------------------------------------------
        | Ketersediaan per cabang
        |
        | Dibuat lebih sederhana.
        | Tidak ada tombol "Pilih" lagi karena cabang
        | sudah dipilih melalui dropdown.
        |--------------------------------------------------------------------------
        */

        if (data.branches && data.branches.length) {

            const selectedBranchData = data.branches.find(
                b => String(b.branch_id) === String(branchId)
            );

            if (selectedBranchData) {

                const branchClsMap = {
                    available: 'avail-ok',
                    limited: 'avail-warn',
                    unavailable: 'avail-warn'
                };

                branchListBox.innerHTML =
                    `<div style="margin-top:12px;">
                        <div class="avail-note ${
                            branchClsMap[selectedBranchData.status] ||
                            'avail-warn'
                        }">
                            <strong>${selectedBranchData.branch_name}</strong>
                            <br>
                            <span>${selectedBranchData.message}</span>
                        </div>
                    </div>`;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Alternatif Jadwal
        |--------------------------------------------------------------------------
        */

        if (data.alternatives && data.alternatives.length) {

            let html =
                '<div style="margin-top:14px;">' +
                '<strong>Alternatif jadwal terdekat:</strong>' +
                '<div style="margin-top:8px;">';

            data.alternatives.forEach(a => {

                html += `
                    <div
                        style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                            gap:10px;
                            padding:10px 0;
                            border-bottom:1px solid var(--line);
                        "
                    >
                        <div>
                            <div style="font-size:0.9rem;">
                                ${formatDateTime(a.start_at)}
                            </div>

                            <div style="
                                color:var(--muted);
                                font-size:0.8rem;
                                margin-top:2px;
                            ">
                                sampai ${formatDateTime(a.end_at)}
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline btn-alt"
                            data-start="${a.start_at}"
                            data-end="${a.end_at}"
                            style="
                                padding:5px 10px;
                                font-size:0.8rem;
                                white-space:nowrap;
                            "
                        >
                            Pakai
                        </button>
                    </div>
                `;
            });

            html += '</div></div>';

            altBox.innerHTML = html;

            /*
            |--------------------------------------------------------------------------
            | Tombol Pakai Jadwal Alternatif
            |--------------------------------------------------------------------------
            */

            altBox.querySelectorAll('.btn-alt').forEach(btn => {

                btn.addEventListener('click', function() {

                    elStart.value = this.dataset.start;
                    elEnd.value = this.dataset.end;

                    selectedAlternative = true;

                    updateBookingSummary();

                    checkAvailability();
                });
            });

        } else if (data.status !== 'available') {

            altBox.innerHTML =
                '<div class="avail-note" style="margin-top:10px;">' +
                'Tidak ada alternatif jadwal terdekat yang tersedia.' +
                '</div>';
        }

        /*
        |--------------------------------------------------------------------------
        | Estimasi Harga
        |--------------------------------------------------------------------------
        */

        if (data.price_estimate) {

            const p = data.price_estimate;

            priceBox.innerHTML = `
                <div
                    class="alert-box alert-info"
                    style="margin-top:14px;"
                >
                    Estimasi harga:
                    <strong>
                        Rp ${Number(p.subtotal).toLocaleString('id-ID')}
                    </strong>

                    <small>
                        (belum final, dihitung ulang saat checkout)
                    </small>
                </div>
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | Aktifkan tombol tambah ke keranjang
        |--------------------------------------------------------------------------
        */

        if (data.status === 'available') {

            btnTambah.disabled = false;
        }

    } catch (error) {

        console.error(error);

        resultBox.innerHTML =
            '<div class="avail-note avail-warn">' +
            'Gagal memeriksa ketersediaan. Coba lagi.' +
            '</div>';

        updateSummaryStatus(null);

        btnTambah.disabled = true;

    } finally {

        isChecking = false;
    }
}


/*
|--------------------------------------------------------------------------
| Tombol Cek Ketersediaan
|--------------------------------------------------------------------------
*/
document
    .getElementById('btn-cek')
    .addEventListener('click', function() {

        checkAvailability();
    });


/*
|--------------------------------------------------------------------------
| Perubahan tanggal mulai
|--------------------------------------------------------------------------
*/
elStart.addEventListener('input', function() {

    // Kalau user mengubah manual,
    // jadwal ini dianggap sebagai jadwal utama.
    originalStartAt = this.value;
    originalEndAt = elEnd.value;

    selectedAlternative = false;

    resetAvailabilityResult();
    updateBookingSummary();
});


/*
|--------------------------------------------------------------------------
| Perubahan tanggal selesai
|--------------------------------------------------------------------------
*/
elEnd.addEventListener('input', function() {

    originalStartAt = elStart.value;
    originalEndAt = this.value;

    selectedAlternative = false;

    resetAvailabilityResult();
    updateBookingSummary();
});


/*
|--------------------------------------------------------------------------
| Perubahan jumlah
|--------------------------------------------------------------------------
*/
elQty.addEventListener('input', function() {

    updateBookingSummary();

    // Hasil lama tidak lagi dianggap valid
    resetAvailabilityResult();
});


/*
|--------------------------------------------------------------------------
| Perubahan cabang
|--------------------------------------------------------------------------
|
| Kalau sebelumnya memakai jadwal alternatif,
| ketika cabang diganti kita kembali ke jadwal awal.
|--------------------------------------------------------------------------
*/
elBranch.addEventListener('change', function() {

    if (selectedAlternative) {

        elStart.value = originalStartAt;
        elEnd.value = originalEndAt;

        selectedAlternative = false;
    }

    updateBookingSummary();

    resetAvailabilityResult();
});


/*
|--------------------------------------------------------------------------
| Tambah ke Keranjang
|--------------------------------------------------------------------------
*/
document
    .getElementById('btn-tambah')
    .addEventListener('click', async function() {

        btnTambah.disabled = true;
        btnTambah.textContent = 'Menambahkan...';

        const startAt = elStart.value;
        const endAt = elEnd.value;
        const qty = Math.max(1, Number(elQty.value) || 1);
        const branchId = elBranch.value;

        /*
        |--------------------------------------------------------------------------
        | Validasi sebelum kirim
        |--------------------------------------------------------------------------
        */

        if (!startAt || !endAt || !branchId) {

            resultBox.innerHTML =
                '<div class="avail-note avail-warn">' +
                'Lengkapi detail booking terlebih dahulu.' +
                '</div>';

            btnTambah.textContent = 'Tambah ke Keranjang';
            btnTambah.disabled = false;

            return;
        }

        try {

            const bodyObj = {
                catalog_item_id: ITEM_ID,
                start_at: startAt,
                end_at: endAt,
                qty: qty,
                branch_id: branchId,
                [CSRF_NAME]: CSRF_HASH
            };

            const body = new URLSearchParams(bodyObj);

            const res = await fetch(CART_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body
            });

            /*
            |--------------------------------------------------------------------------
            | Jika controller melakukan redirect
            |--------------------------------------------------------------------------
            */

            if (res.redirected) {

                window.location.href = res.url;
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Ambil response JSON
            |--------------------------------------------------------------------------
            */

            const data = await res.json().catch(() => null);

            if (data && data.success) {

                window.location.href =
                    '<?= base_url('/cart') ?>';

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Gagal
            |--------------------------------------------------------------------------
            */

            resultBox.innerHTML =
                '<div class="avail-note avail-warn">' +
                (
                    data?.message ||
                    'Ketersediaan berubah, silakan cek ulang.'
                ) +
                '</div>';

            btnTambah.textContent =
                'Tambah ke Keranjang';

            /*
            | Cek ulang ketersediaan
            */
            await checkAvailability();

        } catch (error) {

            console.error(error);

            resultBox.innerHTML =
                '<div class="avail-note avail-warn">' +
                'Gagal menambah ke keranjang. Coba lagi.' +
                '</div>';

            btnTambah.textContent =
                'Tambah ke Keranjang';

            btnTambah.disabled = false;
        }
    });


/*
|--------------------------------------------------------------------------
| Saat halaman pertama kali dibuka
|--------------------------------------------------------------------------
*/
window.addEventListener('DOMContentLoaded', function() {

    // Isi Ringkasan Booking
    updateBookingSummary();

    // Kalau tanggal sudah otomatis terisi dari halaman item,
    // langsung cek ketersediaan.
    if (elStart.value && elEnd.value && elBranch.value) {

        checkAvailability();
    }
});
</script>
<?php endif; ?>

<?= view('partials/footer') ?>