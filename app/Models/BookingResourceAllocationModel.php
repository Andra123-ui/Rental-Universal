<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingResourceAllocationModel extends Model
{
    protected $table = 'booking_resource_allocations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false; // tabel ini tidak punya kolom deleted_at

    protected $allowedFields = [
        'booking_item_id',
        'resource_id',
        'allocated_qty',
        'start_at',
        'end_at',
        'allocation_status',
        'allocated_by_user_id',
    ];

    protected $useTimestamps = false; // hanya punya created_at, bukan pasangan created/updated standar
    protected $createdField = 'created_at';

    // Status constants (sesuai enum di skema: RESERVED, IN_USE, RETURNED, CANCELLED)
    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_IN_USE = 'IN_USE';
    public const STATUS_RETURNED = 'RETURNED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Cek konflik jadwal untuk satu resource pada rentang waktu tertentu.
     * Dipakai untuk validasi anti double-booking (PUB-05, PUB-09, Checkout::proses()).
     *
     * Overlap rule: requested_start < existing_end AND requested_end > existing_start
     */
    public function hasConflict(int $resourceId, string $startAt, string $endAt, ?int $excludeAllocationId = null): bool
    {
        $builder = $this->where('resource_id', $resourceId)
            ->whereIn('allocation_status', [self::STATUS_RESERVED, self::STATUS_IN_USE])
            ->where('start_at <', $endAt)
            ->where('end_at >', $startAt);

        if ($excludeAllocationId !== null) {
            $builder = $builder->where('id !=', $excludeAllocationId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Ambil semua alokasi aktif (RESERVED/IN_USE) untuk satu booking_item.
     */
    public function getActiveByBookingItem(int $bookingItemId): array
    {
        return $this->where('booking_item_id', $bookingItemId)
            ->whereIn('allocation_status', [self::STATUS_RESERVED, self::STATUS_IN_USE])
            ->findAll();
    }

    /**
     * Ambil semua alokasi untuk satu resource dalam rentang tanggal —
     * dipakai untuk menampilkan kalender resource (BOOK-05, RES-02).
     */
    public function getByResourceAndRange(int $resourceId, string $startAt, string $endAt): array
    {
        return $this->where('resource_id', $resourceId)
            ->where('start_at <', $endAt)
            ->where('end_at >', $startAt)
            ->orderBy('start_at', 'ASC')
            ->findAll();
    }

    /**
     * Update status alokasi (mis. RESERVED -> IN_USE saat handover pickup,
     * atau -> RETURNED saat barang kembali).
     */
    public function setStatus(int $allocationId, string $status): bool
    {
        return (bool) $this->update($allocationId, ['allocation_status' => $status]);
    }
}