<?php

namespace App\Models;

use CodeIgniter\Model;

class CatalogItemModel extends Model
{
    protected $table = 'catalog_items'; // Sesuaikan dengan nama tabel item/produk di database Anda
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'status', 'created_at'];
}