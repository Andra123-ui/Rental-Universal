<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
helper(['idhash', 'image']);
$isEdit = !empty($item);
$hash   = $isEdit ? id_encode($item['id']) : '';
$action = $isEdit
    ? site_url('backoffice/produk/ubah/' . $hash)
    : site_url('backoffice/produk/simpan');

$v   = fn (string $k, $d = '') => esc((string) old($k, $item[$k] ?? $d), 'attr');
$sel = fn (string $k, $val, $d = '') => (string) old($k, $item[$k] ?? $d) === (string) $val ? 'selected' : '';

$trim = static fn ($n) => $n === null || $n === '' ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
$price    = old('base_price', isset($item['base_price']) ? (int) $item['base_price'] : '');
$deposit  = old('deposit_amount', isset($item['deposit_amount']) ? (int) $item['deposit_amount'] : '');
$minDur   = old('min_duration', $trim($item['min_duration'] ?? null));
$maxDur   = old('max_duration', $trim($item['max_duration'] ?? null));
$capacity = old('default_capacity', $trim($item['default_capacity'] ?? null));

$curType = (string) old('item_type', $item['item_type'] ?? 'ITEM');
?>

<style>
.sec-title {
    grid-column: 1 / -1;
    margin: 10px 0 -4px;
    padding-top: 18px;
    border-top: 1px solid var(--line);
    font-size: .95rem;
    font-weight: 600;
    color: var(--accent);
}

.sec-title:first-child {
    border-top: 0;
    padding-top: 0;
    margin-top: 0;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.media-card {
    border: 1px solid var(--line);
    border-radius: var(--radius-sm);
    padding: 10px;
    background: #fff;
}

.media-card img {
    width: 100%;
    height: 130px;
    object-fit: cover;
    border-radius: 8px;
    display: block;
    background: var(--paper);
}

.media-card .m-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 8px;
}

.media-card input[type=text] {
    height: 34px;
    font-size: .8rem;
    margin-top: 8px;
}

.btn[aria-disabled=true] {
    opacity: .45;
    cursor: not-allowed;
}
</style>

<div class="page-head">
    <h1><?= esc($title) ?></h1>
    <a href="<?= site_url('backoffice/produk') ?>" class="btn btn-ghost">&larr; Kembali</a>
</div>

