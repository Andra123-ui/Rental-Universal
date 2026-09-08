<?php
$biz      = site_business();
$bizName  = $biz['business_name'] ?? 'Rental Universal';
$bizPhone = $biz['phone'] ?? '0812-0000-0000';
$bizEmail = $biz['email'] ?? 'halo@rentaluniversal.id';
$bizAddr  = $biz['address'] ?? 'Alamat usaha Anda akan tampil di sini';
?>
<footer id="kontak">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <a href="<?= base_url('/') ?>" class="logo"><?= esc($bizName) ?></a>
                <p style="margin-top:14px;">Sistem sewa barang dan jasa dalam satu platform — cek ketersediaan, booking,
                    dan kelola riwayat transaksi Anda sendiri.</p>
            </div>
            <div>
                <h4>Tautan</h4>
                <ul>
                    <li><a href="<?= base_url('/katalog') ?>">Katalog</a></li>
                    <li><a href="<?= base_url('/keranjang') ?>">Keranjang</a></li>
                    <li><a href="/account/login">Akun Saya</a></li>
                </ul>
            </div>
            <div>
                <h4>Kontak</h4>
                <ul>
                    <li><?= esc($bizAddr) ?></li>
                    <li><?= esc($bizPhone) ?></li>
                    <li><?= esc($bizEmail) ?></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= esc($bizName) ?>. Semua hak dilindungi.</span>
            <span>Dibangun dengan CodeIgniter 4</span>
        </div>
    </div>
</footer>
<script>
document.querySelectorAll('.nav-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('nav-open');
    });
});
</script>
</body>

</html>