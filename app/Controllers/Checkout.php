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
    protected const PAYMENT_WINDOW_MINUTES = 3;
    protected const COOLDOWN_SECONDS = 120;
    protected $helpers = ['business'];

    /**
     * PUB-07: Form Data Penyewa
     */
    public function index()
    {
        $cart = session()->get(self::CART_KEY) ?? [];
        if (empty($cart)) {
            return redirect()->to('/cart')->with('error', 'Keranjang Anda masih kosong.');
        }

        $loggedCustomer = null;
        if (session()->get('customer_logged_in')) {
            $customerModel = new CustomerModel();
            $loggedCustomer = $customerModel->find(session()->get('customer_id'));
        }

        return view('pub/checkout_customer', [
            'loggedCustomer' => $loggedCustomer,
        ]);
    }

    public function simpanCustomer()
    {
        $rules = [
            'name' => 'required|min_length[3]',
            'phone' => 'required|min_length[9]',
            'agree_terms' => 'required',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', [
                'Nama, nomor HP, dan persetujuan syarat & ketentuan wajib diisi/dicentang.',
            ]);
        }

        $wait = $this->cooldownRemaining((string) $this->request->getPost('phone'));
        if ($wait > 0) {
            return redirect()->back()->withInput()->with('error', "Pembayaran sebelumnya gagal. Anda bisa booking lagi dalam {$wait} detik.");
        }

        $authMode = $this->request->getPost('auth_mode');

        $data = [
            'name' => $this->request->getPost('name'),
            'phone' => $this->normalizePhone((string) $this->request->getPost('phone')),
            'email' => $this->request->getPost('email'),
            'notes' => $this->request->getPost('notes'),
            'is_guest' => $authMode !== 'account',
            'terms_agreed_version' => $this->request->getPost('terms_version'),
            'terms_agreed_at' => date('Y-m-d H:i:s'),
            'terms_agreed_ip' => $this->request->getIPAddress(),
        ];

        session()->set(self::CHECKOUT_KEY, array_merge(
            session()->get(self::CHECKOUT_KEY) ?? [],
            ['customer' => $data]
        ));

        return redirect()->to('/checkout/fulfillment');
    }

    /**
     * PUB-08: Metode Pemenuhan & Lokasi (Per Item)
     */
    public function fulfillment()
    {
        $checkoutData = session()->get(self::CHECKOUT_KEY) ?? [];
        if (empty($checkoutData['customer'])) {
            return redirect()->to('/checkout')->with('error', 'Lengkapi data penyewa terlebih dahulu.');
        }

        $cart = session()->get(self::CART_KEY) ?? [];
        if (empty($cart)) {
            return redirect()->to('/cart')->with('error', 'Keranjang Anda kosong.');
        }

        $catalogModel = new CatalogItemModel();
        $availabilityModel = new \App\Models\AvailabilityModel();

        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $key => $line) {
            $product = $catalogModel->find($line['catalog_item_id']);
            if ($product) {
                // Hitung harga per item
                $price = $availabilityModel->estimatePrice(
                    $product,
                    $line['branch_id'] ?? null,
                    $line['start_at'],
                    $line['end_at'],
                    (int) $line['qty']
                );

                $lineTotal = $price['subtotal'];
                $subtotal += $lineTotal;

                $cartItems[] = [
                    'key' => $key,
                    'catalog_item_id' => $product['id'],
                    'name' => $product['name'],
                    'item_type' => $product['item_type'],
                    'qty' => $line['qty'],
                    'start_at' => $line['start_at'],
                    'end_at' => $line['end_at'],
                    'line_total' => $lineTotal, // Kirim nominal harga
                ];
            }
        }

        return view('pub/checkout_fulfillment', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'grandTotal' => $subtotal // Asumsi belum ada deposit di tahap ini
        ]);
    }
    public function simpanFulfillment()
    {
        $items = $this->request->getPost('items');

        $rules = [];
        if (!empty($items)) {
            foreach ($items as $index => $item) {
                $rules["items.{$index}.fulfillment_method"] = 'required|in_list[SELF_PICKUP,DELIVERY,ONSITE,SERVICE]';
            }
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $fulfillmentData = [];
        foreach ($items as $index => $item) {
            $fulfillmentData[$index] = [
                'cart_key' => $item['cart_key'] ?? $index,
                'catalog_item_id' => $item['catalog_item_id'],
                'fulfillment_method' => $item['fulfillment_method'],
                'pickup_address' => $item['pickup_address'] ?? null,
                'return_address' => $item['return_address'] ?? null,
            ];
        }

        session()->set(self::CHECKOUT_KEY, array_merge(
            session()->get(self::CHECKOUT_KEY) ?? [],
            ['fulfillment_items' => $fulfillmentData]
        ));

        return redirect()->to('/checkout/review');
    }

    /**
     * PUB-09: Review Booking & Harga (Per Item Fulfillment)
     */
    public function review()
    {
        $checkoutData = session()->get(self::CHECKOUT_KEY) ?? [];

        // Validasi: Cek apakah data fulfillment_items sudah ada
        if (empty($checkoutData['fulfillment_items'])) {
            return redirect()->to('/checkout/fulfillment')->with('error', 'Lengkapi metode pemenuhan terlebih dahulu.');
        }

        helper('business');

        $cart = session()->get(self::CART_KEY) ?? [];
        $catalogModel = new CatalogItemModel();
        $availabilityModel = new \App\Models\AvailabilityModel();
        $items = [];
        $subtotal = 0;
        $depositTotal = 0;

        foreach ($cart as $key => $line) {
            $product = $catalogModel->find($line['catalog_item_id']);
            if (!$product) {
                continue;
            }

            $branchId = !empty($line['branch_id']) ? (int) $line['branch_id'] : null;

            $price = $availabilityModel->estimatePrice(
                $product,
                $branchId,
                $line['start_at'],
                $line['end_at'],
                (int) $line['qty']
            );
            $lineTotal = $price['subtotal'];

            $subtotal += $lineTotal;
            $depositTotal += ($product['deposit_required'] ? $product['deposit_amount'] * $line['qty'] : 0);

            // Ambil data fulfillment spesifik untuk item ini
            $itemFulfillment = $checkoutData['fulfillment_items'][$key] ?? [
                'fulfillment_method' => 'SELF_PICKUP',
                'pickup_address' => null,
                'return_address' => null,
            ];

            $items[] = [
                'key' => $key,
                'product' => $product,
                'qty' => $line['qty'],
                'start_at' => $line['start_at'],
                'end_at' => $line['end_at'],
                'line_total' => $lineTotal,
                'business_name' => business_name($product['business_id'] ?? null),
                'branch_name' => branch_name($branchId),
                'fulfillment' => $itemFulfillment, // Kirim data fulfillment per item ke view
            ];
        }

        $grandTotal = $subtotal + $depositTotal;

        return view('pub/checkout_review', [
            'items' => $items,
            'subtotal' => $subtotal,
            'depositTotal' => $depositTotal,
            'grandTotal' => $grandTotal,
            'customer' => $checkoutData['customer'],
            // Tidak ada lagi 'fulfillment' global, karena sudah per item
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

        if (empty($checkoutData['customer']) || empty($checkoutData['fulfillment_items']) || empty($cart)) {
            return redirect()->to('/cart')->with('error', 'Data booking tidak lengkap. Silakan ulangi.');
        }

        if (!$this->request->is('post')) {
            return redirect()->to('/checkout/review');
        }

        $wait = $this->cooldownRemaining((string) $checkoutData['customer']['phone']);
        if ($wait > 0) {
            return redirect()->to('/checkout/review')
                ->with('error', "Pembayaran sebelumnya gagal. Anda bisa booking lagi dalam {$wait} detik.");
        }

        $db = \Config\Database::connect();
        $availabilityModel = new \App\Models\AvailabilityModel();
        $db->transStart();

        try {
            $availabilityModel->lockItems(array_column($cart, 'catalog_item_id'));

            $customerModel = new CustomerModel();
            $catalogModel = new CatalogItemModel();
            $bookingModel = new BookingModel();
            $bookingItemModel = new BookingItemModel();
            $allocModel = new BookingResourceAllocationModel();

            $custData = $checkoutData['customer'];

            if (session()->get('customer_logged_in') && session()->get('customer_id')) {
                $customerId = (int) session()->get('customer_id');
            } else {
                $phone = $this->normalizePhone($custData['phone']);
                $existingCustomer = $customerModel->where('phone', $phone)->first();
                if ($existingCustomer) {
                    $customerId = (int) $existingCustomer['id'];
                } else {
                    $customerId = $customerModel->insert([
                        'name' => $custData['name'],
                        'phone' => $phone,
                        'email' => $custData['email'] ?? null,
                        'notes' => $custData['notes'] ?? null,
                    ]);
                }
            }

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

                $availCheck = $availabilityModel->checkAvailability(
                    (int) $product['id'],
                    $line['branch_id'] ?? null,
                    $line['start_at'],
                    $line['end_at'],
                    (int) $line['qty']
                );

                if ($availCheck['status'] !== 'available') {
                    throw new DatabaseException("Item '{$product['name']}': " . ($availCheck['message'] ?? 'sudah tidak tersedia pada jadwal yang dipilih.'));
                }

                $price = $availabilityModel->estimatePrice(
                    $product,
                    $line['branch_id'] ?? null,
                    $line['start_at'],
                    $line['end_at'],
                    (int) $line['qty']
                );

                $lineTotal = $price['subtotal'];
                $lineDeposit = $product['deposit_required'] ? $product['deposit_amount'] * $line['qty'] : 0;
                $subtotal += $lineTotal;
                $depositTotal += $lineDeposit;

                if ($earliestStart === null || $line['start_at'] < $earliestStart) {
                    $earliestStart = $line['start_at'];
                }
                if ($latestEnd === null || $line['end_at'] > $latestEnd) {
                    $latestEnd = $line['end_at'];
                }

                $cartLines[] = [
                    'product' => $product,
                    'line' => $line,
                    'line_total' => $lineTotal,
                    'price' => $price,
                    'key' => $line['key'] ?? null // Simpan key untuk lookup fulfillment
                ];
            }

            if (empty($cartLines)) {
                throw new DatabaseException('Tidak ada item valid pada keranjang.');
            }

            $grandTotal = $subtotal + $depositTotal;
            $invoiceNo = $this->generateInvoiceNo();

            // Untuk tabel bookings, kita isi dengan metode dari item pertama sebagai fallback
            // (karena bookings hanya bisa menampung 1 metode global)
            $firstFulfillment = $checkoutData['fulfillment_items'][array_key_first($checkoutData['fulfillment_items'])] ?? null;

            $bookingId = $bookingModel->insert([
                'invoice_no' => $invoiceNo,
                'business_id' => (int) ($cartLines[0]['product']['business_id'] ?? 1),
                'customer_id' => $customerId,
                'booking_source' => 'WEB',
                'start_at' => $earliestStart,
                'end_at' => $latestEnd,
                'customer_name_snapshot' => $custData['name'],
                'customer_phone_snapshot' => $custData['phone'],
                'customer_email_snapshot' => $custData['email'] ?? null,
                // Fallback global (opsional, karena data asli ada di booking_items)
                'fulfillment_method' => $firstFulfillment['fulfillment_method'] ?? 'SELF_PICKUP',
                'pickup_address' => $firstFulfillment['pickup_address'] ?? null,
                'return_address' => $firstFulfillment['return_address'] ?? null,
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'subtotal' => $subtotal,
                'deposit_total' => $depositTotal,
                'grand_total' => $grandTotal,
                'paid_total' => 0,
                'balance_due' => $grandTotal,
                'customer_notes' => $custData['notes'] ?? null,
                'internal_notes' => 'Menyetujui Terms v' . ($custData['terms_agreed_version'] ?? '-')
                    . ' pada ' . ($custData['terms_agreed_at'] ?? '-')
                    . ' dari IP ' . ($custData['terms_agreed_ip'] ?? '-'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+' . self::PAYMENT_WINDOW_MINUTES . ' minutes')),
            ]);

            // ====== LOOPING PER ITEM (FULFILLMENT + ALLOCATION) ======
            foreach ($cartLines as $cl) {
                $product = $cl['product'];
                $line = $cl['line'];
                $cartKey = $cl['key'];

                // Ambil data fulfillment spesifik untuk item ini
                $itemFulfillment = $checkoutData['fulfillment_items'][$cartKey] ?? [
                    'fulfillment_method' => 'SELF_PICKUP',
                    'pickup_address' => null,
                    'return_address' => null,
                ];

                // Insert booking_items dengan data fulfillment per item
                $bookingItemId = $bookingItemModel->insert([
                    'booking_id' => $bookingId,
                    'catalog_item_id' => $product['id'],
                    'item_name_snapshot' => $product['name'],
                    'item_type_snapshot' => $product['item_type'],
                    'start_at' => $line['start_at'],
                    'end_at' => $line['end_at'],
                    'quantity' => $line['qty'],
                    'duration_value' => $cl['price']['units'],
                    'duration_unit' => $product['pricing_unit'],
                    'unit_price' => $product['base_price'],
                    'subtotal' => $cl['line_total'],
                    'status' => 'RESERVED',
                    // DATA FULFILLMENT PER ITEM:
                    'fulfillment_method' => $itemFulfillment['fulfillment_method'],
                    'pickup_address' => $itemFulfillment['pickup_address'],
                    'return_address' => $itemFulfillment['return_address'],
                ]);

                // Alokasi resource untuk item ini
                $plan = $availabilityModel->allocateResources(
                    (int) $product['id'],
                    $line['branch_id'] ?? null,
                    $line['start_at'],
                    $line['end_at'],
                    (int) $line['qty']
                );

                if (empty($plan)) {
                    throw new DatabaseException("Stok '{$product['name']}' baru saja habis untuk jadwal yang dipilih. Silakan pilih tanggal lain.");
                }

                foreach ($plan as $p) {
                    $allocModel->insert([
                        'booking_item_id' => $bookingItemId,
                        'resource_id' => $p['resource_id'],
                        'allocated_qty' => $p['qty'],
                        'start_at' => $line['start_at'],
                        'end_at' => $line['end_at'],
                        'allocation_status' => 'RESERVED',
                    ]);
                }
            }
            // ====== END LOOPING ======

            // Isi bookings.branch_id dari cabang resource yang benar-benar teralokasi
            $branchRows = $db->query(
                'SELECT DISTINCT r.branch_id
                   FROM booking_items bi
                   JOIN booking_resource_allocations a ON a.booking_item_id = bi.id
                   JOIN resources r ON r.id = a.resource_id
                  WHERE bi.booking_id = ? AND r.branch_id IS NOT NULL',
                [$bookingId]
            )->getResultArray();

            if (count($branchRows) === 1) {
                $db->table('bookings')->where('id', $bookingId)->update([
                    'branch_id' => (int) $branchRows[0]['branch_id'],
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new DatabaseException('Gagal menyimpan booking. Silakan coba lagi.');
            }

            session()->remove(self::CART_KEY);
            session()->remove(self::CHECKOUT_KEY);

            session()->set('last_invoice', $invoiceNo);
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
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        $bookingModel = new BookingModel();
        $booking = $bookingModel->where('invoice_no', $invoiceNo)->first();

        if (!$booking) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $secondsLeft = null;
        $freezeLeft = 0;
        if ($booking['status'] === 'PENDING' && $booking['payment_status'] === 'UNPAID' && !empty($booking['expires_at'])) {
            $secondsLeft = max(0, strtotime($booking['expires_at']) - time());
        } elseif ($booking['status'] === 'EXPIRED' && !empty($booking['expires_at'])) {
            $freezeLeft = max(0, strtotime($booking['expires_at']) + self::COOLDOWN_SECONDS - time());
        }

        return view('pub/checkout_berhasil', [
            'booking' => $booking,
            'secondsLeft' => $secondsLeft,
            'freezeLeft' => $freezeLeft,
        ]);
    }

    private function generateInvoiceNo(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    private function normalizePhone(string $phone): string
    {
        return (new \App\Libraries\OtpService())->normalizePhone($phone) ?? trim($phone);
    }

    private function cooldownRemaining(string $phone): int
    {
        $phone = $this->normalizePhone($phone);
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        $customer = (new CustomerModel())->where('phone', $phone)->first();
        if (!$customer) {
            return 0;
        }

        $last = \Config\Database::connect()->table('bookings')
            ->select('expires_at')
            ->where('customer_id', $customer['id'])
            ->where('status', 'EXPIRED')
            ->where('expires_at IS NOT NULL')
            ->orderBy('expires_at', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$last) {
            return 0;
        }

        return max(0, strtotime($last['expires_at']) + self::COOLDOWN_SECONDS - time());
    }
}