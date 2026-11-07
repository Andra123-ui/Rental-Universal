<?php

namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Pemeriksa ketersediaan unit + kalkulator harga untuk booking dari backoffice.
 * Dipakai BOOK-03 (buat manual), kelak BOOK-04 (reschedule) dan BOOK-05 (kalender).
 *
 * Aturan bentrok sebuah unit pada periode [start, end]:
 *  - unit harus status AVAILABLE dan tidak dihapus
 *  - kapasitas terpakai = jumlah allocated_qty alokasi aktif yang beririsan
 *    dengan [start - buffer_before, end + buffer_after]
 *  - alokasi dari booking CANCELLED / EXPIRED / PENDING yang hold-nya sudah lewat tidak dihitung
 *  - blackout aktif (FULL = unit tertutup, PARTIAL = kapasitas dikurangi capacity_reduction)
 *  - maintenance yang belum selesai/dibatalkan dan beririsan
 */
class BookingAvailability
{
    private ConnectionInterface $db;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    // ===================================================================
    // Parsing
    // ===================================================================

    /** Terima 'Y-m-d' (jadi 00:00) atau 'Y-m-d\TH:i'. Null jika tidak valid. */
    public static function parseMoment($v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);
        if ($v === '') {
            return null;
        }

        $d = \DateTime::createFromFormat('!Y-m-d', $v);
        if ($d && $d->format('Y-m-d') === $v) {
            return $d->format('Y-m-d 00:00:00');
        }

        $d = \DateTime::createFromFormat('Y-m-d\TH:i', $v);
        if ($d && $d->format('Y-m-d\TH:i') === $v) {
            return $d->format('Y-m-d H:i:00');
        }

