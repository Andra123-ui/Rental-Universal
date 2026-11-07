<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use App\Libraries\BookingAvailability;

/**
 * BOOK-03 — Buat Booking Manual (walk-in / telepon / admin)
 * Customer TIDAK perlu punya akun login. Akun tidak dibuat di sini.
 */
class BookingCreateController extends BaseController
{
    private const SOURCES = ['WALKIN' => 'Walk-in', 'PHONE' => 'Telepon', 'ADMIN' => 'Admin / WhatsApp'];

    private const FULFILLMENT = [
        'SELF_PICKUP' => 'Ambil sendiri',
        'DELIVERY'    => 'Diantar',
        'ONSITE'      => 'Di lokasi customer',
        'SERVICE'     => 'Layanan / jasa',
    ];

    private const MAX_ITEMS = 20;

    private $conn;
    private ?int $branchId = null;
    private BookingAvailability $avail;

    // ===================================================================
    // GET /backoffice/bookings/baru
    // ===================================================================
    public function create()
    {
        $this->boot();

        $items = $this->conn->table('catalog_items')
            ->select('id, name, item_type, rental_mode, pricing_unit, base_price, deposit_required, deposit_amount')
            ->where('status', 'ACTIVE')->where('deleted_at', null)
            ->orderBy('name')->get()->getResultArray();

        $resources = $this->conn->table('resources')
            ->select('id, catalog_item_id, branch_id, resource_name, resource_code, capacity')
            ->where('deleted_at', null)->where('status', 'AVAILABLE')
            ->orderBy('resource_name')->get()->getResultArray();

        $branches = $this->conn->table('branches')->select('id, branch_name')
            ->where('is_active', 1)->orderBy('branch_name')->get()->getResultArray();

        $prefill = session()->getFlashdata('prefill');

        return view('backoffice/booking/create', [
            'title'       => 'Booking Baru',
            'nav'         => 'booking',
            'items'       => $items,
            'resources'   => $resources,
            'branches'    => $branches,
            'lockBranch'  => $this->branchId,
            'sources'     => self::SOURCES,
            'fulfillment' => self::FULFILLMENT,
            'fees'        => $this->fees(),
            'canOverride' => admin_can('booking.override_price'),
            'prefill'     => is_string($prefill) ? $prefill : 'null',
        ]);
    }

