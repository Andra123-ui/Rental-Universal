<?php
$biz = site_business();
$bizName = $biz['business_name'] ?? 'Rental Universal';
$bizPhone = $biz['phone'] ?? '0812-0000-0000';
$bizEmail = $biz['email'] ?? 'halo@rentaluniversal.id';
$bizAddr = $biz['address'] ?? 'Alamat usaha Anda akan tampil di sini';
?>

<!-- Bar Statistik -->
<section class="stats-bar">
    <div class="wrap">
        <div class="stats-intro">
            <span class="eyebrow">Dipercaya Banyak Pelanggan</span>
            <h2>Sewa <span>lebih mudah</span>, transaksi lebih terpercaya</h2>
        </div>
    </div>
    <div class="wrap">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="num">500+</div>
                <div class="label">Produk & Jasa Tersedia</div>
            </div>
            <div class="stat-item">
                <div class="num">2000+</div>
                <div class="label">Booking Selesai</div>
            </div>
            <div class="stat-item">
                <div class="num">24/7</div>
                <div class="label">Cek Status via Invoice</div>
            </div>
        </div>
    </div>
</section>

<!-- Banner Gradient CTA -->
<section style="background:var(--paper);padding:56px 0;">
    <div class="wrap">
        <div class="gradient-banner">
            <div>
                <h3>Kenapa booking di sini lebih berbeda?</h3>
                <p>Ketersediaan real-time, invoice otomatis, dan tanpa ribet bikin akun untuk mulai transaksi.</p>
            </div>
            <a href="<?= base_url('/katalog') ?>" class="btn btn-light">Mulai Booking</a>
        </div>
    </div>
</section>

<footer id="kontak">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <a href="<?= base_url('/') ?>" class="logo">
                    <?= esc($bizName) ?>
                </a>
                <p style="margin-top:14px;">Sistem sewa barang dan jasa dalam satu platform — cek ketersediaan, booking,
                    dan kelola riwayat transaksi Anda sendiri.</p>
            </div>
            <div>
                <h4>Tautan</h4>
                <ul>
                    <li><a href="<?= base_url('/katalog') ?>">Katalog</a></li>
                    <li><a href="<?= base_url('/keranjang') ?>">Keranjang</a></li>
                    <li><a href="<?= base_url('/cek-booking') ?>">Cek Booking</a></li>
                    <li><a href="/account/login">Akun Saya</a></li>
                </ul>
            </div>
            <div>
                <h4>Hubungi Kami</h4>
                <ul>
                    <li>
                        <?= esc($bizAddr) ?>
                    </li>
                    <li>
                        <?= esc($bizPhone) ?>
                    </li>
                    <li>
                        <?= esc($bizEmail) ?>
                    </li>
                </ul>
            </div>
            <div>
                <h4>Tentang Kami</h4>
                <p style="margin:0;">Platform rental terpadu yang membantu Anda menyewa barang dan jasa apa saja dengan
                    proses yang cepat dan transparan.</p>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy;
                <?= date('Y') ?>
                <?= esc($bizName) ?>. Semua hak dilindungi.
            </span>
            <span>Dibangun dengan CodeIgniter 4</span>
        </div>
    </div>
</footer>
<script>
    document.querySelectorAll('.nav-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelector('.nav-links').classList.toggle('nav-open');
        });
    });
</script>
</body>

</html>