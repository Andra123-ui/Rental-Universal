<?php
/**
 * @var array $branches
 * @var array $categories
 * @var array $items
 * @var array $resources
 * @var array $statuses
 * @var bool  $canManage
 * @var bool  $canCreate
 */
$statusLabel = [
    'PENDING' => 'Menunggu', 'CONFIRMED' => 'Dikonfirmasi', 'ONGOING' => 'Berjalan',
    'COMPLETED' => 'Selesai', 'CANCELLED' => 'Dibatalkan', 'EXPIRED' => 'Kedaluwarsa',
];
?>
<?= $this->extend('backoffice/layout') ?>

<?= $this->section('content') ?>

<div class="cal-page">

    <div class="page-head">
        <h1>Kalender Booking &amp; Resource</h1>
        <?php if ($canManage): ?>
        <button type="button" class="btn btn-primary btn-sm" id="btn-blackout">+ Tambah Blackout</button>
        <?php endif; ?>
    </div>

    <!-- FILTER -->
    <form id="cal-filters" class="filters" onsubmit="return false;">
        <div>
            <label for="f-branch">Cabang</label>
            <select name="branch_id" id="f-branch">
                <?php if (count($branches) !== 1): ?>
                <option value="">Semua cabang</option>
                <?php endif; ?>
                <?php foreach ($branches as $b): ?>
                <option value="<?= (int) $b['id'] ?>"><?= esc($b['branch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!empty($categories)): ?>
        <div>
            <label for="f-cat">Kategori</label>
            <select name="category_id" id="f-cat">
                <option value="">Semua kategori</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div>
            <label for="f-item">Item</label>
            <select name="item_id" id="f-item">
                <option value="">Semua item</option>
                <?php foreach ($items as $i): ?>
                <option value="<?= (int) $i['id'] ?>"><?= esc($i['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="f-wide">
            <label for="f-res">Resource</label>
            <select name="resource_id" id="f-res">
                <option value="">Semua resource</option>
                <?php foreach ($resources as $r): ?>
                <option value="<?= (int) $r['id'] ?>" data-branch="<?= (int) $r['branch_id'] ?>"
                    data-item="<?= (int) $r['item_id'] ?>">
                    <?= esc($r['name']) ?><?= $r['code'] !== '' ? ' (' . esc($r['code']) . ')' : '' ?><?= $r['item_name'] !== '' ? ' — ' . esc($r['item_name']) : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="f-status">Status</label>
            <select name="status" id="f-status">
                <option value="">Aktif (tanpa batal)</option>
                <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s) ?>"><?= esc($statusLabel[$s] ?? $s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="f-actions">
            <button type="button" class="btn btn-ghost" id="btn-reset">Reset</button>
        </div>
    </form>

    <!-- VIEW + LEGEND -->
    <div class="cal-bar">
        <div class="tabs" id="cal-tabs">
            <button type="button" class="tab active" data-mode="dayGridMonth">Bulan</button>
            <button type="button" class="tab" data-mode="timeGridWeek">Minggu</button>
            <button type="button" class="tab" data-mode="timeGridDay">Hari</button>
            <button type="button" class="tab" data-mode="tl">Timeline Resource</button>
        </div>
        <div class="cal-legend">
            <span><i style="background:#f59e0b"></i> Menunggu</span>
            <span><i style="background:#2563eb"></i> Dikonfirmasi</span>
            <span><i style="background:#0891b2"></i> Berjalan</span>
            <span><i style="background:#16a34a"></i> Selesai</span>
            <span><i style="background:#475569"></i> Blackout</span>
            <span><i style="background:#ea580c"></i> Maintenance</span>
        </div>
    </div>

    <div id="cal-error" class="alert alert-error" hidden></div>

    <!-- Bulan / Minggu / Hari -->
    <div id="cal-fc" class="panel"></div>

    <!-- Timeline resource -->
    <div id="cal-tl" class="panel" hidden>
        <div class="tl-bar">
            <button type="button" class="btn btn-ghost btn-sm" id="tl-prev">&lsaquo;</button>
            <button type="button" class="btn btn-ghost btn-sm" id="tl-today">Hari ini</button>
            <button type="button" class="btn btn-ghost btn-sm" id="tl-next">&rsaquo;</button>
            <strong id="tl-title"></strong>
            <select id="tl-days">
                <option value="7">7 hari</option>
                <option value="14" selected>14 hari</option>
                <option value="30">30 hari</option>
            </select>
        </div>
        <div class="tl-scroll">
            <div class="tl-body"></div>
        </div>
        <p class="hint">
            <?= $canCreate ? 'Klik area kosong pada baris resource untuk membuat booking dari slot tersebut. ' : '' ?>
            Klik nama resource untuk memfokuskan kalender ke resource itu.
        </p>
    </div>
</div>

<?php if ($canManage): ?>
<!-- DIALOG: TAMBAH BLACKOUT -->
<dialog id="dlg-blackout" class="cal-dialog">
    <form method="post" action="<?= site_url('backoffice/calendar/blackout') ?>">
        <?= csrf_field() ?>
        <h3 class="panel-title">Tambah Blackout</h3>

        <div class="form-grid">
            <div class="full">
                <label for="bo-branch">Cabang (opsional)</label>
                <select name="branch_id" id="bo-branch">
                    <?php if (count($branches) !== 1): ?>
                    <option value="">Semua cabang</option>
                    <?php endif; ?>
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= (int) $b['id'] ?>"><?= esc($b['branch_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="full">
                <label for="bo-res">Resource (opsional)</label>
                <select name="resource_id" id="bo-res">
                    <option value="">Semua resource</option>
                    <?php foreach ($resources as $r): ?>
                    <option value="<?= (int) $r['id'] ?>">
                        <?= esc($r['name']) ?><?= $r['item_name'] !== '' ? ' — ' . esc($r['item_name']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Jika resource dipilih, blackout hanya berlaku untuk resource itu.</div>
            </div>

            <div>
                <label for="bo-start">Mulai</label>
                <input type="date" name="start_date" id="bo-start" required>
            </div>
            <div>
                <label for="bo-end">Selesai (termasuk)</label>
                <input type="date" name="end_date" id="bo-end" required>
            </div>

            <div class="full">
                <label for="bo-reason">Alasan</label>
                <input type="text" name="reason" id="bo-reason" maxlength="255"
                    placeholder="Mis. libur nasional, inventarisasi">
                <div class="hint">Booking yang sudah ada tidak dibatalkan otomatis; sistem hanya memberi peringatan jika
                    bertabrakan.</div>
            </div>
        </div>

        <div class="actions" style="justify-content:flex-end;">
            <button type="button" class="btn btn-ghost" id="bo-cancel">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<style>
.cal-page [hidden] {
    display: none !important;
}

.cal-page {
    --cw: 46px;
}

.cal-bar {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.cal-bar .tabs {
    margin-bottom: 0;
    border-bottom: 0;
}

.cal-legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    font-size: .8rem;
    color: var(--muted);
    padding-bottom: 6px;
}

.cal-legend i {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 3px;
    margin-right: 5px;
}

/* FullCalendar */
#cal-fc {
    padding: 16px;
}

#cal-fc .fc {
    font-size: .88rem;
}

#cal-fc .fc-toolbar-title {
    font-family: 'Fraunces', serif;
    font-size: 1.15rem;
}

#cal-fc .fc-button {
    background: #fff;
    border: 1px solid var(--line);
    color: var(--ink-soft);
    font-weight: 600;
    font-size: .8rem;
    text-transform: none;
    box-shadow: var(--shadow-sm);
}

