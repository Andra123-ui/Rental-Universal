<?php
helper('image');
/**
 * @var array $catalogItems
 * @var array $categories
 * @var mixed $pager
 * @var string|null $activeKategori
 * @var string|null $keyword
 * @var string $sort
 * @var array $imageMap
 */
?>
<?= view('partials/header', ['title' => 'Katalog']) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Katalog</div>
        <h1>Katalog Barang & Jasa</h1>
    </div>
</div>

<section style="padding-top:0;">
    <div class="wrap">
        <form class="filters-bar" method="get" action="<?= base_url('/catalog') ?>">
            <div class="search-field">
                <label for="f-kategori">Kategori</label>
                <select id="f-kategori" name="kategori">
                    <option value="">Semua kategori</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= esc($cat['slug']) ?>" <?= $activeKategori === $cat['slug'] ? 'selected' : '' ?>>
                        <?= esc($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field" style="flex:2;">
                <label for="f-q">Cari</label>
                <input type="text" id="f-q" name="q" placeholder="Nama barang/jasa..."
                    value="<?= esc($keyword ?? '') ?>">
            </div>
            <div class="search-field">
                <label for="f-sort">Urutkan</label>
                <select id="f-sort" name="sort">
                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="harga_rendah" <?= $sort === 'harga_rendah' ? 'selected' : '' ?>>Harga terendah
                    </option>
                    <option value="harga_tinggi" <?= $sort === 'harga_tinggi' ? 'selected' : '' ?>>Harga tertinggi
                    </option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <?php if (!empty($activeKategori) || !empty($keyword)): ?>
            <a href="<?= base_url('/catalog') ?>" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>

        <div class="card-grid">
            <?php if (empty($catalogItems)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8l-9-5-9 5 9 5 9-5z" />
                    <path d="M3 8v8l9 5 9-5V8" />
                </svg>
                <h3>Tidak ada hasil</h3>
                <p>Coba ubah kata kunci atau kategori pencarian Anda.</p>
            </div>
            <?php else: ?>
            <?php foreach ($catalogItems as $item):
                    $imgUrl = item_image_url($imageMap[$item['id']] ?? null, 'item-' . $item['id']);
                    ?>
            <div class="item-card">
                <div class="item-media"
                    style="background-image:url('<?= esc($imgUrl) ?>');background-size:cover;background-position:center;">
                </div>
                <div class="item-body">
                    <span class="item-type">
                        <?= esc($item['item_type']) ?>
                    </span>
                    <h3>
                        <?= esc($item['name']) ?>
                    </h3>
                    <p>
                        <?= esc(mb_strimwidth($item['description'] ?? 'Detail lengkap tersedia di halaman produk.', 0, 90, '...')) ?>
                    </p>
                    <div class="item-footer">
                        <div class="item-price">Rp
                            <?= number_format($item['base_price'], 0, ',', '.') ?> <small>/
                                <?= esc($item['unit_label']) ?>
                            </small>
                        </div>
                        <a href="<?= base_url('/item/' . $item['id']) ?>" class="btn btn-outline"
                            style="padding:8px 14px;font-size:0.85rem;">Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (isset($pager)): ?>
        <div style="margin-top:40px;display:flex;justify-content:center;">
            <?= $pager->links('default') ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?= view('partials/footer') ?>