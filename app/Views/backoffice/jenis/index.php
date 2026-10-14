<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php helper('idhash'); ?>

<div class="page-head">
    <h1>Jenis Produk</h1>
    <a href="<?= site_url('backoffice/jenis/baru') ?>" class="btn btn-primary">+ Tambah Jenis</a>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Jenis</th>
                <th>Kategori</th>
                <th>Produk</th>
                <th>Urutan</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="6" class="muted">Belum ada jenis.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): $h = id_encode($r['id']); ?>
            <tr>
                <td><strong><?= esc($r['name']) ?></strong><br><span class="muted"><?= esc($r['slug']) ?></span></td>
                <td><?= esc($r['category_name']) ?></td>
                <td><?= (int) $r['item_count'] ?></td>
                <td><?= (int) $r['sort_order'] ?></td>
                <td>
                    <span class="badge <?= $r['is_active'] ? 'badge-ok' : 'badge-off' ?>">
                        <?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td style="white-space:nowrap;text-align:right;">
                    <a href="<?= site_url('backoffice/jenis/ubah/' . $h) ?>" class="btn btn-ghost btn-sm">Ubah</a>
                    <form action="<?= site_url('backoffice/jenis/status/' . $h) ?>" method="post" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit"
                            class="btn btn-ghost btn-sm"><?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>