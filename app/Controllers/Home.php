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

        $catalogItems = $catalogModel
            ->where('status', 'ACTIVE')
            ->orderBy('created_at', 'DESC')
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