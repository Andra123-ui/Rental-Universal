<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use App\Libraries\BookingMoney;

/**
 * BOOK-02 — Detail Booking
 * Confirm & Cancel tetap ada di BookingController (dipakai juga oleh daftar booking).
 */
class BookingDetailController extends BaseController
{
    private const CLOSED = ['CANCELLED', 'EXPIRED'];

    private const PAY_METHODS = [
        'CASH' => 'Tunai', 'TRANSFER' => 'Transfer bank', 'QRIS' => 'QRIS',
        'EWALLET' => 'E-wallet', 'CARD' => 'Kartu', 'OTHER' => 'Lainnya',
    ];
    private const PAY_TYPES = [
        'RENTAL' => 'Sewa', 'DEPOSIT' => 'Deposit', 'CHARGE' => 'Biaya tambahan',
    ];
    private const CHARGE_TYPES = [
        'EXTRA' => 'Tambahan', 'LATE_FEE' => 'Denda keterlambatan', 'DELIVERY' => 'Antar/jemput',
        'DAMAGE' => 'Kerusakan', 'OTHER' => 'Lainnya',
    ];
    private const ASSIGN_TYPES = [
        'DELIVERY' => 'Antar', 'PICKUP' => 'Jemput', 'DRIVER' => 'Driver',
        'TECHNICIAN' => 'Teknisi', 'OTHER' => 'Lainnya',
    ];
    private const CONDITIONS = [
        'GOOD' => 'Baik', 'MINOR_DAMAGE' => 'Lecet ringan', 'DAMAGED' => 'Rusak', 'MISSING' => 'Hilang / tidak lengkap',
    ];

    private $conn;
    private ?int $branchId = null;

