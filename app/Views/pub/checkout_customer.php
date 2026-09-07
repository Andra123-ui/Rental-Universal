<?php
/** @var array|null $loggedCustomer */
?>
<?= view('partials/header', ['title' => 'Data Penyewa']) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / <a
                href="<?= base_url('/keranjang') ?>">Keranjang</a> / Data Penyewa</div>
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
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <div>
                        <?= esc($err) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="detail-grid">
            <div>
                <?php if ($loggedCustomer): ?>
                    <div class="alert-box alert-success">
                        Anda masuk sebagai <strong>
                            <?= esc($loggedCustomer['name']) ?>
                        </strong> (
                        <?= esc($loggedCustomer['phone']) ?>).
                        Data di bawah otomatis terisi dari akun Anda.
                    </div>
                <?php else: ?>
                    <div class="alert-box alert-info">
                        Anda dapat memesan sebagai <strong>tamu</strong> tanpa login, atau
                        <a href="/account/login?redirect=/checkout" style="font-weight:600;color:var(--accent);">masuk
                            dengan OTP WhatsApp</a>
                        supaya riwayat booking otomatis tersimpan di akun Anda.
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('/checkout/simpan-customer') ?>">
                    <?= csrf_field() ?>

                    <div class="field-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" required
                            value="<?= esc(old('name', $loggedCustomer['name'] ?? '')) ?>">
                    </div>
                    <div class="field-group">
                        <label for="phone">Nomor HP (WhatsApp aktif)</label>
                        <input type="tel" id="phone" name="phone" required placeholder="08xxxxxxxxxx"
                            value="<?= esc(old('phone', $loggedCustomer['phone'] ?? '')) ?>">
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

                    <label
                        style="display:flex;gap:8px;align-items:flex-start;font-size:0.85rem;color:var(--muted);margin:16px 0;">
                        <input type="checkbox" required style="margin-top:3px;">
                        Saya menyetujui <a href="<?= base_url('/terms') ?>" style="color:var(--accent);">syarat &
                            ketentuan rental</a> dan kebijakan privasi.
                    </label>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Lanjut ke
                        Fulfillment</button>
                </form>
            </div>

            <div class="cart-summary">
                <h3 style="font-size:1.05rem;margin-bottom:12px;">Kenapa isi data ini?</h3>
                <p style="font-size:0.88rem;color:var(--muted);line-height:1.7;">
                    Data ini dipakai untuk konfirmasi booking dan dihubungi lewat WhatsApp bila diperlukan.
                    Anda tidak perlu membuat akun/kata sandi — cukup simpan nomor invoice yang akan diberikan
                    setelah booking selesai untuk mengecek status kapan saja.
                </p>
            </div>
        </div>
    </div>
</section>

<?= view('partials/footer') ?>