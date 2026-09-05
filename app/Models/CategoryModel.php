<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table = 'categories'; // Sesuaikan dengan nama tabel kategori di database Anda
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'parent_id', 'is_active', 'sort_order'];
}