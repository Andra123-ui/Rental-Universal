<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;
use App\Models\CategoryModel;
use App\Models\ItemMediaModel;

class Katalog extends BaseController
{
    public function index()
    {
        helper('business');

        $db = db_connect();
        $catalogModel = new CatalogItemModel();
        $categoryModel = new CategoryModel();

        $kategori = $this->request->getGet('kategori');
        $jenis = $this->request->getGet('jenis');
        $cabang = $this->request->getGet('cabang');
        $keyword = $this->request->getGet('q');
        $sort = $this->request->getGet('sort') ?? 'terbaru';
        $page = (int) ($this->request->getGet('page') ?? 1);
        $perPage = 9;

        $builder = $catalogModel
            ->select('catalog_items.*, jenis.name AS jenis_name')
            ->join('jenis', 'jenis.id = catalog_items.jenis_id', 'left')
            ->where('catalog_items.status', 'ACTIVE');

        if (!empty($kategori)) {
            $cat = $categoryModel->where('slug', $kategori)->first();
            if ($cat) {
                $builder = $builder->where('catalog_items.category_id', $cat['id']);
            }
        }

        if (!empty($jenis)) {
            $builder = $builder->where('jenis.slug', $jenis);
        }

        if (!empty($cabang)) {
            $builder = $builder->where(
                'EXISTS (SELECT 1 FROM resources r
                           JOIN branches b ON b.id = r.branch_id
                          WHERE r.catalog_item_id = catalog_items.id
                            AND r.deleted_at IS NULL
                            AND b.is_active = 1
                            AND b.branch_code = ' . $db->escape($cabang) . ')',
                null,
                false
            );
        }

        if (!empty($keyword)) {
            $builder = $builder->groupStart()
                ->like('catalog_items.name', $keyword)
                ->orLike('catalog_items.description', $keyword)
                ->groupEnd();
        }

        switch ($sort) {
            case 'harga_rendah':
                $builder = $builder->orderBy('catalog_items.base_price', 'ASC');
                break;
            case 'harga_tinggi':
                $builder = $builder->orderBy('catalog_items.base_price', 'DESC');
                break;
            default:
                $builder = $builder->orderBy('catalog_items.created_at', 'DESC');
        }

        $catalogItems = $builder->paginate($perPage, 'default', $page);
        $pager = $catalogModel->pager;

        $categories = $categoryModel
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $businessId = (int) (site_business()['id'] ?? 1);
        $jenisList = (new \App\Models\JenisModel())->forFilter($businessId);

        $branches = $db->table('branches')
            ->select('branch_code, branch_name')
            ->where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('branch_name', 'ASC')
            ->get()->getResultArray();

        $mediaModel = new ItemMediaModel();
        $itemIds = array_column($catalogItems, 'id');
        $imageMap = $mediaModel->getPrimaryImageMap($itemIds);

        return view('pub/katalog', [
            'catalogItems' => $catalogItems,
            'categories' => $categories,
            'jenisList' => $jenisList,
            'branches' => $branches,
            'pager' => $pager,
            'activeKategori' => $kategori,
            'activeJenis' => $jenis,
            'activeCabang' => $cabang,
            'keyword' => $keyword,
            'sort' => $sort,
            'imageMap' => $imageMap,
        ]);
    }

    public function detail(string $hash)
    {
        helper('business');
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

        // ================= PERBAIKAN: AMBIL DATA CABANG + JUMLAH UNIT =================
        // Gunakan SUM(capacity) karena untuk produk QUANTITY (misal Tenda & Kursi),
        // 1 baris resource bisa mewakili N unit (capacity = N).
        $itemBranches = db_connect()->table('resources r')
            ->select('b.id, b.branch_code AS code, b.branch_name AS name, b.address, b.phone, 
                      SUM(COALESCE(r.capacity, 1)) AS total_unit')
            ->join('branches b', 'b.id = r.branch_id')
            ->where('r.catalog_item_id', $id)
            ->where('r.deleted_at', null)
            ->where('b.is_active', 1)
            ->groupBy('r.branch_id')
            ->get()->getResultArray();

        $itemBranches = array_map(fn($r) => [
            'id' => (int) $r['id'],
            'code' => $r['code'],
            'name' => $r['name'],
            'address' => $r['address'] ?? '',
            'phone' => $r['phone'] ?? '',
            'total_unit' => (int) $r['total_unit'],
        ], $itemBranches);
        // =============================================================================

        // Kalender ketersediaan
        $availabilityModel = new \App\Models\AvailabilityModel();
        $branches = $this->getActiveBranches((int) ($item['business_id'] ?? 1));

        $calendarData = [];
        $cursor = new \DateTime('first day of this month');
        for ($i = 0; $i < 3; $i++) {
            $y = (int) $cursor->format('Y');
            $m = (int) $cursor->format('n');

            $monthAll = $availabilityModel->getMonthlyAvailability($id, null, $y, $m);

            $perBranch = [];
            foreach ($branches as $b) {
                $perBranch[$b['id']] = $availabilityModel->getMonthlyAvailability($id, (int) $b['id'], $y, $m);
            }

            foreach ($monthAll as $dateKey => $info) {
                $info['branches'] = [];
                foreach ($branches as $b) {
                    $bi = $perBranch[$b['id']][$dateKey] ?? null;
                    if (!$bi || (int) ($bi['total'] ?? 0) <= 0) {
                        continue;
                    }
                    $info['branches'][] = [
                        'id' => (int) $b['id'],
                        'code' => $b['code'],
                        'name' => $b['name'],
                        'available' => (int) $bi['available'],
                        'total' => (int) $bi['total'],
                    ];
                }
                $calendarData[$dateKey] = $info;
            }

            $cursor->modify('+1 month');
        }

        $bookingCount = $availabilityModel->getBookingCount($id);

        return view('pub/katalog_detail', [
            'item' => $item,
            'category' => $category,
            'related' => $related,
            'gallery' => $gallery,
            'relatedImages' => $relatedImages,
            'calendarData' => $calendarData,
            'bookingCount' => $bookingCount,
            'itemBranches' => $itemBranches,
            'businessName' => business_name($item['business_id'] ?? null),
        ]);
    }

    private function getActiveBranches(int $businessId = 1): array
    {
        $rows = db_connect()->table('branches')
            ->select('id, branch_code, branch_name, address, phone')
            ->where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return array_map(fn($r) => [
            'id' => (int) $r['id'],
            'code' => $r['branch_code'],
            'name' => $r['branch_name'],
            'address' => $r['address'] ?? '',
            'phone' => $r['phone'] ?? '',
        ], $rows);
    }

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
            'status' => $check['status'],
            'message' => $check['message'],
            'available_units' => $check['available_units'],
        ]);
    }

    public function kalenderTersedia(string $hash)
    {
        $catalogItemId = id_decode($hash);
        if ($catalogItemId === null) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Item tidak ditemukan.']);
        }

        $bulan = $this->request->getGet('bulan');
        if (empty($bulan) || !preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }
        [$year, $month] = array_map('intval', explode('-', $bulan));
        $branchId = $this->request->getGet('branch_id') ? (int) $this->request->getGet('branch_id') : null;

        $availabilityModel = new \App\Models\AvailabilityModel();
        $calendar = $availabilityModel->getMonthlyAvailability($catalogItemId, $branchId, $year, $month);

        return $this->response->setJSON([
            'bulan'    => $bulan,
            'calendar' => $calendar,
        ]);
    }
}