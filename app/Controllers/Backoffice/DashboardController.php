<?php

namespace App\Controllers\Backoffice;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;

class DashboardController extends BaseController
{
    private const CLOSED = ['CANCELLED', 'EXPIRED'];

    private $conn;
    private ?int $branchId = null;
    private array $routeKeys = [];

    public function index()
    {
        helper('backoffice');

        $this->conn     = db_connect();
        $this->branchId = session()->get('admin_branch_id') ? (int) session()->get('admin_branch_id') : null;

        $now        = date('Y-m-d H:i:s');
        $todayStart = date('Y-m-d') . ' 00:00:00';
        $tomorrow   = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
        $monthStart = date('Y-m-01') . ' 00:00:00';

        $canBooking = admin_can('booking.view');
        $canManage  = admin_can('booking.manage');
        $canFinance = admin_can('dashboard.finance');
        $canVerify  = admin_can('customer.verify');
        $canStaff   = admin_can('staff.manage');

        $data = [
            'title'       => 'Dashboard',
            'nav'         => 'dashboard',
            'branchLabel' => $this->branchLabel(),
            'canBooking'  => $canBooking,
            'canFinance'  => $canFinance,
            'canVerify'   => $canVerify,
            'canStaff'    => $canStaff,
            'kpi'         => [],
            'finance'     => [],
            'schedule'    => [],
            'payAttention'=> ['pending' => 0, 'overdue' => []],
            'resAttention'=> [],
            'custAttention'=> ['docs' => 0, 'locked' => 0],
            'recentBookings' => [],
            'recentLogs'  => [],
            'quick'       => [],
        ];

        // ---------------- KPI operasional ----------------
        if ($canBooking) {
            $data['kpi']['today'] = $this->bookings()
                ->where('b.created_at >=', $todayStart)->where('b.created_at <', $tomorrow)
                ->whereNotIn('b.status', self::CLOSED)->countAllResults();

            $data['kpi']['pending'] = $this->bookings()
                ->where('b.status', 'PENDING')->countAllResults();

            $data['kpi']['ongoing'] = $this->bookings()
                ->whereIn('b.status', ['CONFIRMED', 'ONGOING'])
                ->where('b.start_at <=', $now)->where('b.end_at >', $now)->countAllResults();

            $totalRes = $this->resources()->countAllResults();

            $usedQ = $this->conn->table('booking_resource_allocations a')
                ->select('COUNT(DISTINCT a.resource_id) AS c')
                ->join('resources r', 'r.id = a.resource_id')
                ->where('r.deleted_at', null)
                ->whereNotIn('a.allocation_status', ['CANCELLED', 'RELEASED'])
                ->where('a.start_at <', $tomorrow)->where('a.end_at >', $todayStart);
            if ($this->branchId) {
                $usedQ->where('r.branch_id', $this->branchId);
            }
            $used = (int) ($usedQ->get()->getRow('c') ?? 0);

            $data['kpi']['utilization'] = $totalRes > 0 ? (int) round($used / $totalRes * 100) : 0;
            $data['kpi']['res_used']    = $used;
            $data['kpi']['res_total']   = $totalRes;
        }

        // ---------------- KPI keuangan (sensitif) ----------------
        if ($canFinance) {
            $rev = $this->bookings()
                ->select('COALESCE(SUM(b.paid_total), 0) AS revenue')
                ->where('b.created_at >=', $monthStart)
                ->whereNotIn('b.status', self::CLOSED)
                ->get()->getRowArray();

            $unpaid = $this->bookings()
                ->select('COUNT(*) AS c, COALESCE(SUM(b.balance_due), 0) AS total')
                ->whereIn('b.status', ['PENDING', 'CONFIRMED', 'ONGOING'])
                ->where('b.balance_due >', 0)
                ->get()->getRowArray();

            $data['finance'] = [
                'revenue'      => (float) ($rev['revenue'] ?? 0),
                'unpaid_count' => (int) ($unpaid['c'] ?? 0),
                'unpaid_total' => (float) ($unpaid['total'] ?? 0),
            ];

            // Pembayaran menunggu verifikasi
            $pq = $this->conn->table('payments p')
                ->join('bookings b', 'b.id = p.booking_id')
                ->where('p.status', 'PENDING');
            if ($this->branchId) {
                $pq->where('b.branch_id', $this->branchId);
            }
            $data['payAttention']['pending'] = $pq->countAllResults();

            // Overdue: jadwal sudah mulai tapi belum lunas
            $overdue = $this->bookings()
                ->select('b.id, b.invoice_no, b.customer_name_snapshot, b.balance_due, b.start_at')
                ->whereIn('b.status', ['CONFIRMED', 'ONGOING'])
                ->where('b.balance_due >', 0)
                ->where('b.start_at <', $todayStart)
                ->orderBy('b.start_at', 'ASC')->limit(5)
                ->get()->getResultArray();
            foreach ($overdue as &$o) {
                $o['url'] = $this->bookingUrl((int) $o['id']);
            }
            unset($o);
            $data['payAttention']['overdue'] = $overdue;
        }

        // ---------------- Jadwal hari ini ----------------
        if ($canBooking) {
            $events = [];

            $pickups = $this->bookings()
                ->select('b.id, b.invoice_no, b.customer_name_snapshot, b.start_at, b.fulfillment_method')
                ->whereIn('b.status', ['CONFIRMED', 'ONGOING'])
                ->where('b.start_at >=', $todayStart)->where('b.start_at <', $tomorrow)
                ->get()->getResultArray();
            foreach ($pickups as $r) {
                $events[] = [
                    'time'  => $r['start_at'], 'type' => 'Pickup', 'badge' => 'badge-info',
                    'title' => $r['invoice_no'],
                    'sub'   => $r['customer_name_snapshot'] . ($r['fulfillment_method'] ? ' · ' . str_replace('_', ' ', $r['fulfillment_method']) : ''),
                    'url'   => $this->bookingUrl((int) $r['id']),
                ];
            }

            $returns = $this->bookings()
                ->select('b.id, b.invoice_no, b.customer_name_snapshot, b.end_at')
                ->whereIn('b.status', ['CONFIRMED', 'ONGOING'])
                ->where('b.end_at >=', $todayStart)->where('b.end_at <', $tomorrow)
                ->get()->getResultArray();
            foreach ($returns as $r) {
                $events[] = [
                    'time'  => $r['end_at'], 'type' => 'Return', 'badge' => 'badge-warn',
                    'title' => $r['invoice_no'], 'sub' => $r['customer_name_snapshot'],
                    'url'   => $this->bookingUrl((int) $r['id']),
                ];
            }

            $asQ = $this->conn->table('assignments s')
                ->select('s.booking_id, s.assignment_type, s.assignee_name_snapshot, s.start_at, b.invoice_no')
                ->join('bookings b', 'b.id = s.booking_id')
                ->whereNotIn('s.status', ['CANCELLED', 'DONE', 'COMPLETED'])
                ->where('s.start_at >=', $todayStart)->where('s.start_at <', $tomorrow);
            if ($this->branchId) {
                $asQ->where('b.branch_id', $this->branchId);
            }
            foreach ($asQ->get()->getResultArray() as $r) {
                $events[] = [
                    'time'  => $r['start_at'], 'type' => 'Assignment', 'badge' => 'badge-ok',
                    'title' => $r['invoice_no'],
                    'sub'   => str_replace('_', ' ', $r['assignment_type']) . ' · ' . $r['assignee_name_snapshot'],
                    'url'   => $this->bookingUrl((int) $r['booking_id']),
                ];
            }

            $mtQ = $this->conn->table('maintenance_records m')
                ->select('m.maintenance_type, m.start_at, r.resource_name')
                ->join('resources r', 'r.id = m.resource_id')
                ->whereNotIn('m.status', ['CANCELLED', 'DONE', 'COMPLETED'])
                ->where('m.start_at >=', $todayStart)->where('m.start_at <', $tomorrow);
            if ($this->branchId) {
                $mtQ->where('r.branch_id', $this->branchId);
            }
            foreach ($mtQ->get()->getResultArray() as $r) {
                $events[] = [
                    'time'  => $r['start_at'], 'type' => 'Service', 'badge' => 'badge-danger',
                    'title' => $r['resource_name'], 'sub' => str_replace('_', ' ', $r['maintenance_type']),
                    'url'   => null,
                ];
            }

            usort($events, static fn ($a, $b) => strcmp($a['time'], $b['time']));
            $data['schedule'] = array_slice($events, 0, 12);
        }

        // ---------------- Resource attention ----------------
        if ($canBooking) {
            $items = [];

            $mq = $this->conn->table('maintenance_records m')
                ->select('m.maintenance_type, m.start_at, m.end_at, r.resource_name')
                ->join('resources r', 'r.id = m.resource_id')
                ->whereNotIn('m.status', ['CANCELLED', 'DONE', 'COMPLETED'])
                ->where('m.start_at <', $tomorrow)
                ->groupStart()->where('m.end_at', null)->orWhere('m.end_at >=', $now)->groupEnd();
            if ($this->branchId) {
                $mq->where('r.branch_id', $this->branchId);
            }
            foreach ($mq->limit(6)->get()->getResultArray() as $r) {
                $items[] = ['type' => 'Maintenance', 'badge' => 'badge-danger',
                    'title' => $r['resource_name'],
                    'sub'   => str_replace('_', ' ', $r['maintenance_type']) . ' · ' . $this->period($r['start_at'], $r['end_at'])];
            }

            $bq = $this->conn->table('blackout_periods o')
                ->select('o.reason, o.block_type, o.start_at, o.end_at, r.resource_name')
                ->join('resources r', 'r.id = o.resource_id', 'left')
                ->where('o.status', 'ACTIVE')
                ->where('o.start_at <', $tomorrow)->where('o.end_at >', $now);
            if ($this->branchId) {
                $bq->groupStart()->where('o.branch_id', null)->orWhere('o.branch_id', $this->branchId)->groupEnd();
            }
            foreach ($bq->limit(6)->get()->getResultArray() as $r) {
                $items[] = ['type' => 'Blackout', 'badge' => 'badge-warn',
                    'title' => $r['resource_name'] ?: ($r['reason'] ?: 'Blackout'),
                    'sub'   => ($r['reason'] ?: $r['block_type']) . ' · ' . $this->period($r['start_at'], $r['end_at'])];
            }

            $uq = $this->resources()->select('r.resource_name, r.status')->where('r.status !=', 'AVAILABLE');
            foreach ($uq->limit(6)->get()->getResultArray() as $r) {
                $items[] = ['type' => 'Unavailable', 'badge' => 'badge-off',
                    'title' => $r['resource_name'], 'sub' => 'Status: ' . $r['status']];
            }

            $data['resAttention'] = array_slice($items, 0, 10);
        }

        // ---------------- Customer auth attention ----------------
        if ($canVerify) {
            $data['custAttention']['docs'] = $this->conn->table('customer_documents')
                ->where('verification_status', 'PENDING')->countAllResults();

            $data['custAttention']['locked'] = $this->conn->table('customer_accounts')
                ->groupStart()
                    ->whereIn('status', ['SUSPENDED', 'BLOCKED', 'LOCKED'])
                    ->orGroupStart()->where('locked_until >', $now)->groupEnd()
                ->groupEnd()->countAllResults();
        }

        // ---------------- Aktivitas terbaru ----------------
        if ($canBooking) {
            $data['recentBookings'] = $this->bookings()
                ->select('b.id, b.invoice_no, b.customer_name_snapshot, b.status, b.payment_status, b.created_at')
                ->orderBy('b.created_at', 'DESC')->limit(6)
                ->get()->getResultArray();
            foreach ($data['recentBookings'] as &$rb) {
                $rb['url'] = $this->bookingUrl((int) $rb['id']);
            }
            unset($rb);
        }

        if ($canStaff) {
            $data['recentLogs'] = $this->conn->table('activity_logs a')
                ->select('a.event_type, a.description, a.created_at, u.name AS user_name')
                ->join('users u', 'u.id = a.user_id', 'left')
                ->orderBy('a.id', 'DESC')->limit(8)
                ->get()->getResultArray();
        }

        // ---------------- Quick actions (hanya jika route-nya sudah ada) ----------------
        $quick = [];
        if ($canManage && $this->has('backoffice/bookings/baru')) {
            $quick[] = ['label' => '+ Booking baru', 'url' => site_url('backoffice/bookings/baru'), 'primary' => true];
        }
        if ($canBooking && $this->has('backoffice/bookings')) {
            $quick[] = ['label' => 'Buka booking', 'url' => site_url('backoffice/bookings'), 'primary' => false];
        }
        if ($canBooking && $this->has('backoffice/kalender')) {
            $quick[] = ['label' => 'Buka kalender', 'url' => site_url('backoffice/kalender'), 'primary' => false];
        }
        if ($canVerify && $this->has('backoffice/verifikasi-identitas')) {
            $quick[] = ['label' => 'Verifikasi identitas', 'url' => site_url('backoffice/verifikasi-identitas'), 'primary' => false];
        }
        $data['quick']        = $quick;
        $data['identityUrl']  = $this->has('backoffice/verifikasi-identitas') ? site_url('backoffice/verifikasi-identitas') : null;

        return view('backoffice/dashboard', $data);
    }

