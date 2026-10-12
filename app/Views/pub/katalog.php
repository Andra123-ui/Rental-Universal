<?php
helper(['image', 'idhash']);
/**
 * @var array $catalogItems
 * @var array $categories
 * @var array $jenisList
 * @var array $branches
 * @var mixed $pager
 * @var string|null $activeKategori
 * @var string|null $activeJenis
 * @var string|null $activeCabang
 * @var string|null $keyword
 * @var string $sort
 * @var array $imageMap
 */
$hasFilter = !empty($activeKategori) || !empty($activeJenis) || !empty($activeCabang) || !empty($keyword);
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
        <form class="filters-bar filters-grid" method="get" action="<?= base_url('/catalog') ?>">
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
            <div class="search-field">
                <label for="f-jenis">Jenis</label>
                <select id="f-jenis" name="jenis">
                    <option value="">Semua jenis</option>
                    <?php foreach ($jenisList as $j): ?>
                    <option value="<?= esc($j['slug']) ?>" data-kategori="<?= esc($j['category_slug']) ?>"
                        <?= ($activeJenis ?? '') === $j['slug'] ? 'selected' : '' ?>>
                        <?= esc($j['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field">
                <label for="f-cabang">Cabang</label>
                <select id="f-cabang" name="cabang">
                    <option value="">Semua cabang</option>
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= esc($b['branch_code']) ?>"
                        <?= ($activeCabang ?? '') === $b['branch_code'] ? 'selected' : '' ?>>
                        <?= esc($b['branch_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
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

            <div class="search-field filter-search">
                <label for="f-q">Cari</label>
                <input type="text" id="f-q" name="q" placeholder="Nama barang/jasa..."
                    value="<?= esc($keyword ?? '') ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                <?php if ($hasFilter): ?>
                <a href="<?= base_url('/catalog') ?>" class="btn btn-outline">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="card-grid">
            <?php if (empty($catalogItems)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8l-9-5-9 5 9 5 9-5z" />
                    <path d="M3 8v8l9 5 9-5V8" />
                </svg>
                <h3>Tidak ada hasil</h3>
                <p>Coba ubah kata kunci atau filter pencarian Anda.</p>
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
                        <?= esc($item['jenis_name'] ?? $item['item_type']) ?>
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
                        <a href="<?= base_url('/item/' . id_encode($item['id'])) ?>" class="btn btn-outline"
                            style="padding:8px 14px;font-size:0.85rem;">Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (isset($pager)): ?>
        <div style="margin-top:40px;display:flex;justify-content:center;">
            <?= $pager->links('default', 'pub_pagination') ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.filters-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px 18px;
    align-items: end;
    padding: 22px 24px;
}

.filters-grid .search-field {
    min-width: 0;
    flex: none;
}

.filters-grid .filter-search {
    grid-column: span 3;
}

.filters-grid .search-field label {
    font-size: .8rem;
    font-weight: 600;
    color: var(--muted);
}

.filters-grid .search-field select,
.filters-grid .search-field input {
    width: 100%;
    height: 44px;
    padding: 0 12px;
    border-radius: 8px;
    font-family: inherit;
    font-size: .92rem;
}

.filters-grid .search-field input::placeholder {
    color: #94A3B8;
}

.filters-grid .search-field select:focus,
.filters-grid .search-field input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}

.filters-grid .search-field select:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.filter-actions .btn {
    flex: 1;
    height: 44px;
    padding: 0 22px;
    justify-content: center;
}

@media (max-width: 880px) {
    .filters-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .filters-grid .filter-search,
    .filter-actions {
        grid-column: span 2;
    }
}

@media (max-width: 640px) {
    .filters-grid {
        grid-template-columns: 1fr;
        padding: 18px;
    }

    .filters-grid .filter-search,
    .filter-actions {
        grid-column: auto;
    }
}
</style>

<script>
(function() {
    const kat = document.getElementById('f-kategori');
    const jenis = document.getElementById('f-jenis');

    // Dropdown jenis hanya menampilkan jenis milik kategori yang dipilih
    function syncJenis(reset) {
        const slug = kat.value;

        Array.from(jenis.options).forEach(function(o) {
            if (!o.value) return;
            const show = slug !== '' && o.dataset.kategori === slug;
            o.hidden = !show;
            o.disabled = !show;
        });

        if (reset) jenis.value = '';
        jenis.options[0].textContent = slug ? 'Semua jenis' : 'Pilih kategori dulu';
        jenis.disabled = slug === '';
    }

    kat.addEventListener('change', function() {
        syncJenis(true);
    });
    syncJenis(false);
})();
</script>

<?= view('partials/footer') ?>