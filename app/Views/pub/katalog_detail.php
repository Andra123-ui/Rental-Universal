<?php
helper(['image', 'idhash']);
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
            <a href="<?= base_url('/catalog') ?>">Katalog</a>
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
                    <?php if (!empty($businessName)): ?>
                    <div style="margin-top:4px;font-size:.88rem;color:var(--muted);">
                        Disediakan oleh <strong style="color:inherit;"><?= esc($businessName) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="detail-price">
                        Rp
                        <?= number_format($item['base_price'], 0, ',', '.') ?>
                        <small>/
                            <?= esc($unit) ?>
                        </small>
                    </div>

                    <div style="margin-top:8px;font-size:.85rem;color:var(--muted);">
                        <?= $bookingCount > 0
        ? 'Sudah dipesan ' . number_format($bookingCount, 0, ',', '.') . ' kali'
        : 'Belum pernah dipesan' ?>
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
                    <?php if (!empty($itemBranches)): ?>
                    <div class="branch-info">
                        <h4>Lokasi Cabang</h4>
                        <?php foreach ($itemBranches as $b): ?>
                        <div class="branch-info-item">
                            <strong><?= esc($b['name']) ?></strong>
                            <?php if (!empty($b['address'])): ?>
                            <div class="bi-row"><span>Alamat</span> <?= esc($b['address']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($b['phone'])): ?>
                            <div class="bi-row"><span>No. HP</span>
                                <a
                                    href="tel:<?= esc(preg_replace('/[^\d+]/', '', $b['phone'])) ?>"><?= esc($b['phone']) ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking card: kalender ketersediaan + tambah ke keranjang -->
            <!-- Booking card: kalender ketersediaan + tambah ke keranjang -->
            <div class="booking-card">
                <h3>Cek Ketersediaan</h3>
                <?php if (!empty($itemBranches)): ?>
                <div class="branch-select-wrap">
                    <label for="branch-select">Pilih Cabang</label>
                    <select id="branch-select" class="branch-select">
                        <option value="">Semua cabang</option>
                        <?php foreach ($itemBranches as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"><?= esc($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="branch-note" class="branch-note"></div>
                </div>
                <?php endif; ?>

                <div class="cal-legend">
                    <span><i class="dot dot-ok"></i> Tersedia</span>
                    <span><i class="dot dot-limited"></i> Terbatas</span>
                    <span><i class="dot dot-full"></i> Habis</span>
                </div>

                <div class="cal-header">
                    <button type="button" id="cal-prev" class="cal-nav">&lsaquo;</button>
                    <div id="cal-title" class="cal-title">-</div>
                    <button type="button" id="cal-next" class="cal-nav">&rsaquo;</button>
                </div>
                <div id="cal-dow" class="cal-dow"></div>
                <div id="cal-grid" class="cal-grid"></div>

                <div id="cal-selection" class="cal-selection">
                    <p class="cs-title">Pilih tanggal mulai di kalender.</p>
                </div>

                <form id="form-tambah-cart" method="post" action="<?= base_url('/cart/tambah') ?>">
                    <input type="hidden" name="catalog_item_id" value="<?= esc($item['id']) ?>">
                    <input type="hidden" name="start_at" id="input_start_at">
                    <input type="hidden" name="qty" value="1">
                    <input type="hidden" name="branch_id" id="input_branch_id" value="">
                    <input type="hidden" name="end_at" id="input_end_at">
                    <?= csrf_field() ?>

                    <button type="submit" id="btn-tambah" class="btn btn-primary"
                        style="width:100%;justify-content:center;margin-top:16px;" disabled>Tambah ke Keranjang</button>
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
                        <a href="<?= base_url('/item/' . id_encode($r['id'])) ?>" class="btn btn-outline"
                            style="padding:8px 14px;font-size:0.85rem;">Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.branch-info {
    margin-top: 20px;
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: 10px;
}

.branch-info h4 {
    margin: 0 0 10px;
    font-size: 1rem;
}

.branch-info-item+.branch-info-item {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed var(--line);
}

.branch-info-item .bi-row {
    font-size: .88rem;
    color: var(--muted);
    margin-top: 3px;
    line-height: 1.5;
}

.branch-info-item .bi-row span {
    display: inline-block;
    min-width: 56px;
    font-weight: 600;
    color: inherit;
}

.branch-select-wrap {
    margin-bottom: 14px;
}

.branch-select-wrap label {
    display: block;
    font-size: .82rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.branch-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--line);
    border-radius: 8px;
    font-size: .92rem;
    background: #fff;
}

