<?php

namespace App\Models;

use CodeIgniter\Model;

class ResourceModel extends Model
{
    protected $table = 'resources';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true; // tabel punya kolom deleted_at

    protected $allowedFields = [
        'catalog_item_id',
        'branch_id',
        'resource_code',
        'resource_name',
        'resource_type',
        'primary_identifier',
        'secondary_identifier',
        'phone',
        'email',
        'capacity',
        'status',
        'purchase_date',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Status constants (sesuai enum di skema: AVAILABLE, RESERVED, IN_USE, MAINTENANCE, INACTIVE)
    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_IN_USE = 'IN_USE';
    public const STATUS_MAINTENANCE = 'MAINTENANCE';
    public const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Ambil resource yang berstatus AVAILABLE untuk satu catalog_item.
     * Dipakai saat auto-allocate resource ke booking_items (lihat Checkout::proses()).
     */
    public function getAvailableByItem(int $catalogItemId): array
    {
        return $this->where('catalog_item_id', $catalogItemId)
            ->where('status', self::STATUS_AVAILABLE)
            ->orderBy('resource_code', 'ASC')
            ->findAll();
    }

    /**
     * Cari 1 resource yang benar-benar kosong (tidak overlap jadwal) untuk
     * suatu periode tertentu — lebih akurat daripada sekadar cek status statis,
     * karena status AVAILABLE tidak menjamin bebas di semua tanggal (lihat
     * catatan RES-01 di blueprint: "kalender allocation tetap sumber availability").
     */
    public function findFreeResourceForPeriod(int $catalogItemId, string $startAt, string $endAt): ?array
    {
        $db = \Config\Database::connect();

        $candidates = $this->where('catalog_item_id', $catalogItemId)
            ->where('status !=', self::STATUS_INACTIVE)
            ->where('status !=', self::STATUS_MAINTENANCE)
            ->findAll();

        foreach ($candidates as $resource) {
            $conflict = $db->table('booking_resource_allocations')
                ->where('resource_id', $resource['id'])
                ->whereIn('allocation_status', ['RESERVED', 'IN_USE'])
                ->where('start_at <', $endAt)
                ->where('end_at >', $startAt)
                ->countAllResults();

            if ($conflict === 0) {
                return $resource;
            }
        }

        return null;
    }
}