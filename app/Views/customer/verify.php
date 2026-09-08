<?= $this->extend('layouts/customer') ?>

<?= $this->section('content') ?>

<div class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Verifikasi Kode OTP</h1>
        <p class="auth-subtitle">
            Kode OTP telah dikirim ke <strong><?= esc($maskedPhone) ?></strong> via WhatsApp.
        </p>

        <?php if (session()->getFlashdata('notice')): ?>
            <div class="alert alert-info"><?= esc(session()->getFlashdata('notice')) ?></div>
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

        <form action="<?= site_url('account/verify') ?>" method="post" class="auth-form">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="code">Kode OTP (6 digit)</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    class="form-control otp-input"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    required
                    autofocus
                >
                <p class="form-help" id="countdown-text">
                    Kode berlaku selama <span id="countdown">--:--</span>
                </p>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Verifikasi</button>
        </form>

        <form action="<?= site_url('account/verify/resend') ?>" method="post" class="resend-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-link" id="resend-btn" disabled>
                Kirim ulang kode (<span id="resend-cooldown"><?= (int) $resendCooldown ?></span>s)
            </button>
        </form>

        <div class="auth-links">
            <a href="<?= site_url('account/verify/change-phone') ?>" class="link-secondary">Ganti nomor HP</a>
            <a href="<?= site_url('bantuan/login') ?>" class="link-secondary">Bantuan login</a>
        </div>
    </div>
</div>

<script>
(function () {
    // Countdown masa berlaku OTP
    var expiresAt = <?= $expiresAt ? 'new Date(' . json_encode($expiresAt) . '.replace(" ", "T"))' : 'null' ?>;
    var countdownEl = document.getElementById('countdown');

    function tickExpiry() {
        if (!expiresAt || !countdownEl) return;
        var diff = Math.max(0, Math.floor((expiresAt.getTime() - Date.now()) / 1000));
        var m = Math.floor(diff / 60);
        var s = diff % 60;
        countdownEl.textContent = m + ':' + String(s).padStart(2, '0');
        if (diff <= 0) {
            countdownEl.textContent = 'kadaluarsa';
            return;
        }
        setTimeout(tickExpiry, 1000);
    }
    tickExpiry();

    // Cooldown tombol resend
    var cooldown = <?= (int) $resendCooldown ?>;
    var resendBtn = document.getElementById('resend-btn');
    var resendCooldownEl = document.getElementById('resend-cooldown');

    function tickCooldown() {
        cooldown -= 1;
        if (cooldown <= 0) {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Kirim ulang kode';
            return;
        }
        resendCooldownEl.textContent = cooldown;
        setTimeout(tickCooldown, 1000);
    }
    setTimeout(tickCooldown, 1000);
})();
</script>

<?= $this->endSection() ?>