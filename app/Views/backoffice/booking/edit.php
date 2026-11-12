<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
$rp  = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$tgl = static function ($v) {
    if (!$v) { return '-'; }
    $t = strtotime($v);
    return date('H:i', $t) === '00:00' ? date('d/m/Y', $t) : date('d/m/Y H:i', $t);
};
$map = [
    'CONFIRMED' => 'badge-info', 'PAID' => 'badge-ok', 'PENDING' => 'badge-warn',
    'UNPAID' => 'badge-warn', 'PARTIAL' => 'badge-warn',
];
$badge = static fn ($s) => $map[$s] ?? 'badge-off';

$jsFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$jsData  = json_encode([
    'catalog'    => $catalog,
    'resources'  => $resources,
    'rows'       => $rows,
    'fees'       => $fees,
    'canReprice' => $canReprice,
    'checkUrl'   => site_url('backoffice/bookings/' . $seg . '/ubah/check'),
    'oldMethod'  => $bk['fulfillment_method'],
], $jsFlags);
?>
<style>
.item-table input,
.item-table select {
    height: 38px;
    padding: 0 8px;
    font-size: .86rem;
}

.item-table td {
    vertical-align: top;
}

.res-cell {
    min-width: 240px;
    font-size: .82rem;
}

.res-ok {
    color: #166534;
}

.res-bad {
    color: #B91C1C;
}

.res-warn {
    color: #92400E;
}

.rm-box {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .8rem;
    margin: 0;
    color: var(--ink);
    cursor: pointer;
}

.rm-box input {
    width: auto;
    height: auto;
}

tr.row-removed td:not(:last-child):not(:nth-last-child(2)) {
    opacity: .45;
}

.cmp {
    width: 100%;
    max-width: 560px;
    margin-top: 14px;
}

.cmp th,
.cmp td {
    padding: 7px 8px;
    font-size: .88rem;
}

.cmp td.num,
.cmp th.num {
    text-align: right;
    white-space: nowrap;
}

.cmp .strong td {
    font-weight: 700;
    border-top: 2px solid var(--ink);
}

.btn-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 16px;
}

.radio-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.radio-row label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 0;
    font-size: .9rem;
    color: var(--ink);
    font-weight: 500;
    cursor: pointer;
}

.radio-row input {
    width: auto;
    height: auto;
    margin-top: 3px;
}
</style>

<div class="page-head">
    <div>
        <a href="<?= site_url('backoffice/bookings/' . $seg) ?>" class="muted">&larr; Kembali ke detail</a>
        <h1 style="margin-top:4px;">Ubah <?= esc($bk['invoice_no']) ?></h1>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">
            <span class="badge <?= esc($badge($bk['status']), 'attr') ?>"><?= esc($bk['status']) ?></span>
            <span
                class="badge <?= esc($badge($bk['payment_status']), 'attr') ?>"><?= esc($bk['payment_status']) ?></span>
            <?php if ($branch): ?><span class="badge badge-off"><?= esc($branch['branch_name']) ?></span><?php endif; ?>
            <span class="badge badge-off"><?= esc($bk['customer_name_snapshot']) ?></span>
        </div>
    </div>
</div>

<!-- ============ Kondisi saat ini ============ -->
<div class="panel">
    <h2 class="panel-title">Kondisi saat ini</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Periode</th>
                    <th>Unit</th>
                    <th>Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= esc($it['item_name_snapshot']) ?></td>
                    <td><?= esc(rtrim(rtrim((string) $it['quantity'], '0'), '.')) ?></td>
                    <td><?= esc($tgl($it['start_at'])) ?> <span class="muted">s/d <?= esc($tgl($it['end_at'])) ?></span>
                    </td>
                    <td><?= esc(implode(', ', $allocNames[(int) $it['id']] ?? ['-'])) ?></td>
                    <td><?= esc($rp($it['subtotal'])) ?><?php if ((float) $it['discount_amount'] > 0): ?>
                        <div class="muted">diskon <?= esc($rp($it['discount_amount'])) ?></div><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <dl class="dl" style="margin-top:12px;max-width:560px;">
        <dt>Pengambilan</dt>
        <dd><?= esc($fulfillment[$bk['fulfillment_method']] ?? $bk['fulfillment_method']) ?><?= $bk['pickup_address'] ? ' · ' . esc($bk['pickup_address']) : '' ?>
        </dd>
        <dt>Total</dt>
        <dd><?= esc($rp($bk['grand_total'])) ?> <span class="muted">(deposit
                <?= esc($rp($bk['deposit_total'])) ?>)</span></dd>
        <dt>Terbayar</dt>
        <dd><?= esc($rp($bk['paid_total'])) ?></dd>
        <dt>Sisa tagihan</dt>
        <dd><?= esc($rp($bk['balance_due'])) ?></dd>
    </dl>
