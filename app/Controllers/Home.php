<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\CatalogItemModel;
use App\Models\ItemMediaModel;
use App\Models\BranchModel; // Pastikan ini ditambahkan

class Home extends BaseController
{
    public function index()
    {
        $categoryModel = new CategoryModel();
        $catalogModel = new CatalogItemModel();
        $mediaModel = new ItemMediaModel();
        $branchModel = new BranchModel(); // Sekarang ini akan berfungsi

        // 1. Tangkap parameter cabang dari URL (default: 'semua')
        $selectedBranchSlug = $this->request->getGet('cabang') ?? 'semua';
        $branchId = null;

        // 2. Cari ID Cabang jika bukan 'semua'
        if ($selectedBranchSlug !== 'semua') {
            // Menggunakan branch_code sebagai slug di URL
            $branchData = $branchModel->where('branch_code', $selectedBranchSlug)->first();

            if ($branchData) {
                $branchId = $branchData['id'];
            } else {
                // Jika cabang tidak ditemukan, kembalikan ke 'semua'
                $selectedBranchSlug = 'semua';
            }
        }

        // 3. Ambil Kategori
        $categories = $categoryModel
            ->where('parent_id', null)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll(6);

        // 4. Ambil Produk Populer menggunakan method di Model (yang sudah kita buat sebelumnya)
        $catalogItems = $catalogModel->getPopularItems($branchId, 6);

        // 5. Ambil Gambar
        $itemIds = array_column($catalogItems, 'id');
        $imageMap = $mediaModel->getPrimaryImageMap($itemIds);

        // 6. Ambil semua daftar cabang untuk tombol filter
        $branches = $branchModel->where('is_active', 1)->findAll();

        return view('pub/landing', [
            'categories' => $categories,
            'catalogItems' => $catalogItems,
            'imageMap' => $imageMap,
            'branches' => $branches,
            'selectedBranch' => $selectedBranchSlug
        ]);
    }
}