<div class="panel" style="max-width:860px;">
    <form action="<?= $action ?>" method="post" id="produk-form">
        <?= csrf_field() ?>
        <div class="form-grid">

            <div class="sec-title">Identitas</div>

            <div class="full">
                <label for="name">Nama produk</label>
                <input type="text" id="name" name="name" required maxlength="180" value="<?= $v('name') ?>">
            </div>

            <div>
                <label for="sku">SKU (opsional)</label>
                <input type="text" id="sku" name="sku" maxlength="60" value="<?= $v('sku') ?>"
                    placeholder="mis. VHC-003">
                <div class="hint">Unik. Dipakai sebagai awalan kode unit.</div>
            </div>
            <div>
                <label for="item_type">Tipe</label>
                <select id="item_type" name="item_type" required>
                    <?php foreach ($itemTypes as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $sel('item_type', $k, 'ITEM') ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $sel('category_id', $c['id']) ?>>
                        <?= esc(str_repeat('— ', (int) $c['depth']) . $c['name']) ?><?= (int) $c['is_active'] ? '' : ' (nonaktif)' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="jenis_id">Jenis</label>
                <select id="jenis_id" name="jenis_id">
                    <option value="">Tanpa jenis</option>
                    <?php foreach ($jenisList as $j): ?>
                    <option value="<?= (int) $j['id'] ?>" data-kategori="<?= (int) $j['category_id'] ?>"
                        <?= $sel('jenis_id', $j['id']) ?>>
                        <?= esc($j['name']) ?><?= $j['is_active'] ? '' : ' (nonaktif)' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="full">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" rows="5"
                    maxlength="5000"><?= esc((string) old('description', $item['description'] ?? '')) ?></textarea>
            </div>

            <div class="sec-title">Model sewa & stok</div>

            <div>
                <label for="rental_mode">Mode sewa</label>
                <select id="rental_mode" name="rental_mode" required>
                    <?php foreach ($rentalModes as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $sel('rental_mode', $k, 'SERIALIZED') ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint" id="mode-hint"></div>
            </div>
            <div>
                <label for="track_stock">Lacak stok / unit</label>
                <select id="track_stock" name="track_stock">
                    <option value="1" <?= $sel('track_stock', '1', '1') ?>>Ya</option>
                    <option value="0" <?= $sel('track_stock', '0', '1') ?>>Tidak</option>
                </select>
            </div>

            <div id="cap-wrap">
                <label for="default_capacity">Kapasitas default per unit</label>
                <input type="number" id="default_capacity" name="default_capacity" min="1" step="1"
                    value="<?= esc((string) $capacity, 'attr') ?>" placeholder="mis. 20 peserta">
                <div class="hint">Hanya untuk mode Per kapasitas.</div>
            </div>

            <div class="sec-title">Harga & durasi</div>

            <div>
                <label for="base_price">Harga dasar (Rp)</label>
                <input type="number" id="base_price" name="base_price" min="0" step="1000" required
                    value="<?= esc((string) $price, 'attr') ?>">
                <?php if ($isEdit): ?>
                <div class="hint">Perubahan harga tidak mengubah booking yang sudah dibuat (harga dibekukan di booking).
                </div>
                <?php endif; ?>
            </div>
            <div>
                <label for="pricing_unit">Satuan harga</label>
                <select id="pricing_unit" name="pricing_unit" required>
                    <?php foreach ($pricingUnits as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $sel('pricing_unit', $k, 'DAY') ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="unit_label">Label satuan (tampil di harga)</label>
                <input type="text" id="unit_label" name="unit_label" required maxlength="30"
                    value="<?= $v('unit_label', 'hari') ?>" placeholder="hari / malam / sesi / paket">
            </div>
            <div></div>

            <div>
                <label for="min_duration">Durasi minimum (opsional)</label>
                <input type="number" id="min_duration" name="min_duration" min="0" step="0.5"
                    value="<?= esc((string) $minDur, 'attr') ?>">
            </div>
            <div>
                <label for="max_duration">Durasi maksimum (opsional)</label>
                <input type="number" id="max_duration" name="max_duration" min="0" step="0.5"
                    value="<?= esc((string) $maxDur, 'attr') ?>">
            </div>

            <div class="sec-title">Buffer</div>

            <div>
                <label for="buffer_before_minutes">Buffer sebelum (menit)</label>
                <input type="number" id="buffer_before_minutes" name="buffer_before_minutes" min="0" max="1440" step="5"
                    value="<?= $v('buffer_before_minutes', '0') ?>">
                <div class="hint">Persiapan sebelum sewa mulai.</div>
            </div>
            <div>
                <label for="buffer_after_minutes">Buffer sesudah (menit)</label>
                <input type="number" id="buffer_after_minutes" name="buffer_after_minutes" min="0" max="1440" step="5"
                    value="<?= $v('buffer_after_minutes', '0') ?>">
                <div class="hint">Cleaning / reset / perjalanan setelah sewa.</div>
            </div>

            <div class="sec-title">Deposit</div>

            <div>
                <label for="deposit_required">Deposit/jaminan</label>
                <select id="deposit_required" name="deposit_required" required>
                    <option value="0" <?= $sel('deposit_required', '0', '0') ?>>Tidak ada</option>
                    <option value="1" <?= $sel('deposit_required', '1', '0') ?>>Ada</option>
                </select>
            </div>
            <div>
                <label for="deposit_amount">Nominal deposit (Rp)</label>
                <input type="number" id="deposit_amount" name="deposit_amount" min="0" step="1000"
                    value="<?= esc((string) $deposit, 'attr') ?>">
            </div>

            <div class="sec-title">Visibilitas</div>

            <div>
                <label for="allow_online_booking">Bisa dipesan online</label>
                <select id="allow_online_booking" name="allow_online_booking" required>
                    <option value="1" <?= $sel('allow_online_booking', '1', '1') ?>>Ya (tampil di website)</option>
                    <option value="0" <?= $sel('allow_online_booking', '0', '1') ?>>Tidak (hanya booking manual)
                    </option>
                </select>
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="ACTIVE" <?= $sel('status', 'ACTIVE', 'ACTIVE') ?>>Aktif</option>
                    <option value="INACTIVE" <?= $sel('status', 'INACTIVE', 'ACTIVE') ?>>Nonaktif (disembunyikan)
                    </option>
                </select>
            </div>

            <!-- CABANG & UNIT -->
            <div class="full" id="cabang-unit">
                <div class="sec-title" style="margin-top:0;">Distribusi cabang & unit</div>
                <p id="unit-hint" style="margin:10px 0 15px;font-size:.85rem;color:var(--muted);">
                    Tentukan di cabang mana produk tersedia dan berapa unitnya. Menambah jumlah membuat unit baru;
                    mengurangi hanya menonaktifkan unit yang tidak sedang dipakai booking.
                </p>
                <p id="unit-na" style="margin:10px 0 15px;font-size:.85rem;color:var(--muted);" hidden>
                    Tipe/mode ini tidak memakai unit fisik (paket memakai komponen paket; mode tanpa batas tidak melacak
                    unit).
                </p>

                <div id="branch-units-container">
                    <?php if (!empty($selectedBranches)): foreach ($selectedBranches as $sb): ?>
                    <div class="branch-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <select name="branches[]" style="flex:1;">
                            <option value="">-- Pilih Cabang --</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"
                                <?= ((int) $sb['branch_id'] === (int) $b['id']) ? 'selected' : '' ?>>
                                <?= esc($b['branch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="units[]" min="1" placeholder="Jumlah Unit" style="flex:1;"
                            value="<?= esc($sb['total_unit']) ?>">
                        <button type="button" class="btn btn-ghost btn-sm remove-row btn-danger">Hapus</button>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="branch-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <select name="branches[]" style="flex:1;">
                            <option value="">-- Pilih Cabang --</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= esc($b['branch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="units[]" min="1" placeholder="Jumlah Unit" style="flex:1;">
                        <button type="button" class="btn btn-ghost btn-sm remove-row btn-danger">Hapus</button>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-ghost btn-sm" id="add-branch-row" style="margin-top:5px;">+ Tambah
                    Cabang Lain</button>
            </div>
        </div>

        <div class="actions" style="flex-wrap:wrap;">
            <button type="submit" name="save_action" value="save" class="btn btn-primary">Simpan</button>
            <button type="submit" name="save_action" value="resource" class="btn btn-ghost">Simpan & tambah
                resource</button>

            <?php if ($priceUrl): ?>
            <a href="<?= esc($priceUrl) ?>" class="btn btn-ghost">Manage price</a>
            <?php else: ?>
            <span class="btn btn-ghost" aria-disabled="true" title="Halaman aturan harga (PRICE-01) belum dibuat">Manage
                price</span>
            <?php endif; ?>

            <?php if ($curType === 'PACKAGE'): ?>
            <?php if ($packageUrl): ?>
            <a href="<?= esc($packageUrl) ?>" class="btn btn-ghost">Manage package</a>
            <?php else: ?>
            <span class="btn btn-ghost" aria-disabled="true" title="Package builder (PKG-01) belum dibuat">Manage
                package</span>
            <?php endif; ?>
            <?php endif; ?>

            <a href="<?= site_url('backoffice/produk') ?>" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>

<!-- MEDIA -->
<div class="panel" id="media" style="max-width:860px;margin-top:22px;">
    <h3 class="panel-title">Media</h3>

    <?php if (!$isEdit): ?>
    <p class="muted">Simpan produk terlebih dahulu, lalu foto bisa diunggah di sini.</p>
    <?php else: ?>
    <?php $mediaUrl = site_url('backoffice/produk/media/' . $hash); ?>

    <?php if (empty($media)): ?>
    <p class="muted">Belum ada foto. Produk tanpa foto tampil dengan gambar placeholder.</p>
    <?php else: ?>
    <div class="media-grid">
        <?php foreach ($media as $i => $m): ?>
        <div class="media-card">
            <img src="<?= esc(item_image_url($m['file_path'], 'item-' . $item['id']), 'attr') ?>"
                alt="<?= esc($m['alt_text'] ?? '', 'attr') ?>" loading="lazy">
            <div style="margin-top:8px;">
                <?php if ((int) $m['is_primary'] === 1): ?>
                <span class="badge badge-ok">Foto utama</span>
                <?php endif; ?>
            </div>

            <div class="m-actions">
                <?php foreach ([['up', '▲', $i === 0], ['down', '▼', $i === count($media) - 1]] as [$act, $lbl, $off]): ?>
                <form action="<?= $mediaUrl ?>" method="post" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $act ?>">
                    <input type="hidden" name="media_id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm"
                        <?= $off ? 'disabled' : '' ?>><?= $lbl ?></button>
                </form>
                <?php endforeach; ?>

                <?php if ((int) $m['is_primary'] !== 1): ?>
                <form action="<?= $mediaUrl ?>" method="post" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="primary">
                    <input type="hidden" name="media_id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm">Jadikan utama</button>
                </form>
                <?php endif; ?>

                <form action="<?= $mediaUrl ?>" method="post" class="inline"
                    onsubmit="return confirm('Hapus foto ini?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="media_id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm btn-danger">Hapus</button>
                </form>
            </div>

            <form action="<?= $mediaUrl ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="alt">
                <input type="hidden" name="media_id" value="<?= (int) $m['id'] ?>">
                <input type="text" name="alt_text" maxlength="255" value="<?= esc($m['alt_text'] ?? '', 'attr') ?>"
                    placeholder="Teks alternatif (SEO/aksesibilitas)" onchange="this.form.submit()">
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form action="<?= $mediaUrl ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <label for="images">Tambah foto (JPG / PNG / WEBP, maks 3 MB per file, maks 12 foto)</label>
        <input type="file" id="images" name="images[]" multiple required accept=".jpg,.jpeg,.png,.webp"
            style="height:auto;padding:8px;">
        <div class="hint">Foto pertama otomatis jadi foto utama. Urutan bisa diubah dengan tombol ▲ ▼.</div>
        <div class="actions"><button type="submit" class="btn btn-primary btn-sm">Unggah</button></div>
    </form>
    <?php endif; ?>
</div>

<script>
(function() {
    const kat = document.getElementById('category_id');
    const jenis = document.getElementById('jenis_id');
    const depReq = document.getElementById('deposit_required');
    const depAmt = document.getElementById('deposit_amount');
    const type = document.getElementById('item_type');
    const mode = document.getElementById('rental_mode');
    const track = document.getElementById('track_stock');
    const capWrap = document.getElementById('cap-wrap');
    const capInp = document.getElementById('default_capacity');
    const hint = document.getElementById('mode-hint');
    const unitBox = document.getElementById('branch-units-container');
    const unitHint = document.getElementById('unit-hint');
    const unitNa = document.getElementById('unit-na');
    const addBtn = document.getElementById('add-branch-row');

    const HINTS = {
        SERIALIZED: 'Tiap unit punya identitas sendiri (mobil, kamera). Jumlah unit = stok.',
        QUANTITY: 'Stok berupa jumlah (kursi, tenda). Hanya untuk Barang.',
        CAPACITY: 'Satu unit punya kuota (peserta, tamu). Isi kapasitas default.',
        SCHEDULE: 'Ketersediaan ditentukan jadwal (villa, jasa).',
        UNLIMITED: 'Tidak ada batas stok; tidak membuat unit.'
    };

    function syncJenis(reset) {
        Array.from(jenis.options).forEach(function(o) {
            if (!o.value) return;
            const show = kat.value !== '' && o.dataset.kategori === kat.value;
            o.hidden = !show;
            o.disabled = !show;
        });
        if (reset) jenis.value = '';
        jenis.options[0].textContent = kat.value ? 'Tanpa jenis' : 'Pilih kategori dulu';
        jenis.disabled = kat.value === '';
    }

    function syncDeposit() {
        depAmt.disabled = depReq.value !== '1';
        if (depAmt.disabled) depAmt.value = '';
    }

    function syncMode() {
        const t = type.value;

        // Opsi mode yang boleh untuk tipe terpilih
        Array.from(mode.options).forEach(function(o) {
            let ok = true;
            if (t === 'PACKAGE') ok = (o.value === 'SCHEDULE' || o.value === 'UNLIMITED');
            if (o.value === 'QUANTITY' && t !== 'ITEM') ok = false;
            o.disabled = !ok;
            o.hidden = !ok;
        });
        if (mode.options[mode.selectedIndex].disabled) {
            mode.value = 'SCHEDULE';
        }

        const m = mode.value;
        hint.textContent = HINTS[m] || '';

        capWrap.hidden = (m !== 'CAPACITY');
        capInp.required = (m === 'CAPACITY');

        if (m === 'UNLIMITED') {
            track.value = '0';
            track.disabled = true;
        } else {
            track.disabled = false;
        }

        const noUnits = (t === 'PACKAGE' || m === 'UNLIMITED');
        unitBox.hidden = noUnits;
        addBtn.hidden = noUnits;
        unitHint.hidden = noUnits;
        unitNa.hidden = !noUnits;
    }

    kat.addEventListener('change', function() {
        syncJenis(true);
    });
    depReq.addEventListener('change', syncDeposit);
    type.addEventListener('change', syncMode);
    mode.addEventListener('change', syncMode);
    syncJenis(false);
    syncDeposit();
    syncMode();

    addBtn.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'branch-row';
        row.style.cssText = 'display:flex; gap:10px; margin-bottom:10px; align-items:center;';
        const optionsHtml = unitBox.querySelector('select').innerHTML;
        row.innerHTML = '<select name="branches[]" style="flex:1;">' + optionsHtml + '</select>' +
            '<input type="number" name="units[]" min="1" placeholder="Jumlah Unit" style="flex:1;">' +
            '<button type="button" class="btn btn-ghost btn-sm remove-row btn-danger">Hapus</button>';
        row.querySelector('select').value = '';
        unitBox.appendChild(row);
    });

    unitBox.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-row')) {
            e.target.closest('.branch-row').remove();
        }
    });
})();
</script>

<?= $this->endSection() ?>