</div>

<form method="post" action="<?= site_url('backoffice/bookings/' . $seg . '/ubah') ?>" id="editForm" autocomplete="off">
    <?= csrf_field() ?>

    <!-- ============ Perubahan ============ -->
    <div class="panel" style="margin-top:20px;">
        <h2 class="panel-title">Perubahan (sesudah)</h2>
        <p class="muted" style="margin-top:0;">Ubah item, jumlah, atau jadwal pada baris di bawah. Item yang diganti
            atau dihapus tidak dihapus dari database: ditandai batal dan unitnya dilepas.</p>

        <div class="table-wrap">
            <table class="item-table">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Item</th>
                        <th style="min-width:170px;">Unit</th>
                        <th style="min-width:150px;">Mulai</th>
                        <th style="min-width:150px;">Selesai</th>
                        <th style="width:80px;">Qty</th>
                        <?php if ($canReprice): ?><th style="width:130px;">Harga satuan manual</th><?php endif; ?>
                        <th>Hasil</th>
                        <th style="width:90px;"></th>
                    </tr>
                </thead>
                <tbody id="itemRows"></tbody>
            </table>
        </div>

        <div class="btn-row">
            <button type="button" class="btn btn-ghost btn-sm" id="addRowBtn">+ Tambah item</button>
        </div>

        <h3 class="sub-title">Pengambilan</h3>
        <div class="form-grid">
            <div>
                <label for="fulfillment">Metode</label>
                <select id="fulfillment" name="fulfillment_method">
                    <?php foreach ($fulfillment as $k => $v): ?>
                    <option value="<?= esc($k, 'attr') ?>" <?= $bk['fulfillment_method'] === $k ? 'selected' : '' ?>>
                        <?= esc($v) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint" id="feeHint"></div>
            </div>
            <div></div>
            <div>
                <label for="addrIn">Alamat pengantaran / lokasi</label>
                <textarea id="addrIn" name="pickup_address" rows="2"
                    maxlength="500"><?= esc($bk['pickup_address']) ?></textarea>
            </div>
            <div>
                <label for="addrOut">Alamat pengambilan kembali (kosong = sama)</label>
                <textarea id="addrOut" name="return_address" rows="2"
                    maxlength="500"><?= esc($bk['return_address']) ?></textarea>
            </div>
        </div>
    </div>

    <!-- ============ Harga ============ -->
    <div class="panel" style="margin-top:20px;">
        <h2 class="panel-title">Harga</h2>
        <div class="radio-row">
            <label>
                <input type="radio" name="price_mode" value="KEEP" checked>
                <span><strong>Pertahankan harga</strong><br>
                    <span class="muted">Item yang tidak berubah durasi dan jumlahnya tetap memakai harga lama. Jika
                        durasi atau jumlah berubah, harga satuan lama dikalikan durasi baru. Item baru memakai aturan
                        harga saat ini.</span></span>
            </label>
            <label>
                <input type="radio" name="price_mode" value="RECALC" <?= $canReprice ? '' : 'disabled' ?>>
                <span><strong>Hitung ulang sesuai aturan harga</strong>
                    <?= $canReprice ? '' : '<span class="badge badge-off">butuh izin</span>' ?><br>
                    <span class="muted">Semua item dihitung ulang dengan aturan harga (surcharge, diskon) yang berlaku
                        sekarang.</span></span>
            </label>
        </div>
    </div>

    <!-- ============ Dampak & simpan ============ -->
    <div class="panel" style="margin-top:20px;">
        <h2 class="panel-title">Dampak &amp; alasan</h2>

        <div class="btn-row" style="margin-top:0;">
            <button type="button" class="btn btn-ghost btn-sm" id="checkBtn">Cek ketersediaan &amp; dampak</button>
        </div>
        <div class="hint" id="checkMsg"></div>
        <div id="impactBox" hidden></div>

        <div style="margin-top:16px;max-width:560px;">
            <label for="reason">Alasan perubahan (wajib, tercatat di riwayat)</label>
            <input type="text" id="reason" name="reason" maxlength="240" required>
        </div>

        <div class="btn-row">
            <button type="button" class="btn btn-primary" id="saveBtn" disabled>Simpan perubahan</button>
            <a href="<?= site_url('backoffice/bookings/' . $seg) ?>" class="btn btn-ghost">Batalkan perubahan</a>
        </div>
        <div class="hint" style="margin-top:8px;">
            Tombol simpan aktif setelah pemeriksaan berhasil. Setiap perubahan pada form meminta pemeriksaan ulang.
            Ketersediaan diperiksa lagi saat disimpan.
            Pembayaran yang sudah masuk tidak pernah dihapus; kelebihan bayar dicatat sebagai refund dari halaman
            detail.
        </div>
    </div>
