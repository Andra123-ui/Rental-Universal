<?php

namespace App\Controllers\Customer;

use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\CustomerDocumentModel;
use App\Models\HandoverModel;
use App\Models\PaymentModel;

class Dashboard extends BaseCustomerController
{
    protected BookingModel $bookingModel;
    protected BookingItemModel $bookingItemModel;
    protected CustomerDocumentModel $documentModel;
    protected HandoverModel $handoverModel;
    protected PaymentModel $paymentModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->bookingModel     = new BookingModel();
        $this->bookingItemModel = new BookingItemModel();
        $this->documentModel    = new CustomerDocumentModel();
        $this->handoverModel    = new HandoverModel();
        $this->paymentModel     = new PaymentModel();
    }

    /**
     * GET /account (CUS-01: Dashboard Akun)
     */
    public function index()
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        // Expire stale bookings bila ada
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        // 1. Data Ringkasan Booking Aktif
        $activeBookings = $this->bookingModel
            ->where('customer_id', $customerId)
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'ONGOING'])
            ->orderBy('start_at', 'ASC')
            ->findAll();

        $activeCount = count($activeBookings);

        // 2. Data Pembayaran Perhatian (Balance Due / Belum Lunas)
        $unpaidBookings = $this->bookingModel
            ->where('customer_id', $customerId)
            ->where('balance_due >', 0)
            ->whereNotIn('status', ['CANCELLED', 'EXPIRED'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $totalBalanceDue = 0;
        foreach ($unpaidBookings as $ub) {
            $totalBalanceDue += (float) $ub['balance_due'];
        }

        // 3. Jadwal Operasional Terdekat (Pickup / Return)
        $upcomingHandovers = $this->handoverModel->getUpcomingByCustomer($customerId, 3);

        // Jika tidak ada data handover spesifik, ambil dari start_at / end_at booking aktif
        $upcomingSchedules = [];
        if (!empty($upcomingHandovers)) {
            foreach ($upcomingHandovers as $h) {
                $upcomingSchedules[] = [
                    'type'        => $h['handover_type'] === 'PICKUP' ? 'Pengambilan' : 'Pengembalian',
                    'invoice_no'  => $h['invoice_no'],
                    'date'        => $h['scheduled_at'],
                    'location'    => $h['location'] ?? 'Lokasi Rental',
                ];
            }
        } else {
            foreach ($activeBookings as $ab) {
                if ($ab['status'] === 'CONFIRMED' && strtotime($ab['start_at']) >= time()) {
                    $upcomingSchedules[] = [
                        'type'        => 'Mulai Sewa / Serah Terima',
                        'invoice_no'  => $ab['invoice_no'],
                        'date'        => $ab['start_at'],
                        'location'    => $ab['pickup_address'] ?: 'Kantor Rental',
                    ];
                } elseif ($ab['status'] === 'ONGOING' && strtotime($ab['end_at']) >= time()) {
                    $upcomingSchedules[] = [
                        'type'        => 'Pengembalian Unit',
                        'invoice_no'  => $ab['invoice_no'],
                        'date'        => $ab['end_at'],
                        'location'    => $ab['return_address'] ?: 'Kantor Rental',
                    ];
                }
            }
        }

        // 4. Riwayat Booking Terbaru (5 transaksi terakhir)
        $recentBookings = $this->bookingModel
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'DESC')
            ->findAll(5);

        // Ambil nama item snapshot untuk tiap booking
        $itemsByBooking = [];
        if (!empty($recentBookings)) {
            $bookingIds = array_column($recentBookings, 'id');
            $items = $this->bookingItemModel
                ->whereIn('booking_id', $bookingIds)
                ->findAll();

            foreach ($items as $item) {
                $itemsByBooking[$item['booking_id']][] = $item;
            }
        }

        foreach ($recentBookings as &$rb) {
            $rb['items'] = $itemsByBooking[$rb['id']] ?? [];
        }
        unset($rb);

        // 5. Dokumen identitas verifikasi status
        $latestDocument = $this->documentModel->getLatestByCustomer($customerId);

        return $this->renderCustomerView('customer/dashboard/index', [
            'activeNav'         => 'dashboard',
            'pageTitle'         => 'Dashboard Akun',
            'pageSubtitle'      => 'Ringkasan transaksi aktif, tagihan pembayaran, dan jadwal operasional Anda.',
            'activeCount'       => $activeCount,
            'activeBookings'    => $activeBookings,
            'unpaidCount'       => count($unpaidBookings),
            'totalBalanceDue'   => $totalBalanceDue,
            'unpaidBookings'    => $unpaidBookings,
            'upcomingSchedules' => $upcomingSchedules,
            'recentBookings'    => $recentBookings,
            'latestDocument'    => $latestDocument,
        ]);
    }
}