.branch-note {
    margin-top: 6px;
    font-size: .8rem;
    color: var(--muted);
    line-height: 1.5;
}

.cal-day .d-branch {
    font-size: .52rem;
    line-height: 1.1;
    font-weight: 600;
    text-align: center;
    opacity: .9;
    margin-top: 1px;
}

.cs-branches {
    list-style: none;
    margin: 10px 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.cs-branches li {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-size: .8rem;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(255, 255, 255, .6);
}

.cs-branches li.b-full {
    opacity: .55;
    text-decoration: line-through;
}

.cal-legend {
    display: flex;
    gap: 14px;
    font-size: .78rem;
    color: var(--muted);
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.cal-legend .dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    margin-right: 4px;
}

.dot-ok {
    background: #16a34a;
}

.dot-limited {
    background: #f59e0b;
}

.dot-full {
    background: #dc2626;
}

.cal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}

.cal-title {
    font-weight: 600;
    font-size: .95rem;
}

.cal-nav {
    background: none;
    border: 1px solid var(--line);
    border-radius: 6px;
    width: 30px;
    height: 30px;
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
}

.cal-nav:disabled {
    opacity: .35;
    cursor: not-allowed;
}

.cal-dow {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    font-size: .72rem;
    color: var(--muted);
    text-align: center;
    margin-bottom: 4px;
}

.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.cal-day {
    position: relative;
    aspect-ratio: 1;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    cursor: pointer;
    border: 1px solid transparent;
    user-select: none;
}

.cal-day .d-num {
    font-weight: 600;
}

.cal-day .d-info {
    font-size: .58rem;
    opacity: .85;
    text-align: center;
    line-height: 1.1;
}

.cal-day.empty {
    visibility: hidden;
    cursor: default;
}

.cal-day.past {
    opacity: .3;
    cursor: not-allowed;
}

.cal-day.ok {
    background: #dcfce7;
    color: #166534;
}

.cal-day.limited {
    background: #fef3c7;
    color: #92400e;
}

.cal-day.full {
    background: #fee2e2;
    color: #991b1b;
    cursor: not-allowed;
}

.cal-day.selected {
    outline: 2px solid #2563eb;
    outline-offset: -2px;
}

.cal-day.in-range {
    outline: 1px dashed #2563eb;
    outline-offset: -2px;
}

.cal-selection {
    margin-top: 14px;
    padding: 14px 16px;
    border-radius: 10px;
    background: var(--surface, #f5f5f5);
    border: 1px solid var(--line);
    transition: background .15s, border-color .15s;
}

.cal-selection .cs-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
}

.cal-selection .cs-sub {
    margin: 4px 0 0;
    font-size: .82rem;
    color: var(--muted);
}

.cal-selection.cs-ok {
    background: #dcfce7;
    border-color: #86efac;
}

.cal-selection.cs-ok .cs-title {
    color: #166534;
}

.cal-selection.cs-limited {
    background: #fef3c7;
    border-color: #fcd34d;
}

.cal-selection.cs-limited .cs-title {
    color: #92400e;
}

.cal-selection.cs-full {
    background: #fee2e2;
    border-color: #fca5a5;
}

.cal-selection.cs-full .cs-title {
    color: #991b1b;
}
</style>

