<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerAccountModel extends Model
{
    protected $table            = 'customer_accounts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'customer_id',
        'login_phone',
        'phone_verified_at',
        'status',
        'last_login_at',
        'last_login_ip',
        'failed_attempts',
        'locked_until',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    /**
     * Cari account berdasarkan nomor HP login (format canonical +62...).
     */
    public function findByLoginPhone(string $canonicalPhone): ?array
    {
        return $this->where('login_phone', $canonicalPhone)->first();
    }

    /**
     * Cek apakah account sedang dalam status suspended.
     * Dipakai di CAUTH-02 (verifikasi OTP) — account boleh minta OTP,
     * tapi tidak boleh berhasil login setelah OTP jika suspended.
     */
    public function isSuspended(array $account): bool
    {
        return ($account['status'] ?? null) === self::STATUS_SUSPENDED;
    }
}