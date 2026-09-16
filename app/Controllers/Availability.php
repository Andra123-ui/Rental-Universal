<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;
use App\Models\AvailabilityModel;
use App\Models\ItemMediaModel;

class Availability extends BaseController
{
    public function index()
{
    $catalogModel = new CatalogItemModel();
    $availabilityModel = new AvailabilityModel();
    $mediaModel = new ItemMediaModel();

    $itemId = (int) ($this->request->getGet('item_id') ?? 0);
    $items = $catalogModel->where('status', 'ACTIVE')->orderBy('name', 'ASC')->findAll();

    $selectedItem = $itemId ? $catalogModel->find($itemId) : null;
    $gallery = $selectedItem ? $mediaModel->getGallery($itemId) : [];

    // Cabang: opsional. Kalau belum ada resource yang di-assign ke branch tertentu
    // (atau tabel branches masih kosong), availability check tetap jalan tanpa filter cabang.
    $locations = $selectedItem ? $availabilityModel->getLocationsForItem($itemId) : [];
    $branches = [];
if (!empty($locations)) {
    $branchIds = array_column($locations, 'branch_id');
    $branches = \Config\Database::connect()->table('branches')
        ->select('id, branch_name')
        ->whereIn('id', $branchIds)
        ->where('is_active', 1)
        ->orderBy('branch_name', 'ASC')
        ->get()
        ->getResultArray();
}

    return view('pub/availability', [
        'items'        => $items,
        'selectedItem' => $selectedItem,
        'gallery'      => $gallery,
        'branches'     => $branches,
        'prefStartAt'  => $this->request->getGet('start_at'),
        'prefEndAt'    => $this->request->getGet('end_at'),
        'prefQty'      => (int) ($this->request->getGet('qty') ?? 1),
    ]);
}

    /**
     * AJAX: cek ketersediaan + estimasi harga + alternatif jadwal.
     */
    public function cek(int $catalogItemId)
{
    $startAt  = $this->request->getGet('start_at');
    $endAt    = $this->request->getGet('end_at');
    $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
    $qty      = max(1, (int) ($this->request->getGet('qty') ?? 1));

if ($branchId <= 0) {
    return $this->response->setJSON([
        'status'  => 'unavailable',
        'message' => 'Silakan pilih cabang terlebih dahulu.',
    ]);
}

    if (empty($startAt) || empty($endAt)) {
        return $this->response->setJSON([
            'status' => 'unavailable',
            'message' => 'Tanggal/jam tidak lengkap.',
        ]);
    }

    $catalogModel = new CatalogItemModel();
    $item = $catalogModel->find($catalogItemId);

    if (!$item) {
        return $this->response->setJSON([
            'status' => 'unavailable',
            'message' => 'Item tidak ditemukan.',
        ]);
    }

    // Validasi durasi min/max kalau diisi di catalog_items
    $durationHours = (strtotime($endAt) - strtotime($startAt)) / 3600;
    if (!empty($item['min_duration']) && $durationHours < $item['min_duration']) {
        return $this->response->setJSON([
            'status' => 'unavailable',
            'message' => "Durasi minimal untuk item ini adalah {$item['min_duration']} jam.",
        ]);
    }
    if (!empty($item['max_duration']) && $durationHours > $item['max_duration']) {
        return $this->response->setJSON([
            'status' => 'unavailable',
            'message' => "Durasi maksimal untuk item ini adalah {$item['max_duration']} jam.",
        ]);
    }

    $availabilityModel = new AvailabilityModel();

    // 1) Cek jadwal operasional
    $window = $availabilityModel->checkOperationalWindow($catalogItemId, $branchId, $startAt);
    if (!$window['ok']) {
        return $this->response->setJSON([
            'status' => 'unavailable',
            'message' => $window['message'],
        ]);
    }

    // 2) Terapkan buffer sebelum/sesudah dari catalog_items untuk pengecekan bentrok
    $bufferBefore = (int) ($item['buffer_before_minutes'] ?? 0);
    $bufferAfter  = (int) ($item['buffer_after_minutes'] ?? 0);
    $checkStart = date('Y-m-d H:i:s', strtotime($startAt) - $bufferBefore * 60);
    $checkEnd   = date('Y-m-d H:i:s', strtotime($endAt) + $bufferAfter * 60);

    // 3) Cek ketersediaan kapasitas/unit (pakai window ber-buffer)
    $result = $availabilityModel->checkAvailability($catalogItemId, $branchId, $checkStart, $checkEnd, $qty);

    if ($result['status'] !== 'available') {
        $result['alternatives'] = $availabilityModel->findAlternativeSlots($catalogItemId, $branchId, $startAt, $endAt, $qty);
    } elseif ($bufferBefore > 0 || $bufferAfter > 0) {
        $result['buffer_note'] = "Unit perlu waktu persiapan {$bufferBefore} menit sebelum dan {$bufferAfter} menit sesudah jadwal Anda.";
    }

    // Estimasi harga tetap pakai jam asli (tanpa buffer)
    $result['price_estimate'] = $availabilityModel->estimatePrice($item, $branchId, $startAt, $endAt, $qty);

    $result['branches'] = $availabilityModel->checkAvailabilityByBranch($catalogItemId, $checkStart, $checkEnd, $qty);

    return $this->response->setJSON($result);
}
}