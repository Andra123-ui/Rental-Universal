<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
helper('idhash');
$isEdit = !empty($row);
$action = $isEdit
    ? site_url('backoffice/jenis/ubah/' . id_encode($row['id']))
    : site_url('backoffice/jenis/simpan');
?>

<div class="page-head">
    <h1><?= esc($title) ?></h1>
    <a href="<?= site_url('backoffice/jenis') ?>" class="btn btn-ghost">&larr; Kembali</a>
</div>

<div class="panel" style="max-width:640px;">
    <form action="<?= $action ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="full">
                <label for="name">Nama jenis</label>
                <input type="text" id="name" name="name" required maxlength="120"
                    value="<?= esc(old('name', $row['name'] ?? ''), 'attr') ?>" placeholder="mis. Mobil">
            </div>
            <div>
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                        <?= (string) old('category_id', $row['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= esc($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit && $itemCount > 0): ?>
                <p class="hint">Dipakai <?= (int) $itemCount ?> produk, jadi kategori tidak bisa dipindah.</p>
                <?php endif; ?>
            </div>
            <div>
                <label for="sort_order">Urutan tampil</label>
                <input type="number" id="sort_order" name="sort_order" min="0"
                    value="<?= esc(old('sort_order', $row['sort_order'] ?? 0), 'attr') ?>">
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?= site_url('backoffice/jenis') ?>" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>