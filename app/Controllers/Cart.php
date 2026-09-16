<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;

class Cart extends BaseController
{
    protected const SESSION_KEY = 'rental_cart';

    public function index()
    {
        $cart = session()->get(self::SESSION_KEY) ?? [];
        $catalogModel = new CatalogItemModel();
        $items = [];
        $subtotal = 0;

        foreach ($cart as $key => $line) {
            $product = $catalogModel->find($line['catalog_item_id']);
            if (!$product) {
                continue;
            }

            $unit = $product['base_price'] * $line['qty'];
            $subtotal += $unit;

            $items[] = [
                'key' => $key,
                'product' => $product,
                'qty' => $line['qty'],
                'start_at' => $line['start_at'],
                'end_at' => $line['end_at'],
                'line_total' => $unit,
            ];
        }

        return view('pub/keranjang', [
            'items' => $items,
            'subtotal' => $subtotal,
            'unitLabels' => function_exists('unit_labels') ? unit_labels() : [],
        ]);
    }

    public function tambah()
{
    $catalogItemId = (int) $this->request->getPost('catalog_item_id');
    $startAt       = $this->request->getPost('start_at');
    $endAt         = $this->request->getPost('end_at');
    $branchId      = (int) $this->request->getPost('branch_id');
    $qty           = max(1, (int) $this->request->getPost('qty'));

    // 1. Validasi data wajib
    if (!$catalogItemId || !$startAt || !$endAt || !$branchId) {
        return redirect()->back()->with(
            'error',
            'Item, jadwal, jumlah, dan cabang wajib dipilih.'
        );
    }

    // 2. Pastikan waktu valid
    if (strtotime($startAt) >= strtotime($endAt)) {
        return redirect()->back()->with(
            'error',
            'Tanggal/jam selesai harus setelah tanggal/jam mulai.'
        );
    }

    // 3. Cari item
    $catalogModel = new CatalogItemModel();
    $product = $catalogModel->find($catalogItemId);

    if (!$product) {
        return redirect()->back()->with(
            'error',
            'Item tidak ditemukan.'
        );
    }

    // 4. CEK KETERSEDIAAN ULANG DI BACKEND
    $availabilityModel = new \App\Models\AvailabilityModel();

    $result = $availabilityModel->checkAvailability(
        $catalogItemId,
        $branchId,
        $startAt,
        $endAt,
        $qty
    );

    // 5. Hanya status AVAILABLE yang boleh masuk keranjang
    if ($result['status'] !== 'available') {
        return redirect()->back()->with(
            'error',
            $result['message'] ?? 'Item tidak tersedia pada jadwal tersebut.'
        );
    }

    // 6. Simpan ke cart
    $cart = session()->get(self::SESSION_KEY) ?? [];

    // Branch ikut dimasukkan ke key supaya booking
    // item yang sama pada cabang berbeda tidak tertukar.
    $key = $catalogItemId . '_' . $branchId . '_' . md5($startAt . $endAt);

    $cart[$key] = [
        'catalog_item_id' => $catalogItemId,
        'branch_id'       => $branchId,
        'start_at'        => $startAt,
        'end_at'          => $endAt,
        'qty'             => $qty,
    ];

    session()->set(self::SESSION_KEY, $cart);

    return redirect()
        ->to('/cart')
        ->with('success', 'Item berhasil ditambahkan ke keranjang.');
}

    public function update()
    {
        $key = $this->request->getPost('key');
        $qty = max(1, (int) $this->request->getPost('qty'));

        $cart = session()->get(self::SESSION_KEY) ?? [];
        if (isset($cart[$key])) {
            $cart[$key]['qty'] = $qty;
            session()->set(self::SESSION_KEY, $cart);
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