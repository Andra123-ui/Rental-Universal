<?php

namespace App\Controllers;

use App\Models\BookingModel;

class Payment extends BaseController
{
    protected const PAYMENT_WINDOW_MINUTES = 3;

    /**
     * Halaman pembayaran
     */
    public function index(string $invoiceNo)
    {
        // Pastikan booking yang sudah lewat waktunya diproses
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        $bookingModel = new BookingModel();

        $booking = $bookingModel
            ->where('invoice_no', $invoiceNo)
            ->first();

        if (!$booking) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Invoice tidak ditemukan.'
            );
        }

        // Sudah dibayar
        if ($booking['payment_status'] === 'PAID') {
            return redirect()->to('/track/' . $invoiceNo)
                ->with('success', 'Booking ini sudah dibayar.');
        }

        // Booking sudah expired
        if ($booking['status'] === 'EXPIRED') {
            return redirect()->to('/checkout/berhasil/' . $invoiceNo);
        }

        // Booking bukan PENDING
        if ($booking['status'] !== 'PENDING') {
            return redirect()->to('/track/' . $invoiceNo)
                ->with('error', 'Booking tidak dapat dibayar.');
        }

        $secondsLeft = 0;

        if (!empty($booking['expires_at'])) {
            $secondsLeft = max(
                0,
                strtotime($booking['expires_at']) - time()
            );
        }

        // Kalau waktunya sudah habis, expire sekarang
        if ($secondsLeft <= 0) {
            (new \App\Models\AvailabilityModel())->expireStaleBookings();

            return redirect()->to('/checkout/berhasil/' . $invoiceNo);
        }

        return view('pub/payment', [
            'booking' => $booking,
            'secondsLeft' => $secondsLeft,
        ]);
    }

    /**
     * Proses pembayaran
     *
     * Untuk sementara dibuat instant/simulasi:
     * klik bayar -> langsung PAID.
     *
     * Nanti bagian ini bisa diganti Midtrans/Xendit/payment gateway.
     */
    public function process(string $invoiceNo)
    {
        if (!$this->request->is('post')) {
            return redirect()->to('/track/' . $invoiceNo . '/payment');
        }

        $bookingModel = new BookingModel();

        $booking = $bookingModel
            ->where('invoice_no', $invoiceNo)
            ->first();

        if (!$booking) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Invoice tidak ditemukan.'
            );
        }

        // Sudah dibayar
        if ($booking['payment_status'] === 'PAID') {
            return redirect()->to('/track/' . $invoiceNo)
                ->with('success', 'Pembayaran sudah berhasil.');
        }

        // Cek expired
        if (
            $booking['status'] === 'EXPIRED' ||
            (
                !empty($booking['expires_at']) &&
                strtotime($booking['expires_at']) <= time()
            )
        ) {
            (new \App\Models\AvailabilityModel())->expireStaleBookings();

            return redirect()->to('/checkout/berhasil/' . $invoiceNo);
        }

        $paymentMethod = $this->request->getPost('payment_method');

        if (!$paymentMethod) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Pilih metode pembayaran terlebih dahulu.');
        }

        /*
         * =====================================================
         * PEMBAYARAN INSTANT / SIMULASI
         * =====================================================
         *
         * Klik tombol bayar langsung dianggap berhasil.
         */

        $bookingModel->update($booking['id'], [
            'payment_status' => 'PAID',
            'status' => 'CONFIRMED',
            'paid_total' => $booking['grand_total'],
            'balance_due' => 0,
        ]);

        return redirect()->to('/track/' . $invoiceNo)
            ->with('success', 'Pembayaran berhasil! Booking Anda sudah dikonfirmasi.');
    }
}