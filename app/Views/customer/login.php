<?= $this->extend('layouts/auth_glass') ?>

<?= $this->section('content') ?>

<div class="glass-auth-card">

    <!-- Top Smartphone Icon Badge -->
    <div class="auth-icon-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
            <line x1="12" y1="18" x2="12.01" y2="18" stroke-width="2.5"></line>
        </svg>
    </div>

    <!-- Title & Subtitle -->
    <h1 class="auth-title">Masuk ke Akun Anda</h1>
    <p class="auth-subtitle">
        Masukkan nomor telepon Anda. Kami akan mengirimkan kode OTP melalui WhatsApp untuk verifikasi.
    </p>

    <!-- Main Login Form -->
    <form action="<?= site_url('account/login/send-otp') ?>" method="post" id="loginForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="return_url" value="<?= esc($returnUrl ?? current_url(), 'attr') ?>">

        <div class="text-start mb-2">
            <label for="phoneInput" class="form-label-custom">Nomor Telepon</label>
            <div class="phone-input-group" id="phoneGroup">
                <div class="phone-country-pill">
                    <span class="phone-flag">🇮🇩</span>
                    <span>+62</span>
                    <span class="phone-arrow">▼</span>
                </div>
                <input 
                    type="tel" 
                    id="phoneInput" 
                    name="phone" 
                    class="phone-number-field" 
                    placeholder="8xxxxxxxxxx" 
                    inputmode="numeric" 
                    autocomplete="tel-local" 
                    value="<?= esc(old('phone'), 'attr') ?>" 
                    required 
                    autofocus
                >
            </div>
        </div>

        <!-- Privacy Shield Note -->
        <div class="security-badge-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <polyline points="9 12 11 14 15 10"></polyline>
            </svg>
            <span>Kami tidak membagikan nomor Anda kepada pihak lain.</span>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-gradient-auth" id="btnSubmitOtp">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
            <span id="btnSubmitText">Kirim Kode OTP</span>
        </button>
    </form>

    <!-- Help Link -->
    <a href="<?= site_url('bantuan/login') ?>" class="auth-help-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <span>Butuh Bantuan?</span>
    </a>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var loginForm = document.getElementById('loginForm');
    var phoneInput = document.getElementById('phoneInput');
    var btnSubmit = document.getElementById('btnSubmitOtp');
    var btnText = document.getElementById('btnSubmitText');

    // Filter input: cegah karakter selain angka, +, spasi, strip saat mengetik
    phoneInput.addEventListener('input', function () {
        var raw = this.value;
        // Bersihkan karakter aneh langsung jika bukan digit/+/- /spasi
        var filtered = raw.replace(/[^0-9+\-\s]/g, '');
        if (filtered !== raw) {
            this.value = filtered;
        }
    });

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var rawValue = phoneInput.value.trim();

        // 1. Validasi: Nomor telepon kosong
        if (rawValue === '') {
            showAuthPopup('warning', 'Nomor Telepon Kosong', 'Silakan masukkan nomor WhatsApp Anda.', 'Mengerti', function() {
                phoneInput.focus();
            });
            return;
        }

        // 2. Validasi: Karakter tidak valid
        if (/[^0-9+\-\s]/.test(rawValue)) {
            showAuthPopup('error', 'Format Tidak Valid', 'Masukkan nomor telepon dengan format yang benar.', 'Perbaiki', function() {
                phoneInput.focus();
            });
            return;
        }

        // Ekstrak angka murni
        var digits = rawValue.replace(/\D/g, '');
        if (digits === '') {
            showAuthPopup('warning', 'Nomor Telepon Kosong', 'Silakan masukkan nomor WhatsApp Anda.', 'Mengerti');
            return;
        }

        // Normalisasi nomor lokal
        var local = digits;
        if (local.startsWith('620')) {
            local = local.substring(3);
        } else if (local.startsWith('62')) {
            local = local.substring(2);
        } else if (local.startsWith('0')) {
            local = local.substring(1);
        }

        // Cek awalan nomor seluler Indonesia (wajib 8)
        if (!local.startsWith('8')) {
            showAuthPopup('error', 'Format Tidak Valid', 'Masukkan nomor telepon dengan format yang benar (diawali angka 8).', 'Perbaiki', function() {
                phoneInput.focus();
            });
            return;
        }

        // 3. Validasi: Kurang dari 10 digit
        if (local.length < 10) {
            showAuthPopup('warning', 'Nomor Tidak Lengkap', 'Nomor WhatsApp minimal 10 digit.', 'Perbaiki', function() {
                phoneInput.focus();
            });
            return;
        }

        // 4. Validasi: Lebih dari 13 digit
        if (local.length > 13) {
            showAuthPopup('warning', 'Nomor Terlalu Panjang', 'Nomor WhatsApp maksimal 13 digit.', 'Perbaiki', function() {
                phoneInput.focus();
            });
            return;
        }

        // Form valid! Lakukan request AJAX ke backend
        btnSubmit.disabled = true;
        btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Mengirim Kode OTP...';

        var formData = new FormData(loginForm);
        formData.append('ajax', '1');

        fetch(loginForm.action, {
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
            btnText.textContent = 'Kirim Kode OTP';

            if (!res.data) {
                // Kondisi 10: Gangguan server
                showAuthPopup('error', 'Gangguan Sistem', 'Terjadi kendala pada server. Silakan coba beberapa saat lagi.', 'Tutup');
                return;
            }

            var d = res.data;

            if (res.ok && d.status === 'success') {
                // Kondisi 5: Kode OTP Berhasil Dikirim
                showAuthPopup('success', d.title || 'Kode OTP Berhasil Dikirim', d.message || 'Kode OTP telah dikirim ke nomor WhatsApp Anda. Silakan periksa pesan masuk.', 'Lanjut Verifikasi', function () {
                    window.location.href = d.redirect || '<?= site_url('account/verify') ?>';
                });

                // Auto-redirect jika user tidak mengklik tombol dalam 1.8 detik
                setTimeout(function () {
                    window.location.href = d.redirect || '<?= site_url('account/verify') ?>';
                }, 1800);
            } else {
                // Tangani respons error terstruktur dari backend
                var errorType = 'error';
                var btnTitle = 'Perbaiki';

                if (d.code === 'empty_phone' || d.code === 'phone_too_short' || d.code === 'phone_too_long' || d.code === 'cooldown') {
                    errorType = 'warning';
                } else if (d.code === 'otp_failed') {
                    btnTitle = 'Coba Lagi';
                }

                showAuthPopup(
                    errorType,
                    d.title || 'Gagal Mengirim OTP',
                    d.message || 'Terjadi kesalahan saat mengirim kode OTP. Pastikan nomor terdaftar di WhatsApp atau coba lagi nanti.',
                    btnTitle,
                    function () {
                        phoneInput.focus();
                    }
                );
            }
        })
        .catch(function (err) {
            btnSubmit.disabled = false;
            btnText.textContent = 'Kirim Kode OTP';
            // Kondisi 10: Gangguan server / koneksi
            showAuthPopup('error', 'Gangguan Sistem', 'Terjadi kendala pada server. Silakan coba beberapa saat lagi.', 'Tutup');
        });
    });
});
</script>
<?= $this->endSection() ?>
