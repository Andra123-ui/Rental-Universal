<?php

namespace App\Controllers;

class Terms extends BaseController
{
    /**
     * Versi terms saat ini. WAJIB dinaikkan setiap kali isi terms berubah
     * secara substantif, supaya persetujuan customer di masa lalu tetap
     * bisa dilacak berlaku untuk versi berapa (lihat business rule PUB-16).
     */
    public const CURRENT_VERSION = '1.0';
    public const LAST_UPDATED = '2026-09-01';

    public function index()
    {
        $biz = site_business();

        $sections = [
            [
                'id' => 'general',
                'title' => 'Ketentuan Umum',
                'content' => [
                    'Dengan melakukan booking di platform ini, Anda (selanjutnya disebut "Penyewa") menyetujui seluruh syarat dan ketentuan yang tercantum di halaman ini.',
                    'Penyewa wajib memberikan data diri yang benar dan dapat dihubungi (nama dan nomor HP aktif) pada saat proses booking.',
                    'Pemilik/pengelola platform (selanjutnya disebut "Penyedia") berhak menolak atau membatalkan booking apabila ditemukan data yang tidak valid, atau apabila item yang dipesan ternyata tidak dapat dipenuhi karena force majeure.',
                    'Penyewa bertanggung jawab penuh atas barang/jasa yang disewa selama masa periode sewa berlangsung, sesuai dengan detail yang tercantum pada invoice booking.',
                ],
            ],
            [
                'id' => 'payment',
                'title' => 'Pembayaran & Pembatalan',
                'content' => [
                    'Pembayaran dapat dilakukan melalui transfer manual atau metode pembayaran lain yang tersedia pada halaman checkout, sesuai dengan kebijakan masing-masing item.',
                    'Booking yang belum menerima pembayaran (DP maupun pelunasan) dalam batas waktu yang tertera pada halaman detail booking akan kedaluwarsa secara otomatis dan dianggap batal.',
                    'Pembatalan oleh Penyewa: pengembalian dana (refund) mengikuti kebijakan pembatalan yang berlaku pada saat booking dibuat. Semakin dekat dengan tanggal mulai sewa, semakin besar kemungkinan potongan biaya pembatalan.',
                    'Reschedule (perubahan jadwal) dapat diajukan melalui WhatsApp/telepon dengan menyertakan nomor invoice, dan akan dicek ulang ketersediaannya sebelum disetujui.',
                    'Penyedia berhak membatalkan booking secara sepihak apabila ditemukan indikasi kecurangan, dengan pengembalian dana penuh kepada Penyewa.',
                ],
            ],
            [
                'id' => 'operational',
                'title' => 'Ketentuan Operasional',
                'content' => [
                    'Waktu pengambilan (pickup) dan pengembalian (return) mengikuti jadwal yang telah disepakati pada saat booking, sebagaimana tercantum pada invoice.',
                    'Keterlambatan pengembalian (overtime) akan dikenakan biaya tambahan sesuai tarif yang berlaku, dihitung per satuan waktu (jam/hari) keterlambatan.',
                    'Barang/jasa yang disewa hanya boleh digunakan sesuai peruntukan yang wajar dan legal. Dilarang menggunakan barang sewaan untuk kegiatan ilegal, berbahaya, atau di luar kesepakatan awal.',
                    'Penyewa dilarang menyewakan kembali (sub-rental) barang yang telah disewa kepada pihak ketiga tanpa izin tertulis dari Penyedia.',
                ],
            ],
            [
                'id' => 'deposit',
                'title' => 'Deposit & Kerusakan',
                'content' => [
                    'Sebagian item mewajibkan deposit/jaminan yang bersifat refundable (dapat dikembalikan), dengan nominal yang tertera pada halaman detail item.',
                    'Deposit akan dikembalikan penuh setelah barang/jasa dikembalikan dalam kondisi baik, sesuai dengan kondisi awal saat penyerahan (dicatat pada proses handover).',
                    'Apabila ditemukan kerusakan, kehilangan, atau kondisi yang tidak sesuai saat pengembalian, Penyedia berhak memotong sebagian atau seluruh deposit untuk menutup biaya perbaikan/penggantian, dengan bukti (foto/catatan) yang akan diinformasikan kepada Penyewa.',
                    'Apabila biaya kerusakan melebihi nominal deposit yang ditahan, Penyewa wajib melunasi kekurangannya sesuai dengan estimasi biaya yang disepakati.',
                ],
            ],
            [
                'id' => 'privacy',
                'title' => 'Privasi Data',
                'content' => [
                    'Data pribadi Penyewa (nama, nomor HP, email, alamat) yang dikumpulkan pada saat booking hanya digunakan untuk keperluan transaksi, konfirmasi, dan komunikasi terkait booking yang bersangkutan.',
                    'Penyedia tidak akan membagikan data pribadi Penyewa kepada pihak ketiga untuk kepentingan pemasaran tanpa persetujuan eksplisit dari Penyewa.',
                    'Dokumen identitas (KTP/SIM/dsb) yang diunggah, apabila diperlukan untuk item tertentu, disimpan secara aman dan hanya dapat diakses oleh staf internal yang berwenang.',
                    'Penyewa berhak meminta penghapusan data pribadinya dari sistem, sepanjang tidak ada kewajiban transaksi yang masih berjalan atau kewajiban hukum yang mengharuskan data tersebut disimpan.',
                ],
            ],
        ];

        return view('pub/terms', [
            'biz' => $biz,
            'sections' => $sections,
            'version' => self::CURRENT_VERSION,
            'lastUpdated' => self::LAST_UPDATED,
        ]);
    }
}