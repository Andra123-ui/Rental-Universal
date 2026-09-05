<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\CatalogItemModel;

class Home extends BaseController
{
    public function index()
    {
        $categoryModel = new CategoryModel();
        $catalogModel = new CatalogItemModel();

        $categories = $categoryModel
            ->where('parent_id', null)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll(6);

        $catalogItems = $catalogModel
            ->where('status', 'ACTIVE')
            ->orderBy('created_at', 'DESC')
            ->findAll(6);

        return view('pub/landing', [
            'categories' => $categories,
            'catalogItems' => $catalogItems,
            'unitLabels' => function_exists('unit_labels') ? unit_labels() : [],
        ]);
    }
}