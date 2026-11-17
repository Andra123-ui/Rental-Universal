<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
helper('idhash');
$isEdit = !empty($row);
$action = $isEdit
    ? site_url('backoffice/kategori/ubah/' . id_encode($row['id']))
    : site_url('backoffice/kategori/simpan');
$val = static fn (string $k, $d = '') => esc((string) old($k, $row[$k] ?? $d), 'attr');
?>

<div class="page-head">
    <h1><?= esc($title) ?></h1>
    <a href="<?= site_url('backoffice/kategori') ?>" class="btn btn-ghost">&larr; Kembali</a>
</div>

<div class="panel" style="max-width:700px;">
    <form action="<?= $action ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="full">
                <label for="name">Nama kategori</label>
                <input type="text" id="name" name="name" required maxlength="120" value="<?= $val('name') ?>"
                    placeholder="mis. Mobil">
            </div>

            <div>
                <label for="parent_id">Induk (opsional)</label>
                <select id="parent_id" name="parent_id">
                    <option value="">— Kategori utama —</option>
                    <?php foreach ($parents as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= (string) old('parent_id', $row['parent_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
                        <?= esc(str_repeat('— ', (int) $p['depth']) . $p['name']) ?><?= (int) $p['is_active'] ? '' : ' (nonaktif)' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Diri sendiri dan turunannya tidak tampil di sini agar tidak membentuk loop.</div>
            </div>

            <div>
                <label for="sort_order">Urutan tampil</label>
                <input type="number" id="sort_order" name="sort_order" min="0" value="<?= $val('sort_order') ?>"
                    placeholder="otomatis">
            </div>

            <div class="full">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" maxlength="140" value="<?= $val('slug') ?>"
                    placeholder="otomatis dari nama" pattern="[a-z0-9]+(-[a-z0-9]+)*">
                <div class="hint">
                    Huruf kecil, angka, dan tanda hubung. Dipakai di link katalog (<code>?kategori=slug</code>).
                    <?php if ($isEdit): ?>Mengubah slug membuat link lama tidak berlaku.<?php endif; ?>
                </div>
            </div>

            <div class="full">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" rows="3"
                    maxlength="2000"><?= esc((string) old('description', $row['description'] ?? '')) ?></textarea>
            </div>

            <div>
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active">
                    <option value="1"
                        <?= (string) old('is_active', $row['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif
                    </option>
                    <option value="0"
                        <?= (string) old('is_active', $row['is_active'] ?? '1') === '0' ? 'selected' : '' ?>>Nonaktif
                    </option>
                </select>
            </div>
        </div>

        <?php if ($isEdit && ($itemCount > 0 || $childCount > 0)): ?>
        <p class="hint" style="margin-top:14px;">
            Kategori ini dipakai <?= (int) $itemCount ?> produk dan punya <?= (int) $childCount ?> subkategori.
            Menonaktifkannya tidak menghapus apa pun.
        </p>
        <?php endif; ?>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?= site_url('backoffice/kategori') ?>" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>

<?php if (!$isEdit): ?>
<script>
(function() {
    const name = document.getElementById('name');
    const slug = document.getElementById('slug');
    let touched = false;
    slug.addEventListener('input', function() {
        touched = true;
    });
    name.addEventListener('input', function() {
        if (touched) return;
        slug.value = name.value.toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>