#cal-fc .fc-button:hover,
#cal-fc .fc-button-primary:not(:disabled):active {
    background: var(--accent-soft);
    border-color: var(--accent);
    color: var(--accent);
}

#cal-fc .fc-event {
    cursor: pointer;
    font-size: .76rem;
    border-radius: 5px;
}

#cal-fc .fc-day-today {
    background: #EEF2FF !important;
}

/* Timeline */
.tl-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.tl-bar strong {
    margin: 0 8px;
    font-size: .92rem;
    font-variant-numeric: tabular-nums;
}

.tl-bar select {
    width: auto;
    height: 34px;
    padding: 4px 10px;
    font-size: .84rem;
}

.tl-scroll {
    overflow: auto;
    border: 1px solid var(--line);
    border-radius: var(--radius-sm);
}

.tl-body {
    min-width: min-content;
}

.tl-row {
    display: flex;
    border-bottom: 1px solid var(--line-soft);
}

.tl-row:last-child {
    border-bottom: 0;
}

.tl-label {
    position: sticky;
    left: 0;
    z-index: 3;
    flex: 0 0 200px;
    background: #F8FAFC;
    border-right: 1px solid var(--line);
    padding: 8px 12px;
    font-size: .84rem;
    overflow: hidden;
}

.tl-label a {
    color: var(--ink);
    font-weight: 600;
    text-decoration: none;
}

