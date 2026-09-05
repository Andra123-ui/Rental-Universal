<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;

class Cart extends BaseController
{
    public function index()
    {
        return view('pub/keranjang', [
            'cart'       => session()->get('cart') ?? [],
            'unitLabels' => unit_labels(),
        ]);
    }

    public function tambah()
    {
        $catalogModel = new CatalogItemModel();
        $itemId  = $this->request->getPost('catalog_item_id');
        $qty     = max(1, (int) $this->request->getPost('qty'));
        $startAt = $this->request->getPost('start_at');
        $endAt   = $this->request->getPost('end_at');

        $item = $catalogModel->find($itemId);
        if (! $item) {
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }

        $cart = session()->get('cart') ?? [];
        $cart[$itemId] = [
            'catalog_item_id' => $item['id'],
            'name'            => $item['name'],
            'unit_price'      => $item['base_price'],
            'pricing_unit'    => $item['pricing_unit'],
            'qty'             => $qty,
            'start_at'        => $startAt,
            'end_at'          => $endAt,
            'subtotal'        => $item['base_price'] * $qty,
        ];

        session()->set('cart', $cart);

        return redirect()->to('/keranjang')->with('success', 'Ditambahkan ke keranjang.');
    }

    public function update()
    {
        $cart = session()->get('cart') ?? [];
        $qtys = $this->request->getPost('qty') ?? [];

        foreach ($qtys as $itemId => $qty) {
            if (isset($cart[$itemId])) {
                $qty = max(1, (int) $qty);
                $cart[$itemId]['qty']      = $qty;
                $cart[$itemId]['subtotal'] = $cart[$itemId]['unit_price'] * $qty;
            }
        }

        session()->set('cart', $cart);

        return redirect()->to('/keranjang')->with('success', 'Keranjang diperbarui.');
    }

    public function hapus($itemId)
    {
        $cart = session()->get('cart') ?? [];
        unset($cart[$itemId]);
        session()->set('cart', $cart);

        return redirect()->to('/keranjang')->with('success', 'Item dihapus dari keranjang.');
    }
}