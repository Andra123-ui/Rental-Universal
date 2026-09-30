<?php
/**
 * @var array|null $loggedCustomer
 */
$isLoggedIn = !empty($loggedCustomer);
?>
<?= view('partials/header', ['title' => 'Data Penyewa']) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / <a href="<?= base_url('/cart') ?>">Keranjang</a>
            / Data Penyewa</div>
        <h1>Data Penyewa</h1>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="step-indicator">
            <div class="s-item done">1. Keranjang</div>
            <div class="s-item active">2. Data Penyewa</div>
            <div class="s-item">3. Fulfillment</div>
            <div class="s-item">4. Review</div>
            <div class="s-item">5. Selesai</div>
        </div>

        <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert-box alert-warn">
            <?php foreach ((array) session()->getFlashdata('errors') as $err): ?>
            <div>
                <?= esc($err) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="detail-grid">
            <div>

                <?php if ($isLoggedIn): ?>

                <!-- SUDAH LOGIN: langsung tampilkan data akun, tidak perlu pilih tab -->
                <div class="alert-box alert-success">
                    <strong>Anda masuk sebagai
                        <?= esc($loggedCustomer['name']) ?>
                    </strong><br>
                    <?= esc($loggedCustomer['phone']) ?> &middot; Riwayat booking Anda akan tersimpan otomatis di akun
                    ini.
                </div>

                <form method="post" action="<?= base_url('/checkout/simpan-customer') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="auth_mode" value="account">

                    <div class="field-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" required
                            value="<?= esc(old('name', $loggedCustomer['name'])) ?>">
                    </div>
                    <div class="field-group">
                        <label for="phone">Nomor HP</label>
                        <input type="tel" id="phone" name="phone" value="<?= esc($loggedCustomer['phone']) ?>" readonly
                            style="background:var(--paper);color:var(--muted);">
                        <p style="font-size:0.78rem;color:var(--muted);margin-top:4px;">Nomor HP terverifikasi, tidak
                            dapat diubah di sini.</p>
                    </div>
                    <div class="field-group">
                        <label for="email">Email (opsional)</label>
                        <input type="email" id="email" name="email"
                            value="<?= esc(old('email', $loggedCustomer['email'] ?? '')) ?>">
                    </div>
                    <div class="field-group">
                        <label for="notes">Catatan / Permintaan Khusus</label>
                        <textarea id="notes" name="notes" rows="3"><?= esc(old('notes')) ?></textarea>
                    </div>

                    <?= view('pub/_terms_checkbox') ?>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Lanjut ke
                        Fulfillment</button>
                </form>

                <a href="<?= site_url('account/logout') ?>"
                    style="display:inline-block;margin-top:14px;font-size:0.82rem;color:var(--muted);">Bukan Anda?
                    Keluar dari akun</a>

                <?php else: ?>

                <!-- BELUM LOGIN: wajib pilih salah satu dari 2 jalur -->
                <div class="checkout-tabs">
                    <button type="button" class="checkout-tab active" data-tab="guest">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        Isi Data Sebagai Tamu
                    </button>
                    <button type="button" class="checkout-tab" data-tab="login">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="6" y="2" width="12" height="20" rx="2" />
                            <path d="M9 18h6" />
                        </svg>
                        Masuk dengan OTP
                    </button>
                </div>

                <!-- TAB: GUEST -->
                <div class="checkout-tab-panel" data-panel="guest">
                    <div class="alert-box alert-info">
                        Anda bisa booking tanpa akun. Cukup isi data di bawah, dan simpan nomor invoice untuk mengecek
                        status booking kapan saja.
                    </div>

                    <form method="post" action="<?= base_url('/checkout/simpan-customer') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="auth_mode" value="guest">

                        <div class="field-group">
                            <label for="name">Nama Lengkap <span style="color:#B91C1C;">*</span></label>
                            <input type="text" id="name" name="name" required minlength="3"
                                value="<?= esc(old('name')) ?>">
                        </div>
                        <div class="field-group">
                            <label for="phone">Nomor HP (WhatsApp aktif) <span style="color:#B91C1C;">*</span></label>
                            <input type="tel" id="phone" name="phone" required minlength="9" placeholder="08xxxxxxxxxx"
                                value="<?= esc(old('phone')) ?>">
                        </div>
                        <div class="field-group">
                            <label for="email">Email (opsional)</label>
                            <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>">
                        </div>
                        <div class="field-group">
                            <label for="notes">Catatan / Permintaan Khusus</label>
                            <textarea id="notes" name="notes" rows="3"><?= esc(old('notes')) ?></textarea>
                        </div>

                        <?= view('pub/_terms_checkbox') ?>

                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Lanjut
                            ke Fulfillment</button>
                    </form>
                </div>

                <!-- TAB: LOGIN -->
                <div class="checkout-tab-panel" data-panel="login" style="display:none;">
                    <div class="alert-box alert-info">
                        Masuk dengan nomor HP Anda — kode OTP akan dikirim lewat WhatsApp. Riwayat booking otomatis
                        tersimpan di akun Anda, dan Anda tidak perlu isi ulang data di booking berikutnya.
                    </div>

                    <a href="<?= site_url('account/login') ?>?return_url=<?= urlencode('/checkout') ?>"
                        class="btn btn-primary" style="width:100%;justify-content:center;">
                        Masuk dengan OTP WhatsApp
                    </a>

                    <p style="text-align:center;font-size:0.82rem;color:var(--muted);margin-top:14px;">
                        Setelah berhasil masuk, Anda akan otomatis dibawa kembali ke halaman ini.
                    </p>
                </div>

                <?php endif; ?>

            </div>

            <div class="cart-summary">
                <h3 style="font-size:1.05rem;margin-bottom:12px;">Kenapa isi data ini wajib?</h3>
                <p style="font-size:0.88rem;color:var(--muted);line-height:1.7;">
                    Data ini dipakai untuk konfirmasi booking dan dihubungi lewat WhatsApp bila diperlukan.
                    Anda dapat memilih <strong>booking sebagai tamu</strong> (tanpa akun, cukup simpan nomor invoice)
                    atau <strong>masuk dengan OTP</strong> agar seluruh riwayat booking tersimpan otomatis di satu
                    tempat.
                </p>
            </div>
        </div>
    </div>
</section>

<style>
.checkout-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 22px;
    border-bottom: 1px solid var(--line);
}

.checkout-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 12px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    transition: color .2s ease, border-color .2s ease;
}

.checkout-tab svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
}

.checkout-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
}

.checkout-tab:hover:not(.active) {
    color: var(--ink);
}
</style>

<script>
document.querySelectorAll('.checkout-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const target = btn.dataset.tab;

        document.querySelectorAll('.checkout-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.checkout-tab-panel').forEach(panel => {
            panel.style.display = (panel.dataset.panel === target) ? 'block' : 'none';
        });
    });
});
</script>

<?= view('partials/footer') ?>