<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use App\Libraries\BookingAvailability;
use App\Libraries\BookingMoney;

/**
 * BOOK-04 — Edit / Reschedule Booking
 *
 * Prinsip:
 *  - Hanya booking PENDING / CONFIRMED.
 *  - Item yang berubah TIDAK ditimpa: item lama ditandai CANCELLED + alokasinya RELEASED,
 *    item baru dibuat. Item yang tidak berubah dibiarkan apa adanya.
 *  - Payment tidak pernah disentuh. Selisih uang ditampilkan; refund dicatat lewat halaman detail.
 *  - Ketersediaan diperiksa ulang di dalam transaksi (baris resources dikunci).
 */
class BookingEditController extends BaseController
{
    private const EDITABLE  = ['PENDING', 'CONFIRMED'];
    private const MAX_ITEMS = 20;

    private const FULFILLMENT = [
        'SELF_PICKUP' => 'Ambil sendiri',
        'DELIVERY'    => 'Diantar',
        'ONSITE'      => 'Di lokasi customer',
        'SERVICE'     => 'Layanan / jasa',
    ];

    private $conn;
    private BookingAvailability $avail;
    private ?int $branchId = null;
    private array $catCache = [];

    // ===================================================================
    // GET /backoffice/bookings/{id}/ubah
    // ===================================================================
    public function edit(string $seg)
    {
        $this->boot();

        $bk = $this->loadBooking($seg);
        if (!$bk) {
            return redirect()->to(site_url('backoffice/bookings'))->with('error', 'Booking tidak ditemukan.');
        }
        if (!in_array($bk['status'], self::EDITABLE, true)) {
            return redirect()->to(site_url('backoffice/bookings/' . $seg))
                ->with('error', 'Booking berstatus ' . $bk['status'] . ' dan tidak bisa diubah. Hanya PENDING atau CONFIRMED.');
        }

        $items = $this->activeItems((int) $bk['id']);

        $allocNames = [];
        $al = $this->conn->table('booking_resource_allocations a')
            ->select('a.booking_item_id, a.allocated_qty, r.id AS rid, r.resource_name')
            ->join('booking_items i', 'i.id = a.booking_item_id')
            ->join('resources r', 'r.id = a.resource_id', 'left')
            ->where('i.booking_id', $bk['id'])
            ->whereNotIn('a.allocation_status', ['CANCELLED', 'RELEASED'])
            ->get()->getResultArray();
        foreach ($al as $a) {
            $allocNames[(int) $a['booking_item_id']][] = $a['resource_name'] . ((float) $a['allocated_qty'] > 1 ? ' ×' . (float) $a['allocated_qty'] : '');
        }

        $catalog = $this->conn->table('catalog_items')
            ->select('id, name, pricing_unit')
            ->where('status', 'ACTIVE')->where('deleted_at', null)->orderBy('name')->get()->getResultArray();

        $resources = $this->conn->table('resources')
            ->select('id, catalog_item_id, resource_name')
            ->where('deleted_at', null)->where('status', 'AVAILABLE')->where('branch_id', $bk['branch_id'])
            ->orderBy('resource_name')->get()->getResultArray();

        $unitOf = [];
        foreach ($this->conn->table('catalog_items')->select('id, pricing_unit')->get()->getResultArray() as $c) {
            $unitOf[(int) $c['id']] = $c['pricing_unit'];
        }

        $rows = [];
        foreach ($items as $it) {
            $cal = in_array(strtoupper((string) ($unitOf[(int) $it['catalog_item_id']] ?? '')), ['DAY', 'NIGHT'], true);
            $fmt = $cal ? 'Y-m-d' : 'Y-m-d\TH:i';
            $rows[] = [
                'row_id'  => (int) $it['id'],
                'item_id' => (int) $it['catalog_item_id'],
                'name'    => $it['item_name_snapshot'],
                'start'   => date($fmt, strtotime($it['start_at'])),
                'end'     => date($fmt, strtotime($it['end_at'])),
                'qty'     => (int) $it['quantity'],
                'alloc'   => implode(', ', $allocNames[(int) $it['id']] ?? []),
            ];
        }

        $branch = $this->conn->table('branches')->where('id', $bk['branch_id'])->get()->getRowArray();

        return view('backoffice/booking/edit', [
            'title'       => 'Ubah ' . $bk['invoice_no'],
            'nav'         => 'booking',
            'seg'         => $seg,
            'bk'          => $bk,
            'branch'      => $branch,
            'items'       => $items,
            'allocNames'  => $allocNames,
            'catalog'     => $catalog,
            'resources'   => $resources,
            'rows'        => $rows,
            'fulfillment' => self::FULFILLMENT,
            'fees'        => $this->fees(),
            'canReprice'  => admin_can('booking.override_price'),
            'prefill'     => is_string(session()->getFlashdata('prefill')) ? session()->getFlashdata('prefill') : 'null',
        ]);
    }

