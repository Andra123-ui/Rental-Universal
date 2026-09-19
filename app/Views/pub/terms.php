<?= $this->include('partials/header') ?>

<div class="container my-5" style="max-width: 1000px;">
    <!-- Breadcrumb / Header Blueprint Info -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid #dee2e6;">
        <div>
            <span style="font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">RENTAL
                UNIVERSAL | UI / PAGE BLUEPRINT</span>
            <h2 style="font-weight: 700; color: #212529; margin-top: 0.25rem;">PUB-16 - Syarat & Ketentuan Rental</h2>
        </div>
        <div>
            <span
                style="background-color: #198754; color: white; padding: 0.35rem 0.65rem; border-radius: 0.375rem; font-size: 0.85rem; font-weight: 600;">MVP</span>
        </div>
    </div>

    <!-- Deskripsi Singkat Blueprint -->
    <div
        style="background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
        <p style="margin-bottom: 0; color: #495057;">
            <strong>Tujuan:</strong> Menjelaskan aturan penggunaan, pembayaran, pembatalan, kerusakan, keterlambatan,
            deposit, dan privasi secara jelas.
        </p>
    </div>

    <!-- Konten Komponen Syarat & Ketentuan -->
    <div
        style="background: #ffffff; border: 1px solid #e9ecef; border-radius: 0.5rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); margin-bottom: 1.5rem;">
        <div
            style="background-color: #212529; color: white; padding: 1rem 1.25rem; border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem;">
            <h5 style="margin: 0; font-size: 1.1rem; font-weight: 600;">Ketentuan Layanan Rental</h5>
        </div>
        <div style="padding: 1.5rem;">

            <!-- 1. General Terms -->
            <div style="margin-bottom: 1.5rem;">
                <h5 style="color: #0d6efd; font-weight: 700; margin-bottom: 0.5rem;">1. General Terms (Ketentuan Umum)
                </h5>
                <p style="color: #6c757d; margin-bottom: 0.5rem;">Hak dan kewajiban penyewa dan pemilik kendaraan
                    dijelaskan dengan bahasa yang mudah dipahami.</p>
                <ul style="color: #495057; padding-left: 1.25rem; margin-bottom: 0;">
                    <li style="margin-bottom: 0.25rem;">Penyewa wajib memiliki SIM yang sah dan masih berlaku.</li>
                    <li>Pemilik menjamin kondisi kendaraan dalam keadaan prima dan layak jalan.</li>
                </ul>
            </div>
            <hr style="border-top: 1px solid #dee2e6; margin: 1.5rem 0;">

            <!-- 2. Payment / Cancellation -->
            <div style="margin-bottom: 1.5rem;">
                <h5 style="color: #0d6efd; font-weight: 700; margin-bottom: 0.5rem;">2. Payment / Cancellation
                    (Pembayaran & Pembatalan)</h5>
                <p style="color: #6c757d; margin-bottom: 0.5rem;">Sesuai kebijakan bisnis perusahaan:</p>
                <ul style="color: #495057; padding-left: 1.25rem; margin-bottom: 0;">
                    <li style="margin-bottom: 0.25rem;"><strong>DP (Down Payment):</strong> Wajib dibayarkan minimal 30%
                        saat konfirmasi booking.</li>
                    <li style="margin-bottom: 0.25rem;"><strong>Pelunasan:</strong> Dilakukan selambat-lambatnya saat
                        serah terima kendaraan (pickup).</li>
                    <li><strong>Refund & Reschedule:</strong> Pembatalan H-3 mendapatkan pengembalian dana 50%,
                        penundaan jadwal (reschedule) gratis 1 kali.</li>
                </ul>
            </div>
            <hr style="border-top: 1px solid #dee2e6; margin: 1.5rem 0;">

            <!-- 3. Operational Terms -->
            <div style="margin-bottom: 1.5rem;">
                <h5 style="color: #0d6efd; font-weight: 700; margin-bottom: 0.5rem;">3. Operational Terms (Operasional)
                </h5>
                <p style="color: #6c757d; margin-bottom: 0.5rem;">Generik dan dapat dikustom:</p>
                <ul style="color: #495057; padding-left: 1.25rem; margin-bottom: 0;">
                    <li style="margin-bottom: 0.25rem;"><strong>Pickup / Return:</strong> Serah terima dan pengembalian
                        dilakukan sesuai jam operasional di pool atau lokasi yang disepakati.</li>
                    <li style="margin-bottom: 0.25rem;"><strong>Overtime:</strong> Keterlambatan pengembalian dikenakan
                        denda per jam sesuai tarif yang berlaku.</li>
                    <li><strong>Prohibited Use:</strong> Dilarang keras menggunakan kendaraan untuk balap liar, tindak
                        kejahatan, atau dipindahtangankan ke pihak ketiga.</li>
                </ul>
            </div>
            <hr style="border-top: 1px solid #dee2e6; margin: 1.5rem 0;">

            <!-- 4. Deposit / Damage -->
            <div style="margin-bottom: 1.5rem;">
                <h5 style="color: #0d6efd; font-weight: 700; margin-bottom: 0.5rem;">4. Deposit / Damage (Deposit &
                    Kerusakan)</h5>
                <p style="color: #6c757d; margin-bottom: 0.5rem;">Kapan deposit ditahan, dipotong, atau dikembalikan:
                </p>
                <ul style="color: #495057; padding-left: 1.25rem; margin-bottom: 0;">
                    <li style="margin-bottom: 0.25rem;">Deposit keamanan (refundable deposit) dibayarkan saat pickup dan
                        dikembalikan maksimal 1x24 jam setelah kendaraan dicek saat pengembalian.</li>
                    <li>Pemotongan deposit dilakukan jika terjadi kerusakan ringan, baret, atau kekurangan bahan bakar.
                    </li>
                </ul>
            </div>
            <hr style="border-top: 1px solid #dee2e6; margin: 1.5rem 0;">

            <!-- 5. Privacy -->
            <div>
                <h5 style="color: #0d6efd; font-weight: 700; margin-bottom: 0.5rem;">5. Privacy (Privasi & Keamanan
                    Data)</h5>
                <p style="color: #6c757d; margin-bottom: 0.5rem;">Sesuai kebutuhan legal:</p>
                <ul style="color: #495057; padding-left: 1.25rem; margin-bottom: 0;">
                    <li>Penggunaan data dan dokumen customer (KTP, SIM, KK) dijamin kerahasiaannya dan hanya digunakan
                        untuk keperluan verifikasi rental.</li>
                </ul>
            </div>

        </div>
    </div>

    <!-- Aksi / Tombol Utama & Catatan Validasi -->
    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
        <div
            style="flex: 1; min-width: 280px; background: #ffffff; border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 1.25rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
            <h6 style="font-weight: 700; color: #212529; margin-bottom: 1rem;">Aksi / Tombol Utama</h6>
            <a href="<?= base_url('booking') ?>"
                style="display: inline-block; background-color: #6c757d; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none; font-weight: 500;">
                ← Kembali ke Booking
            </a>
        </div>
        <div
            style="flex: 1; min-width: 280px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 1.25rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
            <h6 style="font-weight: 700; color: #212529; margin-bottom: 0.75rem;">Business Rules / Validasi</h6>
            <p style="font-size: 0.9rem; color: #6c757d; margin-bottom: 0;">
                Versi terms yang disetujui customer sebaiknya dapat dilacak jika kebutuhan legal tinggi; minimal simpan
                timestamp persetujuan/metadata di aplikasi pada tabel <code>bookings</code>.
            </p>
        </div>
    </div>
</div>

<?= $this->include('partials/footer') ?>