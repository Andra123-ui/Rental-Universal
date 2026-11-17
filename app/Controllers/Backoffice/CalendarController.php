<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;

/**
 * BOOK-05 — Kalender Booking & Resource
 *
 * Sumber availability: booking_resource_allocations + blackout_periods + maintenance_records
 * (bukan resources.status saja).
 */
class CalendarController extends BaseController
{
    private const ALL_STATUSES    = ['PENDING', 'CONFIRMED', 'ONGOING', 'COMPLETED', 'CANCELLED', 'EXPIRED'];
    private const HIDDEN_STATUSES = ['CANCELLED', 'EXPIRED'];
    private const MAX_RANGE_DAYS  = 92;

    private $conn;
    private ?int $branchId = null;

    // ===================================================================
    // GET /backoffice/calendar
    // ===================================================================
    public function index()
    {
        $this->boot();

        $categories = [];
        if ($this->conn->tableExists('categories') && $this->conn->fieldExists('category_id', 'catalog_items')) {
            $categories = $this->conn->table('categories')->select('id, name')->orderBy('name')->get()->getResultArray();
        }

        $branchQ = $this->conn->table('branches')->select('id, branch_name')->where('is_active', 1)->orderBy('branch_name');
        if ($this->branchId) {
            $branchQ->where('id', $this->branchId);
        }

        return view('backoffice/calendar/index', [
            'title'      => 'Kalender Booking & Resource',
            'nav'        => 'calendar',
            'branches'   => $branchQ->get()->getResultArray(),
            'categories' => $categories,
            'items'      => $this->conn->table('catalog_items')->select('id, name')
                ->where('deleted_at', null)->orderBy('name')->get()->getResultArray(),
            'resources'  => $this->loadResources($this->branchId ? ['branch_id' => $this->branchId] : []),
            'statuses'   => self::ALL_STATUSES,
            'canManage'  => admin_can('booking.manage'),
            'canCreate'  => admin_can('booking.create') || admin_can('booking.manage'),
        ]);
    }

