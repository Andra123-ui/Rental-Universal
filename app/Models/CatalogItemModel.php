<?php

namespace App\Models;

use CodeIgniter\Model;

class CatalogItemModel extends Model
{
    protected $table = 'catalog_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'status', 'created_at'];

    public function getPopularItems($branchId = null, $limit = 6)
    {
        $builder = $this->db->table('catalog_items');

        if ($branchId !== null) {
            // Filter berdasarkan cabang spesifik
            $builder->select('catalog_items.*, COUNT(DISTINCT b.id) AS total_booked');
            $builder->join('booking_items bi', 'bi.catalog_item_id = catalog_items.id', 'left');
            $builder->join('bookings b', "b.id = bi.booking_id AND b.branch_id = {$branchId} AND b.status NOT IN ('CANCELLED', 'EXPIRED')", 'left');

            $builder->groupBy('catalog_items.id');
            $builder->orderBy('total_booked', 'DESC');
        } else {
            // Tampilkan semua tanpa hitungan
            $builder->select('catalog_items.*');
            $builder->orderBy('catalog_items.created_at', 'DESC');
        }

        $builder->where('catalog_items.status', 'ACTIVE');
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }
}