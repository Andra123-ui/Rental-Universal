<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemSettingModel extends Model
{
    protected $table            = 'system_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'business_id',
        'branch_id',
        'setting_key',
        'setting_value',
        'value_type',
        'is_public',
        'description',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil satu nilai setting berdasarkan key.
     * Ambil baris pertama yang cocok (business/branch scoping bisa
     * ditambahkan di sini jika multi-tenant aktif untuk halaman ini).
     */
    public function getValue(string $key): ?string
    {
        $row = $this->where('setting_key', $key)->first();

        return $row['setting_value'] ?? null;
    }
}