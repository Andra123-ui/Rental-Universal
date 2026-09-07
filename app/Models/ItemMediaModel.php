<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemMediaModel extends Model
{
    protected $table = 'item_media';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'catalog_item_id',
        'media_type',
        'file_path',
        'is_primary',
        'sort_order',
        'alt_text',
    ];
    protected $useTimestamps = false;
    protected $returnType = 'array';

    /**
     * Ambil gambar utama (is_primary=1) untuk satu item.
     * Kalau tidak ada yang primary, ambil yang sort_order paling kecil.
     */
    public function getPrimaryImage(int $catalogItemId): ?array
    {
        $img = $this->where('catalog_item_id', $catalogItemId)
            ->where('media_type', 'IMAGE')
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->first();

        return $img ?: null;
    }

    /**
     * Ambil semua gambar untuk galeri detail item.
     */
    public function getGallery(int $catalogItemId): array
    {
        return $this->where('catalog_item_id', $catalogItemId)
            ->where('media_type', 'IMAGE')
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    /**
     * Ambil primary image untuk banyak item sekaligus (hindari N+1 query).
     * Return: [catalog_item_id => file_path]
     */
    public function getPrimaryImageMap(array $catalogItemIds): array
    {
        if (empty($catalogItemIds)) {
            return [];
        }

        $rows = $this->whereIn('catalog_item_id', $catalogItemIds)
            ->where('media_type', 'IMAGE')
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            // Karena sudah diurutkan primary dulu, entry pertama per item_id yang dipakai
            if (!isset($map[$row['catalog_item_id']])) {
                $map[$row['catalog_item_id']] = $row['file_path'];
            }
        }

        return $map;
    }
}