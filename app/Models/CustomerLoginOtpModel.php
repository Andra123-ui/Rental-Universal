<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerLoginOtpModel extends Model
{
    protected $table            = 'customer_login_otps';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'customer_account_id',
        'phone',
        'purpose',
        'code_hash',
        'expires_at',
        'attempt_count',
        'max_attempts',
        'status',
        'requested_ip',
        'user_agent',
        'verified_at',
    ];

    // Tabel ini tidak punya updated_at, hanya created_at (default CURRENT_TIMESTAMP)
    protected $useTimestamps = false;

    public const PURPOSE_LOGIN = 'LOGIN';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_USED    = 'USED';    // one-time use, di-set setelah verifikasi sukses
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_BLOCKED = 'BLOCKED'; // max attempts terlampaui

    /**
     * Hitung berapa kali phone ini minta OTP dalam window waktu tertentu.
     * Dipakai untuk rate limit per-phone.
     */
    public function countRecentByPhone(string $phone, int $windowSeconds): int
    {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);

        return $this->where('phone', $phone)
            ->where('purpose', self::PURPOSE_LOGIN)
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    /**
     * Hitung berapa kali IP ini minta OTP dalam window waktu tertentu.
     * Dipakai untuk rate limit per-IP.
     */
    public function countRecentByIp(string $ip, int $windowSeconds): int
    {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);

        return $this->where('requested_ip', $ip)
            ->where('purpose', self::PURPOSE_LOGIN)
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    /**
     * OTP terakhir yang masih PENDING & belum expired untuk phone ini.
     * Dipakai untuk cegah spam "kirim ulang" dalam jeda sangat pendek.
     */
    public function latestPendingByPhone(string $phone): ?array
    {
        return $this->where('phone', $phone)
            ->where('purpose', self::PURPOSE_LOGIN)
            ->where('status', self::STATUS_PENDING)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    /**
     * OTP PENDING terbaru untuk sebuah customer_account (boleh sudah expired —
     * dipakai di halaman verify untuk cek expiry/attempt/blocked secara eksplisit).
     */
    public function latestPendingByAccountId(int $customerAccountId): ?array
    {
        return $this->where('customer_account_id', $customerAccountId)
            ->where('purpose', self::PURPOSE_LOGIN)
            ->where('status', self::STATUS_PENDING)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public function incrementAttempt(int $otpId): void
    {
        $this->set('attempt_count', 'attempt_count + 1', false)->update($otpId);
    }

    public function markUsed(int $otpId): void
    {
        $this->update($otpId, [
            'status'      => self::STATUS_USED,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markBlocked(int $otpId): void
    {
        $this->update($otpId, ['status' => self::STATUS_BLOCKED]);
    }

    public function markExpired(int $otpId): void
    {
        $this->update($otpId, ['status' => self::STATUS_EXPIRED]);
    }
}