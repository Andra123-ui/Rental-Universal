<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'payment_no',
        'payment_type',
        'direction',
        'method',
        'provider',
        'reference_no',
        'amount',
        'status',
        'paid_at',
        'proof_path',
        'received_by_user_id',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_PAID     = 'PAID';
    public const STATUS_FAILED   = 'FAILED';
    public const STATUS_REFUNDED = 'REFUNDED';

    /**
     * Cari payments berdasarkan customer_id dengan join ke bookings
     */
    public function getPaymentsByCustomer(int $customerId, ?string $status = null, int $limit = 0, int $offset = 0): array
    {
        $builder = $this->db->table('payments p')
            ->select('p.*, b.invoice_no, b.customer_id, b.status as booking_status, b.customer_name_snapshot')
            ->join('bookings b', 'b.id = p.booking_id')
            ->where('b.customer_id', $customerId)
            ->orderBy('p.created_at', 'DESC');

        if ($status !== null && $status !== '' && $status !== 'ALL') {
            $builder->where('p.status', $status);
        }

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Hitung total payments berdasarkan customer
     */
    public function countPaymentsByCustomer(int $customerId, ?string $status = null): int
    {
        $builder = $this->db->table('payments p')
            ->join('bookings b', 'b.id = p.booking_id')
            ->where('b.customer_id', $customerId);

        if ($status !== null && $status !== '' && $status !== 'ALL') {
            $builder->where('p.status', $status);
        }

        return $builder->countAllResults();
    }

    /**
     * Ambil pembayaran untuk sebuah booking tertentu
     */
    public function getPaymentsByBooking(int $bookingId): array
    {
        return $this->where('booking_id', $bookingId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Temukan payment berdasarkan payment_no dan pastikan kepemilikannya milik customer
     */
    public function findCustomerPayment(string $paymentNo, int $customerId): ?array
    {
        return $this->db->table('payments p')
            ->select('p.*, b.invoice_no, b.customer_id, b.grand_total, b.paid_total, b.balance_due, b.start_at, b.end_at, b.customer_name_snapshot, b.customer_phone_snapshot')
            ->join('bookings b', 'b.id = p.booking_id')
            ->where('p.payment_no', $paymentNo)
            ->where('b.customer_id', $customerId)
            ->get()
            ->getRowArray();
    }
}