    // ===================================================================
    // GET /backoffice/bookings/{id}
    // ===================================================================
    public function show(string $seg)
    {
        $this->boot();

        $bk = $this->loadBooking($seg);
        if (!$bk) {
            return redirect()->to(site_url('backoffice/bookings'))->with('error', 'Booking tidak ditemukan.');
        }

        $db     = $this->conn;
        $id     = (int) $bk['id'];
        $manage = admin_can('booking.manage');

        $customer = $db->table('customers')->where('id', $bk['customer_id'])->get()->getRowArray();
        $account  = $db->table('customer_accounts')->where('customer_id', $bk['customer_id'])->get()->getRowArray();
        $doc      = $db->table('customer_documents')->where('customer_id', $bk['customer_id'])
            ->orderBy('id', 'DESC')->get()->getRowArray();
        $branch   = $bk['branch_id']
            ? $db->table('branches')->where('id', $bk['branch_id'])->get()->getRowArray() : null;
        $createdBy = $bk['created_by_user_id']
            ? $db->table('users')->where('id', $bk['created_by_user_id'])->get()->getRow('name') : null;

        $items = $db->table('booking_items')->where('booking_id', $id)->orderBy('id')->get()->getResultArray();

        $allocs = $db->table('booking_resource_allocations a')
            ->select('a.*, i.item_name_snapshot, r.resource_name, r.resource_code')
            ->join('booking_items i', 'i.id = a.booking_item_id')
            ->join('resources r', 'r.id = a.resource_id', 'left')
            ->where('i.booking_id', $id)->orderBy('a.id')->get()->getResultArray();
        $activeAlloc = 0;
        foreach ($allocs as $a) {
            if (!in_array($a['allocation_status'], ['CANCELLED', 'RELEASED'], true)) {
                $activeAlloc++;
            }
        }

        $payments = $db->table('payments p')
            ->select('p.*, u.name AS received_by')
            ->join('users u', 'u.id = p.received_by_user_id', 'left')
            ->where('p.booking_id', $id)->orderBy('p.id', 'DESC')->get()->getResultArray();
        foreach ($payments as &$p) {
            $p['enc'] = id_encode((int) $p['id']);
        }
        unset($p);

        $charges = $db->table('booking_charges')->where('booking_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        foreach ($charges as &$c) {
            $c['enc'] = id_encode((int) $c['id']);
        }
        unset($c);

        $deposit = $db->table('security_deposits')->where('booking_id', $id)->get()->getRowArray();

        $assignments = $db->table('assignments')->where('booking_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        foreach ($assignments as &$s) {
            $s['enc'] = id_encode((int) $s['id']);
        }
        unset($s);

        $handovers = $db->table('handovers h')
            ->select('h.*, u.name AS handled_by')
            ->join('users u', 'u.id = h.handled_by_user_id', 'left')
            ->where('h.booking_id', $id)->orderBy('h.id')->get()->getResultArray();
        $hasPickup = $hasReturn = false;
        foreach ($handovers as &$h) {
            $hasPickup = $hasPickup || $h['handover_type'] === 'PICKUP';
            $hasReturn = $hasReturn || $h['handover_type'] === 'RETURN';
            $h['details'] = $db->table('handover_details d')
                ->select('d.*, i.item_name_snapshot, r.resource_name')
                ->join('booking_items i', 'i.id = d.booking_item_id')
                ->join('resources r', 'r.id = d.resource_id', 'left')
                ->where('d.handover_id', $h['id'])->orderBy('d.id')->get()->getResultArray();
        }
        unset($h);

        $documents = $db->table('booking_documents')->where('booking_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        $damages   = $db->table('damages')->where('booking_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();

        // ---- Riwayat gabungan: perubahan status + log aktivitas booking ----
        $timeline = [[
            'at' => $bk['created_at'], 'title' => 'Booking dibuat',
            'sub' => 'Sumber ' . $bk['booking_source'], 'who' => $createdBy ?: 'Customer',
        ]];
        $hist = $db->table('booking_status_history s')
            ->select('s.*, u.name AS who')
            ->join('users u', 'u.id = s.changed_by_user_id', 'left')
            ->where('s.booking_id', $id)->get()->getResultArray();
        foreach ($hist as $r) {
            $timeline[] = [
                'at' => $r['changed_at'],
                'title' => ($r['old_status'] === $r['new_status'])
                    ? 'Perubahan booking (status tetap ' . $r['new_status'] . ')'
                    : 'Status ' . ($r['old_status'] ?: '-') . ' → ' . $r['new_status'],
                'sub' => (string) $r['note'], 'who' => $r['who'] ?: 'Sistem',
            ];
        }
        $logs = $db->table('activity_logs a')
            ->select('a.event_type, a.description, a.created_at, u.name AS who')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where('a.entity_type', 'booking')->where('a.entity_id', $id)
            ->whereNotIn('a.event_type', ['BOOKING_CONFIRMED', 'BOOKING_CANCELLED'])
            ->get()->getResultArray();
        foreach ($logs as $r) {
            $timeline[] = [
                'at' => $r['created_at'], 'title' => (string) $r['description'],
                'sub' => '', 'who' => $r['who'] ?: 'Sistem',
            ];
        }
        usort($timeline, static fn ($a, $b) => strcmp($b['at'], $a['at']));

        $users = $db->table('users u')
            ->select('u.id, u.name, r.name AS role_name')
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.status', 'ACTIVE')->where('u.deleted_at', null)
            ->orderBy('u.name')->get()->getResultArray();

        $st   = $bk['status'];
        $open = !in_array($st, self::CLOSED, true);
        $can  = [
            'manage'  => $manage,
            'confirm' => $manage && $st === 'PENDING' && $activeAlloc > 0,
            'cancel'  => $manage && in_array($st, ['PENDING', 'CONFIRMED'], true),
            'pay'     => $manage && $open && (float) $bk['balance_due'] > 0,
            'refund'  => $this->may('payment.refund') && $st !== 'EXPIRED' && (float) $bk['paid_total'] > 0,
            'verify'  => $this->may('payment.verify'),
            'charge'  => $manage && $open,
            'assign'  => $manage && $open && $st !== 'COMPLETED',
            'pickup'  => $manage && $st === 'CONFIRMED' && !$hasPickup,
            'return'  => $manage && $st === 'ONGOING' && !$hasReturn,
        ];

        $paidRows = 0;
        foreach ($payments as $p) {
            if ($p['status'] === 'PAID') {
                $paidRows++;
            }
        }

        return view('backoffice/booking/show', [
            'title'         => $bk['invoice_no'],
            'nav'           => 'booking',
            'seg'           => $seg,
            'bk'            => $bk,
            'customer'      => $customer,
            'account'       => $account,
            'doc'           => $doc,
            'branch'        => $branch,
            'createdBy'     => $createdBy,
            'items'         => $items,
            'allocs'        => $allocs,
            'activeAlloc'   => $activeAlloc,
            'payments'      => $payments,
            'legacyPaid'    => $paidRows === 0 && (float) $bk['paid_total'] > 0,
            'charges'       => $charges,
            'deposit'       => $deposit,
            'assignments'   => $assignments,
            'handovers'     => $handovers,
            'documents'     => $documents,
            'damages'       => $damages,
            'timeline'      => $timeline,
            'users'         => $users,
            'can'           => $can,
            'payMethods'    => self::PAY_METHODS,
            'payTypes'      => self::PAY_TYPES,
            'chargeTypes'   => self::CHARGE_TYPES,
            'assignTypes'   => self::ASSIGN_TYPES,
            'conditions'    => self::CONDITIONS,
            'rescheduleUrl' => ($this->may('booking.update') && in_array($st, ['PENDING', 'CONFIRMED'], true))
                ? site_url('backoffice/bookings/' . $seg . '/ubah') : null,
            'invoiceUrl'    => site_url('backoffice/bookings/' . $seg . '/invoice'),
        ]);
    }

    // ===================================================================
    // GET /backoffice/bookings/{id}/invoice  (halaman cetak)
    // ===================================================================
    public function invoice(string $seg)
    {
        $this->boot();

        $bk = $this->loadBooking($seg);
        if (!$bk) {
            return redirect()->to(site_url('backoffice/bookings'))->with('error', 'Booking tidak ditemukan.');
        }

        $id = (int) $bk['id'];
        $db = $this->conn;

        return view('backoffice/booking/invoice', [
            'bk'       => $bk,
            'business' => function_exists('site_business') ? (array) site_business() : [],
            'branch'   => $bk['branch_id'] ? $db->table('branches')->where('id', $bk['branch_id'])->get()->getRowArray() : null,
            'items'    => $db->table('booking_items')->where('booking_id', $id)->where('status !=', 'CANCELLED')->orderBy('id')->get()->getResultArray(),
            'charges'  => $db->table('booking_charges')->where('booking_id', $id)->whereNotIn('status', BookingMoney::CHARGE_OFF)->orderBy('id')->get()->getResultArray(),
        ]);
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/payment   (direction IN = terima, OUT = refund)
    // ===================================================================
    public function payment(string $seg)
    {
        $this->boot();
        $id = $this->resolveId($seg);
        if (!$id) {
            return $this->gone();
        }

        return $this->tx($seg, 'payments', function () use ($id) {
            $bk = $this->lock($id);

            $dir    = $this->request->getPost('direction') === 'OUT' ? 'OUT' : 'IN';
            $amount = $this->num($this->request->getPost('amount'));
            $method = (string) $this->request->getPost('method');

            if ($amount === null || $amount <= 0 || $amount > 999999999999) {
                throw new \DomainException('Nominal pembayaran tidak valid.');
            }
            if (!isset(self::PAY_METHODS[$method])) {
                throw new \DomainException('Pilih metode pembayaran.');
            }

            if ($dir === 'OUT' && !$this->may('payment.refund')) {
                throw new \DomainException('Anda tidak punya izin membuat refund (payment.refund).');
            }
            if ($dir === 'IN') {
                if (in_array($bk['status'], self::CLOSED, true)) {
                    throw new \DomainException('Booking sudah ' . $bk['status'] . ', tidak bisa menerima pembayaran.');
                }
                if ($amount > (float) $bk['balance_due'] + 0.009) {
                    throw new \DomainException('Nominal melebihi sisa tagihan (Rp ' . number_format((float) $bk['balance_due'], 0, ',', '.') . ').');
                }
                $type = (string) $this->request->getPost('payment_type');
                $type = isset(self::PAY_TYPES[$type]) ? $type : 'RENTAL';
            } else {
                if ($bk['status'] === 'EXPIRED') {
                    throw new \DomainException('Booking EXPIRED tidak bisa direfund.');
                }
                if ($amount > (float) $bk['paid_total'] + 0.009) {
                    throw new \DomainException('Refund melebihi total yang sudah dibayar (Rp ' . number_format((float) $bk['paid_total'], 0, ',', '.') . ').');
                }
                $type = 'REFUND';
            }

            // Pastikan saldo lama (bookings.paid_total tanpa riwayat) tidak hilang saat dihitung ulang
            $this->money()->ensureLegacy($bk);

            $this->conn->table('payments')->insert([
                'booking_id'          => $id,
                'payment_no'          => $this->paymentNo(),
                'payment_type'        => $type,
                'direction'           => $dir,
                'method'              => $method,
                'provider'            => null,
                'reference_no'        => $this->str('reference_no', 120),
                'amount'              => $amount,
                'status'              => 'PAID',
                'paid_at'             => $this->parseDt($this->request->getPost('paid_at')) ?? date('Y-m-d H:i:s'),
                'received_by_user_id' => $this->uid(),
                'notes'               => $this->str('notes', 255),
            ]);

            // Staff sudah mencatat pembayaran: hentikan batas waktu hold supaya tidak ikut kedaluwarsa
            if ($dir === 'IN' && $bk['status'] === 'PENDING') {
                $this->conn->table('bookings')->where('id', $id)->update(['expires_at' => null]);
            }

            $this->money()->recalc($id);
            $label = $dir === 'IN' ? 'Pembayaran' : 'Refund';
            $this->logActivity('BOOKING_PAYMENT', $id, $dir === 'IN' ? 'payment_in' : 'payment_out',
                "{$label} Rp " . number_format($amount, 0, ',', '.') . " ({$method}) untuk {$bk['invoice_no']}");

            return "{$label} Rp " . number_format($amount, 0, ',', '.') . ' dicatat.';
        });
    }

    // POST /backoffice/bookings/{id}/payment/{pid}/verify
    public function verifyPayment(string $seg, string $pseg)
    {
        return $this->decidePayment($seg, $pseg, true);
    }

    // POST /backoffice/bookings/{id}/payment/{pid}/reject
    public function rejectPayment(string $seg, string $pseg)
    {
        return $this->decidePayment($seg, $pseg, false);
    }

    private function decidePayment(string $seg, string $pseg, bool $approve)
    {
        $this->boot();
        $id  = $this->resolveId($seg);
        $pid = id_decode($pseg);
        if (!$id || !$pid) {
            return $this->gone();
        }

        return $this->tx($seg, 'payments', function () use ($id, $pid, $approve) {
            $bk = $this->lock($id);

            if (!$this->may('payment.verify')) {
                throw new \DomainException('Anda tidak punya izin memverifikasi pembayaran (payment.verify).');
            }

            $pay = $this->conn->table('payments')->where('id', (int) $pid)->where('booking_id', $id)
                ->where('status', 'PENDING')->get()->getRowArray();
            if (!$pay) {
                throw new \DomainException('Pembayaran tidak ditemukan atau sudah diproses.');
            }

            if ($approve) {
                $this->money()->ensureLegacy($bk);
                $this->conn->table('payments')->where('id', $pay['id'])->update([
                    'status'              => 'PAID',
                    'paid_at'             => $pay['paid_at'] ?: date('Y-m-d H:i:s'),
                    'received_by_user_id' => $this->uid(),
                ]);
                $this->money()->recalc($id);
                $this->logActivity('BOOKING_PAYMENT', $id, 'payment_verify', "Verifikasi pembayaran {$pay['payment_no']} ({$bk['invoice_no']})");
                return "Pembayaran {$pay['payment_no']} diverifikasi.";
            }

            $this->conn->table('payments')->where('id', $pay['id'])->update(['status' => 'FAILED']);
            $this->logActivity('BOOKING_PAYMENT', $id, 'payment_reject', "Tolak pembayaran {$pay['payment_no']} ({$bk['invoice_no']})");
            return "Pembayaran {$pay['payment_no']} ditolak.";
        });
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/charge
    // ===================================================================
    public function charge(string $seg)
    {
        $this->boot();
        $id = $this->resolveId($seg);
        if (!$id) {
            return $this->gone();
        }

        return $this->tx($seg, 'charges', function () use ($id) {
            $bk = $this->lock($id);

            if (in_array($bk['status'], self::CLOSED, true)) {
                throw new \DomainException('Booking sudah ' . $bk['status'] . ', tidak bisa menambah biaya.');
            }

            $type = (string) $this->request->getPost('charge_type');
            $desc = $this->str('description', 255);
            $qty  = $this->num($this->request->getPost('quantity'));
            $unit = $this->num($this->request->getPost('unit_price'));

            if (!isset(self::CHARGE_TYPES[$type])) {
                throw new \DomainException('Pilih jenis biaya.');
            }
            if ($desc === null) {
                throw new \DomainException('Keterangan biaya wajib diisi.');
            }
            if ($qty === null || $qty <= 0 || $qty > 100000 || $unit === null || $unit <= 0 || $unit > 999999999999) {
                throw new \DomainException('Jumlah dan harga satuan harus lebih dari 0.');
            }

            $amount = round($qty * $unit, 2);

            $this->conn->table('booking_charges')->insert([
                'booking_id'         => $id,
                'booking_item_id'    => null,
                'charge_type'        => $type,
                'description'        => $desc,
                'quantity'           => $qty,
                'unit_price'         => $unit,
                'amount'             => $amount,
                'status'             => 'PENDING',
                'created_by_user_id' => $this->uid(),
            ]);

            $this->money()->recalc($id);
            $this->logActivity('BOOKING_CHARGE', $id, 'charge_add',
                "Tambah biaya {$type} Rp " . number_format($amount, 0, ',', '.') . " pada {$bk['invoice_no']}: {$desc}");

            return 'Biaya Rp ' . number_format($amount, 0, ',', '.') . ' ditambahkan.';
        });
    }

    // POST /backoffice/bookings/{id}/charge/{cid}/void
    public function voidCharge(string $seg, string $cseg)
    {
        $this->boot();
        $id  = $this->resolveId($seg);
        $cid = id_decode($cseg);
        if (!$id || !$cid) {
            return $this->gone();
        }

        return $this->tx($seg, 'charges', function () use ($id, $cid) {
            $bk = $this->lock($id);

            $ch = $this->conn->table('booking_charges')->where('id', (int) $cid)->where('booking_id', $id)
                ->whereNotIn('status', BookingMoney::CHARGE_OFF)->get()->getRowArray();
            if (!$ch) {
                throw new \DomainException('Biaya tidak ditemukan atau sudah dibatalkan.');
            }

            // Tidak dihapus: hanya ditandai VOID supaya histori tetap ada
            $this->conn->table('booking_charges')->where('id', $ch['id'])->update(['status' => 'WAIVED']);
            $this->money()->recalc($id);
            $this->logActivity('BOOKING_CHARGE', $id, 'charge_void',
                "Batalkan biaya Rp " . number_format((float) $ch['amount'], 0, ',', '.') . " pada {$bk['invoice_no']}: {$ch['description']}");

            return 'Biaya dibatalkan.';
        });
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/assign
    // ===================================================================
    public function assign(string $seg)
    {
        $this->boot();
        $id = $this->resolveId($seg);
        if (!$id) {
            return $this->gone();
        }

        return $this->tx($seg, 'assignment', function () use ($id) {
            $bk = $this->lock($id);

            if (in_array($bk['status'], ['CANCELLED', 'EXPIRED', 'COMPLETED'], true)) {
                throw new \DomainException('Booking sudah ' . $bk['status'] . ', tidak bisa menugaskan.');
            }

            $type   = (string) $this->request->getPost('assignment_type');
            $userId = (int) $this->request->getPost('user_id');
            $start  = $this->parseDt($this->request->getPost('start_at'));
            $end    = $this->parseDt($this->request->getPost('end_at'));

            if (!isset(self::ASSIGN_TYPES[$type])) {
                throw new \DomainException('Pilih jenis tugas.');
            }
            if ($start && $end && $end < $start) {
                throw new \DomainException('Waktu selesai tidak boleh sebelum waktu mulai.');
            }

            $user = $this->conn->table('users')->select('id, name')->where('id', $userId)
                ->where('status', 'ACTIVE')->where('deleted_at', null)->get()->getRowArray();
            if (!$user) {
                throw new \DomainException('Petugas tidak ditemukan atau tidak aktif.');
            }

            $this->conn->table('assignments')->insert([
                'booking_id'             => $id,
                'booking_item_id'        => null,
                'assignment_type'        => $type,
                'user_id'                => (int) $user['id'],
                'resource_id'            => null,
                'assignee_name_snapshot' => $user['name'],
                'start_at'               => $start,
                'end_at'                 => $end,
                'status'                 => 'PLANNED',
                'notes'                  => $this->str('notes', 1000),
                'assigned_by_user_id'    => $this->uid(),
            ]);

            $this->logActivity('BOOKING_ASSIGN', $id, 'assign', "Tugaskan {$user['name']} ({$type}) pada {$bk['invoice_no']}");

            return "{$user['name']} ditugaskan.";
        });
    }

    // POST /backoffice/bookings/{id}/assign/{aid}/cancel
    public function cancelAssign(string $seg, string $aseg)
    {
        $this->boot();
        $id  = $this->resolveId($seg);
        $aid = id_decode($aseg);
        if (!$id || !$aid) {
            return $this->gone();
        }

        return $this->tx($seg, 'assignment', function () use ($id, $aid) {
            $bk = $this->lock($id);

            $this->conn->table('assignments')->where('id', (int) $aid)->where('booking_id', $id)
                ->whereIn('status', ['PLANNED', 'IN_PROGRESS'])->update(['status' => 'CANCELLED']);

            if ($this->conn->affectedRows() === 0) {
                throw new \DomainException('Penugasan tidak ditemukan atau sudah selesai.');
            }

            $this->logActivity('BOOKING_ASSIGN', $id, 'assign_cancel', "Batalkan penugasan pada {$bk['invoice_no']}");

            return 'Penugasan dibatalkan.';
        });
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/handover
    // ===================================================================
    public function handover(string $seg)
    {
        $this->boot();
        $id = $this->resolveId($seg);
        if (!$id) {
            return $this->gone();
        }

        return $this->tx($seg, 'handover', function () use ($id) {
            $bk = $this->lock($id);

            $type = (string) $this->request->getPost('handover_type');
            if (!in_array($type, ['PICKUP', 'RETURN'], true)) {
                throw new \DomainException('Jenis handover tidak valid.');
            }

            $need = $type === 'PICKUP' ? 'CONFIRMED' : 'ONGOING';
            if ($bk['status'] !== $need) {
                throw new \DomainException(
                    $type === 'PICKUP'
                        ? 'Serah terima ke customer hanya bisa saat booking CONFIRMED (sekarang ' . $bk['status'] . ').'
                        : 'Pengembalian hanya bisa saat booking ONGOING (sekarang ' . $bk['status'] . ').'
                );
            }

            $exists = $this->conn->table('handovers')->where('booking_id', $id)->where('handover_type', $type)->countAllResults();
            if ($exists > 0) {
                throw new \DomainException("Handover {$type} untuk booking ini sudah dicatat.");
            }

            $items = $this->conn->table('booking_items')->where('booking_id', $id)
                ->where('status !=', 'CANCELLED')->orderBy('id')->get()->getResultArray();
            if (!$items) {
                throw new \DomainException('Booking tidak punya item aktif.');
            }

            // item -> unit yang dialokasikan
            $resByItem = [];
            $al = $this->conn->table('booking_resource_allocations a')
                ->select('a.booking_item_id, a.resource_id')
                ->join('booking_items i', 'i.id = a.booking_item_id')
                ->where('i.booking_id', $id)
                ->whereNotIn('a.allocation_status', ['CANCELLED', 'RELEASED'])
                ->orderBy('a.id')->get()->getResultArray();
            foreach ($al as $r) {
                $resByItem[(int) $r['booking_item_id']] = $resByItem[(int) $r['booking_item_id']] ?? (int) $r['resource_id'];
            }

            $actual = $this->parseDt($this->request->getPost('actual_at')) ?? date('Y-m-d H:i:s');

            $this->conn->table('handovers')->insert([
                'booking_id'             => $id,
                'handover_type'          => $type,
                'scheduled_at'           => null,
                'actual_at'              => $actual,
                'handled_by_user_id'     => $this->uid(),
                'customer_receiver_name' => $this->str('receiver_name', 150),
                'location'               => $this->str('location', 255),
                'signature_path'         => null,
                'notes'                  => $this->str('notes', 2000),
            ]);
            $hid = (int) $this->conn->insertID();

            $rows       = (array) $this->request->getPost('items');
            $badCount   = 0;
            foreach ($items as $it) {
                $in   = (array) ($rows[$it['id']] ?? []);
                $cond = (string) ($in['condition'] ?? 'GOOD');
                $cond = isset(self::CONDITIONS[$cond]) ? $cond : 'GOOD';
                $note = isset($in['notes']) ? mb_substr(trim((string) $in['notes']), 0, 1000) : '';
                $meter = $this->num($in['meter'] ?? null);

                $this->conn->table('handover_details')->insert([
                    'handover_id'     => $hid,
                    'booking_item_id' => (int) $it['id'],
                    'resource_id'     => $resByItem[(int) $it['id']] ?? null,
                    'quantity'        => $it['quantity'],
                    'condition_status' => $cond,
                    'condition_notes' => $note !== '' ? $note : null,
                    'meter_value'     => $meter,
                    'accessory_notes' => isset($in['accessory']) && trim((string) $in['accessory']) !== ''
                        ? mb_substr(trim((string) $in['accessory']), 0, 1000) : null,
                    'photo_path'      => null,
                ]);
                $did = (int) $this->conn->insertID();

                // Kondisi tidak baik saat pengembalian -> otomatis jadi laporan kerusakan
                if ($type === 'RETURN' && $cond !== 'GOOD') {
                    $badCount++;
                    $this->conn->table('damages')->insert([
                        'booking_id'         => $id,
                        'handover_detail_id' => $did,
                        'resource_id'        => $resByItem[(int) $it['id']] ?? null,
                        'damage_type'        => $cond,
                        'description'        => $note !== '' ? $note : (self::CONDITIONS[$cond] . ' — ' . $it['item_name_snapshot']),
                        'estimated_cost'     => 0,
                        'charged_amount'     => 0,
                        'status'             => 'REPORTED',
                        'created_by_user_id' => $this->uid(),
                    ]);
                }
            }

            $new = $type === 'PICKUP' ? 'ONGOING' : 'COMPLETED';
            $this->conn->table('bookings')->where('id', $id)->where('status', $bk['status'])->update(['status' => $new]);
            if ($this->conn->affectedRows() === 0) {
                throw new \DomainException('Status booking berubah saat diproses. Muat ulang halaman.');
            }

            $this->history($id, $bk['status'], $new, $type === 'PICKUP' ? 'Serah terima ke customer' : 'Pengembalian dari customer');
            $this->logActivity('BOOKING_HANDOVER', $id, 'handover', "Handover {$type} {$bk['invoice_no']}" . ($badCount ? " ({$badCount} item bermasalah)" : ''));

            return ($type === 'PICKUP' ? 'Serah terima dicatat, booking ONGOING.' : 'Pengembalian dicatat, booking COMPLETED.')
                . ($badCount ? " {$badCount} laporan kerusakan dibuat; tambahkan biayanya di tab Biaya jika perlu." : '');
        });
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/note  (catatan internal, hanya menambah)
    // ===================================================================
    public function note(string $seg)
    {
        $this->boot();
        $id = $this->resolveId($seg);
        if (!$id) {
            return $this->gone();
        }

        return $this->tx($seg, 'items', function () use ($id) {
            $bk   = $this->lock($id);
            $text = $this->str('note', 1000);
            if ($text === null) {
                throw new \DomainException('Catatan tidak boleh kosong.');
            }

            $line = '[' . date('d/m/Y H:i') . ' · ' . (string) session()->get('admin_name') . '] ' . $text;
            $new  = trim(((string) $bk['internal_notes']) . "\n" . $line);

            $this->conn->table('bookings')->where('id', $id)->update(['internal_notes' => $new]);
            $this->logActivity('BOOKING_NOTE', $id, 'note', "Catatan internal pada {$bk['invoice_no']}");

            return 'Catatan disimpan.';
        });
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function boot(): void
    {
        // Jika id_encode/id_decode dimuat lewat helper lain, tambahkan helper itu di sini
        // (samakan dengan BookingController::boot()).
        helper('backoffice');

        if (!function_exists('id_encode') || !function_exists('id_decode')) {
            throw new \RuntimeException('Helper id_encode/id_decode belum dimuat di BookingDetailController::boot().');
        }

        $this->conn     = db_connect();
        $this->branchId = session()->get('admin_branch_id') ? (int) session()->get('admin_branch_id') : null;
    }

    /** Izin khusus, atau booking.manage sebagai izin umum. */
    private function may(string $code): bool
    {
        return admin_can($code) || admin_can('booking.manage');
    }

    private function money(): BookingMoney
    {
        return new BookingMoney($this->conn);
    }

    private function loadBooking(string $seg): ?array
    {
        $id = id_decode($seg);
        if (!$id) {
            return null;
        }

        $q = $this->conn->table('bookings')->where('id', (int) $id);
        if ($this->branchId) {
            $q->where('branch_id', $this->branchId);
        }

        return $q->get()->getRowArray() ?: null;
    }

    private function resolveId(string $seg): ?int
    {
        $bk = $this->loadBooking($seg);
        return $bk ? (int) $bk['id'] : null;
    }

    private function lock(int $id): array
    {
        $row = $this->conn->query('SELECT * FROM bookings WHERE id = ? FOR UPDATE', [$id])->getRowArray();
        if (!$row) {
            throw new \DomainException('Booking tidak ditemukan.');
        }
        return $row;
    }

    /** Jalankan $fn dalam transaksi. DomainException = pesan untuk staff. */
    private function tx(string $seg, string $tab, callable $fn)
    {
        $this->conn->transBegin();
        try {
            $msg = $fn();
            $this->conn->transCommit();
            return $this->done($seg, $tab, 'success', (string) $msg);
        } catch (\DomainException $e) {
            $this->conn->transRollback();
            return $this->done($seg, $tab, 'error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            log_message('error', 'BookingDetail gagal: ' . $e->getMessage());
            return $this->done($seg, $tab, 'error', 'Terjadi kesalahan saat menyimpan. Coba lagi.');
        }
    }

    private function done(string $seg, string $tab, string $type, string $msg)
    {
        return redirect()->to(site_url('backoffice/bookings/' . $seg) . '?tab=' . $tab)->with($type, $msg);
    }

    private function gone()
    {
        return redirect()->to(site_url('backoffice/bookings'))->with('error', 'Booking tidak ditemukan.');
    }

    private function uid(): int
    {
        return (int) session()->get('admin_id');
    }

    private function str(string $field, int $max): ?string
    {
        $v = $this->request->getPost($field);
        $v = is_string($v) ? trim($v) : '';
        return $v === '' ? null : mb_substr($v, 0, $max);
    }

    private function num($v): ?float
    {
        if (!is_string($v) && !is_int($v) && !is_float($v)) {
            return null;
        }
        $v = trim((string) $v);
        return is_numeric($v) ? (float) $v : null;
    }

    private function parseDt($v): ?string
    {
        if (!is_string($v) || $v === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d\TH:i', $v);
        return ($d && $d->format('Y-m-d\TH:i') === $v) ? $d->format('Y-m-d H:i:00') : null;
    }

    private function paymentNo(): string
    {
        return 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    private function history(int $bookingId, ?string $old, string $new, string $note): void
    {
        $this->conn->table('booking_status_history')->insert([
            'booking_id'         => $bookingId,
            'old_status'         => $old,
            'new_status'         => $new,
            'changed_by_user_id' => $this->uid(),
            'note'               => mb_substr($note, 0, 255),
        ]);
    }

    private function logActivity(string $event, int $bookingId, string $action, string $desc): void
    {
        $this->conn->table('activity_logs')->insert([
            'user_id'     => $this->uid(),
            'event_type'  => $event,
            'module'      => 'booking',
            'entity_type' => 'booking',
            'entity_id'   => $bookingId,
            'action'      => $action,
            'description' => mb_substr($desc, 0, 1000),
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => mb_substr((string) $this->request->getUserAgent(), 0, 500),
        ]);
    }
}