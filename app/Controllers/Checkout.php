<?php

namespace App\Controllers;

use App\Models\CatalogItemModel;
use App\Models\CustomerModel;
use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\BookingResourceAllocationModel;
use App\Models\ResourceModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class Checkout extends BaseController
{
    protected const CART_KEY = 'rental_cart';
    protected const CHECKOUT_KEY = 'checkout_data';

    /**
     * PUB-07: Form Data Penyewa
     */
    public function index()
    {
        $cart = session()->get(self::CART_KEY) ?? [];
        if (empty($cart)) {
            return redirect()->to('/cart')->with('error', 'Keranjang Anda masih kosong.');
        }

        // Kalau customer sudah login via OTP, prefill data
        $loggedCustomer = session()->get('customer');

        return view('pub/checkout_customer', [
            'loggedCustomer' => $loggedCustomer,
        ]);
    }

    public function simpanCustomer()
    {
        $rules = [
            'name' => 'required|min_length[3]',
            'phone' => 'required|min_length[9]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'phone' => $this->request->getPost('phone'),
            'email' => $this->request->getPost('email'),
            'notes' => $this->request->getPost('notes'),
            'is_guest' => session()->get('customer') ? false : true,
        ];

        session()->set(self::CHECKOUT_KEY, array_merge(
            session()->get(self::CHECKOUT_KEY) ?? [],
            ['customer' => $data]
        ));

        return redirect()->to('/checkout/fulfillment');
    }

    /**
     * PUB-08: Metode Pemenuhan & Lokasi
     */
    public function fulfillment()
    {
        $checkoutData = session()->get(self::CHECKOUT_KEY) ?? [];
        if (empty($checkoutData['customer'])) {
            return redirect()->to('/checkout')->with('error', 'Lengkapi data penyewa terlebih dahulu.');
        }

        return view('pub/checkout_fulfillment');
    }

    public function simpanFulfillment()
    {
        $rules = [
            'fulfillment_method' => 'required|in_list[SELF_PICKUP,DELIVERY,ONSITE,SERVICE]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'fulfillment_method' => $this->request->getPost('fulfillment_method'),
            'pickup_address' => $this->request->getPost('pickup_address'),
            'return_address' => $this->request->getPost('return_address'),
        ];

        session()->set(self::CHECKOUT_KEY, array_merge(
            session()->get(self::CHECKOUT_KEY) ?? [],
            ['fulfillment' => $data]
        ));

        return redirect()->to('/checkout/review');
    }

    /**
     * PUB-09: Review Booking & Harga
     */
    public function review()
    {
        $checkoutData = session()->get(self::CHECKOUT_KEY) ?? [];
        if (empty($checkoutData['fulfillment'])) {
            return redirect()->to('/checkout/fulfillment')->with('error', 'Lengkapi metode pemenuhan terlebih dahulu.');
        }

        $cart = session()->get(self::CART_KEY) ?? [];
        $catalogModel = new CatalogItemModel();
        $items = [];
        $subtotal = 0;
        $depositTotal = 0;

        foreach ($cart as $key => $line) {
            $product = $catalogModel->find($line['catalog_item_id']);
            if (!$product) {
                continue;
            }
            $lineTotal = $product['base_price'] * $line['qty'];
            $subtotal += $lineTotal;
            $depositTotal += ($product['deposit_required'] ? $product['deposit_amount'] * $line['qty'] : 0);

            $items[] = [
                'key' => $key,
                'product' => $product,
                'qty' => $line['qty'],
                'start_at' => $line['start_at'],
                'end_at' => $line['end_at'],
                'line_total' => $lineTotal,
            ];
        }

        $grandTotal = $subtotal + $depositTotal;

        return view('pub/checkout_review', [
            'items' => $items,
            'subtotal' => $subtotal,
            'depositTotal' => $depositTotal,
            'grandTotal' => $grandTotal,
            'customer' => $checkoutData['customer'],
            'fulfillment' => $checkoutData['fulfillment'],
        ]);
    }

    /**
     * Proses final: buat bookings + booking_items + allocations dalam 1 transaction.
     * -> PUB-10 Booking Berhasil
     */
    public function proses()
    {
        $checkoutData = session()->get(self::CHECKOUT_KEY) ?? [];
        $cart = session()->get(self::CART_KEY) ?? [];

        if (empty($checkoutData['customer']) || empty($checkoutData['fulfillment']) || empty($cart)) {
            return redirect()->to('/cart')->with('error', 'Data booking tidak lengkap. Silakan ulangi.');
        }

        if (!$this->request->is('post')) {
            return redirect()->to('/checkout/review');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $customerModel = new CustomerModel();
            $catalogModel = new CatalogItemModel();
            $resourceModel = new ResourceModel();
            $bookingModel = new BookingModel();
            $bookingItemModel = new BookingItemModel();
            $allocModel = new BookingResourceAllocationModel();

            $custData = $checkoutData['customer'];

            // Cari customer existing by phone, atau buat baru
            $existingCustomer = $customerModel->where('phone', $custData['phone'])->first();
            if ($existingCustomer) {
                $customerId = $existingCustomer['id'];
            } else {
                $customerId = $customerModel->insert([
                    'name' => $custData['name'],
                    'phone' => $custData['phone'],
                    'email' => $custData['email'] ?? null,
                    'notes' => $custData['notes'] ?? null,
                ]);
            }

            // Hitung ulang total & tentukan rentang waktu keseluruhan booking
            $earliestStart = null;
            $latestEnd = null;
            $subtotal = 0;
            $depositTotal = 0;
            $cartLines = [];

            foreach ($cart as $line) {
                $product = $catalogModel->find($line['catalog_item_id']);
                if (!$product) {
                    continue;
                }

                // Re-check availability sebelum commit (server-side, wajib)
                $conflict = $db->table('booking_resource_allocations bra')
                    ->join('booking_items bi', 'bi.id = bra.booking_item_id')
                    ->where('bi.catalog_item_id', $product['id'])
                    ->whereIn('bra.allocation_status', ['RESERVED', 'IN_USE'])
                    ->where('bra.start_at <', $line['end_at'])
                    ->where('bra.end_at >', $line['start_at'])
                    ->countAllResults();

                if ($conflict > 0) {
                    throw new DatabaseException("Item '{$product['name']}' sudah tidak tersedia pada jadwal yang dipilih.");
                }

                $lineTotal = $product['base_price'] * $line['qty'];
                $lineDeposit = $product['deposit_required'] ? $product['deposit_amount'] * $line['qty'] : 0;
                $subtotal += $lineTotal;
                $depositTotal += $lineDeposit;

                if ($earliestStart === null || $line['start_at'] < $earliestStart) {
                    $earliestStart = $line['start_at'];
                }
                if ($latestEnd === null || $line['end_at'] > $latestEnd) {
                    $latestEnd = $line['end_at'];
                }

                $cartLines[] = ['product' => $product, 'line' => $line, 'line_total' => $lineTotal];
            }

            if (empty($cartLines)) {
                throw new DatabaseException('Tidak ada item valid pada keranjang.');
            }

            $grandTotal = $subtotal + $depositTotal;
            $invoiceNo = $this->generateInvoiceNo();

            $bookingId = $bookingModel->insert([
                'invoice_no' => $invoiceNo,
                'business_id' => 1, // sesuaikan jika multi-business
                'customer_id' => $customerId,
                'booking_source' => 'WEB',
                'start_at' => $earliestStart,
                'end_at' => $latestEnd,
                'customer_name_snapshot' => $custData['name'],
                'customer_phone_snapshot' => $custData['phone'],
                'customer_email_snapshot' => $custData['email'] ?? null,
                'fulfillment_method' => $checkoutData['fulfillment']['fulfillment_method'],
                'pickup_address' => $checkoutData['fulfillment']['pickup_address'] ?? null,
                'return_address' => $checkoutData['fulfillment']['return_address'] ?? null,
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'subtotal' => $subtotal,
                'deposit_total' => $depositTotal,
                'grand_total' => $grandTotal,
                'paid_total' => 0,
                'balance_due' => $grandTotal,
                'customer_notes' => $custData['notes'] ?? null,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            ]);

            foreach ($cartLines as $cl) {
                $product = $cl['product'];
                $line = $cl['line'];

                $bookingItemId = $bookingItemModel->insert([
                    'booking_id' => $bookingId,
                    'catalog_item_id' => $product['id'],
                    'item_name_snapshot' => $product['name'],
                    'item_type_snapshot' => $product['item_type'],
                    'start_at' => $line['start_at'],
                    'end_at' => $line['end_at'],
                    'quantity' => $line['qty'],
                    'duration_value' => 1,
                    'duration_unit' => $product['pricing_unit'],
                    'unit_price' => $product['base_price'],
                    'subtotal' => $cl['line_total'],
                    'status' => 'RESERVED',
                ]);

                // Auto-allocate resource pertama yang available untuk item ini
                $resource = $resourceModel
                    ->where('catalog_item_id', $product['id'])
                    ->where('status', 'AVAILABLE')
                    ->first();

                if ($resource) {
                    $allocModel->insert([
                        'booking_item_id' => $bookingItemId,
                        'resource_id' => $resource['id'],
                        'allocated_qty' => $line['qty'],
                        'start_at' => $line['start_at'],
                        'end_at' => $line['end_at'],
                        'allocation_status' => 'RESERVED',
                    ]);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new DatabaseException('Gagal menyimpan booking. Silakan coba lagi.');
            }

            // Bersihkan session cart & checkout data
            session()->remove(self::CART_KEY);
            session()->remove(self::CHECKOUT_KEY);

            return redirect()->to('/checkout/berhasil/' . $invoiceNo);

        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->to('/checkout/review')->with('error', $e->getMessage());
        }
    }

    /**
     * PUB-10: Booking Berhasil
     */
    public function berhasil(string $invoiceNo)
    {
        $bookingModel = new BookingModel();
        $booking = $bookingModel->where('invoice_no', $invoiceNo)->first();

        if (!$booking) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('pub/checkout_berhasil', ['booking' => $booking]);
    }

    private function generateInvoiceNo(): string
    {
        // Format: INV-YYYYMMDD-XXXXXX (acak, sulit ditebak sesuai PUB-10 rule)
        return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }
}