    // ===================================================================
    // GET /backoffice/calendar/events   (JSON)
    // ?start=YYYY-MM-DD&end=YYYY-MM-DD (end eksklusif) + filter
    // ===================================================================
    public function events()
    {
        $this->boot();

        $from = $this->dateParam('start', date('Y-m-01'));
        $to   = $this->dateParam('end', date('Y-m-d', strtotime('+1 month')));
        if (strtotime($to) <= strtotime($from)) {
            $to = date('Y-m-d', strtotime($from . ' +1 day'));
        }
        if ((strtotime($to) - strtotime($from)) / 86400 > self::MAX_RANGE_DAYS) {
            $to = date('Y-m-d', strtotime($from . ' +' . self::MAX_RANGE_DAYS . ' days'));
        }

        $filters = [
            'branch_id'   => (int) $this->request->getGet('branch_id') ?: null,
            'category_id' => (int) $this->request->getGet('category_id') ?: null,
            'item_id'     => (int) $this->request->getGet('item_id') ?: null,
            'resource_id' => (int) $this->request->getGet('resource_id') ?: null,
        ];
        if ($this->branchId) {
            $filters['branch_id'] = $this->branchId; // staf terikat cabang: tidak boleh lihat cabang lain
        }

        $status = strtoupper(trim((string) $this->request->getGet('status')));
        if (!in_array($status, self::ALL_STATUSES, true)) {
            $status = '';
        }

        // ---------- Resource (baris timeline) ----------
        $resources = $this->loadResources($filters);
        $resIds    = array_map(static fn ($r) => (int) $r['id'], $resources);
        $resMap    = [];
        foreach ($this->loadResources($this->branchId ? ['branch_id' => $this->branchId] : []) as $r) {
            $resMap[(int) $r['id']] = $r['name'];
        }

        // ---------- Booking (per alokasi) ----------
        $blocks = [];
        $events = [];

        $qb = $this->conn->table('booking_resource_allocations a')
            ->select('a.id AS alloc_id, a.resource_id, a.allocated_qty, a.start_at AS a_start, a.end_at AS a_end')
            ->select('bi.id AS bi_id, bi.item_name_snapshot, bi.quantity')
            ->select('b.id AS booking_id, b.invoice_no, b.status, b.payment_status, b.customer_name_snapshot')
            ->join('booking_items bi', 'bi.id = a.booking_item_id')
            ->join('bookings b', 'b.id = bi.booking_id')
            ->where('a.start_at <', $to)
            ->where('a.end_at >', $from)
            ->whereNotIn('a.allocation_status', ['CANCELLED', 'RELEASED'])
            ->where('bi.status !=', 'CANCELLED');

        if ($status !== '') {
            $qb->where('b.status', $status);
        } else {
            $qb->whereNotIn('b.status', self::HIDDEN_STATUSES);
        }

        if ($filters['item_id']) {
            $qb->where('bi.catalog_item_id', $filters['item_id']);
        }
        if ($filters['branch_id'] || $filters['category_id'] || $filters['item_id'] || $filters['resource_id']) {
            $qb->whereIn('a.resource_id', $resIds ?: [0]);
        }

        foreach ($qb->orderBy('a.start_at')->get()->getResultArray() as $r) {
            $url = site_url('backoffice/bookings/' . id_encode((int) $r['booking_id']));
            $qty = (float) $r['allocated_qty'];

            $blocks[] = [
                'id'             => (int) $r['alloc_id'],
                'resource_id'    => (int) $r['resource_id'],
                'booking_id'     => (int) $r['booking_id'],
                'invoice'        => $r['invoice_no'],
                'customer'       => $r['customer_name_snapshot'],
                'item'           => $r['item_name_snapshot'],
                'qty'            => $qty,
                'start'          => $r['a_start'],
                'end'            => $r['a_end'],
                'status'         => $r['status'],
                'payment_status' => $r['payment_status'],
                'url'            => $url,
            ];

            // Event kalender: satu per booking_item
            $k = (int) $r['bi_id'];
            if (!isset($events[$k])) {
                $events[$k] = [
                    'id'        => 'bi' . $k,
                    'title'     => $r['invoice_no'] . ' · ' . $r['customer_name_snapshot'] . ' · '
                        . $r['item_name_snapshot'] . ' ×' . rtrim(rtrim(number_format((float) $r['quantity'], 2, '.', ''), '0'), '.'),
                    'start'     => $r['a_start'],
                    'end'       => $r['a_end'],
                    'status'    => $r['status'],
                    'payment'   => $r['payment_status'],
                    'url'       => $url,
                    'resources' => [],
                ];
            }
            $events[$k]['resources'][] = $resMap[(int) $r['resource_id']] ?? ('#' . $r['resource_id']);
            if ($r['a_start'] < $events[$k]['start']) {
                $events[$k]['start'] = $r['a_start'];
            }
            if ($r['a_end'] > $events[$k]['end']) {
                $events[$k]['end'] = $r['a_end'];
            }
        }

        foreach ($events as &$e) {
            $e['tip'] = $e['title'] . "\nStatus: " . $e['status'] . ' / ' . $e['payment']
                . "\nResource: " . implode(', ', array_unique($e['resources']));
            unset($e['resources'], $e['payment']);
        }
        unset($e);

        // ---------- Blackout & maintenance ----------
        $blackouts = [];
        foreach ($this->loadPeriods(
            'blackout_periods',
            ['start_at', 'start_date', 'starts_at'],
            ['end_at', 'end_date', 'ends_at'],
            $from,
            $to
        ) as $row) {
            $ids = $this->scopeIds($row, $resources);
            if ($ids !== null) {
                $ids = array_values(array_intersect($ids, $resIds));
                if (!$ids) {
                    continue;
                }
            }
            $title = (string) $this->pick($row, ['reason', 'title', 'description', 'notes'], 'Blokir');
            $blackouts[] = [
                'id'           => (int) $row['id'],
                'title'        => $title,
                'start'        => $row['_start'],
                'end'          => $row['_end'],
                'resource_ids' => $ids,
                'tip'          => 'Blackout: ' . $title . "\n" . $this->scopeLabel($ids, $resMap),
            ];
        }

        $maintenance = [];
        foreach ($this->loadPeriods(
            'maintenance_records',
            ['start_at', 'start_date', 'started_at', 'scheduled_at', 'scheduled_start', 'service_date'],
            ['end_at', 'end_date', 'completed_at', 'finished_at', 'scheduled_end'],
            $from,
            $to
        ) as $row) {
            $st = strtoupper((string) $this->pick($row, ['status'], ''));
            if (in_array($st, ['CANCELLED', 'VOID'], true)) {
                continue;
            }
            $ids = $this->scopeIds($row, $resources);
            if ($ids !== null) {
                $ids = array_values(array_intersect($ids, $resIds));
                if (!$ids) {
                    continue;
                }
            }
            $title = (string) $this->pick($row, ['maintenance_type', 'type', 'title', 'description', 'notes'], 'Maintenance');
            $maintenance[] = [
                'id'           => (int) $row['id'],
                'title'        => $title,
                'start'        => $row['_start'],
                'end'          => $row['_end'],
                'resource_ids' => $ids,
                'tip'          => 'Maintenance: ' . $title . ($st ? ' (' . $st . ')' : '') . "\n" . $this->scopeLabel($ids, $resMap),
            ];
        }

        return $this->response->setJSON([
            'resources'   => array_map(static fn ($r) => [
                'id'        => (int) $r['id'],
                'name'      => $r['name'],
                'code'      => $r['code'],
                'status'    => $r['status'],
                'item_name' => $r['item_name'],
            ], $resources),
            'blocks'      => $blocks,
            'events'      => array_values($events),
            'blackouts'   => $blackouts,
            'maintenance' => $maintenance,
        ]);
    }

