<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
helper('idhash');
$lastPage = max(1, (int) ceil($total / $perPage));
$query = fn(int $p) => http_build_query(array_filter(['q' => $q, 'status' => $status, 'page' => $p]));
?>

<div class="page-head">
    <h1>Produk</h1>
    <?php if (admin_can('product.manage')): ?>
    <a href="<?= site_url('backoffice/produk/baru') ?>" class="btn btn-primary">+ Tambah Produk</a>
    <?php endif; ?>
</div>

<div class="panel">
    <form method="get" action="<?= site_url('backoffice/produk') ?>" class="filters">
        <input type="text" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Cari nama atau SKU..."
            style="max-width:280px;">
        <select name="status" style="max-width:160px;">
            <option value="">Semua status</option>
            <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option>
            <option value="INACTIVE" <?= $status === 'INACTIVE' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-ghost">Cari</button>
        <?php if ($q !== '' || $status !== ''): ?>
        <a href="<?= site_url('backoffice/produk') ?>" class="btn btn-ghost">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori / Jenis</th>
                    <th>Harga</th>
                    <th>Cabang</th>
                    <th>Total Unit</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="muted">Tidak ada produk.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): $h = id_encode($r['id']); ?>
                <tr>
                    <td>
                        <strong><?= esc($r['name']) ?></strong><br>
                        <span class="muted"><?= esc($r['sku'] ?? '-') ?> · <?= esc($r['item_type']) ?></span>
                    </td>
                    <td>
                        <?= esc($r['category_name'] ?? '-') ?><br>
                        <span class="muted"><?= esc($r['jenis_name'] ?? 'Belum ada jenis') ?></span>
                    </td>
                    <td>Rp <?= number_format($r['base_price'], 0, ',', '.') ?> <span class="muted">/
                            <?= esc($r['unit_label']) ?></span></td>

                    <!-- MENAMPILKAN CABANG -->
                    <td>
                        <?php 
                        if (!empty($r['branch_names'])) {
                            echo esc($r['branch_names']);
                        } else {
                            echo '<span class="muted">-</span>';
                        }
                        ?>
                    </td>

                    <td>
                        <?php if ((int) $r['resource_count'] === 0): ?>
                        <span class="badge badge-warn">Belum ada unit</span>
                        <?php else: ?>
                        <?= (int) $r['resource_count'] ?> unit
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $r['status'] === 'ACTIVE' ? 'badge-ok' : 'badge-off' ?>">
                            <?= $r['status'] === 'ACTIVE' ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;text-align:right;">
                        <?php if (admin_can('product.manage')): ?>
                        <a href="<?= site_url('backoffice/produk/ubah/' . $h) ?>" class="btn btn-ghost btn-sm">Ubah</a>
                        <form action="<?= site_url('backoffice/produk/status/' . $h) ?>" method="post" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit"
                                class="btn btn-ghost btn-sm"><?= $r['status'] === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($lastPage > 1): ?>
    <div class="pager">
        <?php if ($page > 1): ?><a href="?<?= $query($page - 1) ?>" class="btn btn-ghost btn-sm">&larr;
            Sebelumnya</a><?php endif; ?>
        <span>Halaman <?= $page ?> dari <?= $lastPage ?> (<?= (int) $total ?> produk)</span>
        <?php if ($page < $lastPage): ?><a href="?<?= $query($page + 1) ?>" class="btn btn-ghost btn-sm">Berikutnya
            &rarr;</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>