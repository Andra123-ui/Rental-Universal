<?= $this->extend('layouts/auth_glass') ?>

<?= $this->section('content') ?>

<div class="glass-auth-card">

    <!-- Top Shield / Lock Icon Badge -->
    <div class="auth-icon-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
    </div>

    <!-- Title & Subtitle -->
    <h1 class="auth-title">Verifikasi Nomor WhatsApp</h1>
    <p class="auth-subtitle mb-2">
        Kode verifikasi 6 digit telah dikirim ke nomor:
    </p>
    <div class="mb-3">
        <span class="otp-masked-badge">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" class="text-success">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            <?= esc($maskedPhone ?? '+62 8xx-****-xxxx') ?>
        </span>
    </div>

    <!-- Verification Form -->
    <form action="<?= site_url('account/verify') ?>" method="post" id="verifyOtpForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="code" id="hiddenOtpCode" value="">

        <!-- 6 Separate Numeric Input Boxes -->
        <div class="otp-boxes-grid" id="otpBoxes">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box-digit" data-index="0" autocomplete="off" autofocus>
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box-digit" data-index="1" autocomplete="off">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box-digit" data-index="2" autocomplete="off">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box-digit" data-index="3" autocomplete="off">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box-digit" data-index="4" autocomplete="off">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-box-digit" data-index="5" autocomplete="off">
        </div>

        <!-- Expiry Timer Countdown -->
        <div>
            <div class="otp-timer-badge">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Kode berlaku selama <strong id="otpCountdownTimer">05:00</strong></span>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-gradient-auth" id="btnVerifySubmit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span id="btnVerifyText">Verifikasi & Masuk</span>
        </button>
    </form>

    <!-- Resend & Change Phone Actions -->
    <div class="otp-secondary-actions">
        <a href="<?= site_url('account/verify/change-phone') ?>" class="btn-link-action">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>Ganti Nomor</span>
        </a>

        <button type="button" class="btn-link-action" id="btnResendOtp" disabled>
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 4v6h-6"></path>
                <path d="M1 20v-6h6"></path>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            <span id="resendBtnText">Kirim Ulang Kode (<span id="resendCountdown"><?= (int) ($resendCooldown ?? 60) ?></span>s)</span>
        </button>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var verifyForm = document.getElementById('verifyOtpForm');
    var hiddenCode = document.getElementById('hiddenOtpCode');
    var btnSubmit = document.getElementById('btnVerifySubmit');
    var btnText = document.getElementById('btnVerifyText');
    var btnResend = document.getElementById('btnResendOtp');
    var resendText = document.getElementById('resendBtnText');
    var resendCountEl = document.getElementById('resendCountdown');
    var countdownEl = document.getElementById('otpCountdownTimer');
    var digitBoxes = Array.from(document.querySelectorAll('.otp-box-digit'));

    // 1. Inisialisasi Countdown Masa Berlaku OTP (5 Menit)
    var expiresAtStr = <?= $expiresAt ? json_encode($expiresAt) : 'null' ?>;
    var expiresAtDate = expiresAtStr ? new Date(expiresAtStr.replace(" ", "T")) : new Date(Date.now() + 300000);
    var expiryInterval = null;

    function updateExpiryTimer() {
        var now = Date.now();
        var diffSec = Math.max(0, Math.floor((expiresAtDate.getTime() - now) / 1000));
        var m = Math.floor(diffSec / 60);
        var s = diffSec % 60;
        countdownEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');

        if (diffSec <= 0) {
            countdownEl.textContent = 'Kedaluwarsa';
            countdownEl.style.color = '#EF4444';
            clearInterval(expiryInterval);
        }
    }
    updateExpiryTimer();
    expiryInterval = setInterval(updateExpiryTimer, 1000);

    // 2. Inisialisasi Cooldown Kirim Ulang OTP (60 Detik)
    var cooldownSec = <?= (int) ($resendCooldown ?? 60) ?>;
    var cooldownInterval = null;

    function startCooldownTimer(seconds) {
        cooldownSec = seconds;
        btnResend.disabled = true;
        resendCountEl.textContent = cooldownSec;

        if (cooldownInterval) clearInterval(cooldownInterval);
        cooldownInterval = setInterval(function () {
            cooldownSec -= 1;
            if (cooldownSec <= 0) {
                clearInterval(cooldownInterval);
                btnResend.disabled = false;
                resendText.innerHTML = 'Kirim Ulang Kode';
            } else {
                resendCountEl.textContent = cooldownSec;
            }
        }, 1000);
    }
    startCooldownTimer(cooldownSec);

    // 3. Logic 6 Kotak Digit OTP
    function getFullCode() {
        return digitBoxes.map(function (box) { return box.value.trim(); }).join('');
    }

    function syncHiddenCode() {
        var code = getFullCode();
        hiddenCode.value = code;
        return code;
    }

    function clearBoxesError() {
        digitBoxes.forEach(function (box) {
            box.classList.remove('is-invalid');
        });
    }

    digitBoxes.forEach(function (box, idx) {
        // Event input digit
        box.addEventListener('input', function (e) {
            clearBoxesError();
            var val = this.value.replace(/\D/g, '');
            if (val.length > 0) {
                this.value = val.charAt(val.length - 1);
                // Pindah otomatis ke kotak berikutnya
                if (idx < 5) {
                    digitBoxes[idx + 1].focus();
                    digitBoxes[idx + 1].select();
                }
            } else {
                this.value = '';
            }
            syncHiddenCode();

            // Auto-submit bila ke-6 digit sudah terisi
            if (getFullCode().length === 6) {
                verifyForm.dispatchEvent(new Event('submit'));
            }
        });

        // Event backspace & navigasi tombol panah
        box.addEventListener('keydown', function (e) {
            clearBoxesError();
            if (e.key === 'Backspace') {
                if (this.value === '' && idx > 0) {
                    digitBoxes[idx - 1].focus();
                    digitBoxes[idx - 1].value = '';
                    syncHiddenCode();
                }
            } else if (e.key === 'ArrowLeft' && idx > 0) {
                digitBoxes[idx - 1].focus();
            } else if (e.key === 'ArrowRight' && idx < 5) {
                digitBoxes[idx + 1].focus();
            }
        });

        // Event paste 6 digit langsung
        box.addEventListener('paste', function (e) {
            e.preventDefault();
            clearBoxesError();
            var pasted = (e.clipboardData || window.clipboardData).getData('text');
            var digitsOnly = pasted.replace(/\D/g, '').slice(0, 6);
            if (digitsOnly.length > 0) {
                digitsOnly.split('').forEach(function (d, i) {
                    if (digitBoxes[i]) {
                        digitBoxes[i].value = d;
                    }
                });
                var targetIdx = Math.min(5, digitsOnly.length - 1);
                digitBoxes[targetIdx].focus();
                syncHiddenCode();

                if (digitsOnly.length === 6) {
                    verifyForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    });

    // 4. Submit Verifikasi OTP (AJAX)
    verifyForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var code = syncHiddenCode();

        // Cek kedaluwarsa visual
        if (countdownEl.textContent === 'Kedaluwarsa') {
            showAuthPopup('warning', 'Kode OTP Kedaluwarsa', 'Kode verifikasi telah habis masa berlakunya. Silakan minta kode baru.', 'Kirim Ulang', function () {
                if (!btnResend.disabled) {
                    btnResend.click();
                }
            });
            return;
        }

        // Kondisi 7: Kode belum 6 digit atau bukan angka
        if (code.length !== 6 || !/^\d{6}$/.test(code)) {
            digitBoxes.forEach(function (box) { box.classList.add('is-invalid'); });
            showAuthPopup('error', 'Kode OTP Tidak Sesuai', 'Kode yang Anda masukkan salah. Silakan periksa kembali.', 'Perbaiki', function () {
                digitBoxes[0].focus();
            });
            return;
        }

        btnSubmit.disabled = true;
        btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memverifikasi...';

        var formData = new FormData(verifyForm);
        formData.append('ajax', '1');

        fetch(verifyForm.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return { status: response.status, ok: response.ok, data: data };
            }).catch(function () {
                return { status: response.status, ok: response.ok, data: null };
            });
        })
        .then(function (res) {
            btnSubmit.disabled = false;
            btnText.textContent = 'Verifikasi & Masuk';

            if (!res.data) {
                // Kondisi 10: Gangguan server
                showAuthPopup('error', 'Gangguan Sistem', 'Terjadi kendala pada server. Silakan coba beberapa saat lagi.', 'Tutup');
                return;
            }

            var d = res.data;

            if (res.ok && d.status === 'success') {
                // Kondisi 9: Login berhasil
                showAuthPopup('success', d.title || 'Login Berhasil', d.message || 'Mengalihkan ke dashboard...', 'Lanjut', function () {
                    window.location.href = d.redirect || '<?= site_url('account') ?>';
                });

                // Auto redirect setelah 1.2 detik
                setTimeout(function () {
                    window.location.href = d.redirect || '<?= site_url('account') ?>';
                }, 1200);
            } else {
                digitBoxes.forEach(function (box) { box.classList.add('is-invalid'); });

                if (d.code === 'otp_expired') {
                    // Kondisi 8: Kode kedaluwarsa
                    showAuthPopup('warning', d.title || 'Kode OTP Kedaluwarsa', d.message || 'Kode verifikasi telah habis masa berlakunya. Silakan minta kode baru.', 'Kirim Ulang', function () {
                        if (!btnResend.disabled) {
                            btnResend.click();
                        }
                    });
                } else if (d.code === 'session_expired') {
                    showAuthPopup('error', d.title || 'Sesi Berakhir', d.message, 'Ke Halaman Login', function () {
                        window.location.href = d.redirect || '<?= site_url('account/login') ?>';
                    });
                } else {
                    // Kondisi 7: Kode salah
                    showAuthPopup('error', d.title || 'Kode OTP Tidak Sesuai', d.message || 'Kode yang Anda masukkan salah. Silakan periksa kembali.', 'Perbaiki', function () {
                        digitBoxes.forEach(function (b) { b.value = ''; });
                        digitBoxes[0].focus();
                        syncHiddenCode();
                    });
                }
            }
        })
        .catch(function (err) {
            btnSubmit.disabled = false;
            btnText.textContent = 'Verifikasi & Masuk';
            // Kondisi 10: Gangguan sistem
            showAuthPopup('error', 'Gangguan Sistem', 'Terjadi kendala pada server. Silakan coba beberapa saat lagi.', 'Tutup');
        });
    });

    // 5. Kirim Ulang OTP (AJAX)
    btnResend.addEventListener('click', function () {
        if (btnResend.disabled) return;

        btnResend.disabled = true;
        resendText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...';

        var formData = new FormData();
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        formData.append('ajax', '1');

        fetch('<?= site_url('account/verify/resend') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return { status: response.status, ok: response.ok, data: data };
            }).catch(function () {
                return { status: response.status, ok: response.ok, data: null };
            });
        })
        .then(function (res) {
            if (!res.data) {
                btnResend.disabled = false;
                resendText.innerHTML = 'Kirim Ulang Kode';
                showAuthPopup('error', 'Gangguan Sistem', 'Terjadi kendala pada server. Silakan coba beberapa saat lagi.', 'Tutup');
                return;
            }

            var d = res.data;

            if (res.ok && d.status === 'success') {
                // Kondisi 5: Kode OTP Berhasil Dikirim
                showAuthPopup('success', d.title || 'Kode OTP Berhasil Dikirim', d.message || 'Kode OTP telah dikirim ke nomor WhatsApp Anda. Silakan periksa pesan masuk.', 'Mengerti');

                // Reset timer kedaluwarsa 5 menit
                expiresAtDate = new Date(Date.now() + ((d.expires_in || 300) * 1000));
                countdownEl.style.color = '';
                if (expiryInterval) clearInterval(expiryInterval);
                updateExpiryTimer();
                expiryInterval = setInterval(updateExpiryTimer, 1000);

                // Reset cooldown 60 detik
                startCooldownTimer(d.cooldown || 60);

                // Bersihkan kotak input
                digitBoxes.forEach(function (b) { b.value = ''; });
                clearBoxesError();
                digitBoxes[0].focus();
                syncHiddenCode();
            } else {
                btnResend.disabled = false;
                resendText.innerHTML = 'Kirim Ulang Kode';

                // Kondisi 6: Gagal mengirim OTP
                showAuthPopup('error', d.title || 'Gagal Mengirim OTP', d.message || 'Terjadi kesalahan saat mengirim kode OTP. Pastikan nomor terdaftar di WhatsApp atau coba lagi nanti.', 'Coba Lagi');
            }
        })
        .catch(function (err) {
            btnResend.disabled = false;
            resendText.innerHTML = 'Kirim Ulang Kode';
            showAuthPopup('error', 'Gangguan Sistem', 'Terjadi kendala pada server. Silakan coba beberapa saat lagi.', 'Tutup');
        });
    });
});
</script>
<?= $this->endSection() ?>