    // ===================================================================
    // POST /backoffice/calendar/blackout
    // Tanggal selesai inklusif di form; disimpan eksklusif (00:00 hari berikutnya).
    // ===================================================================
    public function addBlackout()
    {
        $this->boot();

        if (!admin_can('booking.manage')) {
            return redirect()->to(site_url('backoffice/calendar'))->with('error', 'Anda tidak punya izin menambah blackout.');
        }

        $rules = [
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date'   => 'required|valid_date[Y-m-d]',
            'reason'     => 'permit_empty|max_length[255]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->to(site_url('backoffice/calendar'))->with('errors', array_values($this->validator->getErrors()));
        }

        $startDate = (string) $this->request->getPost('start_date');
        $endDate   = (string) $this->request->getPost('end_date');
        if ($endDate < $startDate) {
            return redirect()->to(site_url('backoffice/calendar'))->with('error', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        if (!$this->conn->tableExists('blackout_periods')) {
            return redirect()->to(site_url('backoffice/calendar'))->with('error', 'Tabel blackout_periods tidak ditemukan.');
        }

        // ---- cabang & resource ----
        $branchId   = $this->branchId ?: ((int) $this->request->getPost('branch_id') ?: null);
        $resourceId = (int) $this->request->getPost('resource_id') ?: null;

        if ($branchId) {
            $ok = $this->conn->table('branches')->where('id', $branchId)->where('is_active', 1)->countAllResults();
            if (!$ok) {
                return redirect()->to(site_url('backoffice/calendar'))->with('error', 'Cabang tidak valid.');
            }
        }

        if ($resourceId) {
            $res = $this->conn->table('resources')->select('id, branch_id')
                ->where('id', $resourceId)->where('deleted_at', null)->get()->getRowArray();
            if (!$res || ($this->branchId && (int) $res['branch_id'] !== $this->branchId)) {
                return redirect()->to(site_url('backoffice/calendar'))->with('error', 'Resource tidak ditemukan atau bukan milik cabang Anda.');
            }
            $branchId = (int) $res['branch_id']; // blackout resource selalu ikut cabang resource-nya
        }

        $reason  = trim((string) $this->request->getPost('reason'));
        $reason  = $reason !== '' ? mb_substr($reason, 0, 255) : 'Blokir manual';
        $endExcl = date('Y-m-d', strtotime($endDate . ' +1 day'));
        $now     = date('Y-m-d H:i:s');

        // Hanya kolom yang benar-benar ada di tabel yang diisi
        $candidate = [
            'business_id'        => 1,
            'branch_id'          => $branchId,
            'resource_id'        => $resourceId,
            'start_at'           => $startDate . ' 00:00:00',
            'end_at'             => $endExcl . ' 00:00:00',
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'reason'             => $reason,
            'title'              => $reason,
            'status'             => 'ACTIVE',
            'is_active'          => 1,
            'created_by_user_id' => $this->uid(),
            'created_at'         => $now,
            'updated_at'         => $now,
        ];
        $data = array_intersect_key($candidate, array_flip($this->conn->getFieldNames('blackout_periods')));

        $this->conn->transBegin();
        try {
            $this->conn->table('blackout_periods')->insert($data);
            $newId = (int) $this->conn->insertID();

            $this->conn->table('activity_logs')->insert([
                'user_id'     => $this->uid(),
                'event_type'  => 'BLACKOUT_CREATED',
                'module'      => 'calendar',
                'entity_type' => 'blackout',
                'entity_id'   => $newId,
                'action'      => 'create',
                'description' => mb_substr("Blackout {$startDate} s/d {$endDate}: {$reason}"
                    . ($resourceId ? " (resource #{$resourceId})" : ($branchId ? " (cabang #{$branchId})" : ' (semua)')), 0, 1000),
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => mb_substr((string) $this->request->getUserAgent(), 0, 500),
            ]);

            $this->conn->transCommit();
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            log_message('error', 'Tambah blackout gagal: ' . $e->getMessage());
            return redirect()->to(site_url('backoffice/calendar'))->with('error', 'Gagal menyimpan blackout. Cek struktur tabel blackout_periods.');
        }

        // Peringatan: booking aktif yang bertabrakan dengan blackout baru (tidak dibatalkan otomatis)
        $conflict = $this->countConflicts($startDate . ' 00:00:00', $endExcl . ' 00:00:00', $resourceId, $branchId);
        $msg      = 'Blackout berhasil ditambahkan.';
        if ($conflict > 0) {
            $msg .= " Perhatian: {$conflict} booking aktif bertabrakan dengan periode ini dan perlu ditangani manual.";
        }

        return redirect()->to(site_url('backoffice/calendar'))->with('success', $msg);
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function boot(): void
    {
        // id_encode/id_decode disediakan helper ini (sama seperti BookingDetailController)
        helper('backoffice');

        $this->conn     = db_connect();
        $this->branchId = session()->get('admin_branch_id') ? (int) session()->get('admin_branch_id') : null;
    }

    private function uid(): int
    {
        return (int) session()->get('admin_id');
    }

    private function dateParam(string $key, string $default): string
    {
        $v = (string) $this->request->getGet($key);
        return preg_match('/^\d{4}-\d{2}-\d{2}/', $v) ? substr($v, 0, 10) : $default;
    }

    private function pick(array $row, array $keys, $default = null)
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return $row[$k];
            }
        }
        return $default;
    }

