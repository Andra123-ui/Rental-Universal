<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;
use App\Models\CategoryModel;
use App\Models\ResourceModel;
use App\Models\BookingResourceAllocationModel;

class Katalog extends BaseController
{
    public function index()
    {
        $catalogModel  = new CatalogItemModel();
        $categoryModel = new CategoryModel();

        $keyword  = $this->request->getGet('q');
        $kategori = $this->request->getGet('kategori');
        $sort     = $this->request->getGet('sort') ?? 'terbaru';

        $query = $catalogModel->where('status', 'ACTIVE');

        if (! empty($keyword)) {
            $query->like('name', $keyword);
        }

        if (! empty($kategori)) {
            $cat = $categoryModel->where('slug', $kategori)->first();
            if ($cat) {
                $query->where('category_id', $cat['id']);
            }
        }

        match ($sort) {
            'harga-terendah'  => $query->orderBy('base_price', 'ASC'),
            'harga-tertinggi' => $query->orderBy('base_price', 'DESC'),
            default           => $query->orderBy('created_at', 'DESC'),
        };

        $items      = $query->paginate(9);
        $categories = $categoryModel->where('is_active', 1)->orderBy('sort_order', 'ASC')->findAll();

        return view('pub/katalog', [
            'items'      => $items,
            'pager'      => $catalogModel->pager,
            'categories' => $categories,
            'keyword'    => $keyword,
            'kategori'   => $kategori,
            'sort'       => $sort,
            'unitLabels' => unit_labels(),
        ]);
    }

    public function detail($id)
    {
        $catalogModel  = new CatalogItemModel();
        $resourceModel = new ResourceModel();
        $allocModel    = new BookingResourceAllocationModel();

        $item = $catalogModel->find($id);
        if (! $item) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $startAt = $this->request->getGet('start_at');
        $endAt   = $this->request->getGet('end_at');
        $availability = null;

        if ($startAt && $endAt) {
            $resourceIds = array_column(
                $resourceModel->select('id')->where('catalog_item_id', $item['id'])->findAll(),
                'id'
            );

            $total = count($resourceIds);

            $overlap = 0;
            if ($total > 0) {
                $overlap = $allocModel
                    ->whereIn('resource_id', $resourceIds)
                    ->where('allocation_status !=', 'CANCELLED')
                    ->groupStart()
                        ->where('start_at <', $endAt)
                        ->where('end_at >', $startAt)
                    ->groupEnd()
                    ->countAllResults();
            }

            $availability = [
                'total'    => $total,
                'overlap'  => $overlap,
                'tersedia' => $total === 0 || $overlap < $total,
            ];
        }

        return view('pub/katalog_detail', [
            'item'         => $item,
            'unitLabels'   => unit_labels(),
            'startAt'      => $startAt,
            'endAt'        => $endAt,
            'availability' => $availability,
        ]);
    }
}