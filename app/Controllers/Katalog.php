<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;
use App\Models\CategoryModel;
use App\Models\ItemMediaModel;

class Katalog extends BaseController
{
  public function index()
  {
    $catalogModel = new CatalogItemModel();
    $categoryModel = new CategoryModel();

    $kategori = $this->request->getGet('kategori');
    $keyword = $this->request->getGet('q');
    $sort = $this->request->getGet('sort') ?? 'terbaru';
    $page = (int) ($this->request->getGet('page') ?? 1);
    $perPage = 9;

    $builder = $catalogModel->where('status', 'ACTIVE');

    if (!empty($kategori)) {
      $cat = $categoryModel->where('slug', $kategori)->first();
      if ($cat) {
        $builder = $builder->where('category_id', $cat['id']);
      }
    }

    if (!empty($keyword)) {
      $builder = $builder->groupStart()
        ->like('name', $keyword)
        ->orLike('description', $keyword)
        ->groupEnd();
    }

    switch ($sort) {
      case 'harga_rendah':
        $builder = $builder->orderBy('base_price', 'ASC');
        break;
      case 'harga_tinggi':
        $builder = $builder->orderBy('base_price', 'DESC');
        break;
      default:
        $builder = $builder->orderBy('created_at', 'DESC');
    }

    $catalogItems = $builder->paginate($perPage, 'default', $page);
    $pager = $catalogModel->pager;

    $categories = $categoryModel
      ->where('is_active', 1)
      ->orderBy('sort_order', 'ASC')
      ->findAll();

    $mediaModel = new ItemMediaModel();
    $itemIds = array_column($catalogItems, 'id');
    $imageMap = $mediaModel->getPrimaryImageMap($itemIds);

    return view('pub/katalog', [
      'catalogItems' => $catalogItems,
      'categories' => $categories,
      'pager' => $pager,
      'activeKategori' => $kategori,
      'keyword' => $keyword,
      'sort' => $sort,
      'imageMap' => $imageMap,
    ]);
  }

  public function detail(string $hash)
  {
    $id = id_decode($hash);
    if ($id === null) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $catalogModel = new CatalogItemModel();
    $categoryModel = new CategoryModel();
    $mediaModel = new ItemMediaModel();

    $item = $catalogModel->find($id);

    if (!$item || $item['status'] !== 'ACTIVE') {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $category = $categoryModel->find($item['category_id']);

    $related = $catalogModel
      ->where('category_id', $item['category_id'])
      ->where('status', 'ACTIVE')
      ->where('id !=', $item['id'])
      ->limit(3)
      ->findAll();

    $gallery = $mediaModel->getGallery($id);
    $relatedIds = array_column($related, 'id');
    $relatedImages = $mediaModel->getPrimaryImageMap($relatedIds);

    return view('pub/katalog_detail', [
      'item' => $item,
      'category' => $category,
      'related' => $related,
      'gallery' => $gallery,
      'relatedImages' => $relatedImages,
    ]);
  }

  /**
   * PUB-05: Cek Ketersediaan (AJAX, JSON response)
   * Pakai AvailabilityModel supaya konsisten dengan Cart & Checkout —
   * ikut hitung kapasitas resource, blackout, dan maintenance,
   * bukan cuma cek overlap jadwal sederhana.
   */
  public function cekTersedia(string $hash)
  {
    $catalogItemId = id_decode($hash);
    if ($catalogItemId === null) {
      return $this->response->setStatusCode(404)->setJSON(['available' => false, 'message' => 'Item tidak ditemukan.']);
    }

    $startAt = $this->request->getGet('start_at');
    $endAt = $this->request->getGet('end_at');
    $qty = max(1, (int) ($this->request->getGet('qty') ?? 1));
    $branchId = $this->request->getGet('branch_id') ? (int) $this->request->getGet('branch_id') : null;

    if (empty($startAt) || empty($endAt)) {
      return $this->response->setJSON(['available' => false, 'message' => 'Tanggal tidak lengkap']);
    }

    $availabilityModel = new \App\Models\AvailabilityModel();
    $check = $availabilityModel->checkAvailability($catalogItemId, $branchId, $startAt, $endAt, $qty);

    return $this->response->setJSON([
      'available' => $check['status'] === 'available',
      'status' => $check['status'], // available | limited | unavailable
      'message' => $check['message'],
      'available_units' => $check['available_units'],
    ]);
  }
}