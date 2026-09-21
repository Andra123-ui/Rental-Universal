<?php
/**
 * @var array $biz
 * @var array $sections
 * @var string $version
 * @var string $lastUpdated
 */
$bizName = $biz['business_name'] ?? 'Rental Universal';
?>
<?= view('partials/header', ['title' => 'Syarat & Ketentuan']) ?>

<div class="page-banner">
    <div class="wrap">
        <div class="crumb"><a href="<?= base_url('/') ?>">Beranda</a> / Syarat & Ketentuan</div>
        <h1>Syarat & Ketentuan Rental</h1>
        <p style="color:#C9D4EE;margin-top:10px;font-size:0.9rem;">
            Versi
            <?= esc($version) ?> &middot; Terakhir diperbarui
            <?= esc(date('d F Y', strtotime($lastUpdated))) ?>
        </p>
    </div>
</div>

<section style="padding-top:32px;">
    <div class="wrap">
        <div class="terms-shell">

            <!-- Sticky Table of Contents -->
            <nav class="terms-toc" id="termsToc">
                <span class="terms-toc-label">Daftar Isi</span>
                <?php foreach ($sections as $sec): ?>
                    <a href="#<?= esc($sec['id']) ?>" data-toc-link data-target="<?= esc($sec['id']) ?>">
                        <?= esc($sec['title']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Isi Terms -->
            <div class="terms-content">
                <div class="alert-box alert-info reveal">
                    Dengan melanjutkan booking di <strong>
                        <?= esc($bizName) ?>
                    </strong>, Anda dianggap telah membaca dan
                    menyetujui seluruh syarat &amp; ketentuan berikut ini.
                </div>

                <?php foreach ($sections as $i => $sec): ?>
                    <div class="terms-section reveal reveal-delay-<?= min($i + 1, 4) ?>" id="<?= esc($sec['id']) ?>">
                        <h2>
                            <?= esc($sec['title']) ?>
                        </h2>
                        <ol class="terms-list">
                            <?php foreach ($sec['content'] as $point): ?>
                                <li>
                                    <?= esc($point) ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endforeach; ?>

                <div class="terms-footer-note reveal">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 8v5M12 16h.01" />
                    </svg>
                    <p>
                        Punya pertanyaan tentang syarat & ketentuan ini? Hubungi kami melalui halaman
                        <a href="<?= base_url('/help') ?>">Bantuan &amp; FAQ</a>.
                    </p>
                </div>

                <!-- Aksi utama: Kembali ke booking -->
                <div class="terms-actions">
                    <button type="button" id="btnBackToBooking" class="btn btn-primary">
                        &larr; Kembali ke Booking
                    </button>
                    <a href="<?= base_url('/catalog') ?>" class="btn btn-outline">Lihat Katalog</a>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
    .terms-shell {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 44px;
        align-items: start;
    }

    .terms-toc {
        position: sticky;
        top: 96px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .terms-toc-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        margin-bottom: 8px;
    }

    .terms-toc a {
        padding: 9px 10px;
        border-radius: 6px;
        font-size: 0.86rem;
        color: var(--muted);
        transition: background .15s ease, color .15s ease;
    }

    .terms-toc a:hover {
        background: var(--paper);
        color: var(--ink);
    }

    .terms-toc a.active {
        background: var(--accent-soft);
        color: var(--accent);
        font-weight: 600;
    }

    .terms-section {
        margin-bottom: 40px;
        padding-bottom: 32px;
        border-bottom: 1px solid var(--line);
    }

    .terms-section:last-of-type {
        border-bottom: none;
    }

    .terms-section h2 {
        font-size: 1.35rem;
        margin-bottom: 16px;
        scroll-margin-top: 110px;
    }

    .terms-list {
        margin: 0;
        padding-left: 22px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .terms-list li {
        font-size: 0.92rem;
        color: var(--muted);
        line-height: 1.7;
    }

    .terms-footer-note {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        background: var(--paper);
        border-radius: var(--radius);
        padding: 16px 18px;
        margin-top: 8px;
    }

    .terms-footer-note svg {
        width: 18px;
        height: 18px;
        stroke: var(--accent);
        flex-shrink: 0;
        margin-top: 2px;
    }

    .terms-footer-note p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--muted);
    }

    .terms-footer-note a {
        color: var(--accent);
        font-weight: 600;
    }

    .terms-actions {
        display: flex;
        gap: 12px;
        margin-top: 28px;
        flex-wrap: wrap;
    }

    @media (max-width:880px) {
        .terms-shell {
            grid-template-columns: 1fr;
        }

        .terms-toc {
            position: static;
            flex-direction: row;
            overflow-x: auto;
            gap: 6px;
        }

        .terms-toc a {
            flex-shrink: 0;
            white-space: nowrap;
        }

        .terms-toc-label {
            display: none;
        }
    }
</style>

<script>
    (function () {
        // Highlight TOC link sesuai section yang sedang terlihat di layar
        const sections = document.querySelectorAll('.terms-section');
        const tocLinks = document.querySelectorAll('[data-toc-link]');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const link = document.querySelector('[data-target="' + entry.target.id + '"]');
                if (!link) return;
                if (entry.isIntersecting) {
                    tocLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                }
            });
        }, { rootMargin: '-100px 0px -60% 0px' });

        sections.forEach(sec => observer.observe(sec));

        // Tombol "Kembali ke Booking": kembali ke halaman sebelumnya (mis. checkout/keranjang),
        // fallback ke /cart kalau tidak ada riwayat browser yang relevan.
        document.getElementById('btnBackToBooking').addEventListener('click', function () {
            const ref = document.referrer;
            const sameSite = ref && ref.indexOf(window.location.origin) === 0;
            if (sameSite && window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?= base_url('/cart') ?>';
            }
        });
    })();
</script>

<?= view('partials/footer') ?>