</form>

<script>
(function() {
    var D = <?= $jsData ?>;
    var PREFILL = <?= $prefill ?>;
    var form = document.getElementById('editForm');
    var rowsBody = document.getElementById('itemRows');
    var saveBtn = document.getElementById('saveBtn');
    var checkMsg = document.getElementById('checkMsg');
    var impactBox = document.getElementById('impactBox');
    var catMap = {};
    D.catalog.forEach(function(c) {
        catMap[c.id] = c;
    });
    var seq = 0;
    var lastImpact = null;

    function rp(n) {
        return 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');
    }

    function el(tag, attrs, kids) {
        var e = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function(k) {
            if (k === 'text') {
                e.textContent = attrs[k];
            } else if (k === 'class') {
                e.className = attrs[k];
            } else {
                e.setAttribute(k, attrs[k]);
            }
        });
        (kids || []).forEach(function(c) {
            if (c) {
                e.appendChild(c);
            }
        });
        return e;
    }

    function isCalendar(itemId) {
        var c = catMap[itemId];
        return c && (c.pricing_unit === 'DAY' || c.pricing_unit === 'NIGHT');
    }

    function setInputType(inp, itemId) {
        var t = isCalendar(itemId) ? 'date' : 'datetime-local';
        if (inp.type !== t) {
            inp.value = '';
            inp.type = t;
        }
    }

    // ---- status "perlu dicek ulang" ----
    function dirty() {
        saveBtn.disabled = true;
        lastImpact = null;
        impactBox.hidden = true;
        impactBox.textContent = '';
        checkMsg.textContent = '';
        rowsBody.querySelectorAll('.res-cell').forEach(function(c) {
            c.textContent = '';
        });
    }

    function resourceOptions(sel, itemId, keep) {
        sel.textContent = '';
        sel.appendChild(el('option', {
            value: '',
            text: 'Otomatis'
        }));
        D.resources.forEach(function(r) {
            if (String(r.catalog_item_id) === String(itemId)) {
                sel.appendChild(el('option', {
                    value: r.id,
                    text: r.resource_name
                }));
            }
        });
        if (keep) {
            sel.value = keep;
        }
    }

    function addRow(v) {
        v = v || {};
        var n = seq++;
        var tr = el('tr');
        var hidRow = el('input', {
            type: 'hidden',
            name: 'items[' + n + '][row_id]',
            value: v.row_id || ''
        });

        var itemSel = el('select', {
            name: 'items[' + n + '][item_id]',
            required: 'required'
        });
        itemSel.appendChild(el('option', {
            value: '',
            text: 'Pilih item…'
        }));
        D.catalog.forEach(function(c) {
            itemSel.appendChild(el('option', {
                value: c.id,
                text: c.name
            }));
        });
        // item lama yang sudah nonaktif tetap bisa dipilih
        if (v.item_id && !catMap[v.item_id]) {
            itemSel.appendChild(el('option', {
                value: v.item_id,
                text: v.name || ('Item #' + v.item_id)
            }));
            catMap[v.item_id] = {
                id: v.item_id,
                name: v.name,
                pricing_unit: 'DAY'
            };
        }

        var resSel = el('select', {
            name: 'items[' + n + '][resource_id]'
        });
        var startIn = el('input', {
            type: 'date',
            name: 'items[' + n + '][start]',
            required: 'required'
        });
        var endIn = el('input', {
            type: 'date',
            name: 'items[' + n + '][end]',
            required: 'required'
        });
        var qtyIn = el('input', {
            type: 'number',
            name: 'items[' + n + '][qty]',
            min: '1',
            step: '1',
            value: v.qty || '1',
            required: 'required'
        });
        var ovIn = D.canReprice ? el('input', {
            type: 'number',
            name: 'items[' + n + '][price_override]',
            min: '0',
            step: '1',
            placeholder: 'sesuai mode'
        }) : null;
        var rmIn = null;
        var resCell = el('td', {
            class: 'res-cell'
        });
        var last = el('td');

        if (v.row_id) {
            rmIn = el('input', {
                type: 'checkbox',
                name: 'items[' + n + '][remove]',
                value: '1'
            });
            last.appendChild(el('label', {
                class: 'rm-box'
            }, [rmIn, el('span', {
                text: 'Hapus'
            })]));
            rmIn.addEventListener('change', function() {
                tr.className = rmIn.checked ? 'row-removed' : '';
                dirty();
            });
        } else {
            var del = el('button', {
                type: 'button',
                class: 'btn btn-ghost btn-sm btn-danger',
                text: '×',
                title: 'Hapus baris'
            });
            del.addEventListener('click', function() {
                tr.remove();
                dirty();
            });
            last.appendChild(del);
        }

        itemSel.addEventListener('change', function() {
            setInputType(startIn, itemSel.value);
            setInputType(endIn, itemSel.value);
            resourceOptions(resSel, itemSel.value, null);
            dirty();
        });
        [resSel, startIn, endIn, qtyIn].concat(ovIn ? [ovIn] : []).forEach(function(x) {
            x.addEventListener('change', dirty);
        });
        if (ovIn) {
            ovIn.addEventListener('input', dirty);
        }

        tr.appendChild(el('td', {}, [hidRow, itemSel]));
        tr.appendChild(el('td', {}, [resSel]));
        tr.appendChild(el('td', {}, [startIn]));
        tr.appendChild(el('td', {}, [endIn]));
        tr.appendChild(el('td', {}, [qtyIn]));
        if (ovIn) {
            tr.appendChild(el('td', {}, [ovIn]));
        }
        tr.appendChild(resCell);
        tr.appendChild(last);
        rowsBody.appendChild(tr);

        if (v.item_id) {
            itemSel.value = v.item_id;
            setInputType(startIn, v.item_id);
            setInputType(endIn, v.item_id);
            resourceOptions(resSel, v.item_id, v.resource_id || null);
            if (v.start) {
                startIn.value = v.start;
            }
            if (v.end) {
                endIn.value = v.end;
            }
            if (ovIn && v.price_override) {
                ovIn.value = v.price_override;
            }
            if (rmIn && v.remove) {
                rmIn.checked = true;
                tr.className = 'row-removed';
            }
        } else {
            resourceOptions(resSel, '', null);
        }
        if (v.alloc) {
            resCell.appendChild(el('div', {
                class: 'muted',
                text: 'Saat ini: ' + v.alloc
            }));
            resCell.setAttribute('data-initial', v.alloc);
        }
        return tr;
    }

    document.getElementById('addRowBtn').addEventListener('click', function() {
        addRow();
        dirty();
    });

    // ---- pengambilan ----
    var fSel = document.getElementById('fulfillment');
    var feeHint = document.getElementById('feeHint');

    function applyFulfillment() {
        if (fSel.value !== D.oldMethod) {
            var fee = D.fees[fSel.value] || 0;
            feeHint.textContent = 'Biaya pengambilan tidak berubah otomatis. Tarif standar metode ini ' + rp(fee) +
                '; atur di tab Biaya pada halaman detail bila perlu.';
        } else {
            feeHint.textContent = '';
        }
    }
    fSel.addEventListener('change', function() {
        applyFulfillment();
        dirty();
    });
    ['addrIn', 'addrOut'].forEach(function(id) {
        document.getElementById(id).addEventListener('input', dirty);
    });
    form.querySelectorAll('[name=price_mode]').forEach(function(r) {
        r.addEventListener('change', dirty);
    });

    // ---- hasil per baris ----
    var ACTION_LABEL = {
        KEEP: 'Tidak berubah',
        REPLACE: 'Diganti: item lama dilepas',
        ADD: 'Item baru',
        REMOVE: 'Dihapus: unit dilepas',
        SKIP: ''
    };

    function renderRow(cell, r) {
        cell.textContent = '';
        if (r.action === 'REMOVE') {
            cell.appendChild(el('div', {
                class: 'res-warn',
                text: ACTION_LABEL.REMOVE
            }));
            return;
        }
        if (!r.ok) {
            cell.appendChild(el('div', {
                class: 'res-bad',
                text: '✗ ' + (r.message || 'Tidak valid')
            }));
            (r.reasons || []).forEach(function(x) {
                cell.appendChild(el('div', {
                    class: 'muted',
                    text: x
                }));
            });
            return;
        }
        if (r.action === 'KEEP') {
            cell.appendChild(el('div', {
                class: 'res-ok',
                text: '✓ ' + ACTION_LABEL.KEEP
            }));
        } else {
            var names = (r.allocations || []).map(function(a) {
                return a.name + (a.qty > 1 ? ' ×' + a.qty : '');
            }).join(', ');
            cell.appendChild(el('div', {
                class: 'res-ok',
                text: '✓ ' + ACTION_LABEL[r.action] + (names ? ' → ' + names : '')
            }));
        }
        if (r.subtotal !== undefined && (r.action !== 'KEEP' || r.price_changed)) {
            var line = r.duration + ' ' + (r.unit || '').toLowerCase() + ' · ' + rp(r.unit_price) + ' → ' + rp(r
                .subtotal);
            if (r.discount > 0) {
                line += ' − diskon ' + rp(r.discount);
            }
            cell.appendChild(el('div', {
                text: line
            }));
            (r.lines || []).forEach(function(l) {
                cell.appendChild(el('div', {
                    class: 'muted',
                    text: (l.type === 'SURCHARGE' ? '+ ' : '− ') + l.name + ' ' + rp(l.amount)
                }));
            });
            if (r.kept) {
                cell.appendChild(el('div', {
                    class: 'muted',
                    text: 'Harga lama dipertahankan'
                }));
            }
            if (r.overridden) {
                cell.appendChild(el('div', {
                    class: 'res-warn',
                    text: 'Harga manual'
                }));
            }
        }
        (r.warnings || []).forEach(function(w) {
            cell.appendChild(el('div', {
                class: 'res-warn',
                text: '⚠ ' + w
            }));
        });
    }

    // ---- dampak ----
    function renderImpact(im) {
        impactBox.textContent = '';
        impactBox.hidden = false;
        var t = el('table', {
            class: 'cmp'
        });
        t.appendChild(el('thead', {}, [el('tr', {}, [
            el('th', {
                text: ''
            }), el('th', {
                class: 'num',
                text: 'Sebelum'
            }),
            el('th', {
                class: 'num',
                text: 'Sesudah'
            }), el('th', {
                class: 'num',
                text: 'Selisih'
            })
        ])]));
        var tb = el('tbody');
        [
            ['Subtotal', 'subtotal'],
            ['Diskon', 'discount'],
            ['Biaya tambahan', 'charge'],
            ['Deposit', 'deposit'],
            ['Total', 'grand', true],
            ['Terbayar', 'paid'],
            ['Sisa tagihan', 'balance']
        ].forEach(function(x) {
            var a = im.old[x[1]],
                b = im.new[x[1]],
                d = b - a;
            tb.appendChild(el('tr', {
                class: x[2] ? 'strong' : ''
            }, [
                el('td', {
                    text: x[0]
                }), el('td', {
                    class: 'num',
                    text: rp(a)
                }), el('td', {
                    class: 'num',
                    text: rp(b)
                }),
                el('td', {
                    class: 'num',
                    text: d === 0 ? '–' : ((d > 0 ? '+ ' : '− ') + rp(Math.abs(d)))
                })
            ]));
        });
        t.appendChild(tb);
        impactBox.appendChild(t);

        var msg, cls = 'muted';
        if (im.credit > 0) {
            msg = 'Pembayaran melebihi tagihan baru sebesar ' + rp(im.credit) +
                '. Setelah disimpan, catat refund di tab Pembayaran bila perlu.';
            cls = 'res-warn';
        } else if (im.new.balance > im.old.balance) {
            msg = 'Tagihan bertambah ' + rp(im.new.balance - im.old.balance) + '. Sisa yang harus dibayar: ' + rp(im
                .new.balance) + '.';
            cls = 'res-warn';
        } else if (im.new.balance < im.old.balance) {
            msg = 'Sisa tagihan berkurang menjadi ' + rp(im.new.balance) + '.';
        } else {
            msg = 'Tidak ada perubahan pada sisa tagihan.';
        }
        impactBox.appendChild(el('div', {
            class: cls,
            style: 'margin-top:8px;',
            text: msg
        }));
    }

    function runCheck() {
        var fd = new FormData(form);
        var p = new URLSearchParams();
        fd.forEach(function(val, key) {
            if (key.toLowerCase().indexOf('csrf') === -1) {
                p.append(key, val);
            }
        });
        checkMsg.textContent = 'Memeriksa…';
        saveBtn.disabled = true;
        return fetch(D.checkUrl + '?' + p.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(j) {
                checkMsg.textContent = '';
                var trs = rowsBody.querySelectorAll('tr');
                Object.keys(j.rows || {}).forEach(function(i) {
                    if (trs[i]) {
                        renderRow(trs[i].querySelector('.res-cell'), j.rows[i]);
                    }
                });
                if (j.impact) {
                    renderImpact(j.impact);
                    lastImpact = j.impact;
                }
                if (j.errors && j.errors.length) {
                    checkMsg.textContent = j.errors.join(' | ');
                }
                saveBtn.disabled = !j.ok;
                if (j.ok) {
                    checkMsg.textContent = 'Semua pemeriksaan lolos. Isi alasan lalu simpan.';
                }
            })
            .catch(function() {
                checkMsg.textContent = 'Gagal memeriksa. Coba lagi.';
            });
    }
    document.getElementById('checkBtn').addEventListener('click', function() {
        if (form.querySelector('#reason') && !rowsBody.children.length) {
            checkMsg.textContent = 'Booking harus punya minimal satu item.';
            return;
        }
        var req = Array.prototype.filter.call(form.querySelectorAll('[required]'), function(x) {
            return x.id !== 'reason' && !x.checkValidity();
        });
        if (req.length) {
            req[0].reportValidity();
            return;
        }
        runCheck();
    });

    // ---- simpan ----
    var saving = false;
    saveBtn.addEventListener('click', function() {
        if (saving || saveBtn.disabled) {
            return;
        }
        var reason = document.getElementById('reason');
        if (reason.value.trim().length < 3) {
            reason.reportValidity();
            alert('Alasan perubahan wajib diisi.');
            return;
        }
        var m = 'Simpan perubahan?';
        if (lastImpact) {
            m += '\nTotal baru ' + rp(lastImpact.new.grand) + ', sisa tagihan ' + rp(lastImpact.new
                .balance) + '.';
        }
        if (!confirm(m)) {
            return;
        }
        saving = true;
        saveBtn.disabled = true;
        form.submit();
    });

    // ---- isi awal ----
    var initial = D.rows;
    if (PREFILL && typeof PREFILL === 'object') {
        var list = PREFILL.items ? (Array.isArray(PREFILL.items) ? PREFILL.items : Object.keys(PREFILL.items).map(
            function(k) {
                return PREFILL.items[k];
            })) : [];
        if (list.length) {
            initial = list.map(function(it) {
                var base = D.rows.filter(function(r) {
                    return String(r.row_id) === String(it.row_id);
                })[0] || {};
                return {
                    row_id: it.row_id || '',
                    item_id: it.item_id,
                    resource_id: it.resource_id,
                    start: it.start,
                    end: it.end,
                    qty: it.qty,
                    price_override: it.price_override,
                    remove: !!it.remove,
                    name: base.name,
                    alloc: base.alloc
                };
            });
        }
        ['pickup_address', 'return_address', 'reason'].forEach(function(k) {
            var f = form.querySelector('[name="' + k + '"]');
            if (f && PREFILL[k] !== undefined) {
                f.value = PREFILL[k];
            }
        });
        if (PREFILL.fulfillment_method) {
            fSel.value = PREFILL.fulfillment_method;
        }
        if (PREFILL.price_mode === 'RECALC' && D.canReprice) {
            form.querySelector('[name=price_mode][value=RECALC]').checked = true;
        }
    }
    initial.forEach(function(r) {
        addRow(r);
    });
    applyFulfillment();
})();
</script>

<?= $this->endSection() ?>