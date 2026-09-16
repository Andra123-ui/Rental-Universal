<?php

namespace App\Models;

use CodeIgniter\Model;

class AvailabilityModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Cabang yang punya resource aktif untuk item ini.
     */
    public function getLocationsForItem(int $catalogItemId): array
    {
        return $this->db->table('resources')
            ->select('branch_id')
            ->where('catalog_item_id', $catalogItemId)
            ->where('status', 'AVAILABLE')
            ->where('deleted_at IS NULL')
            ->where('branch_id IS NOT NULL')
            ->distinct()
            ->get()
            ->getResultArray();
    }

    /**
     * Resource kandidat (id + capacity) untuk item + cabang tertentu.
     */
    protected function getCandidateResources(int $catalogItemId, ?int $branchId): array
    {
        $builder = $this->db->table('resources')
            ->select('id, capacity')
            ->where('catalog_item_id', $catalogItemId)
            ->where('status', 'AVAILABLE')
            ->where('deleted_at IS NULL');

        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Jadwal operasional (availability_rules). Kalau tidak ada rule sama sekali
     * untuk item ini, dianggap selalu buka (tidak ada batasan hari/jam).
     * Return: ['ok' => bool, 'message' => string|null]
     */
    public function checkOperationalWindow(int $catalogItemId, ?int $branchId, string $startAt): array
    {
        $dayOfWeek = (int) date('w', strtotime($startAt)); // 0=Minggu ... 6=Sabtu
        $time = date('H:i:s', strtotime($startAt));

        $builder = $this->db->table('availability_rules')
            ->where('catalog_item_id', $catalogItemId)
            ->where('day_of_week', $dayOfWeek);

        if ($branchId) {
            $builder->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id IS NULL')
                ->groupEnd();
        }

        $rules = $builder->get()->getResultArray();

        if (empty($rules)) {
            // Tidak ada rule utk item ini sama sekali -> tidak dibatasi jadwal operasional
            $anyRule = $this->db->table('availability_rules')
                ->where('catalog_item_id', $catalogItemId)
                ->countAllResults();
            if ($anyRule === 0) {
                return ['ok' => true, 'message' => null];
            }
            return ['ok' => false, 'message' => 'Item tidak beroperasi pada hari yang dipilih.'];
        }

        foreach ($rules as $rule) {
            if ((int) $rule['is_available'] === 1
                && $time >= $rule['start_time']
                && $time <= $rule['end_time']
            ) {
                // Lead time / notice minimum
                if (!empty($rule['min_notice_minutes'])) {
                    $minutesToStart = (strtotime($startAt) - time()) / 60;
                    if ($minutesToStart < (int) $rule['min_notice_minutes']) {
                        $hours = round($rule['min_notice_minutes'] / 60, 1);
                        return ['ok' => false, 'message' => "Booking untuk item ini perlu dilakukan minimal {$hours} jam sebelumnya."];
                    }
                }
                // Batas maksimal pemesanan di muka
                if (!empty($rule['max_advance_days'])) {
                    $daysAhead = (strtotime($startAt) - time()) / 86400;
                    if ($daysAhead > (int) $rule['max_advance_days']) {
                        return ['ok' => false, 'message' => "Item ini hanya bisa dipesan maksimal {$rule['max_advance_days']} hari sebelumnya."];
                    }
                }
                return ['ok' => true, 'message' => null];
            }
        }

        return ['ok' => false, 'message' => 'Jam yang dipilih berada di luar jam operasional item ini.'];
    }

    /**
     * Total kapasitas terpakai (allocated_qty) untuk sekumpulan resource,
     * dari allocation yang overlap dengan rentang waktu.
     * Return: [resource_id => total_allocated]
     */
    protected function getAllocatedQtyMap(array $resourceIds, string $startAt, string $endAt): array
    {
        if (empty($resourceIds)) {
            return [];
        }

        $rows = $this->db->table('booking_resource_allocations')
            ->select('resource_id, SUM(allocated_qty) as total_qty')
            ->whereIn('resource_id', $resourceIds)
            ->whereIn('allocation_status', ['RESERVED', 'IN_USE'])
            ->where('start_at <', $endAt)
            ->where('end_at >', $startAt)
            ->groupBy('resource_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['resource_id']] = (float) $r['total_qty'];
        }
        return $map;
    }

    /**
     * Blackout aktif yang overlap, per resource: FULL block (kapasitas jadi 0)
     * atau pengurangan kapasitas parsial.
     * Return: [resource_id => ['full' => bool, 'reduction' => float]]
     */
    protected function getBlackoutMap(int $catalogItemId, array $resourceIds, ?int $branchId, string $startAt, string $endAt): array
    {
        $builder = $this->db->table('blackout_periods')
            ->where('status', 'ACTIVE')
            ->where('start_at <', $endAt)
            ->where('end_at >', $startAt)
            ->groupStart()
                ->where('catalog_item_id', $catalogItemId)
                ->orWhereIn('resource_id', $resourceIds ?: [0]);

        if ($branchId) {
            $builder->orWhere('branch_id', $branchId);
        }
        $builder->groupEnd();

        $rows = $builder->get()->getResultArray();

        $map = [];
        $globalFull = false;
        $globalReduction = 0.0;

        foreach ($rows as $row) {
            $isFull = $row['block_type'] === 'FULL';
            $reduction = (float) ($row['capacity_reduction'] ?? 0);

            if (!empty($row['resource_id']) && in_array($row['resource_id'], $resourceIds)) {
                $rid = $row['resource_id'];
                if (!isset($map[$rid])) {
                    $map[$rid] = ['full' => false, 'reduction' => 0.0];
                }
                $map[$rid]['full'] = $map[$rid]['full'] || $isFull;
                $map[$rid]['reduction'] += $reduction;
            } else {
                // berlaku ke seluruh item / seluruh cabang
                $globalFull = $globalFull || $isFull;
                $globalReduction += $reduction;
            }
        }

        foreach ($resourceIds as $rid) {
            if (!isset($map[$rid])) {
                $map[$rid] = ['full' => false, 'reduction' => 0.0];
            }
            $map[$rid]['full'] = $map[$rid]['full'] || $globalFull;
            $map[$rid]['reduction'] += $globalReduction;
        }

        return $map;
    }

    /**
     * Resource dengan maintenance aktif yang overlap -> kapasitas jadi 0.
     */
    protected function getMaintenanceBlockedIds(array $resourceIds, string $startAt, string $endAt): array
    {
        if (empty($resourceIds)) {
            return [];
        }

        $rows = $this->db->table('maintenance_records')
            ->select('resource_id')
            ->whereIn('resource_id', $resourceIds)
            ->whereIn('status', ['SCHEDULED', 'IN_PROGRESS'])
            ->where('start_at <', $endAt)
            ->groupStart()
                ->where('end_at IS NULL')
                ->orWhere('end_at >', $startAt)
            ->groupEnd()
            ->get()
            ->getResultArray();

        return array_unique(array_column($rows, 'resource_id'));
    }

    /**
     * Cek ketersediaan total untuk item+cabang pada rentang waktu, dibanding qty diminta.
     */
    public function checkAvailability(int $catalogItemId, ?int $branchId, string $startAt, string $endAt, int $qty): array
    {
        $resources = $this->getCandidateResources($catalogItemId, $branchId);

        if (empty($resources)) {
            return [
                'status' => 'unavailable',
                'message' => 'Item ini belum memiliki unit tersedia di lokasi tersebut.',
                'available_units' => 0,
            ];
        }

        $resourceIds = array_column($resources, 'id');

        $allocatedMap   = $this->getAllocatedQtyMap($resourceIds, $startAt, $endAt);
        $blackoutMap    = $this->getBlackoutMap($catalogItemId, $resourceIds, $branchId, $startAt, $endAt);
        $maintenanceIds = $this->getMaintenanceBlockedIds($resourceIds, $startAt, $endAt);

        $totalAvailable = 0.0;

        foreach ($resources as $res) {
            $rid = $res['id'];
            $capacity = $res['capacity'] !== null ? (float) $res['capacity'] : 1.0;

            if (in_array($rid, $maintenanceIds)) {
                continue; // 0 tersedia, sedang maintenance
            }
            if (!empty($blackoutMap[$rid]['full'])) {
                continue; // 0 tersedia, full blackout
            }

            $reduction = $blackoutMap[$rid]['reduction'] ?? 0.0;
            $allocated = $allocatedMap[$rid] ?? 0.0;

            $available = $capacity - $reduction - $allocated;
            $totalAvailable += max(0, $available);
        }

        if ($totalAvailable >= $qty) {
            $status = 'available';
            $message = 'Tersedia untuk jadwal yang dipilih.';
        } elseif ($totalAvailable > 0) {
            $status = 'limited';
            $message = "Hanya tersisa " . rtrim(rtrim(number_format($totalAvailable, 2), '0'), '.') . " unit/kapasitas untuk jadwal ini.";
        } else {
            $status = 'unavailable';
            $message = 'Tidak tersedia pada jadwal ini.';
        }

        return [
            'status' => $status,
            'message' => $message,
            'available_units' => $totalAvailable,
        ];
    }

    /**
     * Estimasi harga berdasarkan pricing_rules yang cocok (prioritas tertinggi menang),
     * fallback ke base_price item kalau tidak ada rule yang cocok.
     *
     * CATATAN: enum value_type belum dikonfirmasi. Ditangani generik:
     * PER_HOUR / PER_DAY / PER_UNIT / FLAT / lainnya (fallback per-hari).
     */
    public function estimatePrice(array $item, ?int $branchId, string $startAt, string $endAt, int $qty): array
    {
        $durationHours = max(0.01, (strtotime($endAt) - strtotime($startAt)) / 3600);
        $durationDays  = $durationHours / 24;
        $dayOfWeek     = (int) date('w', strtotime($startAt));
        $date          = date('Y-m-d', strtotime($startAt));
        $time          = date('H:i:s', strtotime($startAt));

        $builder = $this->db->table('pricing_rules')
            ->where('catalog_item_id', $item['id'])
            ->where('is_active', 1)
            ->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id IS NULL')
            ->groupEnd()
            ->groupStart()
                ->where('day_of_week', $dayOfWeek)
                ->orWhere('day_of_week IS NULL')
            ->groupEnd()
            ->groupStart()
                ->where('start_date IS NULL')
                ->orWhere('start_date <=', $date)
            ->groupEnd()
            ->groupStart()
                ->where('end_date IS NULL')
                ->orWhere('end_date >=', $date)
            ->groupEnd()
            ->groupStart()
                ->where('start_time IS NULL')
                ->orWhere('start_time <=', $time)
            ->groupEnd()
            ->groupStart()
                ->where('end_time IS NULL')
                ->orWhere('end_time >=', $time)
            ->groupEnd()
            ->groupStart()
                ->where('min_duration IS NULL')
                ->orWhere('min_duration <=', $durationHours)
            ->groupEnd()
            ->groupStart()
                ->where('max_duration IS NULL')
                ->orWhere('max_duration >=', $durationHours)
            ->groupEnd()
            ->groupStart()
                ->where('min_qty IS NULL')
                ->orWhere('min_qty <=', $qty)
            ->groupEnd()
            ->groupStart()
                ->where('max_qty IS NULL')
                ->orWhere('max_qty >=', $qty)
            ->groupEnd()
            ->orderBy('priority', 'DESC');

        $rule = $builder->get()->getRowArray();

        if (!$rule) {
    $durationHours = max(0.01, (strtotime($endAt) - strtotime($startAt)) / 3600);
    $durationDays  = $durationHours / 24;

    switch ($item['pricing_unit']) {
        case 'SESSION':
            $units = 1;
            break;
        case 'NIGHT':
        case 'DAY':
        default:
            $units = max(1, (int) ceil($durationDays));
            break;
    }

    $subtotal = $item['base_price'] * $units * $qty;

    return [
        'unit_price' => (float) $item['base_price'],
        'basis'      => 'base_price per ' . strtolower($item['pricing_unit']) . ' (tidak ada pricing_rule yang cocok)',
        'units'      => $units,
        'qty'        => $qty,
        'subtotal'   => (float) $subtotal,
    ];
}

        $priceValue = (float) $rule['price_value'];

        switch ($rule['value_type']) {
            case 'PER_HOUR':
                $units = max(1, (int) ceil($durationHours));
                $subtotal = $priceValue * $units * $qty;
                break;
            case 'PER_UNIT':
            case 'FLAT':
                $units = 1;
                $subtotal = $priceValue * $qty;
                break;
            case 'PER_DAY':
            default:
                $units = max(1, (int) ceil($durationDays));
                $subtotal = $priceValue * $units * $qty;
                break;
        }

        return [
            'unit_price' => $priceValue,
            'basis'      => $rule['name'] . ' (' . $rule['value_type'] . ')',
            'units'      => $units,
            'qty'        => $qty,
            'subtotal'   => (float) $subtotal,
        ];
    }

    /**
     * Cari alternatif jadwal terdekat (mundur/maju per hari) dengan durasi sama.
     */
    public function findAlternativeSlots(int $catalogItemId, ?int $branchId, string $startAt, string $endAt, int $qty, int $maxTries = 5): array
    {
        $alternatives = [];
        $durationSec = strtotime($endAt) - strtotime($startAt);

        for ($i = 1; $i <= $maxTries && count($alternatives) < 3; $i++) {
            foreach ([1, -1] as $direction) {
                $newStart = date('Y-m-d H:i:s', strtotime($startAt) + ($direction * $i * 86400));
                $newEnd   = date('Y-m-d H:i:s', strtotime($newStart) + $durationSec);

                $check = $this->checkAvailability($catalogItemId, $branchId, $newStart, $newEnd, $qty);
                if ($check['status'] === 'available') {
                    $alternatives[] = ['start_at' => $newStart, 'end_at' => $newEnd];
                }
                if (count($alternatives) >= 3) {
                    break;
                }
            }
        }

        return $alternatives;
    }

    /**
 * Breakdown ketersediaan per cabang untuk item ini pada rentang waktu tertentu.
 */
public function checkAvailabilityByBranch(int $catalogItemId, string $startAt, string $endAt, int $qty): array
{
    $branchRows = $this->db->table('resources')
        ->select('branch_id')
        ->distinct()
        ->where('catalog_item_id', $catalogItemId)
        ->where('status', 'AVAILABLE')
        ->where('deleted_at IS NULL')
        ->get()
        ->getResultArray();

    $results = [];
    foreach ($branchRows as $row) {
        $branchId = $row['branch_id'] !== null ? (int) $row['branch_id'] : null;
        $check = $this->checkAvailability($catalogItemId, $branchId, $startAt, $endAt, $qty);

        $branchName = 'Lokasi Utama';
if ($branchId) {
    $branch = $this->db->table('branches')->select('branch_name')->where('id', $branchId)->get()->getRowArray();
    $branchName = $branch['branch_name'] ?? ('Cabang #' . $branchId);
}

        $results[] = [
            'branch_id'       => $branchId,
            'branch_name'     => $branchName,
            'status'          => $check['status'],
            'message'         => $check['message'],
            'available_units' => $check['available_units'],
        ];
    }

    return $results;
}
}