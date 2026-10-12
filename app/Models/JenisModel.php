<?php

namespace App\Models;

use CodeIgniter\Model;

class JenisModel extends Model
{
    protected $table         = 'jenis';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['business_id', 'category_id', 'name', 'slug', 'sort_order', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Jenis aktif milik satu bisnis, lengkap dengan slug kategorinya
     * (dipakai dropdown jenis yang bergantung pada kategori).
     */
    public function forFilter(int $businessId): array
    {
        return $this->select('jenis.name, jenis.slug, categories.slug AS category_slug')
            ->join('categories', 'categories.id = jenis.category_id')
            ->where('jenis.business_id', $businessId)
            ->where('jenis.is_active', 1)
            ->orderBy('categories.sort_order')
            ->orderBy('jenis.sort_order')
            ->orderBy('jenis.name')
            ->findAll();
    }
}