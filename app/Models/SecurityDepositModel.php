<?php

namespace App\Models;

use CodeIgniter\Model;

class SecurityDepositModel extends Model
{
    protected $table            = 'security_deposits';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'amount_required',
        'amount_received',
        'amount_deducted',
        'amount_refunded',
        'status',
        'received_payment_id',
        'refund_payment_id',
        'held_at',
        'released_at',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByBookingId(int $bookingId): ?array
    {
        return $this->where('booking_id', $bookingId)->first();
    }
}