    // ===================================================================
    // Helper
    // ===================================================================

    private function bookings(): BaseBuilder
    {
        $b = $this->conn->table('bookings b');
        if ($this->branchId) {
            $b->where('b.branch_id', $this->branchId);
        }
        return $b;
    }

    private function resources(): BaseBuilder
    {
        $r = $this->conn->table('resources r')->where('r.deleted_at', null);
        if ($this->branchId) {
            $r->where('r.branch_id', $this->branchId);
        }
        return $r;
    }

    private function branchLabel(): string
    {
        if (!$this->branchId) {
            return 'Semua cabang';
        }
        $name = $this->conn->table('branches')->select('branch_name')
            ->where('id', $this->branchId)->get()->getRow('branch_name');

        return $name ?: 'Cabang #' . $this->branchId;
    }

    private function period(?string $start, ?string $end): string
    {
        $s = $start ? date('d/m H:i', strtotime($start)) : '-';
        $e = $end ? date('d/m H:i', strtotime($end)) : 'tanpa batas';
        return $s . ' – ' . $e;
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

    /**
     * URL detail booking. Null selama halaman detail (BOOK-02) belum ada,
     * atau helper id_encode belum dimuat.
     */
    private function bookingUrl(int $id): ?string
    {
        if (!$this->has('backoffice/bookings/(:segment)') || !function_exists('id_encode')) {
            return null;
        }
        return site_url('backoffice/bookings/' . id_encode($id));
    }
}