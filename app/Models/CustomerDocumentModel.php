<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerDocumentModel extends Model
{
    protected $table            = 'customer_documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'customer_id',
        'document_type',
        'document_number',
        'file_path',
        'expires_at',
        'is_verified',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil semua dokumen milik customer.
     */
    public function getByCustomer(int $customerId): array
    {
        return $this
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Ambil dokumen terbaru milik customer.
     */
    public function getLatestByCustomer(int $customerId): ?array
    {
        return $this
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * Ambil dokumen berdasarkan ID dan customer.
     */
    public function findCustomerDocument(
        int $documentId,
        int $customerId
    ): ?array {
        return $this
            ->where('id', $documentId)
            ->where('customer_id', $customerId)
            ->first();
    }
}