    private function firstExisting(array $candidates, array $fields): ?string
    {
        foreach ($candidates as $c) {
            if (in_array($c, $fields, true)) {
                return $c;
            }
        }
        return null;
    }

    /** Daftar resource (baris timeline), terfilter cabang/kategori/item/resource. */
    private function loadResources(array $f): array
    {
        $qb = $this->conn->table('resources r')
            ->select('r.id, r.resource_name, r.resource_code, r.status, r.catalog_item_id, r.branch_id, ci.name AS item_name')
            ->join('catalog_items ci', 'ci.id = r.catalog_item_id', 'left')
            ->where('r.deleted_at', null);

        if (!empty($f['branch_id'])) {
            $qb->where('r.branch_id', (int) $f['branch_id']);
        }
        if (!empty($f['item_id'])) {
            $qb->where('r.catalog_item_id', (int) $f['item_id']);
        }
        if (!empty($f['category_id']) && $this->conn->fieldExists('category_id', 'catalog_items')) {
            $qb->where('ci.category_id', (int) $f['category_id']);
        }
        if (!empty($f['resource_id'])) {
            $qb->where('r.id', (int) $f['resource_id']);
        }

        $out = [];
        foreach ($qb->orderBy('ci.name')->orderBy('r.resource_name')->get()->getResultArray() as $r) {
            $out[] = [
                'id'        => (int) $r['id'],
                'name'      => (string) ($r['resource_name'] ?: ('#' . $r['id'])),
                'code'      => (string) ($r['resource_code'] ?? ''),
                'status'    => (string) ($r['status'] ?? ''),
                'item_id'   => (int) ($r['catalog_item_id'] ?? 0),
                'item_name' => (string) ($r['item_name'] ?? ''),
                'branch_id' => (int) ($r['branch_id'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Baris periode (blackout / maintenance) yang beririsan dengan [from, to).
     * Dinormalkan ke _start / _end (end eksklusif).
     */
    private function loadPeriods(string $table, array $startKeys, array $endKeys, string $from, string $to): array
    {
        if (!$this->conn->tableExists($table)) {
            return [];
        }

        $f  = $this->conn->getFieldNames($table);
        $sk = $this->firstExisting($startKeys, $f);
        $ek = $this->firstExisting($endKeys, $f);
        if (!$sk) {
            return [];
        }

        $qb = $this->conn->table($table)->where($sk . ' <', $to);
        if ($ek) {
            $qb->groupStart()
                ->where($ek . ' IS NULL', null, false)
                ->orWhere($ek . ' >=', $from)
                ->groupEnd();
        }
        if (in_array('deleted_at', $f, true)) {
            $qb->where('deleted_at IS NULL', null, false);
        }

        $rows = $qb->get()->getResultArray();
        foreach ($rows as &$r) {
            $s = (string) $r[$sk];
            $e = ($ek && !empty($r[$ek])) ? (string) $r[$ek] : null;

            if ($e === null) {
                $e = date('Y-m-d', strtotime(substr($s, 0, 10) . ' +1 day'));
            } elseif (strlen($e) === 10) {
                $e = date('Y-m-d', strtotime($e . ' +1 day')); // tanggal inklusif -> eksklusif
            }

            $r['_start'] = $s;
            $r['_end']   = $e;
        }
        unset($r);

        return $rows;
    }

    /** Resource yang terkena blackout/maintenance. null = berlaku global. */
    private function scopeIds(array $row, array $resources): ?array
    {
        $rid = (int) $this->pick($row, ['resource_id'], 0);
        if ($rid) {
            return [$rid];
        }

        $iid = (int) $this->pick($row, ['catalog_item_id', 'item_id'], 0);
        if ($iid) {
            return array_values(array_map(
                static fn ($r) => (int) $r['id'],
                array_filter($resources, static fn ($r) => (int) $r['item_id'] === $iid)
            ));
        }

        $bid = (int) $this->pick($row, ['branch_id'], 0);
        if ($bid) {
            return array_values(array_map(
                static fn ($r) => (int) $r['id'],
                array_filter($resources, static fn ($r) => (int) $r['branch_id'] === $bid)
            ));
        }

        return null;
    }

    private function scopeLabel(?array $ids, array $resMap): string
    {
        if ($ids === null) {
            return 'Berlaku: semua resource';
        }
        $names = [];
        foreach (array_slice($ids, 0, 4) as $id) {
            $names[] = $resMap[$id] ?? ('#' . $id);
        }
        return 'Berlaku: ' . implode(', ', $names) . (count($ids) > 4 ? ' +' . (count($ids) - 4) . ' lainnya' : '');
    }

    /** Jumlah booking aktif yang beririsan dengan periode blackout baru. */
    private function countConflicts(string $from, string $to, ?int $resourceId, ?int $branchId): int
    {
        $qb = $this->conn->table('booking_resource_allocations a')
            ->select('COUNT(DISTINCT b.id) AS n', false)
            ->join('booking_items bi', 'bi.id = a.booking_item_id')
            ->join('bookings b', 'b.id = bi.booking_id')
            ->join('resources r', 'r.id = a.resource_id', 'left')
            ->where('a.start_at <', $to)
            ->where('a.end_at >', $from)
            ->whereNotIn('a.allocation_status', ['CANCELLED', 'RELEASED'])
            ->whereNotIn('b.status', ['CANCELLED', 'EXPIRED', 'COMPLETED']);

        if ($resourceId) {
            $qb->where('a.resource_id', $resourceId);
        } elseif ($branchId) {
            $qb->where('r.branch_id', $branchId);
        }

        return (int) ($qb->get()->getRow('n') ?? 0);
    }
}