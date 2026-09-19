<?php
/**
 * @var array $biz
 * @var array $faqs
 * @var array $serviceHours
 */
$bizName = $biz['business_name'] ?? 'Rental Universal';
$bizPhone = $biz['phone'] ?? '0823 8444 4812';
$bizEmail = $biz['email'] ?? 'info@gusaha.id';
$bizAddr = $biz['address'] ?? 'Maspion IT Lt 1 Blok F No 12. Jln Ahmad Yani 83 Surabaya';

// Format nomor WhatsApp: hapus non-digit, ganti awalan 0 dengan 62
$waNumber = preg_replace('/\D/', '', $bizPhone);
if (str_starts_with($waNumber, '0')) {
    $waNumber = '62' . substr($waNumber, 1);
}
$waLink = 'https://wa.me/' . $waNumber . '?text=' . urlencode('Halo, saya mau tanya tentang booking di ' . $bizName);
?>
<?= view('partials/header', ['title' => 'Bantuan & FAQ']) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Bantuan</div>
        <h1>Bantuan & Pertanyaan Umum</h1>
        <p style="color:#C9D4EE;margin-top:10px;font-size:0.95rem;max-width:50ch;">
            Temukan jawaban cepat, atau hubungi kami langsung lewat WhatsApp, telepon, dan email.
        </p>
    </div>
</div>

