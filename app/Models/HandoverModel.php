<?php

namespace App\Models;

use CodeIgniter\Model;

class HandoverModel extends Model
{
    protected $table            = 'handovers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'handover_type',
        'scheduled_at',
        'actual_at',
        'handled_by_user_id',
        'customer_receiver_name',
        'location',
        'signature_path',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil jadwal serah terima terdekat untuk customer
     */
    public function getUpcomingByCustomer(int $customerId, int $limit = 5): array
    {
        return $this->db->table('handovers h')
            ->select('h.*, b.invoice_no, b.customer_id, b.status as booking_status')
            ->join('bookings b', 'b.id = h.booking_id')
            ->where('b.customer_id', $customerId)
            ->where('h.actual_at IS NULL')
            ->where('h.scheduled_at >=', date('Y-m-d H:i:s'))
            ->orderBy('h.scheduled_at', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil handovers berdasarkan booking_id
     */
    public function getByBookingId(int $bookingId): array
    {
        return $this->where('booking_id', $bookingId)
            ->orderBy('scheduled_at', 'ASC')
            ->findAll();
    }
}

