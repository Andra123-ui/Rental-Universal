<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'customer_id',
        'channel',
        'template_code',
        'recipient',
        'subject',
        'message',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $useTimestamps = false;

    public function getCustomerNotifications(int $customerId, int $limit = 10): array
    {
        return $this->where('customer_id', $customerId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }
}

