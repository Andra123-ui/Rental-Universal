<?php
/** @var string|null $title */
$biz = site_business();
$bizName = $biz['business_name'] ?? 'Rental Universal';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= esc($title ?? 'Masuk') ?> —
        <?= esc($bizName) ?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --ink: #0A1633;
            --paper: #F4F7FE;
            --surface: #FFFFFF;
            --accent: #2456F5;
            --accent2: #38BDF8;
            --accent-soft: #E4ECFF;
            --line: #E1E7F5;
            --muted: #5C6785;
            --radius: 16px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'IBM Plex Sans', sans-serif;
            color: var(--ink);
            background: var(--ink);
        }

        h1,
        h2 {
            font-family: 'Fraunces', serif;
            font-weight: 600;
            margin: 0;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.15fr 460px;
        }

        /* ===== PANEL VISUAL KIRI ===== */
        .auth-visual {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            color: #fff;
            background:
                linear-gradient(160deg, rgba(10, 22, 51, 0.92) 0%, rgba(21, 51, 160, 0.85) 45%, rgba(36, 86, 245, 0.75) 100%),
                url('https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1400&q=80') center/cover;
            background-blend-mode: multiply;
        }

        .auth-visual::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            background: radial-gradient(50% 60% at 80% 10%, rgba(56, 189, 248, 0.35), transparent 70%);
            animation: meshFloat 12s ease-in-out infinite alternate;
        }

        @keyframes meshFloat {
            0% {
                transform: translate(0, 0) scale(1);
            }

            100% {
                transform: translate(-3%, 4%) scale(1.08);
            }
        }

        .auth-visual .brand {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Fraunces', serif;
            font-size: 1.35rem;
            font-weight: 600;
        }

        .auth-visual .brand .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent2);
            box-shadow: 0 0 12px var(--accent2);
        }

        /* Kartu kategori mengambang */
        .float-badges {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 260px;
        }

        .fb {
            position: absolute;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.5);
            animation: floatY 5s ease-in-out infinite;
        }

        .fb .ic {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .fb .ic svg {
            width: 17px;
            height: 17px;
            stroke: #fff;
        }

        .fb strong {
            display: block;
            font-size: 0.85rem;
        }

        .fb span {
            display: block;
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .fb1 {
            top: 6%;
            left: 2%;
            animation-delay: 0s;
        }

        .fb2 {
            top: 38%;
            right: 0;
            animation-delay: .8s;
        }

        .fb3 {
            bottom: 8%;
            left: 14%;
            animation-delay: 1.6s;
        }

        @keyframes floatY {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-14px);
            }
        }

        .pitch {
            position: relative;
            z-index: 2;
            max-width: 420px;
            margin-top: 18px;
        }

        .pitch h2 {
            font-size: 2.1rem;
            line-height: 1.2;
            color: #fff;
        }

        .pitch h2 em {
            font-style: normal;
            background: linear-gradient(120deg, var(--accent2), #fff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .pitch p {
            color: rgba(255, 255, 255, 0.82);
            margin-top: 14px;
            font-size: 0.95rem;
            line-height: 1.65;
        }

        /* Trust stats bar */
        .trust-stats {
            position: relative;
            z-index: 2;
            display: flex;
            gap: 28px;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.18);
        }

        .trust-stats .t-item strong {
            display: block;
            font-family: 'Fraunces', serif;
            font-size: 1.4rem;
            color: #fff;
        }

        .trust-stats .t-item span {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.65);
        }

        /* ===== PANEL FORM KANAN ===== */
        .auth-panel {
            background: var(--paper);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
            position: relative;
        }

        .auth-card {
            background: var(--surface);
            border-radius: 24px;
            padding: 44px 38px;
            width: 100%;
            max-width: 390px;
            box-shadow: 0 40px 80px -35px rgba(10, 22, 51, 0.3);
            animation: cardIn .55s cubic-bezier(.16, 1, .3, 1);
            position: relative;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 24px;
            padding: 1px;
            z-index: -1;
            background: linear-gradient(135deg, var(--accent), transparent 40%, transparent 60%, var(--accent2));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            opacity: 0.5;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-icon-badge {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent-soft), #fff);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            box-shadow: 0 8px 20px -8px rgba(36, 86, 245, 0.3);
        }

        .auth-icon-badge svg {
            width: 24px;
            height: 24px;
            stroke: var(--accent);
        }

        .auth-title {
            font-size: 1.65rem;
        }

        .auth-subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 8px;
            line-height: 1.6;
        }

        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.85rem;
            margin-top: 18px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .alert-info {
            background: var(--accent-soft);
            color: #1B3A8C;
        }

        .alert-error {
            background: #FDEDEA;
            color: #B3301C;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 18px;
        }

        .auth-form {
            margin-top: 26px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--ink);
        }

        .phone-input-wrapper {
            display: flex;
            align-items: center;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            transition: border-color .2s ease, box-shadow .2s ease;
            background: var(--surface);
        }

        .phone-input-wrapper:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-soft);
        }

        .phone-prefix {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--paper);
            padding: 14px 14px;
            font-weight: 600;
            color: var(--muted);
            border-right: 1px solid var(--line);
            font-size: 0.92rem;
        }

        .phone-prefix svg {
            width: 16px;
            height: 16px;
            stroke: var(--muted);
        }

        .form-control {
            flex: 1;
            border: none;
            padding: 14px;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            background: transparent;
            color: var(--ink);
        }

        .form-help {
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 9px;
            line-height: 1.5;
            display: flex;
            gap: 6px;
            align-items: flex-start;
        }

        .form-help svg {
            width: 14px;
            height: 14px;
            stroke: var(--muted);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(120deg, var(--accent), var(--accent2));
            color: #fff;
            box-shadow: 0 12px 26px -10px rgba(36, 86, 245, 0.55);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 32px -10px rgba(36, 86, 245, 0.6);
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -60%;
            width: 40%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transform: skewX(-20deg);
        }

        .btn-primary:hover::after {
            animation: shine .8s ease forwards;
        }

        @keyframes shine {
            from {
                left: -60%;
            }

            to {
                left: 130%;
            }
        }

        .btn-primary:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-primary:disabled::after {
            display: none;
        }

        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            font-size: 0.85rem;
        }

        .link-secondary {
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .link-secondary:hover {
            color: var(--accent);
        }

        @media (max-width:900px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-visual {
                padding: 32px 28px 36px;
                min-height: 200px;
            }

            .pitch h2 {
                font-size: 1.5rem;
            }

            .float-badges {
                display: none;
            }

            .trust-stats {
                margin-top: 18px;
                padding-top: 16px;
                gap: 20px;
            }

            .auth-panel {
                padding: 20px;
            }

            .auth-card {
                padding: 32px 24px;
                border-radius: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="auth-shell">
        <div class="auth-visual">
            <div class="brand"><span class="dot"></span>
                <?= esc($bizName) ?>
            </div>

            <div class="float-badges">
                <div class="fb fb1">
                    <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M3 13l1.5-5A2 2 0 0 1 6.4 6.5h11.2A2 2 0 0 1 19.5 8l1.5 5" />
                            <rect x="2.5" y="13" width="19" height="5.5" rx="1.2" />
                        </svg></div>
                    <div><strong>Kendaraan</strong><span>150+ unit</span></div>
                </div>
                <div class="fb fb2">
                    <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="7" width="18" height="13" rx="2" />
                            <circle cx="12" cy="13.5" r="3.2" />
                        </svg></div>
                    <div><strong>Kamera & Alat</strong><span>Siap pakai</span></div>
                </div>
                <div class="fb fb3">
                    <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M4 21V10l8-6 8 6v11" />
                        </svg></div>
                    <div><strong>Ruang & Villa</strong><span>Booking cepat</span></div>
                </div>
            </div>

            <div class="pitch">
                <h2>Sewa apa saja, <em>tanpa ribet.</em></h2>
                <p>Masuk dengan nomor HP Anda — verifikasi lewat OTP WhatsApp, tanpa perlu mengingat kata sandi. Riwayat
                    booking Anda tersimpan otomatis.</p>
            </div>

            <div class="trust-stats">
                <div class="t-item"><strong>500+</strong><span>Produk & Jasa</span></div>
                <div class="t-item"><strong>2000+</strong><span>Booking Selesai</span></div>
                <div class="t-item"><strong>4.9★</strong><span>Rating Pelanggan</span></div>
            </div>
        </div>

        <div class="auth-panel">
            <div class="auth-card">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>
</body>

</html>