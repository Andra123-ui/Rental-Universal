<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\CustomerModel;

class Checkout extends BaseController
{
    private function requireLogin()
    {
        if (! session()->get('customer_id')) {
            return redirect()->to('/account/login?redirect=' . urlencode('/checkout'));
        }
        return null;
    }

    public function index()
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        if (empty(session()->get('cart'))) {
            return redirect()->to('/keranjang')->with('error', 'Keranjang Anda masih kosong.');
        }

        return redirect()->to('/checkout/fulfillment');
    }

    public function fulfillment()
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $cart = session()->get('cart') ?? [];
        if (empty($cart)) {
            return redirect()->to('/keranjang');
        }

        return view('pub/checkout_fulfillment', ['cart' => $cart]);
    }

    public function simpanFulfillment()
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        session()->set('checkout_fulfillment', [
            'fulfillment_method' => $this->request->getPost('fulfillment_method'),
            'pickup_address'     => $this->request->getPost('pickup_address'),
            'return_address'     => $this->request->getPost('return_address'),
            'customer_notes'     => $this->request->getPost('customer_notes'),
        ]);

        return redirect()->to('/checkout/review');
    }

    public function review()
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $cart        = session()->get('cart') ?? [];
        $fulfillment = session()->get('checkout_fulfillment') ?? [];

        if (empty($cart) || empty($fulfillment)) {
            return redirect()->to('/checkout/fulfillment');
        }

        return view('pub/checkout_review', [
            'cart'        => $cart,
            'fulfillment' => $fulfillment,
            'subtotal'    => array_sum(array_column($cart, 'subtotal')),
        ]);
    }

    public function proses()
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $cart        = session()->get('cart') ?? [];
        $fulfillment = session()->get('checkout_fulfillment') ?? [];
        $customerId  = session()->get('customer_id');

        if (empty($cart)) {
            return redirect()->to('/keranjang');
        }

        $customerModel = new CustomerModel();
        $customer      = $customerModel->find($customerId);

        $subtotal = array_sum(array_column($cart, 'subtotal'));

        $db = \Config\Database::connect();
        $db->transStart();

        $bookingModel     = new BookingModel();
        $bookingItemModel = new BookingItemModel();

        $invoiceNo = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $bookingId = $bookingModel->insert([
            'invoice_no'              => $invoiceNo,
            'business_id'             => 1,
            'customer_id'             => $customerId,
            'booking_source'          => 'WEB',
            'start_at'                => min(array_column($cart, 'start_at')),
            'end_at'                  => max(array_column($cart, 'end_at')),
            'customer_name_snapshot'  => $customer['name'] ?? '-',
            'customer_phone_snapshot' => $customer['phone'] ?? '-',
            'customer_email_snapshot' => $customer['email'] ?? null,
            'fulfillment_method'      => $fulfillment['fulfillment_method'] ?? 'SELF_PICKUP',
            'pickup_address'          => $fulfillment['pickup_address'] ?? null,
            'return_address'          => $fulfillment['return_address'] ?? null,
            'status'                  => 'PENDING',
            'payment_status'          => 'UNPAID',
            'subtotal'                => $subtotal,
            'discount_total'          => 0,
            'charge_total'            => 0,
            'deposit_total'           => 0,
            'grand_total'             => $subtotal,
            'paid_total'              => 0,
            'balance_due'             => $subtotal,
            'customer_notes'          => $fulfillment['customer_notes'] ?? null,
        ], true);

        foreach ($cart as $line) {
            $bookingItemModel->insert([
                'booking_id'         => $bookingId,
                'catalog_item_id'    => $line['catalog_item_id'],
                'item_name_snapshot' => $line['name'],
                'item_type_snapshot' => 'GOODS',
                'start_at'           => $line['start_at'],
                'end_at'             => $line['end_at'],
                'quantity'           => $line['qty'],
                'duration_value'     => 1,
                'duration_unit'      => $line['pricing_unit'],
                'unit_price'         => $line['unit_price'],
                'discount_amount'    => 0,
                'subtotal'           => $line['subtotal'],
                'status'             => 'RESERVED',
            ]);
        }

        $db->transComplete();

        session()->remove('cart');
        session()->remove('checkout_fulfillment');

        return redirect()->to('/checkout/berhasil/' . $invoiceNo);
    }

    public function berhasil($invoiceNo)
    {
        $bookingModel = new BookingModel();
        $booking      = $bookingModel->where('invoice_no', $invoiceNo)->first();

        if (! $booking) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('pub/checkout_berhasil', ['booking' => $booking]);
    }
}