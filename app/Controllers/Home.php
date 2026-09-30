<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\CatalogItemModel;
use App\Models\ItemMediaModel;

class Home extends BaseController
{
    public function index()
    {
        $categoryModel = new CategoryModel();
        $catalogModel = new CatalogItemModel();
        $mediaModel = new ItemMediaModel();

        $categories = $categoryModel
            ->where('parent_id', null)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll(6);

        // Ambil 6 produk yang paling banyak dipesan
        $catalogItems = $catalogModel
            ->select('catalog_items.*, COUNT(DISTINCT b.id) AS total_booked')
            ->join(
                'booking_items bi',
                'bi.catalog_item_id = catalog_items.id',
                'left'
            )
            ->join(
                'bookings b',
                "b.id = bi.booking_id
                 AND b.status NOT IN ('CANCELLED', 'EXPIRED')",
                'left'
            )
            ->where('catalog_items.status', 'ACTIVE')
            ->groupBy('catalog_items.id')
            ->orderBy('total_booked', 'DESC')
            ->orderBy('catalog_items.created_at', 'DESC')
            ->findAll(6);

        $itemIds = array_column($catalogItems, 'id');

        $imageMap = $mediaModel->getPrimaryImageMap($itemIds);

        return view('pub/landing', [
            'categories' => $categories,
            'catalogItems' => $catalogItems,
            'imageMap' => $imageMap,
        ]);
    }
}