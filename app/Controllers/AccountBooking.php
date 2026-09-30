<?php

namespace App\Controllers;

use App\Models\AvailabilityModel;
use App\Models\BookingModel;

class AccountBooking extends BaseController
{
    public function index()
    {
        $customerId = $this->sessionCustomerId();
        if (!$customerId) {
            return redirect()->to('/account/login')->with('error', 'Silakan masuk untuk melihat booking Anda.');
        }

        // Lepas hold yang sudah lewat batas bayar supaya status di daftar akurat
        (new AvailabilityModel())->expireStaleBookings();

        // customer_id HANYA dari session, bukan dari input browser
        $bookings = (new BookingModel())
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $itemsByBooking = [];
        if (!empty($bookings)) {
            $rows = \Config\Database::connect()->table('booking_items')
                ->select('booking_id, item_name_snapshot, quantity')
                ->whereIn('booking_id', array_column($bookings, 'id'))
                ->get()
                ->getResultArray();
            foreach ($rows as $r) {
                $itemsByBooking[$r['booking_id']][] = $r;
            }
        }

        $now = time();
        $freezeLeft = 0;

        foreach ($bookings as &$b) {
            $b['items'] = $itemsByBooking[$b['id']] ?? [];
            $b['seconds_left'] = null;

            if ($b['status'] === 'PENDING' && $b['payment_status'] === 'UNPAID' && !empty($b['expires_at'])) {
                $b['seconds_left'] = max(0, strtotime($b['expires_at']) - $now);
            }

            // Freeze dihitung dari booking EXPIRED terbaru (120 detik = Checkout::COOLDOWN_SECONDS)
            if ($freezeLeft === 0 && $b['status'] === 'EXPIRED' && !empty($b['expires_at'])) {
                $freezeLeft = max(0, strtotime($b['expires_at']) + 120 - $now);
            }
        }
        unset($b);

        return view('pub/akun_booking', [
            'bookings' => $bookings,
            'freezeLeft' => $freezeLeft,
        ]);
    }

    private function sessionCustomerId(): int
    {
        $sess = session()->get('customer');
        if (is_array($sess)) {
            return (int) ($sess['customer_id'] ?? $sess['id'] ?? 0);
        }
        return (int) (session()->get('customer_id') ?? 0);
    }
}