        return null;
    }

    // ===================================================================
    // Harga
    // ===================================================================

    /**
     * Hitung harga satu baris item.
     *
     * Konvensi (sama dengan BookingDetailController::recalcMoney):
     *   subtotal  = unit_price x durasi x qty + surcharge   (SEBELUM diskon)
     *   discount  = diskon dari pricing_rules               (disimpan terpisah)
     *   net       = subtotal - discount
     * Jika $override diisi, semua aturan harga diabaikan.
     *
     * @throws \DomainException bila durasi melanggar batas item
     */
    public function quote(array $c, string $start, string $end, float $qty, int $branchId, ?float $override = null): array
    {
        $s = strtotime($start);
        $e = strtotime($end);
        if ($e <= $s) {
            throw new \DomainException('Waktu selesai harus setelah waktu mulai.');
        }

        $unit     = strtoupper((string) $c['pricing_unit']);
        $calendar = in_array($unit, ['DAY', 'NIGHT'], true);

        if ($calendar) {
            $dur = max(1, (int) ceil(($e - $s) / 86400));
        } elseif ($unit === 'HOUR') {
            $dur = max(1, (int) ceil(($e - $s) / 3600));
        } else {
            $dur = 1; // SESSION / paket: satu kali
        }

        if ($c['min_duration'] !== null && $dur < (float) $c['min_duration']) {
            throw new \DomainException('Durasi minimal ' . $this->num($c['min_duration']) . ' ' . strtolower($unit) . '.');
        }
        if ($c['max_duration'] !== null && $dur > (float) $c['max_duration']) {
            throw new \DomainException('Durasi maksimal ' . $this->num($c['max_duration']) . ' ' . strtolower($unit) . '.');
        }

        $base      = (float) $c['base_price'];
        $unitPrice = $override ?? $base;
        $gross     = round($unitPrice * $dur * $qty, 2);
        $surcharge = 0.0;
        $discount  = 0.0;
        $lines     = [];

        if ($override === null) {
            $rules = $this->db->table('pricing_rules')
                ->where('catalog_item_id', (int) $c['id'])
                ->where('is_active', 1)
                ->groupStart()->where('branch_id', null)->orWhere('branch_id', $branchId)->groupEnd()
                ->orderBy('priority', 'DESC')->orderBy('id', 'ASC')
                ->get()->getResultArray();

            $days = $calendar ? $dur : 1;

            // Surcharge: semua aturan yang cocok, dihitung per hari yang kena
            foreach ($rules as $r) {
                if ($r['rule_type'] !== 'SURCHARGE' || !$this->qtyDurOk($r, $qty, $dur)) {
                    continue;
                }
                $hit = 0;
                for ($i = 0; $i < $days; $i++) {
                    $dayTs = strtotime(date('Y-m-d', $s) . " +{$i} day");
                    if ($this->dayOk($r, $dayTs)) {
                        $hit++;
                    }
                }
                if ($hit === 0) {
                    continue;
                }
                $amt = strtoupper((string) $r['value_type']) === 'PERCENT'
                    ? $base * $qty * $hit * (float) $r['price_value'] / 100
                    : (float) $r['price_value'] * $qty * $hit;
                $amt = round($amt, 2);
                if ($amt > 0) {
                    $surcharge += $amt;
                    $lines[] = ['type' => 'SURCHARGE', 'name' => $r['name'], 'amount' => $amt];
                }
            }

            // Diskon: hanya satu yang dipakai (prioritas tertinggi, lalu nominal terbesar)
            $bestAmt  = 0.0;
            $bestRule = null;
            $dateOnly = strtotime(date('Y-m-d', $s));
            foreach ($rules as $r) {
                if ($r['rule_type'] !== 'DISCOUNT' || !$this->qtyDurOk($r, $qty, $dur) || !$this->rangeOk($r, $dateOnly)) {
                    continue;
                }
                $amt = strtoupper((string) $r['value_type']) === 'PERCENT'
                    ? ($gross + $surcharge) * (float) $r['price_value'] / 100
                    : (float) $r['price_value'];
                $amt = round(min($amt, $gross + $surcharge), 2);

                if ($bestRule === null
                    || (int) $r['priority'] > (int) $bestRule['priority']
                    || ((int) $r['priority'] === (int) $bestRule['priority'] && $amt > $bestAmt)) {
                    $bestRule = $r;
                    $bestAmt  = $amt;
                }
            }
            if ($bestRule && $bestAmt > 0) {
                $discount = $bestAmt;
                $lines[]  = ['type' => 'DISCOUNT', 'name' => $bestRule['name'], 'amount' => $bestAmt];
            }
        }

        $subtotal = round($gross + $surcharge, 2);

        return [
            'duration'   => $dur,
            'unit'       => $unit,
            'unit_price' => $unitPrice,
            'gross'      => $gross,
            'surcharge'  => $surcharge,
            'discount'   => $discount,
            'subtotal'   => $subtotal,
            'net'        => round($subtotal - $discount, 2),
            'deposit'    => !empty($c['deposit_required']) ? round((float) $c['deposit_amount'] * $qty, 2) : 0.0,
            'lines'      => $lines,
            'overridden' => $override !== null,
        ];
    }

    // ===================================================================
    // Ketersediaan & alokasi
    // ===================================================================

    /**
     * Cari unit untuk $qty pada periode [start, end].
     *
     * @param int|null $resourceId       alokasi manual ke satu unit (null = otomatis)
     * @param bool     $lock             kunci baris resources (WAJIB di dalam transaksi saat menyimpan)
     * @param int|null $ignoreBookingId  abaikan alokasi milik booking ini (untuk reschedule)
     * @param array    $pending          alokasi yang belum masuk DB: [['resource_id','start','end','qty'], ...]
     * @param array    $ignoreItemIds    id booking_items yang alokasinya dianggap sudah dilepas (simulasi reschedule)
     *
     * @return array{ok:bool, allocations:array, free:float, message:string, reasons:array}
     */
    public function allocate(
        array $c,
        string $start,
        string $end,
        float $qty,
        int $branchId,
        ?int $resourceId = null,
        bool $lock = false,
        ?int $ignoreBookingId = null,
        array $pending = [],
        array $ignoreItemIds = []
    ): array {
        $bb = (int) ($c['buffer_before_minutes'] ?? 0);
        $ba = (int) ($c['buffer_after_minutes'] ?? 0);
        $ws = date('Y-m-d H:i:s', strtotime($start) - $bb * 60);
        $we = date('Y-m-d H:i:s', strtotime($end) + $ba * 60);
        $now = date('Y-m-d H:i:s');

        $sql = "SELECT id, resource_name, resource_code, capacity FROM resources
                 WHERE catalog_item_id = ? AND branch_id = ? AND deleted_at IS NULL AND status = 'AVAILABLE'";
        $bind = [(int) $c['id'], $branchId];
        if ($resourceId) {
            $sql   .= ' AND id = ?';
            $bind[] = $resourceId;
        }
        $sql .= ' ORDER BY id' . ($lock ? ' FOR UPDATE' : '');

        $cands = $this->db->query($sql, $bind)->getResultArray();

        if (!$cands) {
            return $this->fail($qty, 0.0, $resourceId
                ? 'Unit yang dipilih tidak tersedia di cabang ini.'
                : 'Tidak ada unit aktif untuk item ini di cabang yang dipilih.', []);
        }

        $default = $c['default_capacity'] !== null ? (float) $c['default_capacity'] : 1.0;
        $reasons = [];
        $free    = [];

        foreach ($cands as $r) {
            $cap = $r['capacity'] !== null ? (float) $r['capacity'] : $default;
            if ($cap <= 0) {
                $cap = 1.0;
            }

            // Maintenance berjalan -> unit tertutup
            $mt = $this->db->query(
                "SELECT 1 FROM maintenance_records
                  WHERE resource_id = ? AND status NOT IN ('CANCELLED','DONE','COMPLETED')
                    AND start_at < ? AND (end_at IS NULL OR end_at > ?) LIMIT 1",
                [(int) $r['id'], $end, $start]
            )->getRowArray();
            if ($mt) {
                $reasons[] = $r['resource_name'] . ': sedang maintenance';
                continue;
            }

            // Blackout: FULL menutup unit, PARTIAL mengurangi kapasitas
            $blk = $this->db->query(
                "SELECT block_type, capacity_reduction, reason FROM blackout_periods
                  WHERE status = 'ACTIVE' AND start_at < ? AND end_at > ?
                    AND (resource_id = ? OR (resource_id IS NULL AND (catalog_item_id IS NULL OR catalog_item_id = ?)))
                    AND (branch_id IS NULL OR branch_id = ?)",
                [$end, $start, (int) $r['id'], (int) $c['id'], $branchId]
            )->getResultArray();

            $closed = false;
            foreach ($blk as $b) {
                if (strtoupper((string) $b['block_type']) === 'FULL' || $b['capacity_reduction'] === null) {
                    $closed    = true;
                    $reasons[] = $r['resource_name'] . ': blackout' . ($b['reason'] ? ' (' . $b['reason'] . ')' : '');
                    break;
                }
                $cap -= (float) $b['capacity_reduction'];
            }
            if ($closed) {
                continue;
            }

            // Kapasitas terpakai oleh booking lain
            $q = "SELECT COALESCE(SUM(a.allocated_qty), 0) AS used
                    FROM booking_resource_allocations a
                    JOIN booking_items i ON i.id = a.booking_item_id
                    JOIN bookings b      ON b.id = i.booking_id
                   WHERE a.resource_id = ?
                     AND a.allocation_status NOT IN ('CANCELLED','RELEASED')
                     AND a.start_at < ? AND a.end_at > ?
                     AND b.status NOT IN ('CANCELLED','EXPIRED')
                     AND NOT (b.status = 'PENDING' AND b.expires_at IS NOT NULL AND b.expires_at <= ?)";
            $qb = [(int) $r['id'], $we, $ws, $now];
            if ($ignoreBookingId) {
                $q   .= ' AND b.id <> ?';
                $qb[] = $ignoreBookingId;
            }
            if ($ignoreItemIds) {
                $q .= ' AND a.booking_item_id NOT IN (' . implode(',', array_map('intval', $ignoreItemIds)) . ')';
            }
            $used = (float) $this->db->query($q, $qb)->getRow('used');

            foreach ($pending as $p) {
                if ((int) $p['resource_id'] === (int) $r['id'] && $p['start'] < $we && $p['end'] > $ws) {
                    $used += (float) $p['qty'];
                }
            }

            $left = max(0.0, $cap - $used);
            if ($left <= 0) {
                $reasons[] = $r['resource_name'] . ': sudah terpakai pada periode itu';
                continue;
            }

            $free[] = ['id' => (int) $r['id'], 'name' => $r['resource_name'], 'free' => $left];
        }

        $totalFree = array_sum(array_column($free, 'free'));

        if ($totalFree + 0.0001 < $qty) {
            return $this->fail($qty, $totalFree, '', $reasons);
        }

        $remain = $qty;
        $alloc  = [];
        foreach ($free as $f) {
            if ($remain <= 0) {
                break;
            }
            $take = min($f['free'], $remain);
            if ($take <= 0) {
                continue;
            }
            $alloc[] = ['resource_id' => $f['id'], 'name' => $f['name'], 'qty' => $take];
            $remain -= $take;
        }

        return ['ok' => true, 'allocations' => $alloc, 'free' => $totalFree, 'message' => '', 'reasons' => $reasons];
    }

    /**
     * Peringatan (tidak memblokir) soal availability_rules: hari/jam layanan dan batas maju pemesanan.
     */
    public function warnings(array $c, string $start, string $end): array
    {
        $rules = $this->db->table('availability_rules')
            ->where('catalog_item_id', (int) $c['id'])->where('resource_id', null)
            ->get()->getResultArray();

        if (!$rules) {
            return [];
        }

        $out = [];
        foreach (['mulai' => $start, 'selesai' => $end] as $label => $moment) {
            $ts   = strtotime($moment);
            $n    = (int) date('N', $ts);
            $w    = (int) date('w', $ts);
            $time = date('H:i:s', $ts);
            $ok   = false;
            foreach ($rules as $r) {
                $d = (int) $r['day_of_week'];
                if (($d === $n || $d === $w) && (int) $r['is_available'] === 1
                    && $time >= $r['start_time'] && $time <= $r['end_time']) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $out[] = "Waktu {$label} di luar jadwal layanan item ini (hari/jam).";
            }
        }

        $max = null;
        foreach ($rules as $r) {
            if ($r['max_advance_days'] !== null) {
                $max = max((int) $max, (int) $r['max_advance_days']);
            }
        }
        if ($max !== null && strtotime($start) > time() + $max * 86400) {
            $out[] = "Tanggal mulai melewati batas pemesanan di muka ({$max} hari).";
        }

        return $out;
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function fail(float $qty, float $free, string $message, array $reasons): array
    {
        if ($message === '') {
            $message = 'Unit tidak cukup: butuh ' . $this->num($qty) . ', tersedia ' . $this->num($free) . ' pada periode itu.';
        }
        return ['ok' => false, 'allocations' => [], 'free' => $free, 'message' => $message, 'reasons' => $reasons];
    }

    private function num($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    }

    /** Hari dalam aturan harga: 1-7 (Senin-Minggu); 0 juga dianggap Minggu. */
    private function dayOk(array $r, int $dayTs): bool
    {
        if (!$this->rangeOk($r, $dayTs)) {
            return false;
        }
        if ($r['day_of_week'] === null) {
            return true;
        }
        $d = (int) $r['day_of_week'];
        $n = (int) date('N', $dayTs);
        return $d === $n || ($d === 0 && $n === 7);
    }

    private function rangeOk(array $r, int $dayTs): bool
    {
        $d = date('Y-m-d', $dayTs);
        if ($r['start_date'] !== null && $d < $r['start_date']) {
            return false;
        }
        if ($r['end_date'] !== null && $d > $r['end_date']) {
            return false;
        }
        return true;
    }

    private function qtyDurOk(array $r, float $qty, int $dur): bool
    {
        if ($r['min_duration'] !== null && $dur < (float) $r['min_duration']) {
            return false;
        }
        if ($r['max_duration'] !== null && $dur > (float) $r['max_duration']) {
            return false;
        }
        if ($r['min_qty'] !== null && $qty < (float) $r['min_qty']) {
            return false;
        }
        if ($r['max_qty'] !== null && $qty > (float) $r['max_qty']) {
            return false;
        }
        return true;
    }
}