    // ===================================================================
    // GET /backoffice/bookings/{id}/ubah/check   (JSON, hanya membaca)
    // ===================================================================
    public function check(string $seg)
    {
        $this->boot();

        $bk = $this->loadBooking($seg);
        if (!$bk || !in_array($bk['status'], self::EDITABLE, true)) {
            return $this->response->setJSON(['ok' => false, 'errors' => ['Booking tidak bisa diubah.'], 'rows' => []]);
        }

        $plan = $this->plan($bk, $this->request->getGet(), false);

        $rows = [];
        foreach ($plan['rows'] as $i => $r) {
            $rows[$i] = $this->rowJson($r);
        }

        return $this->response->setJSON([
            'ok'     => $plan['ok'],
            'errors' => $plan['errors'],
            'rows'   => $rows,
            'impact' => $plan['impact'],
        ]);
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/ubah
    // ===================================================================
    public function update(string $seg)
    {
        $this->boot();

        $bk0 = $this->loadBooking($seg);
        if (!$bk0) {
            return redirect()->to(site_url('backoffice/bookings'))->with('error', 'Booking tidak ditemukan.');
        }

        $post   = $this->request->getPost();
        $reason = trim((string) ($post['reason'] ?? ''));
        if (mb_strlen($reason) < 3) {
            return $this->failBack($seg, $post, 'Alasan perubahan wajib diisi (minimal 3 karakter).');
        }
        $reason = mb_substr($reason, 0, 240);

        $adminId = (int) session()->get('admin_id');

        $this->conn->transBegin();
        try {
            $bk = $this->conn->query('SELECT * FROM bookings WHERE id = ? FOR UPDATE', [(int) $bk0['id']])->getRowArray();
            if (!$bk || !in_array($bk['status'], self::EDITABLE, true)) {
                throw new \DomainException('Booking sudah berubah status dan tidak bisa diubah lagi. Muat ulang halaman.');
            }

            $plan = $this->plan($bk, $post, true);
            if (!$plan['ok']) {
                throw new \DomainException(implode(' | ', $plan['errors']));
            }

            $id      = (int) $bk['id'];
            $stamp   = '[' . date('d/m/Y H:i') . '] ';
            $summary = [];

            // 1) Lepas item lama yang dihapus / diganti (tidak dihapus dari DB)
            foreach ($plan['rows'] as $r) {
                if (!in_array($r['action'], ['REMOVE', 'REPLACE'], true)) {
                    continue;
                }
                $o = $r['old'];
                $this->conn->table('booking_items')->where('id', $o['id'])->update([
                    'status' => 'CANCELLED',
                    'notes'  => trim(((string) $o['notes']) . "\n" . $stamp . ($r['action'] === 'REMOVE' ? 'Dihapus' : 'Diganti') . ' (perubahan booking): ' . $reason),
                ]);
                $this->conn->table('booking_resource_allocations')->where('booking_item_id', $o['id'])
                    ->whereNotIn('allocation_status', ['CANCELLED', 'RELEASED'])->update(['allocation_status' => 'RELEASED']);

                $summary[] = ($r['action'] === 'REMOVE' ? 'Hapus ' : 'Ganti ') . $o['item_name_snapshot'] . ' (' . substr($o['start_at'], 0, 16) . ' → ' . substr($o['end_at'], 0, 16) . ')';
            }

            // 2) Item baru + alokasi baru
            foreach ($plan['rows'] as $r) {
                if (!in_array($r['action'], ['REPLACE', 'ADD'], true)) {
                    continue;
                }
                $q = $r['quote'];
                $c = $r['catalog'];

                $this->conn->table('booking_items')->insert([
                    'booking_id'         => $id,
                    'catalog_item_id'    => (int) $c['id'],
                    'item_name_snapshot' => $c['name'],
                    'item_type_snapshot' => $c['item_type'],
                    'start_at'           => $r['start'],
                    'end_at'             => $r['end'],
                    'quantity'           => $r['qty'],
                    'duration_value'     => $q['duration'],
                    'duration_unit'      => $q['unit'],
                    'unit_price'         => $q['unit_price'],
                    'discount_amount'    => $q['discount'],
                    'subtotal'           => $q['subtotal'],
                    'status'             => 'RESERVED',
                    'notes'              => $this->priceNote($q, $reason, $r['action'] === 'REPLACE' ? (int) $r['old']['id'] : null),
                ]);
                $itemId = (int) $this->conn->insertID();

                foreach ($r['alloc']['allocations'] as $a) {
                    $this->conn->table('booking_resource_allocations')->insert([
                        'booking_item_id'      => $itemId,
                        'resource_id'          => $a['resource_id'],
                        'allocated_qty'        => $a['qty'],
                        'start_at'             => $r['start'],
                        'end_at'               => $r['end'],
                        'allocation_status'    => 'RESERVED',
                        'allocated_by_user_id' => $adminId,
                    ]);
                }

                $summary[] = ($r['action'] === 'ADD' ? 'Tambah ' : '→ ') . $c['name'] . ' ×' . $this->n($r['qty'])
                    . ' (' . substr($r['start'], 0, 16) . ' → ' . substr($r['end'], 0, 16) . ')';
            }

            // 3) Item tetap, hanya harga yang berubah (mode hitung ulang / harga manual)
            foreach ($plan['rows'] as $r) {
                if ($r['action'] !== 'KEEP' || empty($r['price_changed'])) {
                    continue;
                }
                $o = $r['old'];
                $q = $r['quote'];
                $this->conn->table('booking_items')->where('id', $o['id'])->update([
                    'unit_price'      => $q['unit_price'],
                    'discount_amount' => $q['discount'],
                    'subtotal'        => $q['subtotal'],
                    'notes'           => trim(((string) $o['notes']) . "\n" . $stamp . 'Harga diubah dari Rp ' . number_format((float) $o['subtotal'], 0, ',', '.')
                        . ' menjadi Rp ' . number_format($q['subtotal'], 0, ',', '.') . ': ' . $reason),
                ]);
                $summary[] = 'Harga ' . $o['item_name_snapshot'] . ': Rp ' . number_format((float) $o['subtotal'], 0, ',', '.') . ' → Rp ' . number_format($q['subtotal'], 0, ',', '.');
            }

            // 4) Header booking
            $internal = trim(((string) $bk['internal_notes']) . "\n" . $stamp . (string) session()->get('admin_name') . ' mengubah booking: ' . $reason);
            if ($plan['fulfillment']['method'] !== $bk['fulfillment_method']) {
                $summary[] = 'Pengambilan ' . $bk['fulfillment_method'] . ' → ' . $plan['fulfillment']['method'];
            }

            $this->conn->table('bookings')->where('id', $id)->update([
                'start_at'           => $plan['start'],
                'end_at'             => $plan['end'],
                'subtotal'           => $plan['impact']['new']['subtotal'],
                'discount_total'     => $plan['impact']['new']['discount'],
                'deposit_total'      => $plan['impact']['new']['deposit'],
                'fulfillment_method' => $plan['fulfillment']['method'],
                'pickup_address'     => $plan['fulfillment']['in'],
                'return_address'     => $plan['fulfillment']['out'],
                'internal_notes'     => mb_substr($internal, 0, 60000),
            ]);

            // 5) Grand total, sisa tagihan, status bayar (payment lama tidak disentuh)
            (new BookingMoney($this->conn))->recalc($id);

            $after = $this->conn->table('bookings')->select('grand_total, paid_total, balance_due')->where('id', $id)->get()->getRowArray();

            // 6) Histori + log
            $this->conn->table('booking_status_history')->insert([
                'booking_id'         => $id,
                'old_status'         => $bk['status'],
                'new_status'         => $bk['status'],
                'changed_by_user_id' => $adminId,
                'note'               => mb_substr('Perubahan booking: ' . $reason, 0, 255),
            ]);

            $this->conn->table('activity_logs')->insert([
                'user_id'     => $adminId,
                'event_type'  => 'BOOKING_RESCHEDULED',
                'module'      => 'booking',
                'entity_type' => 'booking',
                'entity_id'   => $id,
                'action'      => 'update',
                'description' => mb_substr("Ubah {$bk['invoice_no']}: " . implode('; ', $summary) . " | Alasan: {$reason}", 0, 1000),
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => mb_substr((string) $this->request->getUserAgent(), 0, 500),
            ]);

            $this->conn->transCommit();
        } catch (\DomainException $e) {
            $this->conn->transRollback();
            return $this->failBack($seg, $post, $e->getMessage());
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            log_message('error', 'Ubah booking gagal: ' . $e->getMessage());
            return $this->failBack($seg, $post, 'Terjadi kesalahan saat menyimpan perubahan. Coba lagi.');
        }

        $msg = "Booking {$bk0['invoice_no']} diubah.";
        $paid = (float) $after['paid_total'];
        $grand = (float) $after['grand_total'];
        if ($paid > $grand + 0.009) {
            $msg .= ' Pembayaran melebihi tagihan Rp ' . number_format($paid - $grand, 0, ',', '.') . ': catat refund di tab Pembayaran bila perlu.';
        } elseif ((float) $after['balance_due'] > 0) {
            $msg .= ' Sisa tagihan Rp ' . number_format((float) $after['balance_due'], 0, ',', '.') . '.';
        }

        return redirect()->to(site_url('backoffice/bookings/' . $seg) . '?tab=history')->with('success', $msg);
    }

    // ===================================================================
    // Perencanaan perubahan (hanya membaca; dipakai check dan update)
    // ===================================================================

    private function plan(array $bk, array $post, bool $lock): array
    {
        $branch = (int) $bk['branch_id'];
        $errors = [];

        // ---- pengambilan ----
        $method = (string) ($post['fulfillment_method'] ?? $bk['fulfillment_method']);
        if (!isset(self::FULFILLMENT[$method])) {
            $errors[] = 'Metode pengambilan tidak valid.';
            $method   = (string) $bk['fulfillment_method'];
        }
        $addrIn  = trim((string) ($post['pickup_address'] ?? $bk['pickup_address']));
        $addrOut = trim((string) ($post['return_address'] ?? $bk['return_address']));
        if (in_array($method, ['DELIVERY', 'ONSITE'], true) && $addrIn === '') {
            $errors[] = 'Alamat wajib diisi untuk metode ' . self::FULFILLMENT[$method] . '.';
        }
        if ($addrOut === '' && $addrIn !== '') {
            $addrOut = $addrIn;
        }
        $addrIn  = mb_substr($addrIn, 0, 500);
        $addrOut = mb_substr($addrOut, 0, 500);

        // ---- mode harga ----
        $mode = ($post['price_mode'] ?? 'KEEP') === 'RECALC' ? 'RECALC' : 'KEEP';
        if ($mode === 'RECALC' && !admin_can('booking.override_price')) {
            $errors[] = 'Anda tidak berwenang menghitung ulang harga. Pilih "Pertahankan harga".';
            $mode = 'KEEP';
        }

        // ---- data lama ----
        $oldMap = [];
        foreach ($this->activeItems((int) $bk['id']) as $o) {
            $oldMap[(int) $o['id']] = $o;
        }

        $oldRes = [];
        $al = $this->conn->table('booking_resource_allocations a')
            ->select('a.booking_item_id, a.resource_id')
            ->join('booking_items i', 'i.id = a.booking_item_id')
            ->where('i.booking_id', $bk['id'])
            ->whereNotIn('a.allocation_status', ['CANCELLED', 'RELEASED'])
            ->get()->getResultArray();
        foreach ($al as $a) {
            $oldRes[(int) $a['booking_item_id']][] = (int) $a['resource_id'];
        }

        $rowsIn = is_array($post['items'] ?? null) ? array_slice(array_values($post['items']), 0, self::MAX_ITEMS) : [];

        // ---- pass 1: baca dan klasifikasi ----
        $rows = [];
        foreach ($rowsIn as $i => $in) {
            $in    = (array) $in;
            $rowId = (int) ($in['row_id'] ?? 0);
            $old   = null;

            if ($rowId) {
                if (!isset($oldMap[$rowId])) {
                    $rows[$i]  = ['action' => 'INVALID', 'ok' => false, 'message' => 'Item tidak ditemukan pada booking ini.'];
                    $errors[]  = 'Baris ' . ($i + 1) . ': item tidak ditemukan pada booking ini.';
                    continue;
                }
                $old = $oldMap[$rowId];
            }

            if (!empty($in['remove'])) {
                $rows[$i] = $old
                    ? ['action' => 'REMOVE', 'ok' => true, 'message' => '', 'old' => $old]
                    : ['action' => 'SKIP', 'ok' => true, 'message' => ''];
                continue;
            }

            $row = $this->readRow($in, $old, $oldRes, $mode, $branch);
            if (!$row['ok']) {
                $rows[$i] = $row;
                $errors[] = 'Baris ' . ($i + 1) . ': ' . $row['message'];
                continue;
            }
            $rows[$i] = $row;
        }

        // ---- pass 2: alokasi untuk baris yang diganti / ditambah ----
        $release = [];
        foreach ($rows as $r) {
            if (in_array($r['action'], ['REMOVE', 'REPLACE'], true) && isset($r['old'])) {
                $release[] = (int) $r['old']['id'];
            }
        }

        $pending = [];
        foreach ($rows as $i => &$r) {
            if (!in_array($r['action'], ['REPLACE', 'ADD'], true) || !$r['ok']) {
                continue;
            }
            $c = $r['catalog'];
            $r['alloc'] = $this->avail->allocate(
                $c, $r['start'], $r['end'], $r['qty'], $branch, $r['resource_id'] ?: null, $lock, null, $pending, $release
            );
            $r['warnings'] = $this->avail->warnings($c, $r['start'], $r['end']);

            if (!$r['alloc']['ok']) {
                $r['ok']      = false;
                $r['message'] = $c['name'] . ': ' . $r['alloc']['message'];
                $errors[]     = 'Baris ' . ($i + 1) . ': ' . $r['message'];
                continue;
            }

            $bb = (int) $c['buffer_before_minutes'];
            $ba = (int) $c['buffer_after_minutes'];
            foreach ($r['alloc']['allocations'] as $a) {
                $pending[] = [
                    'resource_id' => $a['resource_id'],
                    'start'       => date('Y-m-d H:i:s', strtotime($r['start']) - $bb * 60),
                    'end'         => date('Y-m-d H:i:s', strtotime($r['end']) + $ba * 60),
                    'qty'         => $a['qty'],
                ];
            }
        }
        unset($r);

        // ---- total baru ----
        $state = [];
        foreach ($oldMap as $id => $o) {
            $state[$id] = ['sub' => (float) $o['subtotal'], 'disc' => (float) $o['discount_amount'], 'start' => $o['start_at'], 'end' => $o['end_at']];
        }
        $dep      = (float) $bk['deposit_total'];
        $newItems = [];
        $changed  = false;

        foreach ($rows as $r) {
            if (!$r['ok']) {
                continue;
            }
            switch ($r['action']) {
                case 'REMOVE':
                    unset($state[(int) $r['old']['id']]);
                    $dep -= $this->itemDeposit($r['old']);
                    $changed = true;
                    break;
                case 'REPLACE':
                    unset($state[(int) $r['old']['id']]);
                    $dep -= $this->itemDeposit($r['old']);
                    // no break
                case 'ADD':
                    $dep += $r['quote']['deposit'];
                    $newItems[] = ['sub' => $r['quote']['subtotal'], 'disc' => $r['quote']['discount'], 'start' => $r['start'], 'end' => $r['end']];
                    $changed = true;
                    break;
                case 'KEEP':
                    if (!empty($r['price_changed'])) {
                        $oid = (int) $r['old']['id'];
                        $state[$oid]['sub']  = $r['quote']['subtotal'];
                        $state[$oid]['disc'] = $r['quote']['discount'];
                        $changed = true;
                    }
                    break;
            }
        }

        $all = array_merge(array_values($state), $newItems);
        if (!$all) {
            $errors[] = 'Booking harus punya minimal satu item.';
        }

        $sub = $disc = 0.0;
        $minS = $maxE = null;
        foreach ($all as $x) {
            $sub  += $x['sub'];
            $disc += $x['disc'];
            $minS = $minS === null || $x['start'] < $minS ? $x['start'] : $minS;
            $maxE = $maxE === null || $x['end'] > $maxE ? $x['end'] : $maxE;
        }
        $dep = max(0.0, round($dep, 2));

        if ($method !== $bk['fulfillment_method'] || $addrIn !== (string) $bk['pickup_address'] || $addrOut !== (string) $bk['return_address']) {
            $changed = true;
        }
        if (!$changed && !$errors) {
            $errors[] = 'Tidak ada perubahan yang perlu disimpan.';
        }

        $charge   = (float) $bk['charge_total'];
        $grandNew = round($sub - $disc + $charge + $dep, 2);
        $paid     = (float) $bk['paid_total'];

        $impact = [
            'old' => [
                'subtotal' => (float) $bk['subtotal'], 'discount' => (float) $bk['discount_total'], 'charge' => $charge,
                'deposit' => (float) $bk['deposit_total'], 'grand' => (float) $bk['grand_total'], 'paid' => $paid,
                'balance' => (float) $bk['balance_due'],
            ],
            'new' => [
                'subtotal' => round($sub, 2), 'discount' => round($disc, 2), 'charge' => $charge, 'deposit' => $dep,
                'grand' => $grandNew, 'paid' => $paid, 'balance' => max(0.0, round($grandNew - $paid, 2)),
            ],
            'credit' => max(0.0, round($paid - $grandNew, 2)),
        ];

        $ok = !$errors;
        foreach ($rows as $r) {
            if (!$r['ok']) {
                $ok = false;
            }
        }

        return [
            'ok'          => $ok,
            'errors'      => $errors,
            'rows'        => $rows,
            'impact'      => $impact,
            'start'       => $minS ?? $bk['start_at'],
            'end'         => $maxE ?? $bk['end_at'],
            'fulfillment' => ['method' => $method, 'in' => $addrIn, 'out' => $addrOut],
        ];
    }

    /** Baca satu baris form: validasi, klasifikasi (KEEP / REPLACE / ADD) dan hitung harga. */
    private function readRow(array $in, ?array $old, array $oldRes, string $mode, int $branch): array
    {
        $fail = static fn (string $m) => ['action' => 'INVALID', 'ok' => false, 'message' => $m];

        $itemId = (int) ($in['item_id'] ?? 0);
        $c = $this->catalog($itemId);
        if (!$c) {
            return $fail('Pilih item.');
        }
        $sameCatalog = $old && (int) $old['catalog_item_id'] === $itemId;
        if (!$sameCatalog && ($c['status'] !== 'ACTIVE' || $c['deleted_at'] !== null)) {
            return $fail($c['name'] . ' tidak aktif.');
        }

        $start = BookingAvailability::parseMoment($in['start'] ?? null);
        $end   = BookingAvailability::parseMoment($in['end'] ?? null);
        if (!$start || !$end) {
            return $fail($c['name'] . ': tanggal/waktu mulai dan selesai wajib diisi.');
        }
        if ($end <= $start) {
            return $fail($c['name'] . ': waktu selesai harus setelah waktu mulai.');
        }

        $qtyRaw = $in['qty'] ?? '1';
        if (!is_numeric($qtyRaw) || (float) $qtyRaw < 1 || (float) $qtyRaw > 1000 || floor((float) $qtyRaw) != (float) $qtyRaw) {
            return $fail($c['name'] . ': jumlah harus bilangan bulat 1–1000.');
        }
        $qty = (float) $qtyRaw;

        $override = null;
        if (isset($in['price_override']) && trim((string) $in['price_override']) !== '') {
            if (!admin_can('booking.override_price')) {
                return $fail($c['name'] . ': Anda tidak berwenang mengubah harga.');
            }
            if (!is_numeric($in['price_override']) || (float) $in['price_override'] < 0 || (float) $in['price_override'] > 999999999) {
                return $fail($c['name'] . ': harga manual tidak valid.');
            }
            $override = (float) $in['price_override'];
        }

        $resId = (int) ($in['resource_id'] ?? 0);

        // klasifikasi
        $action = 'ADD';
        if ($old) {
            $changed = !$sameCatalog
                || $start !== $old['start_at'] || $end !== $old['end_at']
                || (float) $qty !== (float) $old['quantity']
                || ($resId && !in_array($resId, $oldRes[(int) $old['id']] ?? [], true));
            $action = $changed ? 'REPLACE' : 'KEEP';
        }

        $row = [
            'action' => $action, 'ok' => true, 'message' => '', 'old' => $old, 'catalog' => $c,
            'start' => $start, 'end' => $end, 'qty' => $qty, 'resource_id' => $resId,
            'quote' => null, 'alloc' => ['allocations' => [], 'reasons' => []], 'warnings' => [], 'price_changed' => false,
        ];

        // KEEP tanpa perubahan harga: pakai snapshot lama apa adanya
        if ($action === 'KEEP' && $mode === 'KEEP' && $override === null) {
            return $row;
        }

        try {
            $q = $this->avail->quote($c, $start, $end, $qty, $branch, $override);
        } catch (\DomainException $e) {
            return $fail($c['name'] . ': ' . $e->getMessage());
        }

        $q['kept'] = false;

        // Mode "pertahankan harga" untuk item yang sama
        if ($mode === 'KEEP' && $override === null && $old && $sameCatalog) {
            $unit = (float) $old['unit_price'];
            if ((float) $old['duration_value'] === (float) $q['duration'] && (float) $old['quantity'] === $qty) {
                $q = array_merge($q, [
                    'unit_price' => $unit, 'surcharge' => 0.0, 'lines' => [], 'overridden' => false, 'kept' => true,
                    'subtotal' => (float) $old['subtotal'], 'discount' => (float) $old['discount_amount'],
                ]);
            } else {
                $gross    = round($unit * $q['duration'] * $qty, 2);
                $oldSub   = (float) $old['subtotal'];
                $ratio    = $oldSub > 0 ? (float) $old['discount_amount'] / $oldSub : 0.0;
                $discount = round($gross * $ratio, 2);
                $q = array_merge($q, [
                    'unit_price' => $unit, 'gross' => $gross, 'surcharge' => 0.0, 'lines' => [], 'overridden' => false, 'kept' => true,
                    'subtotal' => $gross, 'discount' => $discount,
                ]);
            }
            $q['net'] = round($q['subtotal'] - $q['discount'], 2);
        }

        $row['quote'] = $q;

        if ($action === 'KEEP') {
            $row['price_changed'] = $old
                && (abs((float) $old['subtotal'] - $q['subtotal']) > 0.009
                    || abs((float) $old['discount_amount'] - $q['discount']) > 0.009
                    || abs((float) $old['unit_price'] - $q['unit_price']) > 0.009);
        }

        return $row;
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function boot(): void
    {
        // Jika id_encode/id_decode dimuat lewat helper lain, tambahkan helper itu di sini
        // (samakan dengan BookingController::boot()).
        helper('backoffice');

        if (!function_exists('id_decode')) {
            throw new \RuntimeException('Helper id_decode belum dimuat di BookingEditController::boot().');
        }

        $this->conn     = db_connect();
        $this->avail    = new BookingAvailability($this->conn);
        $this->branchId = session()->get('admin_branch_id') ? (int) session()->get('admin_branch_id') : null;
    }

    private function loadBooking(string $seg): ?array
    {
        $id = id_decode($seg);
        if (!$id) {
            return null;
        }

        $q = $this->conn->table('bookings')->where('id', (int) $id);
        if ($this->branchId) {
            $q->where('branch_id', $this->branchId);
        }

        return $q->get()->getRowArray() ?: null;
    }

    private function activeItems(int $bookingId): array
    {
        return $this->conn->table('booking_items')->where('booking_id', $bookingId)
            ->where('status !=', 'CANCELLED')->orderBy('id')->get()->getResultArray();
    }

    private function catalog(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        if (!array_key_exists($id, $this->catCache)) {
            $this->catCache[$id] = $this->conn->table('catalog_items')->where('id', $id)->get()->getRowArray() ?: null;
        }
        return $this->catCache[$id];
    }

    /** Deposit sebuah item lama menurut kebijakan katalog. */
    private function itemDeposit(array $item): float
    {
        $c = $this->catalog((int) $item['catalog_item_id']);
        if (!$c || empty($c['deposit_required'])) {
            return 0.0;
        }
        return round((float) $c['deposit_amount'] * (float) $item['quantity'], 2);
    }

    private function priceNote(array $q, string $reason, ?int $replacesItemId): ?string
    {
        $parts = [];
        if ($replacesItemId) {
            $parts[] = 'Menggantikan item #' . $replacesItemId . ' (' . date('d/m/Y H:i') . ')';
        }
        if (!empty($q['overridden'])) {
            $parts[] = 'Harga manual: ' . $reason;
        } elseif (!empty($q['kept'])) {
            $parts[] = 'Harga lama dipertahankan';
        } elseif (!empty($q['lines'])) {
            foreach ($q['lines'] as $l) {
                $parts[] = ($l['type'] === 'SURCHARGE' ? '+' : '-') . ' ' . $l['name'] . ' Rp ' . number_format($l['amount'], 0, ',', '.');
            }
        }
        return $parts ? mb_substr(implode('; ', $parts), 0, 1000) : null;
    }

    private function rowJson(array $r): array
    {
        $out = ['action' => $r['action'], 'ok' => (bool) $r['ok'], 'message' => $r['message'] ?? ''];

        if (isset($r['alloc']['reasons'])) {
            $out['reasons'] = array_slice($r['alloc']['reasons'], 0, 4);
        }
        if (!empty($r['alloc']['allocations'])) {
            $out['allocations'] = array_map(static fn ($a) => ['name' => $a['name'], 'qty' => $a['qty']], $r['alloc']['allocations']);
        }
        $out['warnings'] = $r['warnings'] ?? [];

        if (!empty($r['quote'])) {
            $q = $r['quote'];
            $out += [
                'duration' => $q['duration'], 'unit' => $q['unit'], 'unit_price' => $q['unit_price'],
                'subtotal' => $q['subtotal'], 'discount' => $q['discount'], 'lines' => $q['lines'],
                'overridden' => $q['overridden'], 'kept' => !empty($q['kept']),
            ];
        }
        if (!empty($r['price_changed'])) {
            $out['price_changed'] = true;
        }

        return $out;
    }

    private function failBack(string $seg, array $post, string $message)
    {
        foreach (array_keys($post) as $k) {
            if (stripos((string) $k, 'csrf') !== false) {
                unset($post[$k]);
            }
        }

        return redirect()->to(site_url('backoffice/bookings/' . $seg . '/ubah'))
            ->with('error', $message)
            ->with('prefill', json_encode($post, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    private function fees(): array
    {
        $get = function (string $key) {
            $v = $this->conn->table('system_settings')->where('setting_key', $key)->orderBy('branch_id', 'ASC')->get()->getRow('setting_value');
            return (float) ($v ?? 0);
        };

        return [
            'SELF_PICKUP' => 0.0,
            'DELIVERY'    => $get('delivery.fee_flat'),
            'ONSITE'      => $get('delivery.fee_onsite'),
            'SERVICE'     => $get('delivery.fee_service'),
        ];
    }

    private function n($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    }
}