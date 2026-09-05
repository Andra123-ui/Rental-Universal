<?php

namespace App\Models;

use CodeIgniter\Model;

class BusinessProfileModel extends Model
{
    protected $table = 'business_profiles'; // Sesuaikan dengan nama tabel di database
    protected $primaryKey = 'id';
    protected $allowedFields = ['company_name', 'email', 'phone', 'address', 'logo'];
}