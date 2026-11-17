<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php helper('idhash'); ?>

<div class="page-head">
    <h1>Kategori</h1>
    <a href="<?= site_url('backoffice/kategori/baru') ?>" class="btn btn-primary">+ Tambah Kategori</a>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Induk</th>
                <th>Urutan</th>
                <th>Produk</th>
                <th>Jenis</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="7" class="muted">Belum ada kategori.</td>
            </tr>
            <?php endif; ?>

            <?php foreach ($rows as $r): ?>
            <tr>
                <td style="padding-left:<?= 12 + ((int) $r['depth'] * 26) ?>px;">
                    <?php if ((int) $r['depth'] > 0): ?><span class="muted">└</span> <?php endif; ?>
                    <strong><?= esc($r['name']) ?></strong>
                    <?php if ((int) $r['child_count'] > 0): ?>
                    <span class="muted">(<?= (int) $r['child_count'] ?> sub)</span>
                    <?php endif; ?>
                    <br><span class="muted"><?= esc($r['slug']) ?></span>
                </td>
                <td><?= $r['parent_name'] ? esc($r['parent_name']) : '<span class="muted">—</span>' ?></td>
                <td><?= (int) $r['sort_order'] ?></td>
                <td><?= (int) $r['item_count'] ?></td>
                <td><?= (int) $r['jenis_count'] ?></td>
                <td>
                    <span class="badge <?= (int) $r['is_active'] ? 'badge-ok' : 'badge-off' ?>">
                        <?= (int) $r['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td style="white-space:nowrap;text-align:right;">
                    <?php foreach ([['up', '▲', $r['is_first']], ['down', '▼', $r['is_last']]] as [$dir, $lbl, $off]): ?>
                    <form action="<?= site_url('backoffice/kategori/urutan/' . $r['enc']) ?>" method="post"
                        class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="dir" value="<?= $dir ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" <?= $off ? 'disabled' : '' ?>
                            title="<?= $dir === 'up' ? 'Naikkan' : 'Turunkan' ?>"><?= $lbl ?></button>
                    </form>
                    <?php endforeach; ?>

                    <a href="<?= site_url('backoffice/kategori/ubah/' . $r['enc']) ?>"
                        class="btn btn-ghost btn-sm">Ubah</a>

                    <form action="<?= site_url('backoffice/kategori/status/' . $r['enc']) ?>" method="post"
                        class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-ghost btn-sm">
                            <?= (int) $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="hint" style="margin-top:12px;">
    Kategori yang sudah dipakai tidak dihapus: nonaktifkan saja. Urutan (▲ ▼) hanya berlaku di antara kategori dengan
    induk yang sama.
</p>

<?= $this->endSection() ?>