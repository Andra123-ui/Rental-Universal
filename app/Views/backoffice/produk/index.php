<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
helper(['idhash', 'image']);
$lastPage = max(1, (int) ceil($total / $perPage));
$clean    = array_filter($f, static fn ($v) => $v !== '' && $v !== 0);
$query    = static fn (int $p) => http_build_query(array_merge($clean, ['page' => $p]));
$modeShort = [
    'SERIALIZED' => 'Per unit', 'QUANTITY' => 'Per jumlah', 'CAPACITY' => 'Per kapasitas',
    'SCHEDULE' => 'Per jadwal', 'UNLIMITED' => 'Tanpa batas',
];
$hasFilter = $clean !== [];
?>

<div class="page-head">
    <h1>Produk</h1>
    <?php if (admin_can('product.manage')): ?>
    <a href="<?= site_url('backoffice/produk/baru') ?>" class="btn btn-primary">+ Tambah Produk</a>
    <?php endif; ?>
</div>

<div class="panel">
    <form method="get" action="<?= site_url('backoffice/produk') ?>" class="filters">
        <div class="f-wide">
            <label for="q">Cari</label>
            <input type="text" id="q" name="q" value="<?= esc($f['q'], 'attr') ?>" placeholder="Nama atau SKU...">
        </div>
        <div>
            <label for="category">Kategori</label>
            <select id="category" name="category">
                <option value="">Semua</option>
                <?php foreach ($categoryOptions as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $f['category'] === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= esc(str_repeat('— ', (int) $c['depth']) . $c['name']) ?><?= (int) $c['is_active'] ? '' : ' (nonaktif)' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="type">Tipe</label>
            <select id="type" name="type">
                <option value="">Semua</option>
                <?php foreach ($itemTypes as $k => $label): ?>
                <option value="<?= $k ?>" <?= $f['type'] === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <option value="ACTIVE" <?= $f['status'] === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option>
                <option value="INACTIVE" <?= $f['status'] === 'INACTIVE' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>
        <div>
            <label for="online">Online</label>
            <select id="online" name="online">
                <option value="">Semua</option>
                <option value="1" <?= $f['online'] === '1' ? 'selected' : '' ?>>Bisa dipesan online</option>
                <option value="0" <?= $f['online'] === '0' ? 'selected' : '' ?>>Hanya offline</option>
            </select>
        </div>
        <div class="f-actions">
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if ($hasFilter): ?>
            <a href="<?= site_url('backoffice/produk') ?>" class="btn btn-ghost">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Produk</th>
                    <th>Kategori / Jenis</th>
                    <th>Tipe / Mode</th>
                    <th>Harga</th>
                    <th>Online</th>
                    <th>Unit & Cabang</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="9" class="muted">Tidak ada produk.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): $h = id_encode($r['id']); ?>
                <tr>
                    <td style="width:56px;">
                        <img src="<?= esc(item_image_url($r['thumb'] ?? null, 'item-' . $r['id']), 'attr') ?>" alt=""
                            loading="lazy"
                            style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--line);background:var(--paper);">
                    </td>
                    <td>
                        <strong><?= esc($r['name']) ?></strong><br>
                        <span class="muted"><?= esc($r['sku'] ?? '-') ?></span>
                    </td>
                    <td>
                        <?= esc($r['category_name'] ?? '-') ?><br>
                        <span class="muted"><?= esc($r['jenis_name'] ?? 'Belum ada jenis') ?></span>
                    </td>
                    <td>
                        <?= esc($itemTypes[$r['item_type']] ?? $r['item_type']) ?><br>
                        <span class="muted"><?= esc($modeShort[$r['rental_mode']] ?? $r['rental_mode']) ?></span>
                    </td>
                    <td>
                        Rp <?= number_format($r['base_price'], 0, ',', '.') ?>
                        <span class="muted">/ <?= esc($r['unit_label']) ?></span>
                    </td>
                    <td>
                        <span class="badge <?= (int) $r['allow_online_booking'] ? 'badge-ok' : 'badge-off' ?>">
                            <?= (int) $r['allow_online_booking'] ? 'Online' : 'Offline' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($r['item_type'] === 'PACKAGE'): ?>
                        <span class="muted">Komponen paket</span>
                        <?php elseif ($r['rental_mode'] === 'UNLIMITED'): ?>
                        <span class="muted">Tanpa batas</span>
                        <?php elseif ((int) $r['resource_count'] === 0): ?>
                        <span class="badge badge-warn">Belum ada unit</span>
                        <?php else: ?>
                        <?= (int) $r['resource_count'] ?> unit
                        <div class="muted"><?= esc($r['branch_names'] ?: '-') ?></div>
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
                        <?php if ($r['item_type'] !== 'PACKAGE' && $r['rental_mode'] !== 'UNLIMITED'): ?>
                        <a href="<?= site_url('backoffice/produk/ubah/' . $h) ?>#cabang-unit"
                            class="btn btn-ghost btn-sm">Unit</a>
                        <?php endif; ?>
                        <form action="<?= site_url('backoffice/produk/duplikat/' . $h) ?>" method="post" class="inline"
                            onsubmit="return confirm('Duplikat produk ini? Salinan dibuat nonaktif tanpa unit dan media.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-ghost btn-sm">Duplikat</button>
                        </form>
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