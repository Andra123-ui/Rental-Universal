<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingStatusHistoryModel extends Model
{
    protected $table            = 'booking_status_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'note',
        'changed_at',
    ];

    protected $useTimestamps = false;

    /**
     * Ambil histori status untuk booking_id yang aman untuk dilihat customer
     */
    public function getCustomerHistory(int $bookingId): array
    {
        return $this->select('id, booking_id, old_status, new_status, note, changed_at')
            ->where('booking_id', $bookingId)
            ->orderBy('changed_at', 'ASC')
            ->findAll();
    }
}

