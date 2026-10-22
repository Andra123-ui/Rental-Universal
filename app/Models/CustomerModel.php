<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    
    // PASTIKAN 'profile_photo' ADA DI SINI
    protected $allowedFields    = [
        'customer_code',
        'name',
        'phone',
        'email',
        'profile_photo', // <--- INI WAJIB ADA
        'id_type',
        'id_number',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByPhone(string $canonicalPhone): ?array
    {
        return $this->where('phone', $canonicalPhone)->first();
    }
}