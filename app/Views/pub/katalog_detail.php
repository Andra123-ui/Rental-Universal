<?php
helper('image');
/**
 * @var array $item
 * @var array|null $category
 * @var array $related
 * @var array $gallery
 * @var array $relatedImages
 */
$unit = $item['unit_label'];
$mainImg = !empty($gallery) ? item_image_url($gallery[0]['file_path'], 'item-' . $item['id']) : item_image_url(null, 'item-' . $item['id']);
?>
<?= view('partials/header', ['title' => $item['name']]) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb">
            <a href="<?= base_url('/') ?>">Beranda</a> /
            <a href="<?= base_url('/catalog ') ?>">Katalog</a>
            <?php if ($category): ?> / <a href="<?= base_url('/catalog?kategori=' . $category['slug']) ?>">
                <?= esc($category['name']) ?>
            </a>
            <?php endif; ?>
        </div>
        <h1>
            <?= esc($item['name']) ?>
        </h1>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="detail-grid">
            <div>
                <div class="detail-media"
                    style="background-image:url('<?= esc($mainImg) ?>');background-size:cover;background-position:center;">
                </div>

                <?php if (count($gallery) > 1): ?>
                <div style="display:flex;gap:10px;margin-top:12px;">
                    <?php foreach ($gallery as $g): ?>
                    <div
                        style="width:70px;height:56px;border-radius:4px;background-image:url('<?= esc(item_image_url($g['file_path'])) ?>');background-size:cover;background-position:center;border:1px solid var(--line);">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="detail-info" style="margin-top:24px;">
                    <span class="badge">
                        <?= esc($item['item_type']) ?>
                    </span>
                    <h2 style="font-size:1.4rem;">
                        <?= esc($item['name']) ?>
                    </h2>
                    <div class="detail-price">
                        Rp
                        <?= number_format($item['base_price'], 0, ',', '.') ?>
                        <small>/
                            <?= esc($unit) ?>
                        </small>
                    </div>

                    <p style="margin-top:18px;color:var(--muted);line-height:1.7;">
                        <?= nl2br(esc($item['description'] ?? 'Belum ada deskripsi lengkap untuk item ini.')) ?>
                    </p>

                    <?php if (!empty($item['deposit_amount']) && $item['deposit_amount'] > 0): ?>
                    <div class="alert-box alert-info" style="margin-top:20px;">
                        Deposit/jaminan sebesar <strong>Rp
                            <?= number_format($item['deposit_amount'], 0, ',', '.') ?>
                        </strong> berlaku untuk item ini dan akan dikembalikan sesuai syarat & ketentuan.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking card: cek ketersediaan + tambah ke keranjang -->
            <!-- Booking card: arahkan ke halaman /availability untuk cek ketersediaan lengkap -->
            <div class="booking-card">
                <h3>Cek Ketersediaan</h3>
                <form id="form-cek-tersedia" method="get" action="<?= base_url('/availability') ?>">
                    <input type="hidden" name="item_id" value="<?= esc($item['id']) ?>">

                    <div class="field-group">
                        <label for="start_at">Tanggal/Jam Mulai</label>
                        <input type="datetime-local" id="start_at" name="start_at" required>
                    </div>
                    <div class="field-group">
                        <label for="end_at">Tanggal/Jam Selesai</label>
                        <input type="datetime-local" id="end_at" name="end_at" required>
                    </div>
                    <div class="field-group">
                        <label for="qty">Jumlah</label>
                        <input type="number" id="qty" name="qty" min="1" value="1">
                    </div>

                    <div id="form-error"></div>

                    <div style="margin-top:16px;">
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                            Cek Ketersediaan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($related)): ?>
        <div class="section-head" style="margin-top:64px;">
            <h2>Produk sejenis</h2>
        </div>
        <div class="card-grid">
            <?php foreach ($related as $r):
          $ru = $r['unit_label'];
          $rImg = item_image_url($relatedImages[$r['id']] ?? null, 'item-' . $r['id']);
          ?>
            <div class="item-card">
                <div class="item-media"
                    style="background-image:url('<?= esc($rImg) ?>');background-size:cover;background-position:center;">
                </div>
                <div class="item-body">
                    <span class="item-type">
                        <?= esc($r['item_type']) ?>
                    </span>
                    <h3>
                        <?= esc($r['name']) ?>
                    </h3>
                    <div class="item-footer">
                        <div class="item-price">Rp
                            <?= number_format($r['base_price'], 0, ',', '.') ?> <small>/
                                <?= esc($ru) ?>
                            </small>
                        </div>
                        <a href="<?= base_url('/item/' . $r['id']) ?>" class="btn btn-outline"
                            style="padding:8px 14px;font-size:0.85rem;">Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.getElementById('btn-tambah').disabled = true;

