<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\CatalogItemModel;

class Tracking extends BaseController
{
    public function index(string $invoiceNo)
    {
        // Expire booking yang sudah melewati batas pembayaran
        (new \App\Models\AvailabilityModel())->expireStaleBookings();

        $bookingModel = new BookingModel();

        $booking = $bookingModel
            ->where('invoice_no', $invoiceNo)
            ->first();

        if (!$booking) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Booking tidak ditemukan.'
            );
        }

        // Ambil item booking
        $bookingItemModel = new BookingItemModel();

        $items = $bookingItemModel
            ->where('booking_id', $booking['id'])
            ->findAll();

        return view('pub/track', [
            'booking' => $booking,
            'items' => $items,
        ]);
    }
}