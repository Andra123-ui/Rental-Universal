<?php

namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Hitung ulang total uang sebuah booking dari sumber aslinya.
 * Dipanggil di DALAM transaksi oleh BOOK-02 (pembayaran, biaya) dan BOOK-04 (reschedule).
 *
 *   grand_total = subtotal - discount_total + charge_total + deposit_total
 *   paid_total  = SUM(payments IN PAID) - SUM(payments OUT PAID)
 *   balance_due = max(0, grand_total - paid_total)
 *
 * subtotal, discount_total, dan deposit_total dibaca dari baris bookings (diisi pemanggil).
 * Payment tidak pernah dihapus atau diubah di sini.
 */
class BookingMoney
{
    /** Status biaya yang tidak ikut dihitung. */
    public const CHARGE_OFF = ['WAIVED', 'VOID'];

    private ConnectionInterface $db;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function recalc(int $id): void
    {
        $b = $this->db->table('bookings')->where('id', $id)->get()->getRowArray();
        $this->ensureLegacy($b);

        $charge = (float) ($this->db->table('booking_charges')->selectSum('amount', 's')
            ->where('booking_id', $id)->whereNotIn('status', self::CHARGE_OFF)->get()->getRow('s') ?? 0);

        $in = (float) ($this->db->table('payments')->selectSum('amount', 's')
            ->where('booking_id', $id)->where('direction', 'IN')->where('status', 'PAID')->get()->getRow('s') ?? 0);
        $out = (float) ($this->db->table('payments')->selectSum('amount', 's')
            ->where('booking_id', $id)->where('direction', 'OUT')->where('status', 'PAID')->get()->getRow('s') ?? 0);

        $paid    = max(0.0, $in - $out);
        $grand   = (float) $b['subtotal'] - (float) $b['discount_total'] + $charge + (float) $b['deposit_total'];
        $balance = max(0.0, $grand - $paid);

        if ($paid <= 0) {
            $ps = ($in > 0 && $out > 0) ? 'REFUNDED' : 'UNPAID';
        } elseif ($paid + 0.009 >= $grand) {
            $ps = 'PAID';
        } else {
            $ps = 'PARTIAL';
        }

        $this->db->table('bookings')->where('id', $id)->update([
            'charge_total'   => $charge,
            'grand_total'    => $grand,
            'paid_total'     => $paid,
            'balance_due'    => $balance,
            'payment_status' => $ps,
        ]);
    }

    /**
     * Booking lama menyimpan paid_total tanpa baris di tabel payments.
     * Buat satu baris saldo awal agar hitung ulang tidak menghapus pembayaran itu.
     */
    public function ensureLegacy(array $bk): void
    {
        $paid = (float) $bk['paid_total'];
        if ($paid <= 0) {
            return;
        }

        $has = $this->db->table('payments')->where('booking_id', $bk['id'])->where('status', 'PAID')->countAllResults();
        if ($has > 0) {
            return;
        }

        $this->db->table('payments')->insert([
            'booking_id'          => (int) $bk['id'],
            'payment_no'          => 'LEGACY-' . $bk['invoice_no'],
            'payment_type'        => 'RENTAL',
            'direction'           => 'IN',
            'method'              => 'ONLINE',
            'provider'            => null,
            'reference_no'        => null,
            'amount'              => $paid,
            'status'              => 'PAID',
            'paid_at'             => $bk['updated_at'] ?: date('Y-m-d H:i:s'),
            'received_by_user_id' => null,
            'notes'               => 'Saldo awal dari bookings.paid_total (sebelum pembayaran dicatat per transaksi)',
        ]);
    }
}