<?php

namespace App\Controllers\Customer;

use App\Models\BookingChargeModel;
use App\Models\BookingDocumentModel;
use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\BookingStatusHistoryModel;
use App\Models\BranchModel;
use App\Models\HandoverDetailModel;
use App\Models\HandoverModel;
use App\Models\PaymentModel;
use App\Models\SecurityDepositModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class BookingDetail extends BaseCustomerController
{
    protected BookingModel $bookingModel;
    protected BookingItemModel $bookingItemModel;
    protected PaymentModel $paymentModel;
    protected SecurityDepositModel $depositModel;
    protected BookingChargeModel $chargeModel;
    protected HandoverModel $handoverModel;
    protected HandoverDetailModel $handoverDetailModel;
    protected BookingDocumentModel $documentModel;
    protected BookingStatusHistoryModel $historyModel;
    protected BranchModel $branchModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->bookingModel        = new BookingModel();
        $this->bookingItemModel    = new BookingItemModel();
        $this->paymentModel        = new PaymentModel();
        $this->depositModel        = new SecurityDepositModel();
        $this->chargeModel         = new BookingChargeModel();
        $this->handoverModel       = new HandoverModel();
        $this->handoverDetailModel = new HandoverDetailModel();
        $this->documentModel       = new BookingDocumentModel();
        $this->historyModel        = new BookingStatusHistoryModel();
        $this->branchModel         = new BranchModel();
    }

    /**
     * GET /account/bookings/{invoice_no} (CUS-03: Detail Transaksi)
     */
    public function show(string $invoiceNo)
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        // Expire stale bookings
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        // Validasi invoice_no dan kepemilikan customer
        $booking = $this->bookingModel
            ->where('invoice_no', $invoiceNo)
            ->where('customer_id', $customerId)
            ->first();

        if (!$booking) {
            throw PageNotFoundException::forPageNotFound('Booking tidak ditemukan atau Anda tidak memiliki akses ke transaksi ini.');
        }

        $bookingId = (int) $booking['id'];

        // Item sewa
        $items = $this->bookingItemModel
            ->where('booking_id', $bookingId)
            ->findAll();

        // Riwayat pembayaran
        $payments = $this->paymentModel->getPaymentsByBooking($bookingId);

        // Security deposit
        $deposit = $this->depositModel->getByBookingId($bookingId);

        // Biaya tambahan / denda
        $charges = $this->chargeModel->getByBookingId($bookingId);

        // Serah terima / handovers
        $handovers = $this->handoverModel->getByBookingId($bookingId);
        foreach ($handovers as &$h) {
            $h['details'] = $this->handoverDetailModel->getByHandoverId((int) $h['id']);
        }
        unset($h);

        // Dokumen transaksi
        $documents = $this->documentModel->getByBookingId($bookingId);

        // Histori status aman untuk customer
        $statusHistory = $this->historyModel->getCustomerHistory($bookingId);

        // Info cabang bila ada
        $branch = !empty($booking['branch_id']) ? $this->branchModel->find($booking['branch_id']) : null;

        return $this->renderCustomerView('customer/bookings/detail', [
            'activeNav'     => 'bookings',
            'pageTitle'     => 'Detail Booking ' . esc($booking['invoice_no']),
            'pageSubtitle'  => 'Informasi lengkap status pesanan, rincian biaya, jadwal sewa, dan dokumen resmi.',
            'breadcrumbs'   => [
                'Riwayat Booking' => 'account/bookings',
                $booking['invoice_no'] => null,
            ],
            'booking'       => $booking,
            'items'         => $items,
            'payments'      => $payments,
            'deposit'       => $deposit,
            'charges'       => $charges,
            'handovers'     => $handovers,
            'documents'     => $documents,
            'statusHistory' => $statusHistory,
            'branch'        => $branch,
        ]);
    }

    /**
     * GET /account/bookings/{invoice_no}/print (Cetak Invoice Resmi)
     */
    public function print(string $invoiceNo)
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $booking = $this->bookingModel
            ->where('invoice_no', $invoiceNo)
            ->where('customer_id', $customerId)
            ->first();

        if (!$booking) {
            throw PageNotFoundException::forPageNotFound('Invoice tidak ditemukan.');
        }

        $items    = $this->bookingItemModel->where('booking_id', $booking['id'])->findAll();
        $payments = $this->paymentModel->getPaymentsByBooking($booking['id']);
        $deposit  = $this->depositModel->getByBookingId($booking['id']);
        $charges  = $this->chargeModel->getByBookingId($booking['id']);
        $branch   = !empty($booking['branch_id']) ? $this->branchModel->find($booking['branch_id']) : null;

        return view('customer/bookings/print', [
            'business' => $this->business,
            'booking'  => $booking,
            'items'    => $items,
            'payments' => $payments,
            'deposit'  => $deposit,
            'charges'  => $charges,
            'branch'   => $branch,
        ]);
    }
}

