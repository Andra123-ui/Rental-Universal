<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table          = 'activity_logs';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $allowedFields  = [
        'user_id',
        'event_type',
        'module',
        'entity_type',
        'entity_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'metadata_json',
        'created_at',
    ];

    protected $useTimestamps = false; // created_at defaults to current_timestamp() in MySQL

    public const EVENT_LOGIN_OTP_SENT    = 'CUSTOMER_LOGIN_OTP_SENT';
    public const EVENT_LOGIN_SUCCESS     = 'CUSTOMER_LOGIN_SUCCESS';
    public const EVENT_LOGIN_OTP_FAILED  = 'CUSTOMER_LOGIN_OTP_FAILED';
    public const EVENT_LOGIN_OTP_EXPIRED = 'CUSTOMER_LOGIN_OTP_EXPIRED';
    public const EVENT_LOGIN_OTP_BLOCKED = 'CUSTOMER_LOGIN_OTP_BLOCKED';
    public const EVENT_LOGOUT            = 'CUSTOMER_LOGOUT';
    public const EVENT_PHONE_CHANGE      = 'CUSTOMER_PHONE_CHANGE';
    public const EVENT_PROFILE_UPDATE    = 'CUSTOMER_PROFILE_UPDATE';
    public const EVENT_DOCUMENT_UPLOAD   = 'CUSTOMER_DOCUMENT_UPLOAD';

    /**
     * Catat event dengan aman: TIDAK PERNAH menyimpan kode OTP, hash-nya,
     * atau data rahasia.
     */
    public function record(string $event, ?int $customerAccountId, string $ip, string $userAgent, ?string $description = null): void
    {
        try {
            $this->insert([
                'user_id'       => null,
                'event_type'    => $event,
                'module'        => 'customer_portal',
                'entity_type'   => 'customer_account',
                'entity_id'     => $customerAccountId,
                'action'        => strtolower($event),
                'description'   => $description ?? $event,
                'ip_address'    => $ip,
                'user_agent'    => mb_substr($userAgent, 0, 500),
                'metadata_json' => null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mencatat activity_logs: ' . $e->getMessage());
        }
    }

    /**
     * Ambil histori log keamanan akun customer
     */
    public function getCustomerSecurityLogs(int $customerAccountId, int $limit = 20): array
    {
        return $this->where('entity_type', 'customer_account')
            ->where('entity_id', $customerAccountId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }
}
