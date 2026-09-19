<?php

namespace App\Controllers;

class Help extends BaseController
{
    public function index()
    {
        $biz = site_business();

        // FAQ statis — kalau nanti ada SystemSettingModel/tabel khusus,
        // ganti bagian ini dengan query supaya bisa diedit dari admin.
        $faqs = [
            [
                'q' => 'Bagaimana cara booking di sini?',
                'a' => 'Jelajahi katalog, pilih item, cek ketersediaan tanggal, lalu tambahkan ke keranjang. Lanjutkan ke checkout dengan mengisi data penyewa (atau login OTP WhatsApp), pilih metode pemenuhan, review pesanan, lalu konfirmasi booking. Anda akan mendapat nomor invoice untuk memantau status.',
            ],
            [
                'q' => 'Apakah saya wajib membuat akun untuk booking?',
                'a' => 'Tidak wajib. Anda bisa booking sebagai tamu dan mengecek status transaksi kapan saja menggunakan nomor invoice. Jika ingin riwayat booking tersimpan otomatis, Anda bisa login dengan OTP WhatsApp — tanpa perlu mengingat kata sandi.',
            ],
            [
                'q' => 'Metode pembayaran apa saja yang tersedia?',
                'a' => 'Tergantung kebijakan setiap item, Anda bisa membayar DP (uang muka), pelunasan penuh, atau deposit/jaminan yang bersifat refundable. Rincian metode pembayaran (transfer manual atau gateway) akan ditampilkan saat proses checkout.',
            ],
            [
                'q' => 'Bagaimana jika saya ingin mengubah jadwal booking?',
                'a' => 'Hubungi kami melalui WhatsApp atau telepon dengan menyertakan nomor invoice Anda. Tim kami akan membantu mengecek ketersediaan jadwal baru sebelum melakukan perubahan.',
            ],
            [
                'q' => 'Bagaimana kebijakan pembatalan dan deposit?',
                'a' => 'Kebijakan pembatalan, keterlambatan, dan pengembalian deposit dijelaskan lengkap di halaman Syarat & Ketentuan. Deposit yang bersifat refundable akan dikembalikan sesuai kondisi barang saat pengembalian.',
            ],
            [
                'q' => 'Berapa lama batas waktu pembayaran setelah booking dibuat?',
                'a' => 'Umumnya booking akan otomatis kedaluwarsa dalam 24 jam apabila belum ada pembayaran yang masuk. Batas waktu pasti akan tertera di halaman detail booking Anda.',
            ],
        ];

        // Jam layanan — statis untuk saat ini, bisa dipindah ke system_settings nanti
        $serviceHours = [
            ['day' => 'Senin – Jumat', 'time' => '10.00 – 20.00'],
            ['day' => 'Sabtu – Minggu', 'time' => '10.00 – 18.00'],
        ];

        return view('pub/help', [
            'biz' => $biz,
            'faqs' => $faqs,
            'serviceHours' => $serviceHours,
        ]);
    }
}