document.getElementById('btn-cek').addEventListener('click', async function() {
    const startAt = document.getElementById('start_at').value;
    const endAt = document.getElementById('end_at').value;
    const qty = document.getElementById('qty').value;
    const result = document.getElementById('avail-result');
    const altBox = document.getElementById('alt-slots');
    const priceBox = document.getElementById('price-estimate');
    const btnTambah = document.getElementById('btn-tambah');

    altBox.innerHTML = '';
    priceBox.innerHTML = '';
    btnTambah.disabled = true;

    if (!startAt || !endAt) {
        result.innerHTML =
            '<div class="avail-note avail-warn">Isi tanggal mulai dan selesai terlebih dahulu.</div>';
        return;
    }
    if (new Date(startAt) >= new Date(endAt)) {
        result.innerHTML =
            '<div class="avail-note avail-warn">Tanggal selesai harus setelah tanggal mulai.</div>';
        return;
    }

    result.innerHTML = '<div class="avail-note">Memeriksa ketersediaan...</div>';

    try {
        const params = new URLSearchParams({
            start_at: startAt,
            end_at: endAt,
            qty
        });
        const res = await fetch('<?= base_url('/availability/cek/' . $item['id']) ?>?' + params.toString());
        const data = await res.json();

        const cls = data.status === 'available' ? 'avail-ok' : 'avail-warn';
        result.innerHTML = `<div class="avail-note ${cls}">${data.message}</div>`;

        if (data.buffer_note) {
            result.innerHTML += `<div class="avail-note">${data.buffer_note}</div>`;
        }

        if (data.status === 'available' && elBranch.value) {
            btnTambah.disabled = false;
        }

        if (data.alternatives && data.alternatives.length) {
            let html = '<div style="margin-top:10px;"><strong>Alternatif jadwal terdekat:</strong><ul>';
            data.alternatives.forEach(a => {
                html += `<li>${a.start_at} &ndash; ${a.end_at}</li>`;
            });
            html += '</ul></div>';
            altBox.innerHTML = html;
        }

        if (data.price_estimate) {
            const p = data.price_estimate;
            priceBox.innerHTML = `<div class="alert-box alert-info" style="margin-top:14px;">
                Estimasi harga: Rp ${Number(p.subtotal).toLocaleString('id-ID')}
                <small>(belum final)</small>
            </div>`;
        }
    } catch (e) {
        result.innerHTML =
            '<div class="avail-note avail-warn">Gagal memeriksa ketersediaan. Coba lagi.</div>';
        btnTambah.disabled = true;
    }
});
</script>

<script>
document.getElementById('form-cek-tersedia').addEventListener('submit', function(e) {
    const startAt = document.getElementById('start_at').value;
    const endAt = document.getElementById('end_at').value;
    const errBox = document.getElementById('form-error');
    errBox.innerHTML = '';

    if (!startAt || !endAt) {
        e.preventDefault();
        errBox.innerHTML =
            '<div class="avail-note avail-warn">Isi tanggal mulai dan selesai terlebih dahulu.</div>';
        return;
    }
    if (new Date(startAt) >= new Date(endAt)) {
        e.preventDefault();
        errBox.innerHTML =
            '<div class="avail-note avail-warn">Tanggal selesai harus setelah tanggal mulai.</div>';
        return;
    }
    // valid -> form submit natural GET ke /availability, browser yang redirect
});
</script>

<?= view('partials/footer') ?>