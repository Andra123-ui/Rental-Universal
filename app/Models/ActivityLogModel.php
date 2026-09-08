<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ASUMSI SKEMA (sesuaikan jika tabel activity_logs kamu berbeda):
 *
 * CREATE TABLE activity_logs (
 *   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   actor_type VARCHAR(30) NOT NULL,      -- 'CUSTOMER', 'STAFF', dst.
 *   actor_id BIGINT UNSIGNED NULL,
 *   event VARCHAR(60) NOT NULL,           -- 'CUSTOMER_LOGIN_SUCCESS', dst.
 *   description VARCHAR(255) NULL,
 *   ip_address VARCHAR(45) NULL,
 *   user_agent VARCHAR(500) NULL,
 *   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
 * )
 */
class ActivityLogModel extends Model
{
    protected $table            = 'activity_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'actor_type',
        'actor_id',
        'event',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public const EVENT_LOGIN_OTP_SENT     = 'CUSTOMER_LOGIN_OTP_SENT';
    public const EVENT_LOGIN_SUCCESS      = 'CUSTOMER_LOGIN_SUCCESS';
    public const EVENT_LOGIN_OTP_FAILED   = 'CUSTOMER_LOGIN_OTP_FAILED';
    public const EVENT_LOGIN_OTP_EXPIRED  = 'CUSTOMER_LOGIN_OTP_EXPIRED';
    public const EVENT_LOGIN_OTP_BLOCKED  = 'CUSTOMER_LOGIN_OTP_BLOCKED';

    /**
     * Catat event dengan aman: TIDAK PERNAH menyimpan kode OTP, hash-nya,
     * atau nomor HP utuh — cukup identitas customer_account_id + event.
     */
    public function record(string $event, ?int $customerAccountId, string $ip, string $userAgent, ?string $description = null): void
    {
        try {
            $this->insert([
                'actor_type'  => 'CUSTOMER',
                'actor_id'    => $customerAccountId,
                'event'       => $event,
                'description' => $description,
                'ip_address'  => $ip,
                'user_agent'  => $userAgent,
            ]);
        } catch (\Throwable $e) {
            // Jangan sampai gagal logging menghentikan proses login/verify.
            log_message('error', 'Gagal mencatat activity_logs: ' . $e->getMessage());
        }
    }
}