<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;

class BookingController extends BaseController
{
    private const PER_PAGE   = 20;
    private const EXPORT_MAX = 5000;

    private const DEF_STATUS  = ['PENDING', 'CONFIRMED', 'ONGOING', 'COMPLETED', 'CANCELLED', 'EXPIRED'];
    private const DEF_PAYMENT = ['UNPAID', 'PARTIAL', 'PAID', 'REFUNDED'];
    private const DEF_SOURCE  = ['WEB', 'WALKIN', 'PHONE', 'ADMIN'];

    private $conn;
    private ?int $branchId = null;
    private array $routeKeys = [];

    // ===================================================================
    // GET /backoffice/bookings
    // ===================================================================
    public function index()
    {
        $this->boot();

        $f       = $this->filters();
        $builder = $this->listQuery($f);

        $total = $builder->countAllResults(false);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int) ($this->request->getGet('page') ?? 1)), $pages);

        $rows = $builder
            ->orderBy('b.created_at', 'DESC')->orderBy('b.id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get()->getResultArray();

        $hasDetail = $this->has('backoffice/bookings/(:segment)');
        foreach ($rows as &$r) {
            $r['enc']        = id_encode((int) $r['id']);
            $r['detail_url'] = $hasDetail ? site_url('backoffice/bookings/' . $r['enc']) : null;
        }
        unset($r);

        return view('backoffice/booking/index', [
            'title'      => 'Daftar Booking',
            'nav'        => 'booking',
            'rows'       => $rows,
            'filters'    => $f,
            'total'      => $total,
            'page'       => $page,
            'pages'      => $pages,
            'perPage'    => self::PER_PAGE,
            'statuses'   => $this->options('status', self::DEF_STATUS),
            'payments'   => $this->options('payment_status', self::DEF_PAYMENT),
            'sources'    => $this->options('booking_source', self::DEF_SOURCE),
            'branches'   => $this->branchId ? [] : $this->conn->table('branches')
                                ->select('id, branch_name')->where('is_active', 1)
                                ->orderBy('branch_name')->get()->getResultArray(),
            'lockBranch' => $this->branchId !== null,
            'canManage'  => admin_can('booking.manage'),
            'createUrl'  => $this->has('backoffice/bookings/baru') ? site_url('backoffice/bookings/baru') : null,
        ]);
    }

    // ===================================================================
    // GET /backoffice/bookings/export  (CSV, mengikuti filter aktif)
    // ===================================================================
    public function export()
    {
        $this->boot();

        $rows = $this->listQuery($this->filters())
            ->orderBy('b.created_at', 'DESC')->orderBy('b.id', 'DESC')
            ->limit(self::EXPORT_MAX)
            ->get()->getResultArray();

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8

        fputcsv($fh, [
            'Invoice', 'Dibuat', 'Customer', 'Telepon', 'Status Akun', 'Sumber',
            'Mulai', 'Selesai', 'Status Booking', 'Status Bayar',
            'Grand Total', 'Terbayar', 'Sisa', 'Cabang',
        ], ',', '"', '\\');

        foreach ($rows as $r) {
            fputcsv($fh, array_map([$this, 'csvCell'], [
                $r['invoice_no'], $r['created_at'], $r['customer_name_snapshot'],
                $r['customer_phone_snapshot'], $r['account_status'] ?: 'TANPA AKUN',
                $r['booking_source'], $r['start_at'], $r['end_at'], $r['status'],
                $r['payment_status'], $r['grand_total'], $r['paid_total'],
                $r['balance_due'], $r['branch_name'] ?? '',
            ]), ',', '"', '\\');
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        $this->logActivity('BOOKING_EXPORT', null, 'export', 'Export daftar booking (' . count($rows) . ' baris)');

        return $this->response->download('booking-' . date('Ymd-His') . '.csv', $csv);
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/confirm
    // ===================================================================
    public function confirm(string $seg)
    {
        $this->boot();

        $bk = $this->bookingFromSegment($seg);
        if (!$bk) {
            return $this->back('error', 'Booking tidak ditemukan.');
        }
        if ($bk['status'] !== 'PENDING') {
            return $this->back('error', "Booking {$bk['invoice_no']} berstatus {$bk['status']}; hanya PENDING yang bisa dikonfirmasi.");
        }
        if ($this->activeAllocations((int) $bk['id']) === 0) {
            return $this->back('error', "Alokasi unit untuk {$bk['invoice_no']} sudah dilepas, jadi tidak bisa dikonfirmasi. Buat booking baru setelah cek ketersediaan.");
        }

        $this->conn->transBegin();
        try {
            $this->conn->table('bookings')->where('id', $bk['id'])->where('status', 'PENDING')
                ->update(['status' => 'CONFIRMED']);

            if ($this->conn->affectedRows() === 0) {
                $this->conn->transRollback();
                return $this->back('error', 'Status booking berubah saat diproses. Muat ulang halaman.');
            }

            $this->history((int) $bk['id'], 'PENDING', 'CONFIRMED', 'Dikonfirmasi dari daftar booking');
            $this->logActivity('BOOKING_CONFIRMED', (int) $bk['id'], 'confirm', "Konfirmasi booking {$bk['invoice_no']}");

            $this->conn->transCommit();
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            log_message('error', 'Konfirmasi booking gagal: ' . $e->getMessage());
            return $this->back('error', 'Gagal mengonfirmasi booking.');
        }

        return $this->back('success', "Booking {$bk['invoice_no']} dikonfirmasi.");
    }

    // ===================================================================
    // POST /backoffice/bookings/{id}/cancel
    // ===================================================================
    public function cancel(string $seg)
    {
        $this->boot();

        $bk = $this->bookingFromSegment($seg);
        if (!$bk) {
            return $this->back('error', 'Booking tidak ditemukan.');
        }

        $reason = trim((string) $this->request->getPost('reason'));
        if (mb_strlen($reason) < 3) {
            return $this->back('error', 'Alasan pembatalan wajib diisi (minimal 3 karakter).');
        }
        $reason = mb_substr($reason, 0, 240);

        if (!in_array($bk['status'], ['PENDING', 'CONFIRMED'], true)) {
            return $this->back('error', "Booking {$bk['invoice_no']} berstatus {$bk['status']} dan tidak bisa dibatalkan.");
        }
        if ((float) $bk['paid_total'] > 0) {
            return $this->back('error', "Booking {$bk['invoice_no']} sudah ada pembayaran. Batalkan lewat halaman detail agar refund tercatat.");
        }

        $this->conn->transBegin();
        try {
            $this->conn->table('bookings')->where('id', $bk['id'])->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->update(['status' => 'CANCELLED']);

            if ($this->conn->affectedRows() === 0) {
                $this->conn->transRollback();
                return $this->back('error', 'Status booking berubah saat diproses. Muat ulang halaman.');
            }

            // Histori tidak dihapus: item dan alokasi hanya ditandai CANCELLED
            $this->conn->table('booking_items')->where('booking_id', $bk['id'])
                ->update(['status' => 'CANCELLED']);

            $this->conn->query(
                "UPDATE booking_resource_allocations a
                   JOIN booking_items i ON i.id = a.booking_item_id
                    SET a.allocation_status = 'CANCELLED'
                  WHERE i.booking_id = ?
                    AND a.allocation_status NOT IN ('CANCELLED', 'RELEASED')",
                [(int) $bk['id']]
            );

            $this->history((int) $bk['id'], $bk['status'], 'CANCELLED', $reason);
            $this->logActivity('BOOKING_CANCELLED', (int) $bk['id'], 'cancel', "Batal booking {$bk['invoice_no']}: {$reason}");

            $this->conn->transCommit();
        } catch (\Throwable $e) {
            $this->conn->transRollback();
            log_message('error', 'Pembatalan booking gagal: ' . $e->getMessage());
            return $this->back('error', 'Gagal membatalkan booking.');
        }

        return $this->back('success', "Booking {$bk['invoice_no']} dibatalkan dan unit dilepas.");
    }

    // ===================================================================
    // Query & filter
    // ===================================================================

    private function filters(): array
    {
        $g = $this->request->getGet();

        $date = static function ($v): string {
            return (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v)) ? $v : '';
        };
        $code = static function ($v): string {
            return (is_string($v) && preg_match('/^[A-Z_]{1,30}$/', $v)) ? $v : '';
        };

        return [
            'q'       => mb_substr(is_string($g['q'] ?? null) ? trim($g['q']) : '', 0, 100),
            'from'    => $date($g['from'] ?? ''),
            'to'      => $date($g['to'] ?? ''),
            'status'  => $code($g['status'] ?? ''),
            'payment' => $code($g['payment'] ?? ''),
            'source'  => $code($g['source'] ?? ''),
            'branch'  => ctype_digit((string) ($g['branch'] ?? '')) ? (string) $g['branch'] : '',
        ];
    }

    private function listQuery(array $f): BaseBuilder
    {
        $b = $this->conn->table('bookings b')
            ->select('b.id, b.invoice_no, b.booking_source, b.start_at, b.end_at, b.status, b.payment_status,
                      b.grand_total, b.paid_total, b.balance_due, b.created_at,
                      b.customer_name_snapshot, b.customer_phone_snapshot,
                      br.branch_name, ca.status AS account_status')
            ->join('branches br', 'br.id = b.branch_id', 'left')
            ->join('customer_accounts ca', 'ca.customer_id = b.customer_id', 'left');

        if ($this->branchId) {
            $b->where('b.branch_id', $this->branchId);
        } elseif ($f['branch'] !== '') {
            $b->where('b.branch_id', (int) $f['branch']);
        }

        if ($f['status'] !== '')  { $b->where('b.status', $f['status']); }
        if ($f['payment'] !== '') { $b->where('b.payment_status', $f['payment']); }
        if ($f['source'] !== '')  { $b->where('b.booking_source', $f['source']); }

        // Periode sewa yang beririsan dengan rentang tanggal
        if ($f['from'] !== '') {
            $b->where('b.end_at >', $f['from'] . ' 00:00:00');
        }
        if ($f['to'] !== '') {
            $b->where('b.start_at <', date('Y-m-d', strtotime($f['to'] . ' +1 day')) . ' 00:00:00');
        }

        if ($f['q'] !== '') {
            $b->groupStart()
                ->like('b.invoice_no', $f['q'])
                ->orLike('b.customer_name_snapshot', $f['q']);

            $digits = preg_replace('/\D+/', '', $f['q']);
            if (strlen($digits) >= 4) {
                $b->orLike('b.customer_phone_snapshot', $digits);
                if (substr($digits, 0, 1) === '0') {
                    $b->orLike('b.customer_phone_snapshot', '62' . substr($digits, 1));
                }
            }
            $b->groupEnd();
        }

        return $b;
    }

    /** Daftar pilihan filter: nilai bawaan + nilai yang benar-benar ada di DB. */
    private function options(string $column, array $defaults): array
    {
        $db = array_column(
            $this->conn->table('bookings')->select($column)->distinct()->get()->getResultArray(),
            $column
        );
        $all = array_values(array_unique(array_merge($defaults, array_filter($db))));
        sort($all);
        return $all;
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function boot(): void
    {
        // Jika id_encode/id_decode dimuat lewat helper lain, tambahkan helper itu di sini
        // (samakan dengan yang dipakai ProdukController).
        helper('backoffice');

        if (!function_exists('id_encode') || !function_exists('id_decode')) {
            throw new \RuntimeException('Helper id_encode/id_decode belum dimuat di BookingController::boot().');
        }

        $this->conn     = db_connect();
        $this->branchId = session()->get('admin_branch_id') ? (int) session()->get('admin_branch_id') : null;
    }

    private function bookingFromSegment(string $seg): ?array
    {
        $id = id_decode($seg);
        if (!$id) {
            return null;
        }

        $q = $this->conn->table('bookings')->where('id', (int) $id);
        if ($this->branchId) {
            $q->where('branch_id', $this->branchId); // staff cabang hanya boleh booking cabangnya
        }

        return $q->get()->getRowArray() ?: null;
    }

    private function activeAllocations(int $bookingId): int
    {
        return $this->conn->table('booking_resource_allocations a')
            ->join('booking_items i', 'i.id = a.booking_item_id')
            ->where('i.booking_id', $bookingId)
            ->whereNotIn('a.allocation_status', ['CANCELLED', 'RELEASED'])
            ->countAllResults();
    }

    private function history(int $bookingId, ?string $old, string $new, string $note): void
    {
        $this->conn->table('booking_status_history')->insert([
            'booking_id'         => $bookingId,
            'old_status'         => $old,
            'new_status'         => $new,
            'changed_by_user_id' => (int) session()->get('admin_id'),
            'note'               => mb_substr($note, 0, 255),
        ]);
    }

    private function logActivity(string $event, ?int $bookingId, string $action, string $desc): void
    {
        $this->conn->table('activity_logs')->insert([
            'user_id'     => (int) session()->get('admin_id'),
            'event_type'  => $event,
            'module'      => 'booking',
            'entity_type' => $bookingId ? 'booking' : null,
            'entity_id'   => $bookingId,
            'action'      => $action,
            'description' => mb_substr($desc, 0, 1000),
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => mb_substr((string) $this->request->getUserAgent(), 0, 500),
        ]);
    }

    private function back(string $type, string $message)
    {
        return redirect()->back()->with($type, $message);
    }

    /** Cegah formula injection saat CSV dibuka di Excel. */
    private function csvCell($v)
    {
        if (is_string($v) && $v !== '' && strpbrk($v[0], '=+-@') !== false) {
            return "'" . $v;
        }
        return $v;
    }

    private function has(string $uri): bool
    {
        if (!$this->routeKeys) {
            try {
                $this->routeKeys = array_keys(service('routes')->getRoutes('get'));
            } catch (\Throwable $e) {
                $this->routeKeys = [];
            }
        }
        return in_array($uri, $this->routeKeys, true);
    }
}