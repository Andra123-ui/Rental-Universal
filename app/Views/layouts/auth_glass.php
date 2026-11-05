<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Masuk') ?> — CV. OTW Computer Gusaha | Rental Universal</title>

    <!-- Google Fonts: Plus Jakarta Sans & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&family=Poppins:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #0755C9;
            --deep-navy: #092B67;
            --navy-headline: #073B91;
            --sky-blue: #38BDF8;
            --accent-orange-start: #FF8A00;
            --accent-orange-end: #FFB52E;
            --accent-gold: #FFC247;
            --text-heading: #092B67;
            --text-body: #475569;
            --text-muted: #64748B;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-body);
            background-color: #0b1a3d;
            overflow-x: hidden;
        }

        /* 1. FULL SCREEN VIEWPORT:
           - Memenuhi seluruh area viewport tanpa sidebar navy polos di kiri maupun kanan.
           - Tidak ada margin putih, tidak ada horizontal scrollbar.
           - Menggunakan background-position: center top agar logo OTW di langit tetap utuh.
        */
        .auth-viewport {
            width: 100vw;
            min-height: 100vh;
            background-image: url('<?= base_url('uploads/catalog/BgMasuk.png') ?>');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Ambient subtle contrast layer (sangat tipis agar seluruh detail gambar asli 100% terlihat) */
        .auth-viewport::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 50% 30%, rgba(255, 255, 255, 0.04) 0%, rgba(9, 43, 103, 0.12) 100%);
            pointer-events: none;
            z-index: 1;
        }

        /* 2. CENTER CONTENT COLUMN:
           - Posisinya berada di area kosong tengah gambar (antara gedung kiri dan peralatan rental kanan).
           - Jarak atas (padding-top) disesuaikan agar identitas logo OTW & slogan asli di langit TETAP UTUH.
        */
        .auth-content-container {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            /* Jarak aman di bawah logo OTW: ~23-25vh pada desktop */
            padding-top: clamp(140px, 23.5vh, 260px);
            padding-bottom: 36px;
            padding-left: 16px;
            padding-right: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        /* 3. HEADLINE PROMOSI YANG MENARIK (Sesuai Spesifikasi Bagian 3):
           - "Solusi Sewa Alat & Perlengkapan"
           - "Lebih Mudah dan Cepat" (gradasi oranye #FF8A00 hingga #FFB52E)
           - Font Poppins / Plus Jakarta Sans 800-900, text-shadow halus untuk keterbacaan tinggi.
        */
        .hero-promo-header {
            text-align: center;
            margin-bottom: clamp(14px, 2vh, 22px);
            width: 100%;
            max-width: 540px;
            padding: 0 8px;
        }

        .promo-title {
            font-family: 'Poppins', 'Plus Jakarta Sans', sans-serif;
            font-weight: 900;
            font-size: clamp(22px, 2.35vw, 38px);
            line-height: 1.18;
            letter-spacing: -0.025em;
            color: var(--navy-headline);
            margin: 0;
            text-shadow: 
                0 2px 10px rgba(255, 255, 255, 0.95),
                0 4px 20px rgba(255, 255, 255, 0.85),
                0 4px 18px rgba(7, 59, 145, 0.15);
        }

        .promo-title .line-1 {
            display: block;
            color: var(--navy-headline);
        }

        .promo-title .line-2 {
            display: block;
            color: var(--deep-navy);
            margin-top: 3px;
        }

        .promo-title .text-gradient-orange {
            background: linear-gradient(90deg, #FF8A00 0%, #FFB52E 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        /* 4. FORM CARD CONTAINER */
        .auth-card-wrapper {
            width: 100%;
            max-width: 410px;
        }

        /* 5. DESAIN GLASSMORPHISM SESUAI SPESIFIKASI BAGIAN 4:
           - Lebar desktop 380-420px (max-width: 410px)
           - Padding 28-32px
           - Border radius 28px
           - Background putih transparan rgba(255, 255, 255, 0.68)
           - backdrop-filter: blur(20px)
           - Border putih transparan
           - Shadow lembut
        */
        .glass-auth-card {
            background: rgba(255, 255, 255, 0.68);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1.5px solid rgba(255, 255, 255, 0.85);
            border-radius: 28px;
            padding: clamp(24px, 3vh, 32px) clamp(20px, 2.2vw, 28px);
            box-shadow: 
                0 20px 45px -10px rgba(7, 59, 145, 0.18),
                0 0 0 1px rgba(255, 255, 255, 0.7) inset,
                inset 0 1px 2px rgba(255, 255, 255, 0.95);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Ambient glow di pojok bawah kartu */
        .glass-auth-card::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: -15px;
            right: -15px;
            height: 95px;
            background: 
                radial-gradient(circle at 12% 100%, rgba(7, 85, 201, 0.18) 0%, transparent 65%),
                radial-gradient(circle at 88% 100%, rgba(255, 138, 0, 0.18) 0%, transparent 65%);
            pointer-events: none;
            border-radius: 0 0 28px 28px;
            z-index: 0;
        }

        .glass-auth-card > * {
            position: relative;
            z-index: 1;
        }

        /* Icon Badge Top */
        .auth-icon-badge {
            width: 48px;
            height: 48px;
            background: #FFFFFF;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            box-shadow: 0 8px 18px -4px rgba(7, 85, 201, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.9);
            color: var(--primary-blue);
        }

        .auth-icon-badge svg {
            width: 24px;
            height: 24px;
        }

        /* Form Heading & Subtitle */
        .auth-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: clamp(1.35rem, 1.6vw, 1.55rem);
            color: var(--deep-navy);
            margin-bottom: 4px;
            letter-spacing: -0.025em;
        }

        .auth-subtitle {
            font-size: 0.82rem;
            color: var(--text-body);
            line-height: 1.48;
            margin-bottom: 16px;
            max-width: 330px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Label Input */
        .form-label-custom {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--deep-navy);
            display: block;
            text-align: left;
            margin-bottom: 6px;
        }

        /* Input Group Nomor Telepon (+62 & 8xxxxxxxxxx) */
        .phone-input-group {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.94);
            border: 1.5px solid rgba(7, 85, 201, 0.3);
            border-radius: 14px;
            height: 48px;
            overflow: hidden;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(7, 59, 145, 0.04);
        }

        .phone-input-group:focus-within {
            border-color: var(--primary-blue);
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(7, 85, 201, 0.15);
        }

        .phone-country-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0 14px;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--deep-navy);
            border-right: 1.5px solid #E2E8F0;
            user-select: none;
            height: 100%;
            background: transparent;
        }

        .phone-flag { font-size: 1.15rem; line-height: 1; }
        .phone-arrow { font-size: 0.7rem; color: #94A3B8; }

        .phone-number-field {
            flex: 1;
            height: 100%;
            border: none;
            background: transparent;
            padding: 0 14px;
            font-size: 0.98rem;
            font-weight: 600;
            color: var(--deep-navy);
            outline: none;
        }

        .phone-number-field::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }

        /* Catatan Privasi dengan Ikon Perisai */
        .security-badge-note {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-body);
            font-size: 0.76rem;
            text-align: left;
            margin-top: 8px;
            margin-bottom: 18px;
        }

        .security-badge-note svg {
            width: 16px;
            height: 16px;
            color: var(--primary-blue);
            flex-shrink: 0;
        }

        /* Tombol Utama Pil Gradasi Biru ke Oranye */
        .btn-gradient-auth {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 48px;
            border-radius: 9999px;
            border: none;
            background: linear-gradient(90deg, #0755C9 0%, #155EEF 40%, #FF8A00 100%);
            background-size: 150% auto;
            color: #FFFFFF;
            font-size: 0.96rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 
                0 10px 22px -4px rgba(7, 85, 201, 0.4),
                0 6px 12px -2px rgba(255, 138, 0, 0.25);
            text-decoration: none;
        }

        .btn-gradient-auth:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 
                0 14px 26px -4px rgba(7, 85, 201, 0.45),
                0 8px 16px -2px rgba(255, 138, 0, 0.3);
            color: #FFFFFF;
        }

        .btn-gradient-auth:active {
            transform: translateY(0);
        }

        .btn-gradient-auth:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-gradient-auth svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }

        /* Tautan Bantuan */
        .auth-help-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 14px;
            color: var(--text-body);
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .auth-help-link:hover {
            color: var(--primary-blue);
        }

        .auth-help-link svg {
            width: 15px;
            height: 15px;
        }

        /* Kotak Digit OTP (Halaman Verify) */
        .otp-boxes-grid {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: clamp(12px, 1.6vh, 18px) 0 clamp(10px, 1.4vh, 14px);
        }

        .otp-box-digit {
            width: 42px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(7, 85, 201, 0.3);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--deep-navy);
            text-align: center;
            outline: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(7, 59, 145, 0.04);
        }

        .otp-box-digit:focus {
            border-color: var(--primary-blue);
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(7, 85, 201, 0.15);
            transform: translateY(-2px);
        }

        .otp-box-digit.is-invalid {
            border-color: #EF4444;
            background: #FEF2F2;
            animation: shakeBoxes 0.4s ease;
        }

        @keyframes shakeBoxes {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .otp-timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--text-body);
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(226, 232, 240, 0.8);
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 14px;
        }

        .otp-timer-badge strong {
            color: var(--primary-blue);
            font-family: monospace;
            font-size: 0.92rem;
        }

        .otp-masked-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: var(--deep-navy);
            background: rgba(7, 85, 201, 0.08);
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 0.88rem;
            letter-spacing: 0.02em;
        }

        .otp-secondary-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            font-size: 0.82rem;
            gap: 10px;
        }

        .btn-link-action {
            background: none;
            border: none;
            padding: 0;
            color: var(--text-body);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-link-action:hover {
            color: var(--primary-blue);
        }

        .btn-link-action:disabled {
            color: #94A3B8;
            cursor: not-allowed;
            text-decoration: none;
        }

        /* 6. MODAL NOTIFIKASI BOOTSTRAP 5 (10 Kondisi Notifikasi Wajib) */
        .modal-auth-dialog {
            max-width: 420px;
        }

        .modal-auth-content {
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 26px;
            padding: 28px 24px;
            background: #FFFFFF;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
            text-align: center;
        }

        .modal-auth-icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-auth-icon.icon-warning { background: #FEF3C7; color: #D97706; }
        .modal-auth-icon.icon-error   { background: #FEE2E2; color: #DC2626; }
        .modal-auth-icon.icon-success { background: #DCFCE7; color: #16A34A; }
        .modal-auth-icon.icon-info    { background: #E0F2FE; color: #0284C7; }

        .modal-auth-icon svg { width: 28px; height: 28px; }

        .modal-auth-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--deep-navy);
            margin-bottom: 6px;
        }

        .modal-auth-text {
            font-size: 0.88rem;
            color: var(--text-body);
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .btn-modal-action {
            width: 100%;
            height: 46px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.92rem;
            border: none;
        }

        /* ========================================================
           RESPONSIVE DESIGN BREAKPOINTS (Sesuai Spesifikasi Bagian 8)
           ======================================================== */

        /* 1. LAYAR TINGGI TERBATAS / LAPTOP (<= 768px tinggi layar) */
        @media (max-height: 768px) and (min-width: 768px) {
            .auth-content-container {
                padding-top: clamp(140px, 21vh, 180px);
                padding-bottom: 24px;
            }
            .hero-promo-header {
                margin-bottom: 12px;
            }
            .promo-title {
                font-size: clamp(22px, 2vw, 28px);
            }
            .glass-auth-card {
                padding: 22px 24px;
            }
            .auth-subtitle {
                margin-bottom: 12px;
            }
            .security-badge-note {
                margin-bottom: 12px;
            }
        }

        /* 2. ULTRAWIDE SCREENS (e.g. 1741x800, 21:9) */
        @media (min-aspect-ratio: 2/1) {
            .auth-viewport {
                background-position: center top;
            }
            .auth-content-container {
                padding-top: clamp(160px, 23vh, 220px);
            }
        }

        /* 3. TABLET & MOBILE (<= 767.98px) */
        @media (max-width: 767.98px) {
            .auth-content-container {
                padding-top: max(24px, 15vh);
                max-width: 94%;
                padding-left: 12px;
                padding-right: 12px;
            }
            .promo-title {
                font-size: clamp(20px, 5.8vw, 26px);
            }
            .glass-auth-card {
                padding: 24px 18px;
                border-radius: 24px;
            }
            .auth-card-wrapper {
                max-width: 100%;
            }
        }

        /* 4. SMARTPHONE KECIL (<= 480px) */
        @media (max-width: 480px) {
            .auth-content-container {
                padding-top: max(20px, 13vh);
            }
            .auth-title {
                font-size: 1.35rem;
            }
            .otp-box-digit {
                width: 38px;
                height: 44px;
                font-size: 1.2rem;
            }
            .otp-boxes-grid {
                gap: 6px;
            }
        }
    </style>
</head>
<body>

    <main class="auth-viewport">
        <div class="auth-content-container">
            <!-- Headline Promosi Sesuai Spesifikasi Bagian 3 -->
            <div class="hero-promo-header">
                <h1 class="promo-title">
                    <span class="line-1">Solusi Sewa Alat &amp; Perlengkapan</span>
                    <span class="line-2">Lebih <span class="text-gradient-orange">Mudah dan Cepat</span></span>
                </h1>
            </div>

            <!-- Kartu Glassmorphism Form Login / Verify -->
            <div class="auth-card-wrapper">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </main>

    <!-- Modal Notifikasi Universal Bootstrap 5 (Bebas dari browser alert) -->
    <div class="modal fade" id="authNotificationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-auth-dialog">
            <div class="modal-content modal-auth-content">
                <div class="modal-auth-icon" id="modalAuthIcon">
                    <!-- Dynamic SVG Icon Inserted via JS -->
                </div>
                <h3 class="modal-auth-title" id="modalAuthTitle">Pemberitahuan</h3>
                <p class="modal-auth-text" id="modalAuthMessage">Pesan informasi.</p>
                <button type="button" class="btn btn-primary btn-modal-action" id="modalAuthBtn" data-bs-dismiss="modal">
                    Mengerti
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal Helper Script -->
    <script>
        var authModalEl = document.getElementById('authNotificationModal');
        var authModalInstance = new bootstrap.Modal(authModalEl, { keyboard: true });
        var modalCallback = null;

        authModalEl.addEventListener('hidden.bs.modal', function () {
            if (typeof modalCallback === 'function') {
                var cb = modalCallback;
                modalCallback = null;
                cb();
            }
        });

        function showAuthPopup(type, title, message, btnText, callback) {
            var iconContainer = document.getElementById('modalAuthIcon');
            var titleEl = document.getElementById('modalAuthTitle');
            var messageEl = document.getElementById('modalAuthMessage');
            var btnEl = document.getElementById('modalAuthBtn');

            titleEl.textContent = title || 'Pemberitahuan';
            messageEl.textContent = message || '';
            btnEl.textContent = btnText || 'Mengerti';
            modalCallback = callback || null;

            iconContainer.className = 'modal-auth-icon';
            btnEl.className = 'btn btn-modal-action';

            if (type === 'warning') {
                iconContainer.classList.add('icon-warning');
                btnEl.classList.add('btn-warning');
                iconContainer.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
            } else if (type === 'error') {
                iconContainer.classList.add('icon-error');
                btnEl.classList.add('btn-danger');
                iconContainer.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            } else if (type === 'success') {
                iconContainer.classList.add('icon-success');
                btnEl.classList.add('btn-success');
                iconContainer.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            } else {
                iconContainer.classList.add('icon-info');
                btnEl.classList.add('btn-primary');
                iconContainer.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            }

            authModalInstance.show();
        }

        // Handle flashdata popups on initial load
        <?php $popup = session()->getFlashdata('popup'); ?>
        <?php if ($popup): ?>
        document.addEventListener('DOMContentLoaded', function () {
            showAuthPopup(
                <?= json_encode($popup['type'] ?? 'info') ?>,
                <?= json_encode($popup['title'] ?? 'Pemberitahuan') ?>,
                <?= json_encode($popup['message'] ?? '') ?>
            );
        });
        <?php endif; ?>
    </script>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