    // ===================================================================
    // GET /backoffice/bookings/customers?q=   (JSON)
    // ===================================================================
    public function customers()
    {
        $this->boot();

        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['results' => []]);
        }

        $b = $this->conn->table('customers c')
            ->select('c.id, c.name, c.phone, c.email, ca.status AS account_status')
            ->join('customer_accounts ca', 'ca.customer_id = c.id', 'left')
            ->groupStart()->like('c.name', $q)->orLike('c.email', $q);

        $digits = preg_replace('/\D+/', '', $q);
        if (strlen($digits) >= 3) {
            $b->orLike('c.phone', ltrim($digits, '0'));
        }

        $rows = $b->groupEnd()->orderBy('c.name')->limit(10)->get()->getResultArray();

        return $this->response->setJSON(['results' => $rows]);
    }

    // ===================================================================
    // GET /backoffice/bookings/check   (JSON, hanya membaca)
    // ===================================================================
    public function check()
    {
        $this->boot();

        $data   = $this->request->getGet();
        $branch = $this->resolveBranch($data['branch_id'] ?? null);
        $rows   = [];
        $pend   = [];
        $tot    = ['subtotal' => 0.0, 'discount' => 0.0, 'deposit' => 0.0];
        $allOk  = $branch !== null;

        $rawItems = is_array($data['items'] ?? null) ? array_values($data['items']) : [];

        if ($branch === null) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Pilih cabang terlebih dahulu.', 'rows' => []]);
        }

        foreach (array_slice($rawItems, 0, self::MAX_ITEMS) as $in) {
            $res = $this->evaluateRow((array) $in, $branch, false, $pend);
            if (!$res['ok']) {
                $allOk = false;
            } else {
                $tot['subtotal'] += $res['quote']['subtotal'];
                $tot['discount'] += $res['quote']['discount'];
                $tot['deposit']  += $res['quote']['deposit'];
                foreach ($res['alloc']['allocations'] as $a) {
                    $pend[] = ['resource_id' => $a['resource_id'], 'start' => $res['wstart'], 'end' => $res['wend'], 'qty' => $a['qty']];
                }
            }
            $rows[] = $this->rowJson($res);
        }

        if (!$rows) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Tambahkan minimal satu item.', 'rows' => []]);
        }

        $fee = $this->fulfillmentFee((string) ($data['fulfillment_method'] ?? 'SELF_PICKUP'));

        return $this->response->setJSON([
            'ok'     => $allOk,
            'rows'   => $rows,
            'totals' => [
                'subtotal' => $tot['subtotal'],
                'discount' => $tot['discount'],
                'charge'   => $fee,
                'deposit'  => $tot['deposit'],
                'grand'    => $tot['subtotal'] - $tot['discount'] + $fee + $tot['deposit'],
            ],
        ]);
    }

    // ===================================================================
    // POST /backoffice/bookings/simpan
    // ===================================================================
    public function store()
    {
        $this->boot();

        $post   = $this->request->getPost();
        $action = ($post['action'] ?? '') === 'CONFIRMED' ? 'CONFIRMED' : 'PENDING';

        try {
            $result = $this->save($post, $action);
        } catch (\DomainException $e) {
            return $this->failBack($post, $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Booking manual gagal: ' . $e->getMessage());
            return $this->failBack($post, 'Terjadi kesalahan saat menyimpan booking. Coba lagi.');
        }

        $redirect = redirect()->to(site_url('backoffice/bookings/' . id_encode($result['id'])))
            ->with('success', "Booking {$result['invoice']} dibuat ({$action}).");

        if ($result['invite']) {
            $redirect->with('invite', $result['invite']);
        }

        return $redirect;
    }

    // ===================================================================
    // Inti penyimpanan
    // ===================================================================

    private function save(array $post, string $action): array
    {
        // ---------- validasi non-DB ----------
        $branch = $this->resolveBranch($post['branch_id'] ?? null);
        if ($branch === null) {
            throw new \DomainException('Pilih cabang.');
        }

        $source = (string) ($post['booking_source'] ?? '');
        if (!isset(self::SOURCES[$source])) {
            throw new \DomainException('Pilih sumber booking.');
        }

        $method = (string) ($post['fulfillment_method'] ?? 'SELF_PICKUP');
        if (!isset(self::FULFILLMENT[$method])) {
            throw new \DomainException('Metode pengambilan tidak valid.');
        }
        $addrIn  = trim((string) ($post['pickup_address'] ?? ''));
        $addrOut = trim((string) ($post['return_address'] ?? ''));
        if (in_array($method, ['DELIVERY', 'ONSITE'], true) && $addrIn === '') {
            throw new \DomainException('Alamat wajib diisi untuk metode ' . self::FULFILLMENT[$method] . '.');
        }
        if ($addrOut === '' && $addrIn !== '') {
            $addrOut = $addrIn;
        }

        $rawItems = is_array($post['items'] ?? null) ? array_values($post['items']) : [];
        $rawItems = array_slice($rawItems, 0, self::MAX_ITEMS);
        if (!$rawItems) {
            throw new \DomainException('Tambahkan minimal satu item.');
        }

        $hasOverride = false;
        foreach ($rawItems as $in) {
            if (isset($in['price_override']) && trim((string) $in['price_override']) !== '') {
                $hasOverride = true;
            }
        }
        $priceReason = trim((string) ($post['price_reason'] ?? ''));
        if ($hasOverride) {
            if (!admin_can('booking.override_price')) {
                throw new \DomainException('Anda tidak berwenang mengubah harga manual.');
            }
            if (mb_strlen($priceReason) < 3) {
                throw new \DomainException('Alasan perubahan harga wajib diisi.');
            }
        }

        $customerMode = ($post['customer_mode'] ?? '') === 'new' ? 'new' : 'existing';
        $newName = $newPhone = $newEmail = null;
        if ($customerMode === 'new') {
            $newName = trim((string) ($post['new_name'] ?? ''));
            if (mb_strlen($newName) < 2 || mb_strlen($newName) > 150) {
                throw new \DomainException('Nama customer wajib diisi (2–150 karakter).');
            }
            $newPhone = self::normalizePhone((string) ($post['new_phone'] ?? ''));
            if ($newPhone === null) {
                throw new \DomainException('Nomor HP customer tidak valid.');
            }
            $newEmail = trim((string) ($post['new_email'] ?? ''));
            if ($newEmail !== '' && (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($newEmail) > 150)) {
                throw new \DomainException('Format email tidak valid.');
            }
            $newEmail = $newEmail === '' ? null : $newEmail;
        } elseif ((int) ($post['customer_id'] ?? 0) <= 0) {
            throw new \DomainException('Pilih customer atau pilih "Customer baru".');
        }

        $adminId = (int) session()->get('admin_id');
        $now     = date('Y-m-d H:i:s');

        // ---------- transaksi ----------
        $this->conn->transBegin();
        try {
            // Customer
            if ($customerMode === 'new') {
                $dup = $this->conn->table('customers')->select('name')->where('phone', $newPhone)->get()->getRowArray();
                if ($dup) {
                    throw new \DomainException("Nomor {$newPhone} sudah terdaftar atas nama {$dup['name']}. Pilih dari pencarian customer.");
                }
                $this->conn->table('customers')->insert([
                    'name'  => $newName,
                    'phone' => $newPhone,
                    'email' => $newEmail,
                    'notes' => 'Dibuat dari backoffice (booking manual)',
                ]);
                $customerId = (int) $this->conn->insertID();
            } else {
                $customerId = (int) $post['customer_id'];
            }

            $customer = $this->conn->table('customers')->where('id', $customerId)->get()->getRowArray();
            if (!$customer) {
                throw new \DomainException('Customer tidak ditemukan.');
            }
            $account = $this->conn->table('customer_accounts')->where('customer_id', $customerId)->get()->getRowArray();

            // Header booking (total diisi setelah semua item)
            $expiryMin = (int) $this->setting('booking.expiry_minutes', 1440);
            $status    = $action;
            $expires   = $status === 'PENDING' ? date('Y-m-d H:i:s', time() + max(1, $expiryMin) * 60) : null;

            $internal = 'Dibuat manual oleh ' . (string) session()->get('admin_name') . ' (' . $source . ') pada ' . date('d/m/Y H:i');
            $staffNote = trim((string) ($post['internal_notes'] ?? ''));
            if ($staffNote !== '') {
                $internal .= "\n" . mb_substr($staffNote, 0, 1000);
            }
            if ($hasOverride) {
                $internal .= "\nHarga diubah manual: " . mb_substr($priceReason, 0, 240);
            }

            $invoice = $this->newInvoiceNo();
            $this->conn->table('bookings')->insert([
                'invoice_no'              => $invoice,
                'business_id'             => 1,
                'branch_id'               => $branch,
                'customer_id'             => $customerId,
                'booking_source'          => $source,
                'start_at'                => $now,
                'end_at'                  => $now,
                'customer_name_snapshot'  => $customer['name'],
                'customer_phone_snapshot' => $customer['phone'],
                'customer_email_snapshot' => $customer['email'],
                'fulfillment_method'      => $method,
                'pickup_address'          => $addrIn,
                'return_address'          => $addrOut,
                'status'                  => $status,
                'payment_status'          => 'UNPAID',
                'subtotal'                => 0,
                'discount_total'          => 0,
                'charge_total'            => 0,
                'deposit_total'           => 0,
                'grand_total'             => 0,
                'paid_total'              => 0,
                'balance_due'             => 0,
                'customer_notes'          => mb_substr(trim((string) ($post['customer_notes'] ?? '')), 0, 1000),
                'internal_notes'          => $internal,
                'expires_at'              => $expires,
                'created_by_user_id'      => $adminId,
            ]);
            $bookingId = (int) $this->conn->insertID();

            // Item + alokasi (alokasi baris sebelumnya langsung terlihat oleh baris berikutnya)
            $sub = $disc = $dep = 0.0;
            $minStart = $maxEnd = null;
            $errors = [];

            foreach ($rawItems as $idx => $in) {
                $res = $this->evaluateRow((array) $in, $branch, true, []);
                if (!$res['ok']) {
                    $errors[] = 'Item ' . ($idx + 1) . ': ' . $res['message'];
                    continue;
                }

                $q = $res['quote'];
                $c = $res['catalog'];

                $note = null;
                if ($q['overridden']) {
                    $note = 'Harga manual: ' . mb_substr($priceReason, 0, 200);
                } elseif ($q['lines']) {
                    $parts = [];
                    foreach ($q['lines'] as $l) {
                        $parts[] = ($l['type'] === 'SURCHARGE' ? '+' : '-') . ' ' . $l['name'] . ' Rp ' . number_format($l['amount'], 0, ',', '.');
                    }
                    $note = implode('; ', $parts);
                }

                $this->conn->table('booking_items')->insert([
                    'booking_id'         => $bookingId,
                    'catalog_item_id'    => (int) $c['id'],
                    'item_name_snapshot' => $c['name'],
                    'item_type_snapshot' => $c['item_type'],
                    'start_at'           => $res['start'],
                    'end_at'             => $res['end'],
                    'quantity'           => $res['qty'],
                    'duration_value'     => $q['duration'],
                    'duration_unit'      => $q['unit'],
                    'unit_price'         => $q['unit_price'],
                    'discount_amount'    => $q['discount'],
                    'subtotal'           => $q['subtotal'],
                    'status'             => 'RESERVED',
                    'notes'              => $note,
                ]);
                $itemId = (int) $this->conn->insertID();

                foreach ($res['alloc']['allocations'] as $a) {
                    $this->conn->table('booking_resource_allocations')->insert([
                        'booking_item_id'      => $itemId,
                        'resource_id'          => $a['resource_id'],
                        'allocated_qty'        => $a['qty'],
                        'start_at'             => $res['start'],
                        'end_at'               => $res['end'],
                        'allocation_status'    => 'RESERVED',
                        'allocated_by_user_id' => $adminId,
                    ]);
                }

                $sub  += $q['subtotal'];
                $disc += $q['discount'];
                $dep  += $q['deposit'];
                $minStart = $minStart === null || $res['start'] < $minStart ? $res['start'] : $minStart;
                $maxEnd   = $maxEnd === null || $res['end'] > $maxEnd ? $res['end'] : $maxEnd;
            }

            if ($errors) {
                throw new \DomainException(implode(' | ', $errors));
            }

            // Biaya antar / di lokasi
            $fee = $this->fulfillmentFee($method);
            if ($fee > 0) {
                $this->conn->table('booking_charges')->insert([
                    'booking_id'         => $bookingId,
                    'booking_item_id'    => null,
                    'charge_type'        => 'DELIVERY',
                    'description'        => 'Biaya ' . strtolower(self::FULFILLMENT[$method]),
                    'quantity'           => 1,
                    'unit_price'         => $fee,
                    'amount'             => $fee,
                    'status'             => 'PENDING',
                    'created_by_user_id' => $adminId,
                ]);
            }

            // grand = subtotal - diskon + biaya + deposit (sama dengan recalcMoney di halaman detail)
            $grand = round($sub - $disc + $fee + $dep, 2);
            $this->conn->table('bookings')->where('id', $bookingId)->update([
                'start_at'       => $minStart,
                'end_at'         => $maxEnd,
                'subtotal'       => $sub,
                'discount_total' => $disc,
                'charge_total'   => $fee,
                'deposit_total'  => $dep,
                'grand_total'    => $grand,
                'balance_due'    => $grand,
            ]);

            $this->conn->table('booking_status_history')->insert([
                'booking_id'         => $bookingId,
                'old_status'         => null,
                'new_status'         => $status,
                'changed_by_user_id' => $adminId,
                'note'               => mb_substr('Dibuat manual (' . $source . ')', 0, 255),
            ]);

            $this->conn->table('activity_logs')->insert([
                'user_id'     => $adminId,
                'event_type'  => 'BOOKING_CREATED',
                'module'      => 'booking',
                'entity_type' => 'booking',
                'entity_id'   => $bookingId,
                'action'      => 'create',
                'description' => "Booking manual {$invoice} untuk {$customer['name']} (" . $source . ', ' . $status . ')',
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => mb_substr((string) $this->request->getUserAgent(), 0, 500),
            ]);

            $this->conn->transCommit();
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            throw $e;
        }

        // Info login (hanya dibuatkan teks; tidak ada akun yang dibuat dan tidak ada pesan terkirim otomatis)
        $invite = null;
        $wantInvite = !empty($post['invite']);
        $hasActive  = $account && $account['status'] === 'ACTIVE';
        if ($wantInvite && !$hasActive) {
            $biz  = function_exists('site_business') ? (array) site_business() : [];
            $name = $biz['business_name'] ?? 'Rental Universal';
            $text = "Halo {$customer['name']}, booking {$invoice} di {$name} sudah kami catat.\n"
                . "Untuk melihat riwayat dan pembayaran, masuk di " . site_url('account/login')
                . " memakai nomor WhatsApp ini ({$customer['phone']}). Kode OTP akan dikirim ke nomor tersebut.";
            $invite = [
                'text'   => $text,
                'wa_url' => 'https://wa.me/' . preg_replace('/\D+/', '', (string) $customer['phone']) . '?text=' . rawurlencode($text),
            ];
        }

        return ['id' => $bookingId, 'invoice' => $invoice, 'invite' => $invite];
    }

    // ===================================================================
    // Evaluasi satu baris item (dipakai check dan save)
    // ===================================================================

    private function evaluateRow(array $in, int $branch, bool $lock, array $pending): array
    {
        $fail = static fn (string $m) => ['ok' => false, 'message' => $m, 'catalog' => null, 'quote' => null,
            'alloc' => ['allocations' => [], 'reasons' => []], 'warnings' => [], 'start' => null, 'end' => null,
            'wstart' => null, 'wend' => null, 'qty' => 0];

        $itemId = (int) ($in['item_id'] ?? 0);
        $c = $itemId ? $this->conn->table('catalog_items')->where('id', $itemId)
            ->where('status', 'ACTIVE')->where('deleted_at', null)->get()->getRowArray() : null;
        if (!$c) {
            return $fail('Pilih item.');
        }

        $start = BookingAvailability::parseMoment($in['start'] ?? null);
        $end   = BookingAvailability::parseMoment($in['end'] ?? null);
        if (!$start || !$end) {
            return $fail($c['name'] . ': tanggal/waktu mulai dan selesai wajib diisi.');
        }
        if ($end <= $start) {
            return $fail($c['name'] . ': waktu selesai harus setelah waktu mulai.');
        }

        $qtyRaw = $in['qty'] ?? '1';
        if (!is_numeric($qtyRaw) || (float) $qtyRaw < 1 || (float) $qtyRaw > 1000 || floor((float) $qtyRaw) != (float) $qtyRaw) {
            return $fail($c['name'] . ': jumlah harus bilangan bulat 1–1000.');
        }
        $qty = (float) $qtyRaw;

        $override = null;
        if (isset($in['price_override']) && trim((string) $in['price_override']) !== '') {
            if (!admin_can('booking.override_price')) {
                return $fail($c['name'] . ': Anda tidak berwenang mengubah harga.');
            }
            if (!is_numeric($in['price_override']) || (float) $in['price_override'] < 0 || (float) $in['price_override'] > 999999999) {
                return $fail($c['name'] . ': harga manual tidak valid.');
            }
            $override = (float) $in['price_override'];
        }

        try {
            $quote = $this->avail->quote($c, $start, $end, $qty, $branch, $override);
        } catch (\DomainException $e) {
            return $fail($c['name'] . ': ' . $e->getMessage());
        }

        $resId = (int) ($in['resource_id'] ?? 0);
        $alloc = $this->avail->allocate($c, $start, $end, $qty, $branch, $resId ?: null, $lock, null, $pending);

        $bb = (int) $c['buffer_before_minutes'];
        $ba = (int) $c['buffer_after_minutes'];

        return [
            'ok'       => $alloc['ok'],
            'message'  => $alloc['ok'] ? '' : $c['name'] . ': ' . $alloc['message'],
            'catalog'  => $c,
            'quote'    => $quote,
            'alloc'    => $alloc,
            'warnings' => $this->avail->warnings($c, $start, $end),
            'start'    => $start,
            'end'      => $end,
            'wstart'   => date('Y-m-d H:i:s', strtotime($start) - $bb * 60),
            'wend'     => date('Y-m-d H:i:s', strtotime($end) + $ba * 60),
            'qty'      => $qty,
        ];
    }

    private function rowJson(array $r): array
    {
        if ($r['catalog'] === null) {
            return ['ok' => false, 'message' => $r['message']];
        }
        $q = $r['quote'];
        return [
            'ok'          => $r['ok'],
            'message'     => $r['message'],
            'reasons'     => array_slice($r['alloc']['reasons'] ?? [], 0, 4),
            'duration'    => $q['duration'],
            'unit'        => $q['unit'],
            'unit_price'  => $q['unit_price'],
            'subtotal'    => $q['subtotal'],
            'discount'    => $q['discount'],
            'net'         => $q['net'],
            'deposit'     => $q['deposit'],
            'lines'       => $q['lines'],
            'overridden'  => $q['overridden'],
            'allocations' => array_map(static fn ($a) => ['name' => $a['name'], 'qty' => $a['qty']], $r['alloc']['allocations']),
            'warnings'    => $r['warnings'],
        ];
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function boot(): void
    {
        // Jika id_encode/id_decode dimuat lewat helper lain, tambahkan helper itu di sini
        // (samakan dengan BookingController::boot()).
        helper('backoffice');

        if (!function_exists('id_encode')) {
            throw new \RuntimeException('Helper id_encode belum dimuat di BookingCreateController::boot().');
        }

        $this->conn     = db_connect();
        $this->avail    = new BookingAvailability($this->conn);
        $this->branchId = session()->get('admin_branch_id') ? (int) session()->get('admin_branch_id') : null;
    }

    /** Cabang yang dipakai: user terikat cabang -> paksa cabangnya; selain itu harus dipilih dan valid. */
    private function resolveBranch($posted): ?int
    {
        if ($this->branchId) {
            return $this->branchId;
        }
        $id = (int) $posted;
        if ($id <= 0) {
            return null;
        }
        $ok = $this->conn->table('branches')->where('id', $id)->where('is_active', 1)->countAllResults();
        return $ok ? $id : null;
    }

    private function failBack(array $post, string $message)
    {
        unset($post['csrf_test_name']);
        // buang token csrf apa pun namanya
        foreach (array_keys($post) as $k) {
            if (stripos((string) $k, 'csrf') !== false) {
                unset($post[$k]);
            }
        }

        return redirect()->to(site_url('backoffice/bookings/baru'))
            ->with('error', $message)
            ->with('prefill', json_encode($post, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    private function setting(string $key, $default)
    {
        $v = $this->conn->table('system_settings')->where('setting_key', $key)
            ->orderBy('branch_id', 'ASC')->get()->getRow('setting_value');
        return ($v === null || $v === '') ? $default : $v;
    }

    private function fees(): array
    {
        return [
            'SELF_PICKUP' => 0.0,
            'DELIVERY'    => (float) $this->setting('delivery.fee_flat', 0),
            'ONSITE'      => (float) $this->setting('delivery.fee_onsite', 0),
            'SERVICE'     => (float) $this->setting('delivery.fee_service', 0),
        ];
    }

    private function fulfillmentFee(string $method): float
    {
        return $this->fees()[$method] ?? 0.0;
    }

    private function newInvoiceNo(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $no = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            if (!$this->conn->table('bookings')->where('invoice_no', $no)->countAllResults()) {
                return $no;
            }
        }
        throw new \RuntimeException('Gagal membuat nomor invoice unik.');
    }

    /** 0812…, 62812…, +62812…, 812… -> +62812… ; null jika tidak masuk akal. */
    public static function normalizePhone(string $raw): ?string
    {
        $d = preg_replace('/\D+/', '', $raw);
        if ($d === '' || $d === null) {
            return null;
        }
        if (strpos($d, '62') === 0) {
            $d = substr($d, 2);
        } elseif (strpos($d, '0') === 0) {
            $d = substr($d, 1);
        }
        if ($d === '' || $d[0] !== '8' || strlen($d) < 8 || strlen($d) > 13) {
            return null;
        }
        return '+62' . $d;
    }
}