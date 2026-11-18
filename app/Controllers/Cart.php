<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;
use App\Models\AvailabilityModel;

class Cart extends BaseController
{
    protected const SESSION_KEY = 'rental_cart';

    /**
     * FUNGSI BARU: Sama seperti di Checkout, untuk memotong waktu.
     */
         private function normalizeDates(array $product, string $startAt, string $endAt): array
    {
        $pricingUnit = $product['pricing_unit'] ?? 'DAY';
        
        // Jika pakai DAY (Hari) atau FULL_DAY, paksa jadi 00:00 - 23:59
        if (in_array($pricingUnit, ['DAY', 'FULL_DAY'])) {
            $startDate = date('Y-m-d', strtotime($startAt));
            
            $newStart = $startDate . ' 00:00:00';
            $newEnd   = $startDate . ' 23:59:59';
            
            return [$newStart, $newEnd];
        }
        
        return [$startAt, $endAt];
    }

    public function index()
    {
        session()->remove('buy_now_cart');
        $cart = session()->get(self::SESSION_KEY) ?? [];
        $catalogModel = new CatalogItemModel();
        $availabilityModel = new AvailabilityModel();
        $items = [];
        $subtotal = 0;

        foreach ($cart as $key => $line) {
            $product = $catalogModel->find($line['catalog_item_id']);
            if (!$product) {
                continue;
            }

            // Normalisasi untuk tampilan & perhitungan harga
            [$startAt, $endAt] = $this->normalizeDates($product, $line['start_at'], $line['end_at']);

            $priceEstimate = $availabilityModel->estimatePrice(
                $product,
                $line['branch_id'] ?? null,
                $startAt,
                $endAt,
                (int) $line['qty']
            );

            $lineTotal = $priceEstimate['subtotal'];
            $subtotal += $lineTotal;

            $items[] = [
                'key' => $key,
                'product' => $product,
                'qty' => $line['qty'],
                'branch_id' => $line['branch_id'] ?? null,
                'branch_name' => $line['branch_name'] ?? null,
                'branch_address' => $line['branch_address'] ?? null,
                'start_at' => $startAt, // Gunakan yang sudah dinormalisasi
                'end_at' => $endAt,     // Gunakan yang sudah dinormalisasi
                'line_total' => $lineTotal,
                'price_detail' => $priceEstimate,
            ];
        }

        return view('pub/keranjang', [
            'items' => $items,
            'subtotal' => $subtotal,
        ]);
    }

