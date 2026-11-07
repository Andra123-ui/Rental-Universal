<?= $this->extend('backoffice/layout') ?>
<?= $this->section('content') ?>
<?php
$jsFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$jsData  = json_encode([
    'items'       => $items,
    'resources'   => $resources,
    'fees'        => $fees,
    'lockBranch'  => $lockBranch,
    'canOverride' => $canOverride,
    'checkUrl'    => site_url('backoffice/bookings/check'),
    'customersUrl' => site_url('backoffice/bookings/customers'),
], $jsFlags);
?>
<style>
.cust-mode {
    display: flex;
    gap: 18px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.cust-mode label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    font-size: .9rem;
    color: var(--ink);
    cursor: pointer;
}

.cust-mode input {
    width: auto;
    height: auto;
}

.cust-results {
    margin-top: 8px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
}

.cust-results:empty {
    display: none;
}

.cust-results button {
    display: block;
    width: 100%;
    padding: 9px 12px;
    border: 0;
    border-bottom: 1px solid var(--line);
    background: #fff;
    text-align: left;
    font: inherit;
    cursor: pointer;
}

.cust-results button:last-child {
    border-bottom: 0;
}

.cust-results button:hover {
    background: var(--paper);
}

.cust-picked {
    margin-top: 10px;
    padding: 10px 12px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--paper);
}

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

.sum-box {
    margin-top: 14px;
    padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--paper);
    max-width: 380px;
    margin-left: auto;
}

.sum-box div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 3px 0;
    font-size: .9rem;
}

.sum-box .grand {
    border-top: 2px solid var(--ink);
    margin-top: 6px;
    padding-top: 8px;
    font-weight: 700;
}

.btn-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 16px;
}
</style>

<div class="page-head">
    <div>
        <a href="<?= site_url('backoffice/bookings') ?>" class="muted">&larr; Daftar booking</a>
        <h1 style="margin-top:4px;">Booking baru</h1>
    </div>
</div>