.tl-label a:hover {
    color: var(--accent);
    text-decoration: underline;
}

.tl-label small {
    display: block;
    color: var(--muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tl-label .badge {
    font-size: .64rem;
    padding: 2px 7px;
    margin-left: 4px;
    vertical-align: middle;
}

.tl-head .tl-label {
    background: #F1F5F9;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    font-size: .72rem;
    letter-spacing: .05em;
    display: flex;
    align-items: center;
}

.tl-days {
    display: flex;
}

.tl-day {
    flex: 0 0 var(--cw);
    width: var(--cw);
    text-align: center;
    font-size: .68rem;
    padding: 6px 0;
    color: var(--muted);
    border-right: 1px solid var(--line);
    background: #F1F5F9;
}

.tl-day b {
    display: block;
    font-size: .86rem;
    color: var(--ink);
}

.tl-day.we {
    background: #E8EDF3;
}

.tl-day.today {
    background: var(--info-bg);
}

.tl-lane {
    position: relative;
    flex: 0 0 auto;
    cursor: cell;
    background-image: repeating-linear-gradient(to right, transparent 0, transparent calc(var(--cw) - 1px), var(--line) calc(var(--cw) - 1px), var(--line) var(--cw));
}

.tl-lane.no-create {
    cursor: default;
}

.tl-block {
    position: absolute;
    height: 22px;
    line-height: 22px;
    border-radius: 5px;
    padding: 0 7px;
    font-size: .72rem;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-decoration: none;
    cursor: pointer;
}

.tl-block.st-PENDING {
    background: #f59e0b;
}

.tl-block.st-CONFIRMED {
    background: #2563eb;
}

.tl-block.st-ONGOING {
    background: #0891b2;
}

.tl-block.st-COMPLETED {
    background: #16a34a;
}

.tl-block.st-CANCELLED,
.tl-block.st-EXPIRED {
    background: #94a3b8;
}

.tl-block.tl-blackout {
    background: repeating-linear-gradient(45deg, #475569, #475569 6px, #64748b 6px, #64748b 12px);
    cursor: default;
}

.tl-block.tl-maint {
    background: repeating-linear-gradient(45deg, #ea580c, #ea580c 6px, #f97316 6px, #f97316 12px);
    cursor: default;
}

.tl-empty {
    padding: 30px;
    text-align: center;
    color: var(--muted);
    font-size: .9rem;
}

/* Dialog */
.cal-dialog {
    border: 0;
    border-radius: var(--radius);
    padding: 24px;
    width: min(500px, 92vw);
    box-shadow: var(--shadow-lg);
    color: var(--ink);
}

.cal-dialog::backdrop {
    background: rgba(15, 23, 42, .45);
}

@media (max-width: 860px) {
    .tl-label {
        flex-basis: 150px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/id.global.min.js"></script>
<script>
(function() {
    const BASE = <?= json_encode(rtrim(site_url('backoffice'), '/')) ?>;
    const EVENTS_URL = BASE + '/calendar/events';
    const CAN_CREATE = <?= $canCreate ? 'true' : 'false' ?>;

    const COLORS = {
        PENDING: '#f59e0b',
        CONFIRMED: '#2563eb',
        ONGOING: '#0891b2',
        COMPLETED: '#16a34a',
        CANCELLED: '#94a3b8',
        EXPIRED: '#94a3b8'
    };
    const DOW = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    const filters = document.getElementById('cal-filters');
    const fcEl = document.getElementById('cal-fc');
    const tlEl = document.getElementById('cal-tl');
    const tlBody = tlEl.querySelector('.tl-body');
    const tlTitle = document.getElementById('tl-title');
    const errEl = document.getElementById('cal-error');

    let mode = 'dayGridMonth';
    let tlStart = startOfDay(new Date());
    let tlDays = 14;
    let cache = {
        key: null,
        data: null
    };

    // ---------- util ----------
    const pad = n => String(n).padStart(2, '0');

    function startOfDay(d) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }

    function fmt(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function addDays(d, n) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
    }

    function ymd(s) {
        return String(s).slice(0, 10);
    }

    function timePart(s) {
        return String(s).slice(11, 19) || '00:00:00';
    }

    function utc(s) {
        const [y, m, d] = ymd(s).split('-').map(Number);
        return Date.UTC(y, m - 1, d);
    }

    function dayDiff(a, b) {
        return Math.round((utc(b) - utc(a)) / 86400000);
    }

    function iso(s) {
        return String(s).replace(' ', 'T');
    }

    function isAllDay(s, e) {
        return timePart(s) === '00:00:00' && timePart(e) === '00:00:00';
    }

    function range(s, e) {
        return String(s).slice(0, 16) + ' → ' + String(e).slice(0, 16);
    }

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        } [c]));
    }

    function showError(msg) {
        errEl.textContent = msg || '';
        errEl.hidden = !msg;
    }

    // ---------- data ----------
    async function fetchData(start, end) {
        const p = new URLSearchParams();
        new FormData(filters).forEach((v, k) => {
            if (v) p.set(k, v);
        });
        p.set('start', start);
        p.set('end', end);

        const key = p.toString();
        if (cache.key === key) return cache.data;

        const res = await fetch(EVENTS_URL + '?' + key, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!res.ok) throw new Error('Gagal memuat data kalender (HTTP ' + res.status + ').');

        const data = await res.json();
        cache = {
            key,
            data
        };
        showError('');
        return data;
    }

    // ---------- create from slot ----------
    function createFromSlot(o) {
        if (!CAN_CREATE) return;
        const q = new URLSearchParams();
        q.set('start', o.start);
        if (o.end) q.set('end', o.end);
        const br = o.branch_id || filters.elements.branch_id.value;
        if (br) q.set('branch_id', br);
        if (o.resource_id) q.set('resource_id', o.resource_id);
        location.href = BASE + '/bookings/baru?' + q.toString();
    }

    // ---------- FullCalendar ----------
    function toEvents(d) {
        const ev = [];

        d.events.forEach(e => ev.push({
            id: e.id,
            title: e.title,
            start: iso(e.start),
            end: iso(e.end),
            allDay: isAllDay(e.start, e.end),
            url: e.url,
            backgroundColor: COLORS[e.status] || '#64748b',
            borderColor: COLORS[e.status] || '#64748b',
            extendedProps: {
                tip: e.tip
            }
        }));

        d.blackouts.forEach(b => ev.push({
            title: 'Blackout: ' + b.title,
            start: iso(b.start),
            end: iso(b.end),
            allDay: isAllDay(b.start, b.end),
            backgroundColor: '#475569',
            borderColor: '#475569',
            extendedProps: {
                tip: b.tip
            }
        }));

        d.maintenance.forEach(m => ev.push({
            title: 'Maintenance: ' + m.title,
            start: iso(m.start),
            end: iso(m.end),
            allDay: isAllDay(m.start, m.end),
            backgroundColor: '#ea580c',
            borderColor: '#ea580c',
            extendedProps: {
                tip: m.tip
            }
        }));

        return ev;
    }

    const fc = new FullCalendar.Calendar(fcEl, {
        locale: 'id',
        initialView: 'dayGridMonth',
        firstDay: 1,
        height: 'auto',
        selectable: CAN_CREATE,
        dayMaxEvents: 4,
        nowIndicator: true,
        slotDuration: '01:00:00',
        scrollTime: '07:00:00',
        allDayText: 'Seharian',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        buttonText: {
            today: 'Hari ini'
        },
        events: function(info, ok, fail) {
            fetchData(ymd(info.startStr), ymd(info.endStr))
                .then(d => ok(toEvents(d)))
                .catch(e => {
                    showError(e.message);
                    fail(e);
                });
        },
        eventClick: function(i) {
            if (i.event.url) {
                i.jsEvent.preventDefault();
                location.href = i.event.url;
            }
        },
        eventDidMount: function(i) {
            i.el.title = i.event.extendedProps.tip || i.event.title;
        },
        select: function(info) {
            createFromSlot({
                start: ymd(info.startStr),
                end: ymd(info.endStr)
            });
        }
    });
    fc.render();

    // ---------- Timeline resource ----------
    async function renderTimeline() {
        const from = fmt(tlStart);
        const to = fmt(addDays(tlStart, tlDays));
        tlTitle.textContent = from + ' s/d ' + fmt(addDays(tlStart, tlDays - 1));
        tlBody.innerHTML = '<div class="tl-empty">Memuat…</div>';

        let d;
        try {
            d = await fetchData(from, to);
        } catch (e) {
            showError(e.message);
            tlBody.innerHTML = '';
            return;
        }

        if (!d.resources.length) {
            tlBody.innerHTML = '<div class="tl-empty">Tidak ada resource untuk filter ini.</div>';
            return;
        }

        const todayStr = fmt(new Date());
        const laneW = 'calc(var(--cw) * ' + tlDays + ')';

        let head =
            '<div class="tl-row tl-head"><div class="tl-label">Resource</div><div class="tl-days" style="width:' +
            laneW + '">';
        for (let i = 0; i < tlDays; i++) {
            const dt = addDays(tlStart, i);
            const cls = (dt.getDay() === 0 || dt.getDay() === 6 ? ' we' : '') + (fmt(dt) === todayStr ?
                ' today' : '');
            head += '<div class="tl-day' + cls + '">' + DOW[dt.getDay()] + '<b>' + dt.getDate() + '</b></div>';
        }
        head += '</div></div>';

        // indeks hari relatif terhadap awal timeline; jam bukan 00:00 pada akhir = hari itu terpakai
        const idx = (v, isEnd) => {
            let n = dayDiff(from, ymd(v));
            if (isEnd && timePart(v) !== '00:00:00') n += 1;
            return Math.max(0, Math.min(tlDays, n));
        };

        let rows = '';
        d.resources.forEach(r => {
            const items = [];

            d.blocks.filter(b => +b.resource_id === +r.id).forEach(b => items.push({
                cls: 'st-' + b.status,
                label: b.invoice + ' · ' + b.customer + (b.qty > 1 ? ' ×' + b.qty : ''),
                tip: b.invoice + ' · ' + b.customer + '\n' + b.item + ' ×' + b.qty + '\n' +
                    range(b.start, b.end) + '\nStatus: ' + b.status + ' / ' + b
                    .payment_status,
                start: b.start,
                end: b.end,
                url: b.url
            }));

            d.blackouts.filter(x => x.resource_ids === null || x.resource_ids.map(Number).includes(+r
                .id)).forEach(x => items.push({
                cls: 'tl-blackout',
                label: 'Blackout: ' + x.title,
                tip: x.tip + '\n' + range(x.start, x.end),
                start: x.start,
                end: x.end
            }));

            d.maintenance.filter(x => x.resource_ids === null || x.resource_ids.map(Number).includes(+r
                .id)).forEach(x => items.push({
                cls: 'tl-maint',
                label: 'Maintenance: ' + x.title,
                tip: x.tip + '\n' + range(x.start, x.end),
                start: x.start,
                end: x.end
            }));

            items.forEach(it => {
                it.s = idx(it.start, false);
                it.e = idx(it.end, true);
            });
            const vis = items.filter(it => it.e > it.s).sort((a, b) => a.s - b.s || a.e - b.e);

            // susun ke "lane" supaya blok yang bertumpuk tidak saling menutup
            const laneEnds = [];
            vis.forEach(it => {
                let l = laneEnds.findIndex(e => e <= it.s);
                if (l < 0) {
                    l = laneEnds.length;
                    laneEnds.push(0);
                }
                laneEnds[l] = it.e;
                it.lane = l;
            });
            const h = Math.max(1, laneEnds.length) * 26 + 6;

            let blocks = '';
            vis.forEach(it => {
                const style = 'left:calc(var(--cw) * ' + it.s +
                    ' + 1px);width:calc(var(--cw) * ' + (it.e - it.s) + ' - 2px);top:' + (it
                        .lane * 26 + 3) + 'px';
                const common = 'class="tl-block ' + it.cls + '" style="' + style + '" title="' +
                    esc(it.tip) + '"';
                blocks += it.url ?
                    '<a ' + common + ' href="' + esc(it.url) + '">' + esc(it.label) + '</a>' :
                    '<div ' + common + '>' + esc(it.label) + '</div>';
            });

            const sub = [r.code, r.item_name].filter(Boolean).join(' · ');
            const tag = (r.status && r.status !== 'AVAILABLE') ? '<span class="badge badge-warn">' +
                esc(r.status) + '</span>' : '';

            rows += '<div class="tl-row">' +
                '<div class="tl-label"><a href="#" data-focus="' + r.id + '">' + esc(r.name) + '</a>' +
                tag +
                (sub ? '<small>' + esc(sub) + '</small>' : '') + '</div>' +
                '<div class="tl-lane' + (CAN_CREATE ? '' : ' no-create') + '" data-res="' + r.id +
                '" style="width:' + laneW + ';height:' + h + 'px">' + blocks + '</div>' +
                '</div>';
        });

        tlBody.innerHTML = head + rows;
    }

    tlBody.addEventListener('click', function(e) {
        const focus = e.target.closest('[data-focus]');
        if (focus) {
            e.preventDefault();
            filters.elements.resource_id.value = focus.dataset.focus;
            refresh();
            return;
        }
        if (!CAN_CREATE || e.target.closest('.tl-block')) return;

        const lane = e.target.closest('.tl-lane');
        if (!lane) return;
        const rect = lane.getBoundingClientRect();
        const i = Math.max(0, Math.min(tlDays - 1, Math.floor((e.clientX - rect.left) / (rect.width /
            tlDays))));
        createFromSlot({
            start: fmt(addDays(tlStart, i)),
            resource_id: lane.dataset.res
        });
    });

    document.getElementById('tl-prev').onclick = () => {
        tlStart = addDays(tlStart, -Math.max(7, Math.floor(tlDays / 2)));
        renderTimeline();
    };
    document.getElementById('tl-next').onclick = () => {
        tlStart = addDays(tlStart, Math.max(7, Math.floor(tlDays / 2)));
        renderTimeline();
    };
    document.getElementById('tl-today').onclick = () => {
        tlStart = startOfDay(new Date());
        renderTimeline();
    };
    document.getElementById('tl-days').onchange = function() {
        tlDays = parseInt(this.value, 10) || 14;
        renderTimeline();
    };

    // ---------- pindah tampilan ----------
    document.getElementById('cal-tabs').addEventListener('click', function(e) {
        const btn = e.target.closest('button[data-mode]');
        if (!btn) return;

        this.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        mode = btn.dataset.mode;

        if (mode === 'tl') {
            fcEl.hidden = true;
            tlEl.hidden = false;
            renderTimeline();
        } else {
            tlEl.hidden = true;
            fcEl.hidden = false;
            fc.changeView(mode);
            fc.updateSize();
        }
    });

    // ---------- filter ----------
    function refresh() {
        if (mode === 'tl') renderTimeline();
        else fc.refetchEvents();
    }

    function syncResourceOptions() {
        const br = filters.elements.branch_id.value;
        const it = filters.elements.item_id.value;
        const sel = filters.elements.resource_id;
        Array.from(sel.options).forEach(o => {
            if (!o.value) return;
            const bad = (br && o.dataset.branch && o.dataset.branch !== br) ||
                (it && o.dataset.item && o.dataset.item !== it);
            o.hidden = !!bad;
            if (bad && o.selected) sel.value = '';
        });
    }

    filters.addEventListener('change', function() {
        syncResourceOptions();
        refresh();
    });
    document.getElementById('btn-reset').onclick = function() {
        filters.reset();
        syncResourceOptions();
        refresh();
    };

    // ---------- dialog blackout ----------
    const dlg = document.getElementById('dlg-blackout');
    const btnBo = document.getElementById('btn-blackout');
    if (dlg && btnBo) {
        btnBo.onclick = function() {
            const today = fmt(new Date());
            document.getElementById('bo-start').value = today;
            document.getElementById('bo-end').value = today;
            const br = filters.elements.branch_id.value;
            if (br) document.getElementById('bo-branch').value = br;
            const rs = filters.elements.resource_id.value;
            if (rs) document.getElementById('bo-res').value = rs;
            dlg.showModal();
        };
        document.getElementById('bo-cancel').onclick = () => dlg.close();
        document.getElementById('bo-start').addEventListener('change', function() {
            const end = document.getElementById('bo-end');
            if (!end.value || end.value < this.value) end.value = this.value;
        });
    }
})();
</script>

<?= $this->endSection() ?>