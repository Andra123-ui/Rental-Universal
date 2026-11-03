<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingChargeModel extends Model
{
    protected $table            = 'booking_charges';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'booking_item_id',
        'charge_type',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'status',
        'created_by_user_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByBookingId(int $bookingId): array
    {
        return $this->where('booking_id', $bookingId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}

