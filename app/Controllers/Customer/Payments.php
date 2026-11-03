<?php

namespace App\Controllers\Customer;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Payments extends BaseCustomerController
{
    protected BookingModel $bookingModel;
    protected PaymentModel $paymentModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->bookingModel = new BookingModel();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * GET /account/payments (CUS-04: Pembayaran Saya)
     */
    public function index()
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login')->with('info', 'Silakan masuk terlebih dahulu.');
        }

        // Expire stale bookings
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        $tab = $this->request->getGet('tab') ?? 'outstanding';
        $statusFilter = $this->request->getGet('status');

        // 1. Tagihan Tertunda (Outstanding Bookings dengan balance_due > 0)
        $outstanding = $this->bookingModel
            ->where('customer_id', $customerId)
            ->where('balance_due >', 0)
            ->whereNotIn('status', ['CANCELLED', 'EXPIRED'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $totalOutstanding = 0;
        foreach ($outstanding as $o) {
            $totalOutstanding += (float) $o['balance_due'];
        }

        // 2. Riwayat Pembayaran (dari tabel payments)
        $history = $this->paymentModel->getPaymentsByCustomer($customerId, $statusFilter);

        // Jika customer punya booking CONFIRMED/PAID tapi belum ada row di payments (karena pembayaran simulasi sebelumnya),
        // sinkronkan agar customer dapat melihat kwitansi resmi di portal
        $this->syncPaidBookingsToPayments($customerId);

        // Refresh riwayat setelah sinkronisasi
        $history = $this->paymentModel->getPaymentsByCustomer($customerId, $statusFilter);

        return $this->renderCustomerView('customer/payments/index', [
            'activeNav'        => 'payments',
            'pageTitle'        => 'Pembayaran Saya',
            'pageSubtitle'     => 'Kelola tagihan sewa yang perlu dibayar dan riwayat transaksi pembayaran Anda.',
            'breadcrumbs'      => ['Pembayaran' => null],
            'outstanding'      => $outstanding,
            'totalOutstanding' => $totalOutstanding,
            'history'          => $history,
            'tab'              => $tab,
            'statusFilter'     => $statusFilter,
        ]);
    }

    /**
     * POST /account/payments/upload-proof/{payment_no}
     * Unggah bukti transfer manual
     */
    public function uploadProof(string $paymentNo): RedirectResponse
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $payment = $this->paymentModel->findCustomerPayment($paymentNo, $customerId);
        if (!$payment) {
            return redirect()->back()->with('error', 'Pembayaran tidak ditemukan.');
        }

        $file = $this->request->getFile('proof_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File bukti pembayaran wajib dipilih.');
        }

        // Validasi ekstensi & mime
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($file->getMimeType(), $allowedTypes, true)) {
            return redirect()->back()->with('error', 'Format file harus JPG, PNG, atau PDF.');
        }

        // Validasi ukuran maks 4MB
        if ($file->getSizeByUnit('mb') > 4) {
            return redirect()->back()->with('error', 'Ukuran file maksimal 4 MB.');
        }

        $uploadDir = WRITEPATH . 'uploads/payment_proofs/' . $customerId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        $relPath = 'uploads/payment_proofs/' . $customerId . '/' . $newName;

        // Update bukti transfer tanpa mengubah status jadi PAID otomatis (tetap PENDING untuk direview staf)
        $this->paymentModel->update($payment['id'], [
            'proof_path' => $relPath,
            'status'     => 'PENDING',
            'notes'      => 'Bukti pembayaran diunggah oleh pelanggan pada ' . date('d-m-Y H:i'),
        ]);

        return redirect()->to(site_url('account/payments?tab=history'))
            ->with('success', 'Bukti pembayaran berhasil diunggah dan sedang dalam peninjauan admin.');
    }

    /**
     * GET /account/payments/receipt/{payment_no} (Cetak Kwitansi Pembayaran Resmi)
     */
    public function receipt(string $paymentNo)
    {
        $customerId = $this->customerId();
        if ($customerId <= 0) {
            return redirect()->to('/account/login');
        }

        $payment = $this->paymentModel->findCustomerPayment($paymentNo, $customerId);
        if (!$payment) {
            throw PageNotFoundException::forPageNotFound('Kwitansi pembayaran tidak ditemukan.');
        }

        return view('customer/payments/receipt', [
            'business' => $this->business,
            'payment'  => $payment,
            'customer' => $this->customer,
        ]);
    }

    /**
     * Helper otomatis: jika ada booking yang sudah berstatus PAID tetapi belum tercatat
     * di tabel payments, catat row resmi agar kwitansi & histori tersedia bagi pelanggan
     */
    protected function syncPaidBookingsToPayments(int $customerId): void
    {
        $paidBookings = $this->bookingModel
            ->where('customer_id', $customerId)
            ->where('payment_status', 'PAID')
            ->findAll();

        foreach ($paidBookings as $b) {
            $existing = $this->paymentModel->where('booking_id', $b['id'])->first();
            if (!$existing) {
                $paymentNo = 'PAY-' . date('Ymd', strtotime($b['created_at'])) . '-' . strtoupper(substr(md5((string)$b['id']), 0, 6));
                $this->paymentModel->insert([
                    'booking_id'    => $b['id'],
                    'payment_no'    => $paymentNo,
                    'payment_type'  => 'RENTAL_PAYMENT',
                    'direction'     => 'IN',
                    'method'        => 'TRANSFER',
                    'provider'      => 'BANK_TRANSFER',
                    'reference_no'  => $b['invoice_no'],
                    'amount'        => $b['paid_total'] > 0 ? $b['paid_total'] : $b['grand_total'],
                    'status'        => 'PAID',
                    'paid_at'       => $b['updated_at'] ?? date('Y-m-d H:i:s'),
                    'proof_path'    => null,
                    'notes'         => 'Pembayaran lunas terverifikasi sistem',
                ]);
            }
        }
    }
}

