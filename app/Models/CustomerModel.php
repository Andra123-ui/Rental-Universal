<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table         = 'customers';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'customer_code', 'name', 'phone', 'email', 'id_type', 'id_number', 'address',
        'emergency_contact_name', 'emergency_contact_phone', 'notes',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}