<form method="post" action="<?= site_url('backoffice/bookings/simpan') ?>" id="bookingForm" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="actionField" value="PENDING">

    <!-- ===== Customer ===== -->
    <div class="panel">
        <h2 class="panel-title">1. Customer</h2>
        <div class="cust-mode">
            <label><input type="radio" name="customer_mode" value="existing" checked> Customer yang sudah ada</label>
            <label><input type="radio" name="customer_mode" value="new"> Customer baru</label>
        </div>

        <div id="custExisting">
            <label for="custQ">Cari nama, nomor HP, atau email</label>
            <input type="text" id="custQ" placeholder="Ketik minimal 2 karakter">
            <div class="cust-results" id="custResults"></div>
            <input type="hidden" name="customer_id" id="customerId" value="">
            <input type="hidden" name="customer_label" id="customerLabel" value="">
            <div class="cust-picked" id="custPicked" hidden></div>
        </div>

        <div id="custNew" hidden>
            <div class="form-grid">
                <div>
                    <label for="newName">Nama lengkap</label>
                    <input type="text" id="newName" name="new_name" maxlength="150">
                </div>
                <div>
                    <label for="newPhone">Nomor HP / WhatsApp</label>
                    <input type="text" id="newPhone" name="new_phone" placeholder="08xxxxxxxxxx" maxlength="20">
                    <div class="hint">Disimpan dalam format +62. Akun login tidak dibuat; customer bisa masuk sendiri
                        nanti lewat OTP dengan nomor ini.</div>
                </div>
                <div class="full">
                    <label for="newEmail">Email (opsional)</label>
                    <input type="email" id="newEmail" name="new_email" maxlength="150">
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Info booking ===== -->
    <div class="panel" style="margin-top:20px;">
        <h2 class="panel-title">2. Informasi booking</h2>
        <div class="form-grid">
            <div>
                <label for="branchId">Cabang</label>
                <?php if ($lockBranch): ?>
                <select id="branchId" disabled>
                    <?php foreach ($branches as $b): if ((int) $b['id'] === (int) $lockBranch): ?>
                    <option><?= esc($b['branch_name']) ?></option>
                    <?php endif; endforeach; ?>
                </select>
                <input type="hidden" name="branch_id" value="<?= (int) $lockBranch ?>">
                <?php else: ?>
                <select id="branchId" name="branch_id" required>
                    <option value="">Pilih cabang…</option>
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= (int) $b['id'] ?>"><?= esc($b['branch_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            <div>
                <label for="source">Sumber</label>
                <select id="source" name="booking_source" required>
                    <?php foreach ($sources as $k => $v): ?>
                    <option value="<?= esc($k, 'attr') ?>"><?= esc($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="fulfillment">Pengambilan</label>
                <select id="fulfillment" name="fulfillment_method" required>
                    <?php foreach ($fulfillment as $k => $v): ?>
                    <option value="<?= esc($k, 'attr') ?>">
                        <?= esc($v) ?><?= ($fees[$k] ?? 0) > 0 ? ' (+Rp ' . number_format($fees[$k], 0, ',', '.') . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
            <div class="full" id="addrBox" hidden>
                <div class="form-grid">
                    <div>
                        <label for="addrIn">Alamat pengantaran / lokasi</label>
                        <textarea id="addrIn" name="pickup_address" rows="2" maxlength="500"></textarea>
                    </div>
                    <div>
                        <label for="addrOut">Alamat pengambilan kembali (kosong = sama)</label>
                        <textarea id="addrOut" name="return_address" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Item ===== -->
    <div class="panel" style="margin-top:20px;">
        <h2 class="panel-title">3. Item &amp; jadwal</h2>
        <div class="table-wrap">
            <table class="item-table">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Item</th>
                        <th style="min-width:170px;">Unit</th>
                        <th style="min-width:150px;">Mulai</th>
                        <th style="min-width:150px;">Selesai</th>
                        <th style="width:80px;">Qty</th>
                        <?php if ($canOverride): ?><th style="width:130px;">Harga satuan manual</th><?php endif; ?>
                        <th>Hasil pemeriksaan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemRows"></tbody>
            </table>
        </div>

        <div class="btn-row">
            <button type="button" class="btn btn-ghost btn-sm" id="addRowBtn">+ Tambah item</button>
            <button type="button" class="btn btn-ghost btn-sm" id="checkBtn">Cek ketersediaan &amp; harga</button>
        </div>
        <div class="hint" id="checkMsg"></div>

        <?php if ($canOverride): ?>
        <div id="reasonBox" hidden style="margin-top:14px;">
            <label for="priceReason">Alasan perubahan harga (wajib karena ada harga manual)</label>
            <input type="text" id="priceReason" name="price_reason" maxlength="240">
        </div>
        <?php endif; ?>

        <div class="sum-box" id="sumBox" hidden></div>
    </div>

    <!-- ===== Catatan & simpan ===== -->
    <div class="panel" style="margin-top:20px;">
        <h2 class="panel-title">4. Catatan &amp; simpan</h2>
        <div class="form-grid">
            <div>
                <label for="custNotes">Catatan dari customer</label>
                <textarea id="custNotes" name="customer_notes" rows="2" maxlength="1000"></textarea>
            </div>
            <div>
                <label for="intNotes">Catatan internal (tidak terlihat customer)</label>
                <textarea id="intNotes" name="internal_notes" rows="2" maxlength="1000"></textarea>
            </div>
            <div class="full">
                <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                    <input type="checkbox" name="invite" value="1" style="width:auto;height:auto;">
                    Siapkan pesan info login untuk customer setelah booking disimpan
                </label>
                <div class="hint">Hanya membuat teks dan tautan WhatsApp untuk dikirim manual. Tidak ada akun yang
                    dibuat dan tidak ada pesan terkirim otomatis.</div>
            </div>
        </div>

        <div class="btn-row">
            <button type="button" class="btn btn-ghost" data-save="PENDING">Simpan pending</button>
            <button type="button" class="btn btn-primary" data-save="CONFIRMED">Simpan &amp; confirm</button>
            <a href="<?= site_url('backoffice/bookings') ?>" class="btn btn-ghost">Batal</a>
        </div>
        <div class="hint" style="margin-top:8px;">
            Pending menahan unit sampai batas waktu pembayaran. Confirm langsung mengunci unit tanpa batas waktu.
            Ketersediaan diperiksa ulang saat disimpan.
        </div>
    </div>
</form>

<script>
(function() {
    var D = <?= $jsData ?>;
    var PREFILL = <?= $prefill ?>;
    var form = document.getElementById('bookingForm');
    var rowsBody = document.getElementById('itemRows');
    var branchSel = document.getElementById('branchId');
    var itemMap = {};
    D.items.forEach(function(i) {
        itemMap[i.id] = i;
    });
    var seq = 0;

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

    function currentBranch() {
        return D.lockBranch ? String(D.lockBranch) : (branchSel ? branchSel.value : '');
    }

    function isCalendar(itemId) {
        var i = itemMap[itemId];
        return i && (i.pricing_unit === 'DAY' || i.pricing_unit === 'NIGHT');
    }

    // ---------------- Customer ----------------
    var modeRadios = form.querySelectorAll('[name=customer_mode]');
    var boxEx = document.getElementById('custExisting');
    var boxNew = document.getElementById('custNew');

    function applyMode() {
        var m = form.querySelector('[name=customer_mode]:checked').value;
        boxEx.hidden = m !== 'existing';
        boxNew.hidden = m !== 'new';
    }
    modeRadios.forEach(function(r) {
        r.addEventListener('change', applyMode);
    });

    var q = document.getElementById('custQ');
    var results = document.getElementById('custResults');
    var picked = document.getElementById('custPicked');
    var timer = null;

    function accountText(s) {
        if (!s) {
            return 'Tanpa akun';
        }
        if (s === 'ACTIVE') {
            return 'Akun aktif';
        }
        if (s === 'PENDING') {
            return 'Akun belum diverifikasi';
        }
        return 'Akun ' + s.toLowerCase();
    }

    function pick(c) {
        document.getElementById('customerId').value = c.id;
        document.getElementById('customerLabel').value = c.name;
        picked.hidden = false;
        picked.textContent = '';
        picked.appendChild(el('strong', {
            text: c.name
        }));
        picked.appendChild(el('div', {
            class: 'muted',
            text: (c.phone || '') + (c.email ? ' · ' + c.email : '') + ' · ' + accountText(c
                .account_status)
        }));
        if (c.account_status === 'SUSPENDED' || c.account_status === 'BLOCKED') {
            picked.appendChild(el('div', {
                class: 'res-bad',
                text: 'Peringatan: akun login customer ini ditangguhkan/diblokir. Booking manual tetap bisa dibuat.'
            }));
        }
        results.textContent = '';
        q.value = '';
    }
    q.addEventListener('input', function() {
        clearTimeout(timer);
        var v = q.value.trim();
        if (v.length < 2) {
            results.textContent = '';
            return;
        }
        timer = setTimeout(function() {
            fetch(D.customersUrl + '?q=' + encodeURIComponent(v), {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(j) {
                    results.textContent = '';
                    if (!j.results.length) {
                        results.appendChild(el('div', {
                            class: 'muted',
                            style: 'padding:10px 12px;',
                            text: 'Tidak ditemukan. Pilih "Customer baru" untuk menambahkan.'
                        }));
                        return;
                    }
                    j.results.forEach(function(c) {
                        var b = el('button', {
                            type: 'button'
                        }, [
                            el('strong', {
                                text: c.name
                            }),
                            el('span', {
                                class: 'muted',
                                text: '  ' + (c.phone || '') + ' · ' +
                                    accountText(c.account_status)
                            })
                        ]);
                        b.addEventListener('click', function() {
                            pick(c);
                        });
                        results.appendChild(b);
                    });
                })
                .catch(function() {
                    results.textContent = '';
                });
        }, 300);
    });

    // ---------------- Pengambilan ----------------
    var fSel = document.getElementById('fulfillment');
    var addrBox = document.getElementById('addrBox');

    function applyFulfillment() {
        var v = fSel.value;
        addrBox.hidden = !(v === 'DELIVERY' || v === 'ONSITE');
    }
    fSel.addEventListener('change', function() {
        applyFulfillment();
        clearResults();
    });

    // ---------------- Baris item ----------------
    function resourceOptions(sel, itemId, keep) {
        sel.textContent = '';
        sel.appendChild(el('option', {
            value: '',
            text: 'Otomatis'
        }));
        var b = currentBranch();
        D.resources.forEach(function(r) {
            if (String(r.catalog_item_id) === String(itemId) && (!b || String(r.branch_id) === b)) {
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

    function setInputType(inp, itemId) {
        var t = isCalendar(itemId) ? 'date' : 'datetime-local';
        if (inp.type !== t) {
            inp.value = '';
            inp.type = t;
        }
    }

    function addRow(v) {
        v = v || {};
        var n = seq++;
        var tr = el('tr', {
            'data-row': n
        });

        var itemSel = el('select', {
            name: 'items[' + n + '][item_id]',
            required: 'required'
        });
        itemSel.appendChild(el('option', {
            value: '',
            text: 'Pilih item…'
        }));
        D.items.forEach(function(i) {
            itemSel.appendChild(el('option', {
                value: i.id,
                text: i.name
            }));
        });

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
        var ovIn = null;
        if (D.canOverride) {
            ovIn = el('input', {
                type: 'number',
                name: 'items[' + n + '][price_override]',
                min: '0',
                step: '1',
                placeholder: 'sesuai aturan'
            });
        }
        var resCell = el('td', {
            class: 'res-cell'
        });
        var del = el('button', {
            type: 'button',
            class: 'btn btn-ghost btn-sm btn-danger',
            text: '×',
            title: 'Hapus baris'
        });

        function onItem() {
            setInputType(startIn, itemSel.value);
            setInputType(endIn, itemSel.value);
            resourceOptions(resSel, itemSel.value, null);
            resCell.textContent = '';
            clearSummary();
        }
        itemSel.addEventListener('change', onItem);
        [resSel, startIn, endIn, qtyIn].concat(ovIn ? [ovIn] : []).forEach(function(x) {
            x.addEventListener('change', function() {
                resCell.textContent = '';
                clearSummary();
            });
        });
        if (ovIn) {
            ovIn.addEventListener('input', toggleReason);
        }
        del.addEventListener('click', function() {
            if (rowsBody.children.length > 1) {
                tr.remove();
                clearSummary();
                toggleReason();
            }
        });

        tr.appendChild(el('td', {}, [itemSel]));
        tr.appendChild(el('td', {}, [resSel]));
        tr.appendChild(el('td', {}, [startIn]));
        tr.appendChild(el('td', {}, [endIn]));
        tr.appendChild(el('td', {}, [qtyIn]));
        if (ovIn) {
            tr.appendChild(el('td', {}, [ovIn]));
        }
        tr.appendChild(resCell);
        tr.appendChild(el('td', {}, [del]));
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
        } else {
            resourceOptions(resSel, '', null);
        }
        toggleReason();
        return tr;
    }

    function toggleReason() {
        var box = document.getElementById('reasonBox');
        if (!box) {
            return;
        }
        var any = false;
        rowsBody.querySelectorAll('[name$="[price_override]"]').forEach(function(i) {
            if (i.value.trim() !== '') {
                any = true;
            }
        });
        box.hidden = !any;
        document.getElementById('priceReason').required = any;
    }

    if (branchSel && !D.lockBranch) {
        branchSel.addEventListener('change', function() {
            rowsBody.querySelectorAll('tr').forEach(function(tr) {
                var item = tr.querySelector('[name$="[item_id]"]');
                var res = tr.querySelector('[name$="[resource_id]"]');
                resourceOptions(res, item.value, null);
                tr.querySelector('.res-cell').textContent = '';
            });
            clearSummary();
        });
    }

    document.getElementById('addRowBtn').addEventListener('click', function() {
        addRow();
    });

    // ---------------- Cek ketersediaan ----------------
    var sumBox = document.getElementById('sumBox');
    var checkMsg = document.getElementById('checkMsg');

    function clearSummary() {
        sumBox.hidden = true;
        checkMsg.textContent = '';
    }

    function clearResults() {
        rowsBody.querySelectorAll('.res-cell').forEach(function(c) {
            c.textContent = '';
        });
        clearSummary();
    }

    function renderRow(cell, r) {
        cell.textContent = '';
        if (!r.ok) {
            cell.appendChild(el('div', {
                class: 'res-bad',
                text: '✗ ' + (r.message || 'Tidak tersedia')
            }));
            (r.reasons || []).forEach(function(x) {
                cell.appendChild(el('div', {
                    class: 'muted',
                    text: x
                }));
            });
        } else {
            var names = r.allocations.map(function(a) {
                return a.name + (a.qty > 1 ? ' ×' + a.qty : '');
            }).join(', ');
            cell.appendChild(el('div', {
                class: 'res-ok',
                text: '✓ Tersedia: ' + names
            }));
        }
        if (r.subtotal !== undefined) {
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
            if (r.overridden) {
                cell.appendChild(el('div', {
                    class: 'res-warn',
                    text: 'Harga manual'
                }));
            }
            if (r.deposit > 0) {
                cell.appendChild(el('div', {
                    class: 'muted',
                    text: 'Deposit ' + rp(r.deposit)
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

    function runCheck() {
        var fd = new FormData(form);
        var p = new URLSearchParams();
        fd.forEach(function(val, key) {
            if (key.toLowerCase().indexOf('csrf') === -1 && key !== 'action') {
                p.append(key, val);
            }
        });
        if (D.lockBranch) {
            p.set('branch_id', String(D.lockBranch));
        }
        checkMsg.textContent = 'Memeriksa…';
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
                if (j.error) {
                    checkMsg.textContent = j.error;
                    sumBox.hidden = true;
                    return false;
                }
                (j.rows || []).forEach(function(r, i) {
                    if (trs[i]) {
                        renderRow(trs[i].querySelector('.res-cell'), r);
                    }
                });
                if (j.totals) {
                    sumBox.hidden = false;
                    sumBox.textContent = '';
                    var rows = [
                        ['Subtotal', j.totals.subtotal],
                        ['Diskon', -j.totals.discount],
                        ['Biaya pengambilan', j.totals.charge],
                        ['Deposit', j.totals.deposit]
                    ];
                    rows.forEach(function(x) {
                        if (x[1] !== 0) {
                            sumBox.appendChild(el('div', {}, [el('span', {
                                text: x[0]
                            }), el('span', {
                                text: (x[1] < 0 ? '− ' : '') + rp(Math.abs(x[1]))
                            })]));
                        }
                    });
                    sumBox.appendChild(el('div', {
                        class: 'grand'
                    }, [el('span', {
                        text: 'Total'
                    }), el('span', {
                        text: rp(j.totals.grand)
                    })]));
                }
                if (!j.ok) {
                    checkMsg.textContent = 'Ada item yang tidak bisa dipesan. Perbaiki sebelum menyimpan.';
                }
                return j.ok;
            })
            .catch(function() {
                checkMsg.textContent = 'Gagal memeriksa. Coba lagi.';
                return false;
            });
    }
    document.getElementById('checkBtn').addEventListener('click', function() {
        if (form.reportValidity()) {
            runCheck();
        }
    });

    // ---------------- Simpan ----------------
    var saving = false;
    form.querySelectorAll('[data-save]').forEach(function(b) {
        b.addEventListener('click', function() {
            if (saving) {
                return;
            }
            var action = b.getAttribute('data-save');
            var mode = form.querySelector('[name=customer_mode]:checked').value;
            if (mode === 'existing' && !document.getElementById('customerId').value) {
                alert('Pilih customer terlebih dahulu, atau pilih "Customer baru".');
                return;
            }
            if (mode === 'new') {
                if (!document.getElementById('newName').value.trim() || !document.getElementById(
                        'newPhone').value.trim()) {
                    alert('Nama dan nomor HP customer baru wajib diisi.');
                    return;
                }
            }
            if (!form.reportValidity()) {
                return;
            }
            if (action === 'CONFIRMED' && !confirm('Simpan dan langsung CONFIRM booking ini?')) {
                return;
            }
            saving = true;
            form.querySelectorAll('[data-save]').forEach(function(x) {
                x.disabled = true;
            });
            document.getElementById('actionField').value = action;
            form.submit();
        });
    });

    // ---------------- Pulihkan isian setelah gagal simpan ----------------
    function setVal(name, val) {
        var f = form.querySelector('[name="' + name + '"]');
        if (f && val !== undefined && val !== null) {
            f.value = val;
        }
    }
    if (PREFILL && typeof PREFILL === 'object') {
        if (PREFILL.customer_mode === 'new') {
            form.querySelector('[name=customer_mode][value=new]').checked = true;
        }
        if (PREFILL.customer_id) {
            pick({
                id: PREFILL.customer_id,
                name: PREFILL.customer_label || ('Customer #' + PREFILL.customer_id),
                phone: '',
                email: '',
                account_status: null
            });
            picked.lastChild.textContent = 'Dipilih sebelumnya';
        }
        ['new_name', 'new_phone', 'new_email', 'booking_source', 'fulfillment_method', 'pickup_address',
            'return_address', 'customer_notes', 'internal_notes', 'price_reason'
        ].forEach(function(k) {
            setVal(k, PREFILL[k]);
        });
        if (branchSel && !D.lockBranch) {
            setVal('branch_id', PREFILL.branch_id);
        }
        var list = PREFILL.items ? (Array.isArray(PREFILL.items) ? PREFILL.items : Object.keys(PREFILL.items).map(
            function(k) {
                return PREFILL.items[k];
            })) : [];
        list.forEach(function(it) {
            addRow({
                item_id: it.item_id,
                resource_id: it.resource_id,
                start: it.start,
                end: it.end,
                qty: it.qty,
                price_override: it.price_override
            });
        });
    }
    if (!rowsBody.children.length) {
        addRow();
    }
    applyMode();
    applyFulfillment();
})();
</script>

<?= $this->endSection() ?>