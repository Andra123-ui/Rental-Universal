<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingDocumentModel extends Model
{
    protected $table            = 'booking_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'document_type',
        'document_no',
        'file_path',
        'uploaded_by_type',
        'uploaded_by_user_id',
        'notes',
    ];

    protected $useTimestamps = false; // created_at defaults to current_timestamp()

    /**
     * Ambil seluruh dokumen transaksi milik customer
     */
    public function getDocumentsByCustomer(int $customerId, ?string $type = null, int $limit = 0, int $offset = 0): array
    {
        $builder = $this->db->table('booking_documents bd')
            ->select('bd.*, b.invoice_no, b.customer_id, b.start_at, b.end_at, b.status as booking_status, b.grand_total')
            ->join('bookings b', 'b.id = bd.booking_id')
            ->where('b.customer_id', $customerId)
            ->orderBy('bd.created_at', 'DESC');

        if ($type !== null && $type !== '' && $type !== 'ALL') {
            $builder->where('bd.document_type', $type);
        }

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Hitung total dokumen customer
     */
    public function countDocumentsByCustomer(int $customerId, ?string $type = null): int
    {
        $builder = $this->db->table('booking_documents bd')
            ->join('bookings b', 'b.id = bd.booking_id')
            ->where('b.customer_id', $customerId);

        if ($type !== null && $type !== '' && $type !== 'ALL') {
            $builder->where('bd.document_type', $type);
        }

        return $builder->countAllResults();
    }

    /**
     * Cari dokumen berdasarkan ID dengan validasi kepemilikan customer
     */
    public function findCustomerDocument(int $id, int $customerId): ?array
    {
        return $this->db->table('booking_documents bd')
            ->select('bd.*, b.invoice_no, b.customer_id')
            ->join('bookings b', 'b.id = bd.booking_id')
            ->where('bd.id', $id)
            ->where('b.customer_id', $customerId)
            ->get()
            ->getRowArray();
    }

    /**
     * Ambil dokumen berdasarkan booking_id
     */
    public function getByBookingId(int $bookingId): array
    {
        return $this->where('booking_id', $bookingId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}

