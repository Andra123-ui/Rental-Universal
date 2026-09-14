<?= $this->extend('layouts/customer') ?>

<?= $this->section('content') ?>

<div class="auth-icon-badge">
    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="5" y="11" width="14" height="10" rx="2" />
        <path d="M8 11V7a4 4 0 0 1 8 0v4" />
    </svg>
</div>

<h1 class="auth-title">Masuk ke Akun Anda</h1>
<p class="auth-subtitle">Masukkan nomor HP Anda. Kami akan mengirimkan kode OTP melalui WhatsApp untuk verifikasi.</p>

<?php if (session()->getFlashdata('notice')): ?>
    <div class="alert alert-info">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1B3A8C" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v5M12 16h.01" />
        </svg>
        <span>
            <?= esc(session()->getFlashdata('notice')) ?>
        </span>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                <li>
                    <?= esc($error) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= site_url('account/login/send-otp') ?>" method="post" class="auth-form" id="loginForm">
    <?= csrf_field() ?>
    <!-- Perbaikan di sini: menggunakan ?? untuk mencegah error Undefined variable -->
    <input type="hidden" name="return_url" value="<?= esc($returnUrl ?? current_url(), 'attr') ?>">

    <div class="form-group">
        <label for="phone">Nomor HP</label>
        <div class="phone-input-wrapper">
            <span class="phone-prefix">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="2" width="14" height="20" rx="2" />
                </svg>
                +62
            </span>
            <input type="tel" id="phone" name="phone" class="form-control" placeholder="8xxxxxxxxxx" inputmode="numeric"
                autocomplete="tel" value="<?= esc(old('phone'), 'attr') ?>" required autofocus>
        </div>
        <p class="form-help">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" />
                <path d="M9 12l2 2 4-4" />
            </svg>
            Kami tidak membagikan nomor Anda kepada pihak lain.
        </p>
    </div>

    <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
        <span id="btnText">Kirim Kode OTP</span>
    </button>
</form>

<div class="auth-links">
    <a href="<?= site_url('/') ?>" class="link-secondary">&larr; Kembali ke Beranda</a>
    <a href="<?= site_url('bantuan/login') ?>" class="link-secondary">Bantuan</a>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        document.getElementById('btnText').textContent = 'Mengirim...';
        btn.disabled = true;
    });
</script>

<?= $this->endSection() ?>