<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
helper('idhash');
$isEdit = !empty($item);
$action = $isEdit
    ? site_url('backoffice/produk/ubah/' . id_encode($item['id']))
    : site_url('backoffice/produk/simpan');
$v = fn(string $k, $d = '') => esc((string) old($k, $item[$k] ?? $d), 'attr');
$sel = fn(string $k, $val, $d = '') => (string) old($k, $item[$k] ?? $d) === (string) $val ? 'selected' : '';
$price = old('base_price', isset($item['base_price']) ? (int) $item['base_price'] : '');
$deposit = old('deposit_amount', isset($item['deposit_amount']) ? (int) $item['deposit_amount'] : '');
?>

<div class="page-head">
    <h1><?= esc($title) ?></h1>
    <a href="<?= site_url('backoffice/produk') ?>" class="btn btn-ghost">&larr; Kembali</a>
</div>

<div class="panel" style="max-width:860px;">
    <form action="<?= $action ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="full">
                <label for="name">Nama produk</label>
                <input type="text" id="name" name="name" required maxlength="180" value="<?= $v('name') ?>">
            </div>

            <div>
                <label for="sku">SKU (opsional)</label>
                <input type="text" id="sku" name="sku" maxlength="60" value="<?= $v('sku') ?>"
                    placeholder="mis. VHC-003">
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
                    <option value="<?= (int) $c['id'] ?>" <?= $sel('category_id', $c['id']) ?>><?= esc($c['name']) ?>
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

            <div>
                <label for="rental_mode">Mode sewa</label>
                <select id="rental_mode" name="rental_mode" required>
                    <?php foreach ($rentalModes as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $sel('rental_mode', $k, 'SERIALIZED') ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="ACTIVE" <?= $sel('status', 'ACTIVE', 'ACTIVE') ?>>Aktif (tampil di katalog)</option>
                    <option value="INACTIVE" <?= $sel('status', 'INACTIVE', 'ACTIVE') ?>>Nonaktif (disembunyikan)
                    </option>
                </select>
            </div>

            <div>
                <label for="base_price">Harga dasar (Rp)</label>
                <input type="number" id="base_price" name="base_price" min="0" step="1000" required
                    value="<?= esc((string) $price, 'attr') ?>">
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

            <div class="full">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" rows="5"
                    maxlength="5000"><?= esc((string) old('description', $item['description'] ?? '')) ?></textarea>
            </div>

            <!-- SECTION CABANG & UNIT -->
            <div class="full" style="margin-top: 20px;">
                <hr style="border:0; border-top:1px solid #E3E8F2; margin:20px 0;">
                <h3 style="margin:0 0 10px; font-size:1rem; color:#1B3A8C;">Distribusi Cabang & Unit</h3>
                <p style="margin:0 0 15px; font-size:.85rem; color:#64748b;">
                    Tentukan di cabang mana produk ini tersedia dan berapa jumlah unitnya. (Kosongkan jika belum ada).
                </p>

                <div id="branch-units-container">
                    <?php 
                    if (!empty($selectedBranches)): 
                        foreach ($selectedBranches as $sb): 
                    ?>
                    <div class="branch-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <select name="branches[]" style="flex:1;" class="form-control">
                            <option value="">-- Pilih Cabang --</option>
                            <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($sb['branch_id'] == $b['id']) ? 'selected' : '' ?>>
                                <?= esc($b['branch_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="units[]" min="1" placeholder="Jumlah Unit" class="form-control"
                            style="flex:1;" value="<?= esc($sb['total_unit']) ?>">
                        <button type="button" class="btn btn-ghost btn-sm remove-row"
                            style="color:#b91c1c;">Hapus</button>
                    </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                    <div class="branch-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <select name="branches[]" style="flex:1;" class="form-control">
                            <option value="">-- Pilih Cabang --</option>
                            <?php if(!empty($branches)): foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= esc($b['branch_name']) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                        <input type="number" name="units[]" min="1" placeholder="Jumlah Unit" class="form-control"
                            style="flex:1;">
                        <button type="button" class="btn btn-ghost btn-sm remove-row"
                            style="color:#b91c1c;">Hapus</button>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-ghost btn-sm" id="add-branch-row" style="margin-top: 5px;">+ Tambah
                    Cabang Lain</button>
            </div>

        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?= site_url('backoffice/produk') ?>" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>

<script>
(function() {
    const kat = document.getElementById('category_id');
    const jenis = document.getElementById('jenis_id');
    const depReq = document.getElementById('deposit_required');
    const depAmt = document.getElementById('deposit_amount');

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

    kat.addEventListener('change', function() {
        syncJenis(true);
    });
    depReq.addEventListener('change', syncDeposit);
    syncJenis(false);
    syncDeposit();

    var container = document.getElementById('branch-units-container');
    var addBtn = document.getElementById('add-branch-row');

    addBtn.addEventListener('click', function() {
        var row = document.createElement('div');
        row.className = 'branch-row';
        row.style.cssText = 'display:flex; gap:10px; margin-bottom:10px; align-items:center;';

        var optionsHtml = container.querySelector('select').innerHTML;

        row.innerHTML = `
            <select name="branches[]" style="flex:1;" class="form-control">
                ${optionsHtml}
            </select>
            <input type="number" name="units[]" min="1" placeholder="Jumlah Unit" class="form-control" style="flex:1;">
            <button type="button" class="btn btn-ghost btn-sm remove-row" style="color:#b91c1c;">Hapus</button>
        `;
        container.appendChild(row);
    });

    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-row')) {
            e.target.closest('.branch-row').remove();
        }
    });
})();
</script>

<?= $this->endSection() ?>