<?= $this->extend('layouts/customer') ?>

<?= $this->section('content') ?>

<div class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Masuk</h1>
        <p class="auth-subtitle">Masuk dengan nomor HP Anda. Kami akan mengirimkan kode OTP melalui WhatsApp.</p>

        <?php if (session()->getFlashdata('notice')): ?>
            <div class="alert alert-info">
                <?= esc(session()->getFlashdata('notice')) ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('account/login/send-otp') ?>" method="post" class="auth-form">
            <?= csrf_field() ?>

            <input type="hidden" name="return_url" value="<?= esc($returnUrl, 'attr') ?>">

            <div class="form-group">
                <label for="phone">Nomor HP</label>
                <div class="phone-input-wrapper">
                    <span class="phone-prefix">+62</span>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control"
                        placeholder="8xxxxxxxxxx"
                        inputmode="numeric"
                        autocomplete="tel"
                        value="<?= esc(old('phone'), 'attr') ?>"
                        required
                    >
                </div>
                <p class="form-help">
                    Nomor ini digunakan untuk mengirim kode OTP verifikasi via WhatsApp.
                    Kami tidak membagikan nomor Anda kepada pihak lain.
                </p>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Kirim OTP
            </button>
        </form>

        <div class="auth-links">
            <a href="<?= site_url('/') ?>" class="link-secondary">Kembali</a>
            <a href="<?= site_url('bantuan/login') ?>" class="link-secondary">Bantuan login</a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>