<script>
(function() {
    const CALENDAR_DATA = <?= json_encode($calendarData, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const BRANCHES = <?= json_encode($itemBranches ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const dowNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
        'Oktober', 'November', 'Desember'
    ];
    const BASE_PRICE = <?= (int) $item['base_price'] ?>;
    const PRICING_UNIT = <?= json_encode($item['pricing_unit']) ?>;

    function formatRupiah(n) {
        return 'Rp' + Math.round(n).toLocaleString('id-ID');
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        } [c]));
    }

    function countUnits(startKey, endKey) {
        if (PRICING_UNIT === 'SESSION') return 1;
        const start = new Date(startKey + 'T00:00:00');
        const end = new Date(endKey + 'T00:00:00');
        return Math.round((end - start) / 86400000) + 1; // inklusif tanggal mulai & selesai
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const minYear = today.getFullYear(),
        minMonth = today.getMonth() + 1;
    let maxYear = minYear,
        maxMonth = minMonth + 2;
    if (maxMonth > 12) {
        maxMonth -= 12;
        maxYear += 1;
    }

    let viewYear = minYear,
        viewMonth = minMonth;
    let selStart = null,
        selEnd = null;
    let selBranch = ''; // '' = semua cabang, selain itu id cabang (string)

    const dowEl = document.getElementById('cal-dow');
    dowNames.forEach(n => {
        const s = document.createElement('span');
        s.textContent = n;
        dowEl.appendChild(s);
    });

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function key(y, m, d) {
        return `${y}-${pad(m)}-${pad(d)}`;
    }

    // Data ketersediaan per tanggal, sudah menyesuaikan cabang yang dipilih
    function getInfo(dateKey) {
        const info = CALENDAR_DATA[dateKey];
        if (!info) return null;
        if (!selBranch) return info;
        const b = (info.branches || []).find(x => String(x.id) === selBranch);
        if (!b) return {
            total: 0,
            available: 0,
            branches: []
        };
        return {
            total: b.total,
            available: b.available,
            branches: [b]
        };
    }

    function shortCode(b) {
        return String(b.code || b.name || '').replace(/^BR-/i, '');
    }

    function availBranches(info) {
        return (info && info.branches ? info.branches : []).filter(b => b.available > 0);
    }

    function renderCalendar() {
        document.getElementById('cal-title').textContent = `${monthNames[viewMonth - 1]} ${viewYear}`;
        document.getElementById('cal-prev').disabled = (viewYear === minYear && viewMonth === minMonth);
        document.getElementById('cal-next').disabled = (viewYear === maxYear && viewMonth === maxMonth);

        const grid = document.getElementById('cal-grid');
        grid.innerHTML = '';
        const firstDow = new Date(viewYear, viewMonth - 1, 1).getDay();
        const daysInMonth = new Date(viewYear, viewMonth, 0).getDate();

        for (let i = 0; i < firstDow; i++) {
            const e = document.createElement('div');
            e.className = 'cal-day empty';
            grid.appendChild(e);
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateKey = key(viewYear, viewMonth, d);
            const cellDate = new Date(viewYear, viewMonth - 1, d);
            cellDate.setHours(0, 0, 0, 0);
            const info = getInfo(dateKey);
            const el = document.createElement('div');
            el.className = 'cal-day';

            if (cellDate < today) {
                el.classList.add('past');
            } else if (info) {
                if (info.total <= 0 || info.available <= 0) el.classList.add('full');
                else if (info.available < info.total) el.classList.add('limited');
                else el.classList.add('ok');
            }

            const num = document.createElement('div');
            num.className = 'd-num';
            num.textContent = d;
            el.appendChild(num);

            if (cellDate >= today && info) {
                let label = '';
                if (info.total <= 0 || info.available <= 0) label = 'Habis';
                else if (info.available < info.total) label = 'Terbatas';
                if (label) {
                    const sub = document.createElement('div');
                    sub.className = 'd-info';
                    sub.textContent = info.total > 1 ? `${label} (${info.available}/${info.total})` : label;
                    el.appendChild(sub);
                }

                const ab = availBranches(info);
                if (ab.length) {
                    const br = document.createElement('div');
                    br.className = 'd-branch';
                    br.textContent = ab.map(shortCode).join(' · ');
                    el.appendChild(br);
                    el.title = ab.map(b => `${b.name}: tersisa ${b.available}/${b.total}`).join('\n');
                }
            }

            if (selStart === dateKey || selEnd === dateKey) el.classList.add('selected');
            else if (selStart && selEnd && dateKey > selStart && dateKey < selEnd) el.classList.add('in-range');

            if (cellDate >= today && !el.classList.contains('full')) {
                el.addEventListener('click', () => onPickDate(dateKey));
            }

            grid.appendChild(el);
        }
    }

    function onPickDate(dateKey) {
        if (!selStart || (selStart && selEnd)) {
            selStart = dateKey;
            selEnd = null;
        } else if (dateKey < selStart) {
            selStart = dateKey;
            selEnd = null;
        } else {
            selEnd = dateKey;
        }
        updateSelectionUI();
        renderCalendar();
    }

    function fmtDate(k) {
        if (!k) return null;
        const [y, m, d] = k.split('-');
        return `${d}/${m}/${y}`;
    }

    function getRangeStock(startKey, endKey) {
        const start = new Date(startKey + 'T00:00:00');
        const end = new Date(endKey + 'T00:00:00');
        let total = null,
            minAvailable = null;
        const cursor = new Date(start);
        while (cursor <= end) {
            const k = key(cursor.getFullYear(), cursor.getMonth() + 1, cursor.getDate());
            const info = getInfo(k);
            if (info) {
                total = info.total;
                minAvailable = (minAvailable === null) ? info.available : Math.min(minAvailable, info.available);
            }
            cursor.setDate(cursor.getDate() + 1);
        }
        return {
            total,
            minAvailable
        };
    }

    function getRangeBranches(startKey, endKey) {
        const map = {};
        let days = 0;
        const cursor = new Date(startKey + 'T00:00:00');
        const end = new Date(endKey + 'T00:00:00');
        while (cursor <= end) {
            days++;
            const k = key(cursor.getFullYear(), cursor.getMonth() + 1, cursor.getDate());
            const info = getInfo(k);
            ((info && info.branches) || []).forEach(b => {
                const id = b.code || b.name;
                if (!map[id]) {
                    map[id] = {
                        name: b.name,
                        min: b.available,
                        days: 1
                    };
                } else {
                    map[id].min = Math.min(map[id].min, b.available);
                    map[id].days++;
                }
            });
            cursor.setDate(cursor.getDate() + 1);
        }
        return Object.values(map).map(b => ({
            name: b.name,
            min: b.days < days ? 0 : b.min
        }));
    }

    function branchListHtml(list) {
        if (!list.length) return '';
        return '<ul class="cs-branches">' + list.map(b =>
            `<li class="${b.min > 0 ? 'b-ok' : 'b-full'}"><span>${esc(b.name)}</span><strong>${b.min > 0 ? 'tersisa ' + b.min + ' unit' : 'habis'}</strong></li>`
        ).join('') + '</ul>';
    }

    function setSelectionCard(cssClass, title, sub, extraHtml) {
        const box = document.getElementById('cal-selection');
        box.className = 'cal-selection' + (cssClass ? ' ' + cssClass : '');
        box.innerHTML = `<p class="cs-title">${title}</p>` + (sub ? `<p class="cs-sub">${sub}</p>` : '') + (
            extraHtml || '');
    }

    function updateSelectionUI() {
        const btnTambah = document.getElementById('btn-tambah');

        if (selStart && selEnd) {
            const {
                total,
                minAvailable
            } = getRangeStock(selStart, selEnd);
            const branchHtml = branchListHtml(getRangeBranches(selStart, selEnd));

            document.getElementById('input_start_at').value = `${selStart} 00:00:00`;
            const endDate = new Date(selEnd + 'T00:00:00');
            endDate.setDate(endDate.getDate() + 1);
            document.getElementById('input_end_at').value =
                `${endDate.getFullYear()}-${pad(endDate.getMonth() + 1)}-${pad(endDate.getDate())} 00:00:00`;

            const units = countUnits(selStart, selEnd);
            const estTotal = BASE_PRICE * units;
            const rangeLabel =
                `${fmtDate(selStart)} — ${fmtDate(selEnd)} · ${units} ${PRICING_UNIT === 'SESSION' ? 'sesi' : PRICING_UNIT === 'NIGHT' ? 'malam' : 'hari'} · ${formatRupiah(BASE_PRICE)} x ${units} = ${formatRupiah(estTotal)}`;

            if (total === null || minAvailable <= 0) {
                setSelectionCard('cs-full', 'Habis pada rentang ini', rangeLabel, branchHtml);
                btnTambah.disabled = true;
            } else if (minAvailable < total) {
                setSelectionCard('cs-limited', `Terbatas — tersisa ${minAvailable} unit`, rangeLabel, branchHtml);
                btnTambah.disabled = false;
            } else {
                setSelectionCard('cs-ok', `Tersedia — stok ${total} unit`, rangeLabel, branchHtml);
                btnTambah.disabled = false;
            }
        } else if (selStart) {
            const single = getInfo(selStart);
            const branchHtml = branchListHtml(((single && single.branches) || []).map(b => ({
                name: b.name,
                min: b.available
            })));
            if (single && single.available > 0 && single.available < single.total) {
                setSelectionCard('cs-limited', `Terbatas — tersisa ${single.available} unit`,
                    `${fmtDate(selStart)} — pilih tanggal selesai`, branchHtml);
            } else if (single && single.available > 0) {
                setSelectionCard('cs-ok', `Tersedia — stok ${single.total} unit`,
                    `${fmtDate(selStart)} — pilih tanggal selesai`, branchHtml);
            } else {
                setSelectionCard('', `${fmtDate(selStart)}`, 'Pilih tanggal selesai', branchHtml);
            }
            btnTambah.disabled = true;
        } else {
            setSelectionCard('', 'Pilih tanggal mulai di kalender.', null);
            btnTambah.disabled = true;
        }
    }

    // ===== Pilih cabang =====
    const branchSelect = document.getElementById('branch-select');
    const branchNote = document.getElementById('branch-note');
    const branchInput = document.getElementById('input_branch_id');

    function updateBranchNote() {
        if (!branchNote) return;
        const b = BRANCHES.find(x => String(x.id) === selBranch);
        if (!b) {
            branchNote.innerHTML = '';
            return;
        }
        let html = '';
        if (b.address) html += `<div>📍 ${esc(b.address)}</div>`;
        if (b.phone) html += `<div>📞 ${esc(b.phone)}</div>`;
        branchNote.innerHTML = html;
    }

    if (branchSelect) {
        branchSelect.addEventListener('change', () => {
            selBranch = branchSelect.value;
            if (branchInput) branchInput.value = selBranch;
            // reset pilihan tanggal karena stok tiap cabang berbeda
            selStart = null;
            selEnd = null;
            document.getElementById('input_start_at').value = '';
            document.getElementById('input_end_at').value = '';
            updateBranchNote();
            updateSelectionUI();
            renderCalendar();
        });
    }

    document.getElementById('cal-prev').addEventListener('click', () => {
        if (viewYear === minYear && viewMonth === minMonth) return;
        viewMonth--;
        if (viewMonth < 1) {
            viewMonth = 12;
            viewYear--;
        }
        renderCalendar();
    });
    document.getElementById('cal-next').addEventListener('click', () => {
        if (viewYear === maxYear && viewMonth === maxMonth) return;
        viewMonth++;
        if (viewMonth > 12) {
            viewMonth = 1;
            viewYear++;
        }
        renderCalendar();
    });

    renderCalendar();
})();
</script>

<?= view('partials/footer') ?>