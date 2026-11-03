<?php

namespace App\Controllers\Customer;

use App\Models\BookingItemModel;
use App\Models\BookingModel;

class Bookings extends BaseCustomerController
{
    protected BookingModel $bookingModel;
    protected BookingItemModel $bookingItemModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->bookingModel     = new BookingModel();
        $this->bookingItemModel = new BookingItemModel();
    }

    /**
     * GET /account/bookings (CUS-02: Riwayat Booking)
     */
    public function index()
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        // Expire stale bookings bila ada
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        // Ambil filter query parameter
        $search        = trim((string) $this->request->getGet('q'));
        $bookingStatus = trim((string) $this->request->getGet('status'));
        $paymentStatus = trim((string) $this->request->getGet('payment'));
        $dateFilter    = trim((string) $this->request->getGet('date_range'));
        $startDate     = trim((string) $this->request->getGet('start_date'));
        $endDate       = trim((string) $this->request->getGet('end_date'));
        $perPage       = 10;

        // Query Builder untuk bookings milik customer ini
        $builder = $this->bookingModel
            ->where('customer_id', $customerId);

        // Filter status booking
        if ($bookingStatus !== '' && $bookingStatus !== 'ALL') {
            if ($bookingStatus === 'CANCELLED') {
                $builder->whereIn('status', ['CANCELLED', 'EXPIRED']);
            } else {
                $builder->where('status', $bookingStatus);
            }
        }

        // Filter status pembayaran
        if ($paymentStatus !== '' && $paymentStatus !== 'ALL') {
            $builder->where('payment_status', $paymentStatus);
        }

        // Filter tanggal
        if ($dateFilter === 'this_month') {
            $builder->where('created_at >=', date('Y-m-01 00:00:00'))
                    ->where('created_at <=', date('Y-m-t 23:59:59'));
        } elseif ($dateFilter === 'this_year') {
            $builder->where('created_at >=', date('Y-01-01 00:00:00'))
                    ->where('created_at <=', date('Y-12-31 23:59:59'));
        } elseif ($startDate !== '' && $endDate !== '') {
            $builder->where('created_at >=', $startDate . ' 00:00:00')
                    ->where('created_at <=', $endDate . ' 23:59:59');
        }

        // Filter pencarian invoice atau item
        if ($search !== '') {
            $builder->groupStart()
                ->like('invoice_no', $search)
                ->orWhere("id IN (SELECT booking_id FROM booking_items WHERE item_name_snapshot LIKE '%" . $this->bookingModel->escapeLikeString($search) . "%')")
                ->groupEnd();
        }

        // Ambil data dengan pagination
        $bookings = $builder->orderBy('created_at', 'DESC')
            ->paginate($perPage, 'bookings');

        $pager = $this->bookingModel->pager;

        // Eager load items snapshot untuk setiap booking
        $itemsByBooking = [];
        if (!empty($bookings)) {
            $bookingIds = array_column($bookings, 'id');
            $items = $this->bookingItemModel
                ->whereIn('booking_id', $bookingIds)
                ->findAll();

            foreach ($items as $item) {
                $itemsByBooking[$item['booking_id']][] = $item;
            }
        }

        foreach ($bookings as &$b) {
            $b['items'] = $itemsByBooking[$b['id']] ?? [];
        }
        unset($b);

        // Hitung ringkasan status untuk tab counter
        $counts = [
            'ALL'       => $this->bookingModel->where('customer_id', $customerId)->countAllResults(),
            'PENDING'   => $this->bookingModel->where('customer_id', $customerId)->where('status', 'PENDING')->countAllResults(),
            'CONFIRMED' => $this->bookingModel->where('customer_id', $customerId)->where('status', 'CONFIRMED')->countAllResults(),
            'ONGOING'   => $this->bookingModel->where('customer_id', $customerId)->where('status', 'ONGOING')->countAllResults(),
            'COMPLETED' => $this->bookingModel->where('customer_id', $customerId)->where('status', 'COMPLETED')->countAllResults(),
            'CANCELLED' => $this->bookingModel->where('customer_id', $customerId)->whereIn('status', ['CANCELLED', 'EXPIRED'])->countAllResults(),
        ];

        return $this->renderCustomerView('customer/bookings/index', [
            'activeNav'      => 'bookings',
            'pageTitle'      => 'Riwayat Booking',
            'pageSubtitle'   => 'Daftar semua transaksi rental barang dan jasa yang pernah Anda pesan.',
            'breadcrumbs'    => ['Riwayat Booking' => null],
            'bookings'       => $bookings,
            'pager'          => $pager,
            'counts'         => $counts,
            'search'         => $search,
            'bookingStatus'  => $bookingStatus,
            'paymentStatus'  => $paymentStatus,
            'dateFilter'     => $dateFilter,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
        ]);
    }
}

