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
    <title><?= esc($title ?? 'Beranda') ?> — <?= esc($bizName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,500&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
    :root {
<<<<<<< HEAD
      --ink: #0A1633;
      --paper: #F4F7FE;
      --surface: #FFFFFF;
      --accent: #2456F5;
      --accent2: #38BDF8;
      --accent-soft: #E4ECFF;
      --brass: #FF7A45;
      --line: #E1E7F5;
      --muted: #5C6785;
      --radius: 10px;
      --maxw: 1200px;
=======
        --ink: #0F1B3D;
        --paper: #F0F4FC;
        --surface: #FFFFFF;
        --accent: #1E56E8;
        --accent-soft: #DCE7FF;
        --brass: #FF7A45;
        --line: #D9E2F5;
        --muted: #5B6B8C;
        --radius: 6px;
        --maxw: 1180px;

>>>>>>> 4416665a763add9c90473d2a030fabb722a247f2
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: var(--paper);
        color: var(--ink);
        font-family: 'IBM Plex Sans', sans-serif;
        line-height: 1.55;
    }

    h1,
    h2,
    h3 {
        font-family: 'Fraunces', serif;
        font-weight: 500;
        margin: 0;
        color: var(--ink);
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    img {
        max-width: 100%;
        display: block;
    }

    .wrap {
        max-width: var(--maxw);
        margin: 0 auto;
        padding: 0 28px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 13px 24px;
        border-radius: var(--radius);
        font-weight: 600;
        font-size: 0.95rem;
        border: 1px solid transparent;
        cursor: pointer;
        transition: transform .15s ease, background .15s ease;
    }

    .btn-primary {
        background: var(--accent);
        color: #fff;
    }

    .btn-primary:hover {
        background: #a8481f;
    }

    .btn-outline {
        border-color: var(--ink);
        color: var(--ink);
        background: transparent;
    }

    .btn-outline:hover {
        background: var(--ink);
        color: #fff;
    }

    .btn-light {
        background: #fff;
        color: var(--ink);
    }

    header {
        position: sticky;
        top: 0;
        background: rgba(239, 241, 234, 0.92);
        backdrop-filter: blur(6px);
        border-bottom: 1px solid var(--line);
        z-index: 50;
    }

    .nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 0;
        position: relative;
    }

    .logo {
        font-family: 'Fraunces', serif;
        font-size: 1.35rem;
        font-weight: 600;
    }

    .nav-links {
        display: flex;
        gap: 32px;
        align-items: center;
        font-size: 0.95rem;
    }

    .nav-links a:hover {
        color: var(--accent);
    }

    .nav-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .nav-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.6rem;
        cursor: pointer;
        color: var(--ink);
    }

    .hero {
        padding: 72px 0 60px;
    }

    .hero-grid {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 56px;
        align-items: center;
    }

    .eyebrow-free h1 {
        font-size: 2.8rem;
        line-height: 1.12;
        letter-spacing: -0.01em;
        max-width: 16ch;
    }

    .hero p.lead {
        margin-top: 20px;
        font-size: 1.08rem;
        color: var(--muted);
        max-width: 46ch;
    }

    .hero-ctas {
        margin-top: 30px;
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
    }

    .hero-art {
        position: relative;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 14px 4px 14px 4px;
        padding: 36px;
        min-height: 320px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .art-tile {
        background: var(--paper);
        border-radius: 4px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 18px 10px;
        text-align: center;
    }

    .art-tile svg {
        width: 30px;
        height: 30px;
        stroke: var(--accent);
    }

    .art-tile span {
        font-size: 0.82rem;
        color: var(--muted);
        font-weight: 500;
    }

    .art-tile.tall {
        grid-row: span 2;
        background: var(--ink);
        color: #fff;
        justify-content: flex-end;
        align-items: flex-start;
        padding: 22px;
    }

    .art-tile.tall svg {
        stroke: var(--brass);
        width: 26px;
        height: 26px;
    }

    .art-tile.tall strong {
        font-family: 'Fraunces', serif;
        font-size: 1.05rem;
        font-weight: 500;
        margin-top: 8px;
        display: block;
    }

    .search-card {
        margin-top: 25px;
        position: relative;
        z-index: 5;
        background: var(--ink);
        color: #fff;
        border-radius: var(--radius);
        padding: 22px 26px;
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .search-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 150px;
    }

    .search-field label {
        font-size: 0.78rem;
        color: #C9CFC9;
    }

    .search-field select,
    .search-field input {
        padding: 10px 12px;
        border-radius: 4px;
        border: 1px solid #3B4A45;
        background: #20302B;
        color: #fff;
        font-family: inherit;
        font-size: 0.92rem;
    }

    section {
        padding: 64px 0;
    }

    .section-head {
        max-width: 640px;
        margin-bottom: 40px;
    }

    .section-head h2 {
        font-size: 1.9rem;
    }

    .section-head p {
        color: var(--muted);
        margin-top: 10px;
    }

    .features {
        background: var(--surface);
        border-top: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
    }

    .feature {
        display: flex;
        gap: 14px;
    }

    .feature svg {
        width: 26px;
        height: 26px;
        stroke: var(--accent);
        flex-shrink: 0;
        margin-top: 2px;
    }

    .feature h3 {
        font-size: 1.02rem;
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
    }

    .feature p {
        margin: 6px 0 0;
        font-size: 0.9rem;
        color: var(--muted);
    }

    .card-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .cat-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 26px;
        transition: border-color .15s ease;
    }

    .cat-card:hover {
        border-color: var(--accent);
    }

    .cat-card .icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--accent-soft);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }

    .cat-card .icon svg {
        width: 20px;
        height: 20px;
        stroke: var(--accent);
    }

    .cat-card h3 {
        font-size: 1.08rem;
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
    }

    .cat-card p {
        color: var(--muted);
        font-size: 0.9rem;
        margin-top: 8px;
    }

    .cat-card .link {
        display: inline-block;
        margin-top: 14px;
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--accent);
    }

    .item-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .item-media {
        height: 150px;
        background: linear-gradient(135deg, var(--ink), #2b3d37);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .item-media svg {
        width: 36px;
        height: 36px;
        stroke: var(--brass);
    }

    .item-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
    }

    .item-type {
        font-size: 0.75rem;
        color: var(--accent);
        font-weight: 600;
    }

    .item-body h3 {
        font-size: 1.05rem;
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
    }

    .item-body p {
        color: var(--muted);
        font-size: 0.88rem;
        margin: 0;
        flex: 1;
    }

    .item-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
    }

    .item-price {
        font-weight: 600;
        font-size: 1rem;
    }

    .item-price small {
        font-weight: 400;
        color: var(--muted);
        font-size: 0.78rem;
    }

    .empty-state {
        grid-column: 1 / -1;
        background: var(--surface);
        border: 1px dashed var(--line);
        border-radius: var(--radius);
        padding: 44px 30px;
        text-align: center;
        color: var(--muted);
    }

    .empty-state svg {
        width: 34px;
        height: 34px;
        stroke: var(--brass);
        margin-bottom: 14px;
    }

    .empty-state h3 {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1.02rem;
        color: var(--ink);
        margin-bottom: 6px;
    }

    .steps {
        background: var(--ink);
        color: #fff;
    }

    .steps .section-head p {
        color: #C9CFC9;
    }

    .steps .section-head h2 {
        color: #fff;
    }

    .step-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 26px;
    }

    .step {
        border-top: 2px solid var(--brass);
        padding-top: 16px;
    }

    .step .num {
        font-family: 'Fraunces', serif;
        font-size: 1.6rem;
        color: var(--brass);
    }

    .step h3 {
        font-size: 1.02rem;
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
        color: #fff;
        margin-top: 8px;
    }

    .step p {
        font-size: 0.88rem;
        color: #C9CFC9;
        margin-top: 6px;
    }

    .cta-banner {
        background: var(--accent-soft);
        border-radius: var(--radius);
        padding: 44px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }

    .cta-banner h2 {
        font-size: 1.6rem;
        max-width: 22ch;
    }

    .cta-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    footer {
        background: var(--ink);
        color: #C9CFC9;
        padding: 56px 0 28px;
        font-size: 0.9rem;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr 1fr;
        gap: 40px;
        padding-bottom: 36px;
        border-bottom: 1px solid #2C3B36;
    }

    footer h4 {
        color: #fff;
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.95rem;
        margin-bottom: 14px;
    }

    footer .logo {
        color: #fff;
    }

    footer p {
        margin: 0 0 14px;
        max-width: 34ch;
    }

    footer ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    footer ul a:hover {
        color: #fff;
    }

    .footer-bottom {
        padding-top: 22px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 0.82rem;
    }

    .page-banner {
        background: var(--ink);
        color: #fff;
        padding: 44px 0 34px;
    }

    .page-banner .crumb {
        font-size: 0.82rem;
        color: #C9CFC9;
        margin-bottom: 8px;
    }

    .page-banner .crumb a {
        color: #C9CFC9;
    }

    .page-banner .crumb a:hover {
        color: #fff;
    }

    .page-banner h1 {
        color: #fff;
        font-size: 1.9rem;
    }

    .filters-bar {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 20px 22px;
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: flex-end;
        margin: -26px 0 40px;
        position: relative;
        z-index: 5;
    }

    .filters-bar .search-field select,
    .filters-bar .search-field input {
        background: var(--paper);
        border: 1px solid var(--line);
        color: var(--ink);
    }

    .filters-bar .search-field label {
        color: var(--muted);
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 44px;
        align-items: start;
    }

    .detail-media {
        background: linear-gradient(135deg, var(--ink), #2b3d37);
        border-radius: var(--radius);
        height: 340px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .detail-media svg {
        width: 56px;
        height: 56px;
        stroke: var(--brass);
    }

    .detail-info .badge {
        display: inline-block;
        background: var(--accent-soft);
        color: var(--accent);
        font-size: 0.78rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 3px;
        margin-bottom: 12px;
    }

    .detail-price {
        font-family: 'Fraunces', serif;
        font-size: 1.7rem;
        margin-top: 10px;
    }

    .detail-price small {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.85rem;
        color: var(--muted);
        font-weight: 400;
    }

    .booking-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 26px;
        position: sticky;
        top: 96px;
    }

    .booking-card h3 {
        font-size: 1.05rem;
        margin-bottom: 16px;
    }

    .field-group {
        margin-bottom: 14px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .field-group label {
        font-size: 0.85rem;
        color: var(--muted);
    }

    .field-group input,
    .field-group select,
    .field-group textarea {
        padding: 10px 12px;
        border: 1px solid var(--line);
        border-radius: 4px;
        font-family: inherit;
        font-size: 0.92rem;
        background: var(--paper);
        width: 100%;
    }

    .avail-note {
        font-size: 0.85rem;
        padding: 10px 12px;
        border-radius: 4px;
        margin-top: 10px;
    }

    .avail-ok {
        background: #E4F0E6;
        color: #2C5C3B;
    }

    .avail-warn {
        background: #FBEAE3;
        color: #93381C;
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
    }

    .cart-table th,
    .cart-table td {
        padding: 16px 18px;
        text-align: left;
        border-bottom: 1px solid var(--line);
        font-size: 0.92rem;
    }

    .cart-table th {
        background: var(--paper);
        font-weight: 600;
        font-size: 0.82rem;
        color: var(--muted);
    }

    .cart-table tr:last-child td {
        border-bottom: none;
    }

    .qty-input {
        width: 64px;
        padding: 6px 8px;
        border: 1px solid var(--line);
        border-radius: 4px;
    }

    .cart-remove {
        color: var(--accent);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .cart-summary {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 24px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.92rem;
        padding: 8px 0;
        border-bottom: 1px solid var(--line);
    }

    .summary-row.total {
        font-weight: 600;
        font-size: 1.05rem;
        border-bottom: none;
        padding-top: 14px;
    }

    .step-indicator {
        display: flex;
        gap: 0;
        margin-bottom: 40px;
    }

    .step-indicator .s-item {
        flex: 1;
        text-align: center;
        padding-bottom: 14px;
        border-bottom: 3px solid var(--line);
        color: var(--muted);
        font-size: 0.88rem;
        font-weight: 600;
    }

    .step-indicator .s-item.active {
        border-color: var(--accent);
        color: var(--ink);
    }

    .step-indicator .s-item.done {
        border-color: var(--brass);
        color: var(--ink);
    }

    .alert-box {
        border-radius: var(--radius);
        padding: 18px 20px;
        font-size: 0.9rem;
        margin-bottom: 24px;
    }

    .alert-info {
        background: #E9EEF6;
        color: #2A3E5C;
    }

    .alert-success {
        background: #E4F0E6;
        color: #2C5C3B;
    }

    .alert-warn {
        background: #FBEAE3;
        color: #93381C;
    }

    .success-box {
        text-align: center;
        max-width: 520px;
        margin: 0 auto;
        padding: 60px 0 60px;
    }

    .success-box .icon-circle {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        background: var(--accent-soft);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 22px;
    }

    .success-box .icon-circle svg {
        width: 34px;
        height: 34px;
        stroke: var(--accent);
    }

    .invoice-box {
        background: var(--surface);
        border: 1px dashed var(--line);
        border-radius: var(--radius);
        padding: 20px;
        margin: 24px 0;
        font-family: 'Fraunces', serif;
        font-size: 1.3rem;
    }


    /* ====== TAMBAHAN UNTUK LANDING PAGE BARU ====== */

    /* Baris keunggulan bergaya ikon (mirip 6-icon row di referensi) */
    .trust-row {
        background: var(--surface);
        border-bottom: 1px solid var(--line);
        padding: 34px 0;
    }

    .trust-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 18px;
        text-align: center;
    }

    .trust-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .trust-item .ic {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: var(--accent-soft);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .trust-item .ic svg {
        width: 22px;
        height: 22px;
        stroke: var(--accent);
    }

    .trust-item span {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--ink);
        line-height: 1.3;
    }

    /* Section head dengan tombol "lihat semua" di kanan */
    .section-head-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        gap: 20px;
        flex-wrap: wrap;
    }

    .section-head-row .section-head {
        margin-bottom: 0;
    }

    .eyebrow {
        display: inline-block;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--accent);
        margin-bottom: 8px;
    }

    /* Kartu produk bergaya "room card" dengan ribbon badge */
    .item-card {
        position: relative;
    }

    .item-ribbon {
        position: absolute;
        top: 14px;
        left: 14px;
        background: var(--brass);
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 3px;
        z-index: 2;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .item-media {
        position: relative;
    }

    .item-meta-row {
        display: flex;
        gap: 14px;
        margin: 6px 0 2px;
        flex-wrap: wrap;
    }

    .item-meta-row span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.78rem;
        color: var(--muted);
    }

    .item-meta-row svg {
        width: 14px;
        height: 14px;
        stroke: var(--muted);
    }

    /* Section Promo */
    .promo-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .promo-card {
        border-radius: var(--radius);
        padding: 28px;
        color: #fff;
        position: relative;
        overflow: hidden;
        min-height: 190px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    .promo-card:nth-child(1) {
        background: linear-gradient(160deg, var(--accent), #8a3a1c);
    }

    .promo-card:nth-child(2) {
        background: linear-gradient(160deg, var(--ink), #0f1815);
    }

    .promo-card:nth-child(3) {
        background: linear-gradient(160deg, var(--brass), #7a5f31);
    }

    .promo-tag {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: rgba(255, 255, 255, 0.18);
        display: inline-block;
        padding: 4px 10px;
        border-radius: 3px;
        margin-bottom: 12px;
        width: fit-content;
    }

    .promo-card h3 {
        color: #fff;
        font-size: 1.15rem;
        margin-bottom: 6px;
    }

    .promo-card p {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.85rem;
        margin: 0 0 16px;
    }

    .promo-card a {
        align-self: flex-start;
    }

    /* Bar Statistik (biru, mirip referensi) */
    .stats-bar {
        background: var(--ink);
        color: #fff;
        padding: 0 0 48px;
    }

    .stats-intro {
        padding: 56px 0 32px;
        max-width: 600px;
    }

    .stats-intro .eyebrow {
        color: var(--brass);
    }

    .stats-intro h2 {
        color: #fff;
        font-size: 1.9rem;
    }

    .stats-intro h2 span {
        color: var(--brass);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    .stat-item {
        text-align: center;
        padding: 32px 20px;
        border-right: 1px solid rgba(255, 255, 255, 0.15);
    }

    .stat-item:last-child {
        border-right: none;
    }

    .stat-item .num {
        font-family: 'Fraunces', serif;
        font-size: 2.3rem;
        color: var(--brass);
    }

    .stat-item .label {
        font-size: 0.85rem;
        color: #C9D4EE;
        margin-top: 6px;
    }

    /* Banner gradient CTA (mirip pita warna-warni di referensi) */
    .gradient-banner {
        background: linear-gradient(120deg, var(--accent), #5B8CFF 45%, var(--brass));
        border-radius: var(--radius);
        padding: 40px 44px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        color: #fff;
    }

    .gradient-banner h3 {
        color: #fff;
        font-size: 1.4rem;
        max-width: 26ch;
    }

    .gradient-banner p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        margin-top: 8px;
        max-width: 40ch;
    }

    .gradient-banner .btn-light {
        color: var(--ink);
    }

    /* ===== ANIMASI & POLISH PREMIUM ===== */

    /* Scroll reveal */
    .reveal {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity .7s cubic-bezier(.16, 1, .3, 1), transform .7s cubic-bezier(.16, 1, .3, 1);
    }

    .reveal.in-view {
      opacity: 1;
      transform: translateY(0);
    }

    .reveal-delay-1.in-view {
      transition-delay: .08s;
    }

    .reveal-delay-2.in-view {
      transition-delay: .16s;
    }

    .reveal-delay-3.in-view {
      transition-delay: .24s;
    }

    .reveal-delay-4.in-view {
      transition-delay: .32s;
    }

    /* Hero gradient mesh animasi */
    .hero {
      position: relative;
      overflow: hidden;
      padding: 96px 0 60px;
    }

    .hero::before {
      content: '';
      position: absolute;
      inset: -20% -10%;
      z-index: 0;
      background:
        radial-gradient(38% 45% at 15% 20%, rgba(36, 86, 245, 0.14), transparent 70%),
        radial-gradient(32% 40% at 85% 15%, rgba(56, 189, 248, 0.16), transparent 70%),
        radial-gradient(30% 35% at 70% 85%, rgba(255, 122, 69, 0.10), transparent 70%);
      animation: meshFloat 14s ease-in-out infinite alternate;
      pointer-events: none;
    }

    @keyframes meshFloat {
      0% {
        transform: translate(0, 0) scale(1);
      }

      100% {
        transform: translate(-2%, 3%) scale(1.06);
      }
    }

    .hero .wrap {
      position: relative;
      z-index: 1;
    }

    .eyebrow-free h1 {
      background: linear-gradient(100deg, var(--ink) 30%, var(--accent) 60%, var(--accent2));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    /* Tombol shine sweep */
    .btn {
      position: relative;
      overflow: hidden;
      transition: transform .25s ease, box-shadow .25s ease;
    }

    .btn-primary {
      background: linear-gradient(120deg, var(--accent), var(--accent2));
      box-shadow: 0 8px 20px -8px rgba(36, 86, 245, 0.55);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 28px -10px rgba(36, 86, 245, 0.6);
    }

    .btn::after {
      content: '';
      position: absolute;
      top: 0;
      left: -60%;
      width: 40%;
      height: 100%;
      background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.35), transparent);
      transform: skewX(-20deg);
    }

    .btn:hover::after {
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

    /* Kartu hover-lift + zoom foto */
    .cat-card,
    .item-card,
    .pcard {
      transition: transform .35s cubic-bezier(.16, 1, .3, 1), box-shadow .35s ease, border-color .35s ease;
    }

    .cat-card:hover,
    .item-card:hover,
    .pcard:hover {
      transform: translateY(-8px);
      box-shadow: 0 22px 40px -18px rgba(10, 22, 51, 0.25);
      border-color: var(--accent);
    }

    .item-media,
    .pcard .thumb,
    .cat-card-img {
      transition: transform .5s ease;
      overflow: hidden;
    }

    .item-card:hover .item-media,
    .pcard:hover .thumb {
      transform: scale(1.08);
    }

    /* Icon ring pulse */
    .quicknav-item .ic,
    .trust-item .ic,
    .cat-card .icon {
      position: relative;
    }

    .quicknav-item:hover .ic,
    .trust-item:hover .ic {
      animation: pulseRing 1s ease;
    }

    @keyframes pulseRing {
      0% {
        box-shadow: 0 0 0 0 rgba(36, 86, 245, 0.35);
      }

      100% {
        box-shadow: 0 0 0 14px rgba(36, 86, 245, 0);
      }
    }

    /* Counter section */
    .counter-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .counter-item {
      text-align: center;
      padding: 28px 16px;
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
    }

    .counter-item .num {
      font-family: 'Fraunces', serif;
      font-size: 2.1rem;
      background: linear-gradient(120deg, var(--accent), var(--accent2));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .counter-item .label {
      font-size: 0.82rem;
      color: var(--muted);
      margin-top: 4px;
    }

    /* Loading shimmer (opsional, untuk gambar yang lambat load) */
    .thumb,
    .item-media {
      background-color: #EEF2FC;
      background-image: linear-gradient(100deg, transparent 30%, rgba(255, 255, 255, 0.5) 50%, transparent 70%);
      background-size: 200% 100%;
      animation: shimmer 1.8s infinite;
    }

    .thumb[style*="background-image"]:not([style*="none"]),
    .item-media[style*="background-image"] {
      animation: none;
    }

    @keyframes shimmer {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }

    @media (max-width:880px) {
      .counter-row {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width:880px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .stat-item {
            border-right: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .gradient-banner {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
    }

    /* Icon strip navigasi cepat (mirip baris Regulive/Jobs/Electronics di referensi) */
    .quicknav-row {
      background: var(--surface);
      border-bottom: 1px solid var(--line);
      padding: 24px 0;
    }

    .quicknav-grid {
      display: flex;
      gap: 32px;
      overflow-x: auto;
    }

    .quicknav-item {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    .quicknav-item .ic {
      width: 38px;
      height: 38px;
      border-radius: 8px;
      background: var(--accent-soft);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .quicknav-item .ic svg {
      width: 18px;
      height: 18px;
      stroke: var(--accent);
    }

    .quicknav-item .txt strong {
      display: block;
      font-size: 0.88rem;
      color: var(--ink);
    }

    .quicknav-item .txt span {
      font-size: 0.75rem;
      color: var(--muted);
    }

    /* Layout sidebar + grid (mirip Home list / Featured Pages di referensi) */
    .catalog-shell {
      display: grid;
      grid-template-columns: 220px 1fr;
      gap: 28px;
      align-items: start;
    }

    .side-nav {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 10px;
      position: sticky;
      top: 96px;
    }

    .side-nav a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 6px;
      font-size: 0.88rem;
      color: var(--muted);
    }

    .side-nav a.active {
      background: var(--accent-soft);
      color: var(--accent);
      font-weight: 600;
    }

    .side-nav a:hover {
      background: var(--paper);
    }

    /* Kartu item dengan foto asli, mirip Featured Pages */
    .pcard {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .pcard .thumb {
      height: 150px;
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .pcard .thumb .tag {
      position: absolute;
      top: 10px;
      left: 10px;
      background: var(--accent);
      color: #fff;
      font-size: 0.68rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 3px;
      text-transform: uppercase;
    }

    .pcard .body {
      padding: 16px;
    }

    .pcard .body .cat {
      font-size: 0.72rem;
      color: var(--accent);
      font-weight: 600;
      text-transform: uppercase;
    }

    .pcard .body h4 {
      font-size: 0.95rem;
      margin-top: 4px;
      font-family: 'IBM Plex Sans', sans-serif;
      font-weight: 600;
    }

    .pcard .body .price-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 1px solid var(--line);
    }

    .pgrid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px;
    }

    /* Hero collage foto (pengganti mockup HP di referensi) */
    .hero-collage {
      position: relative;
      height: 380px;
    }

    .hero-collage .ph {
      position: absolute;
      border-radius: 12px;
      background-size: cover;
      background-position: center;
      box-shadow: 0 20px 40px -12px rgba(15, 27, 61, 0.35);
      border: 4px solid #fff;
    }

    .hero-collage .ph1 {
      width: 58%;
      height: 65%;
      top: 0;
      left: 0;
      z-index: 2;
    }

    .hero-collage .ph2 {
      width: 48%;
      height: 50%;
      bottom: 0;
      right: 0;
      z-index: 3;
    }

    .hero-collage .ph3 {
      width: 34%;
      height: 34%;
      top: 8%;
      right: 4%;
      z-index: 1;
    }

    @media (max-width:880px) {
      .catalog-shell {
        grid-template-columns: 1fr;
      }

      .side-nav {
        position: static;
        display: flex;
        overflow-x: auto;
        gap: 6px;
      }

      .side-nav a {
        flex-shrink: 0;
        white-space: nowrap;
      }

      .pgrid {
        grid-template-columns: 1fr 1fr;
      }

      .hero-collage {
        height: 260px;
      }
    }

    @media (max-width:560px) {
      .pgrid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width:880px) {
        .trust-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .promo-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width:560px) {
        .trust-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width:880px) {
        .hero-grid {
            grid-template-columns: 1fr;
        }

        .nav-links {
            display: none;
        }

        .nav-toggle {
            display: block;
        }

        .nav-links.nav-open {
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 64px;
            left: 0;
            right: 0;
            background: var(--paper);
            padding: 20px 28px;
            border-bottom: 1px solid var(--line);
            gap: 16px;
        }

        .feature-grid,
        .card-grid,
        .step-grid {
            grid-template-columns: 1fr 1fr;
        }

        .footer-grid {
            grid-template-columns: 1fr;
        }

        .cta-banner {
            flex-direction: column;
            align-items: flex-start;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .booking-card {
            position: static;
        }
    }

    @media (max-width:560px) {

        .feature-grid,
        .card-grid,
        .step-grid {
            grid-template-columns: 1fr;
        }

        .eyebrow-free h1 {
            font-size: 2.1rem;
        }

        .search-card {
            flex-direction: column;
            align-items: stretch;
        }

        .step-indicator {
            flex-direction: column;
            gap: 10px;
        }
    }
    </style>
</head>

<body>

    <header>
        <div class="wrap nav">
            <a href="<?= base_url('/') ?>" class="logo"><?= esc($bizName) ?></a>
            <nav class="nav-links">
                <a href="<?= base_url('/catalog') ?>">Katalog</a>
                <a href="<?= base_url('/') ?>#kategori">Kategori</a>
                <a href="<?= base_url('/') ?>#cara-kerja">Cara Kerja</a>
                <a href="<?= base_url('/cart') ?>">Keranjang</a>
            </nav>
            <div class="nav-actions">
                <a href="/account/login" class="btn btn-outline">Akun Saya</a>
            </div>
            <button class="nav-toggle" type="button">&#9776;</button>
        </div>
    </header>

    <?php if (session()->getFlashdata('success')): ?>
    <div class="wrap" style="padding-top:18px;">
        <div class="alert-box alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    <div class="wrap" style="padding-top:18px;">
        <div class="alert-box alert-warn"><?= esc(session()->getFlashdata('error')) ?></div>
    </div>
    <?php endif; ?>