    public function tambah()
    {
        session()->remove('buy_now_cart');
        $catalogItemId = (int) $this->request->getPost('catalog_item_id');
        $startAt = $this->request->getPost('start_at');
        $endAt = $this->request->getPost('end_at');
        $qty = max(1, (int) $this->request->getPost('qty'));
        $branchId = $this->request->getPost('branch_id') ? (int) $this->request->getPost('branch_id') : null;

        $catalogModel = new CatalogItemModel();
        $product = $catalogModel->find($catalogItemId);

        if (!$product) {
            return redirect()->back()->with('error', 'Item tidak ditemukan.');
        }

        if (empty($startAt) || empty($endAt)) {
            return redirect()->back()->with('error', 'Tanggal mulai dan selesai wajib diisi.');
        }

        if (!$branchId) {
            return redirect()->back()->with('error', 'Silakan pilih cabang terlebih dahulu.');
        }

        // NORMALISASI WAKTU DI SINI
        [$startAt, $endAt] = $this->normalizeDates($product, $startAt, $endAt);

        $branch = db_connect()->table('branches')
            ->select('branch_name, address, phone')
            ->where('id', $branchId)
            ->get(1)->getRowArray();

        if (!$branch) {
            return redirect()->back()->with('error', 'Cabang tidak ditemukan.');
        }

        $cart = session()->get(self::SESSION_KEY) ?? [];
        $key = $catalogItemId . '_' . md5($startAt . $endAt . $branchId);

        $existingQtyInCart = isset($cart[$key]) ? (int) $cart[$key]['qty'] : 0;
        $requestedTotalQty = $existingQtyInCart + $qty;

        $availabilityModel = new AvailabilityModel();
        $check = $availabilityModel->checkAvailability($catalogItemId, $branchId, $startAt, $endAt, $requestedTotalQty);

        if ($check['status'] === 'unavailable') {
            return redirect()->back()->with('error', $check['message'] ?? 'Item tidak tersedia untuk jadwal ini.');
        }

        if ($check['status'] === 'limited' && $requestedTotalQty > $check['available_units']) {
            $sisa = (int) floor($check['available_units']);
            return redirect()->back()->with(
                'error',
                "Stok tidak mencukupi. Tersisa {$sisa} unit untuk jadwal ini, sementara total permintaan Anda {$requestedTotalQty}."
            );
        }

        $cart[$key] = [
            'catalog_item_id' => $catalogItemId,
            'branch_id' => $branchId,
            'branch_name' => $branch['branch_name'],
            'branch_address' => $branch['address'],
            'branch_phone' => $branch['phone'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'qty' => $requestedTotalQty,
        ];

        session()->set(self::SESSION_KEY, $cart);

        if ($this->request->getPost('redirect') === 'checkout') {
            return redirect()->to('/checkout');
        }

        return redirect()->to('/cart')->with('success', 'Item berhasil ditambahkan ke keranjang.');
    }

    public function update()
    {
        $key = $this->request->getPost('key');
        $qty = max(1, (int) $this->request->getPost('qty'));

        $cart = session()->get(self::SESSION_KEY) ?? [];

        $csrfPayload = [
            'csrf_token_name' => csrf_token(),
            'csrf_hash' => csrf_hash(),
        ];

        if (!isset($cart[$key])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(array_merge(
                    ['success' => false, 'message' => 'Item tidak ditemukan di keranjang.'],
                    $csrfPayload
                ));
            }
            return redirect()->to('/cart')->with('error', 'Item tidak ditemukan di keranjang.');
        }

        $line = $cart[$key];

        $availabilityModel = new AvailabilityModel();
        $check = $availabilityModel->checkAvailability(
            (int) $line['catalog_item_id'],
            $line['branch_id'] ?? null,
            $line['start_at'],
            $line['end_at'],
            $qty
        );

        if ($check['status'] === 'unavailable' || ($check['status'] === 'limited' && $qty > $check['available_units'])) {
            $sisa = (int) floor($check['available_units']);
            $message = $check['status'] === 'unavailable'
                ? ($check['message'] ?? 'Item tidak tersedia untuk jadwal ini.')
                : "Stok tidak mencukupi. Maksimal tersedia {$sisa} unit untuk jadwal ini.";

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(array_merge([
                    'success' => false,
                    'message' => $message,
                    'max_available' => (int) floor($check['available_units']),
                ], $csrfPayload));
            }
            return redirect()->to('/cart')->with('error', $message);
        }

        $cart[$key]['qty'] = $qty;
        session()->set(self::SESSION_KEY, $cart);

        if ($this->request->isAJAX()) {
            $catalogModel = new CatalogItemModel();
            $subtotal = 0;
            $lineTotal = 0;

            foreach ($cart as $k => $l) {
                $product = $catalogModel->find($l['catalog_item_id']);
                if (!$product) {
                    continue;
                }

                [$startAt, $endAt] = $this->normalizeDates($product, $l['start_at'], $l['end_at']);

                $priceEstimate = $availabilityModel->estimatePrice(
                    $product,
                    $l['branch_id'] ?? null,
                    $startAt,
                    $endAt,
                    (int) $l['qty']
                );

                $t = $priceEstimate['subtotal'];
                $subtotal += $t;
                if ($k === $key) {
                    $lineTotal = $t;
                }
            }

            return $this->response->setJSON(array_merge([
                'success' => true,
                'line_total' => $lineTotal,
                'subtotal' => $subtotal,
            ], $csrfPayload));
        }

        return redirect()->to('/cart')->with('success', 'Jumlah item diperbarui.');
    }

    public function hapus(string $key)
    {
        $cart = session()->get(self::SESSION_KEY) ?? [];
        unset($cart[$key]);
        session()->set(self::SESSION_KEY, $cart);

        return redirect()->to('/cart')->with('success', 'Item dihapus dari keranjang.');
    }
}