<section style="padding-top:32px;">
    <div class="wrap">

        <!-- Quick action cards -->
        <div class="help-quick-grid reveal">
            <a href="<?= esc($waLink) ?>" target="_blank" rel="noopener" class="help-quick-card">
                <div class="ic wa">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 20l1.1-5.5A8.5 8.5 0 1 1 21 11.5z" />
                        <path d="M8.5 9.5c0 3.5 2.5 6 6 6" />
                    </svg>
                </div>
                <div><strong>WhatsApp</strong><span>Respon tercepat</span></div>
            </a>
            <a href="tel:<?= esc(preg_replace('/\D/', '', $bizPhone)) ?>" class="help-quick-card">
                <div class="ic phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path
                            d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 2 .7 3a2 2 0 0 1-.4 2.1L8 10.5a16 16 0 0 0 6 6l1.7-1.4a2 2 0 0 1 2.1-.4c1 .4 2 .6 3 .7a2 2 0 0 1 1.2 2z" />
                    </svg>
                </div>
                <div><strong>Telepon</strong><span><?= esc($bizPhone) ?></span></div>
            </a>
            <a href="mailto:<?= esc($bizEmail) ?>" class="help-quick-card">
                <div class="ic mail">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path d="M2 7l10 6 10-6" />
                    </svg>
                </div>
                <div><strong>Email</strong><span>Balasan 1x24 jam</span></div>
            </a>
            <a href="<?= base_url('/cek-booking') ?>" class="help-quick-card">
                <div class="ic invoice">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M9 14l2 2 4-4" />
                        <rect x="3" y="5" width="18" height="16" rx="2" />
                    </svg>
                </div>
                <div><strong>Cek Booking</strong><span>Pakai nomor invoice</span></div>
            </a>
        </div>

        <div class="help-main-grid">

            <!-- FAQ Accordion -->
            <div class="reveal">
                <div class="section-head" style="margin-bottom:24px;">
                    <span class="eyebrow">Pertanyaan Umum</span>
                    <h2 style="font-size:1.5rem;">Frequently Asked Questions</h2>
                </div>

                <div class="faq-list">
                    <?php foreach ($faqs as $i => $faq): ?>
                        <div class="faq-item">
                            <button type="button" class="faq-question" data-faq-toggle>
                                <span><?= esc($faq['q']) ?></span>
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="faq-chevron">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                            <div class="faq-answer">
                                <p><?= esc($faq['a']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert-box alert-info" style="margin-top:24px;">
                    Tidak menemukan jawaban yang Anda cari? Langsung hubungi kami lewat
                    <a href="<?= esc($waLink) ?>" target="_blank" rel="noopener"
                        style="font-weight:600;color:#1D4ED8;">WhatsApp</a>.
                </div>
            </div>

            <!-- Sidebar: Kontak + Jam Layanan -->
            <div class="reveal reveal-delay-1">
                <div class="help-sidebar-card">
                    <h3>Hubungi Kami</h3>
                    <ul class="footer-contact-list" style="margin-top:16px;">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <span><?= esc($bizAddr) ?></span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 2 .7 3a2 2 0 0 1-.4 2.1L8 10.5a16 16 0 0 0 6 6l1.7-1.4a2 2 0 0 1 2.1-.4c1 .4 2 .6 3 .7a2 2 0 0 1 1.2 2z" />
                            </svg>
                            <span><?= esc($bizPhone) ?></span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                                <path d="M2 7l10 6 10-6" />
                            </svg>
                            <span><?= esc($bizEmail) ?></span>
                        </li>
                    </ul>
                </div>

                <div class="help-sidebar-card" style="margin-top:20px;">
                    <h3>Jam Layanan</h3>
                    <ul class="help-hours-list">
                        <?php foreach ($serviceHours as $sh): ?>
                            <li>
                                <span class="day"><?= esc($sh['day']) ?></span>
                                <span class="time"><?= esc($sh['time']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="help-sidebar-card" style="margin-top:20px;">
                    <h3>Tautan Cepat</h3>
                    <ul style="margin-top:14px;">
                        <li><a href="<?= base_url('/catalog') ?>">Lihat Katalog</a></li>
                        <li><a href="<?= base_url('/cek-booking') ?>">Cek Status Booking</a></li>
                        <li><a href="<?= base_url('/terms') ?>">Syarat & Ketentuan</a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
    .help-quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 56px;
    }

    .help-quick-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }

    .help-quick-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 30px -16px rgba(23, 37, 84, 0.2);
        border-color: var(--accent);
    }

    .help-quick-card .ic {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .help-quick-card .ic svg {
        width: 19px;
        height: 19px;
        stroke: #fff;
    }

    .help-quick-card .ic.wa {
        background: #25D366;
    }

    .help-quick-card .ic.phone {
        background: var(--accent);
    }

    .help-quick-card .ic.mail {
        background: var(--accent2);
    }

    .help-quick-card .ic.invoice {
        background: var(--brass);
    }

    .help-quick-card strong {
        display: block;
        font-size: 0.92rem;
        color: var(--ink);
    }

    .help-quick-card span {
        font-size: 0.78rem;
        color: var(--muted);
    }

    .help-main-grid {
        display: grid;
        grid-template-columns: 1.4fr 0.9fr;
        gap: 44px;
        align-items: start;
    }

    .faq-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .faq-item {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
    }

    .faq-question {
        width: 100%;
        background: none;
        border: none;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--ink);
        cursor: pointer;
        font-family: inherit;
    }

    .faq-chevron {
        width: 18px;
        height: 18px;
        stroke: var(--accent);
        flex-shrink: 0;
        transition: transform .25s ease;
    }

    .faq-item.open .faq-chevron {
        transform: rotate(180deg);
    }

    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height .3s ease;
    }

    .faq-item.open .faq-answer {
        max-height: 240px;
    }

    .faq-answer p {
        padding: 0 20px 18px;
        margin: 0;
        font-size: 0.9rem;
        color: var(--muted);
        line-height: 1.65;
    }

    .help-sidebar-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 24px;
    }

    .help-sidebar-card h3 {
        font-size: 1rem;
    }

    .help-sidebar-card ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .help-sidebar-card ul a {
        font-size: 0.88rem;
        color: var(--accent);
        font-weight: 600;
    }

    .help-sidebar-card ul a:hover {
        color: var(--ink);
    }

    .help-hours-list {
        margin-top: 14px !important;
    }

    .help-hours-list li {
        display: flex;
        justify-content: space-between;
        font-size: 0.88rem;
        padding: 8px 0;
        border-bottom: 1px solid var(--line);
    }

    .help-hours-list li:last-child {
        border-bottom: none;
    }

    .help-hours-list .day {
        color: var(--muted);
    }

    .help-hours-list .time {
        font-weight: 600;
        color: var(--ink);
    }

    @media (max-width:880px) {
        .help-quick-grid {
            grid-template-columns: 1fr 1fr;
        }

        .help-main-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width:560px) {
        .help-quick-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.querySelectorAll('[data-faq-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const item = btn.closest('.faq-item');
            const wasOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item.open').forEach(function (el) { el.classList.remove('open'); });
            if (!wasOpen) item.classList.add('open');
        });
    });
</script>

<?= view('partials/footer') ?>