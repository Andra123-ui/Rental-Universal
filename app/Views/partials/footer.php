<?php
$biz = site_business();
$bizName = $biz['business_name'] ?? 'Rental Universal';
$bizPhone = $biz['phone'] ?? '0823 8444 4812';
$bizEmail = $biz['email'] ?? 'info@gusaha.id';
$bizAddr = $biz['address'] ?? ' Maspion IT Lt 1 Blok F No 12. Jln Ahmad Yani 83 Surabaya';

// Untuk embed peta: encode alamat supaya bisa langsung dipakai di query Google Maps
$mapQuery = urlencode($bizAddr);
?>

<!-- Tambahan Style Khusus untuk Memperlebar Peta ke Kanan -->
<style>
    .footer-top-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1.2fr 1.5fr;
        /* Kolom peta dibuat lebih lebar (1.5fr) */
        gap: 30px;
        align-items: start;
    }

    .footer-map-col,
    .footer-map-frame {
        width: 100% !important;
    }

    .footer-map-frame iframe {
        width: 100% !important;
        height: 220px;
        display: block;
        border-radius: 8px;
    }

    @media (max-width: 992px) {
        .footer-top-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 576px) {
        .footer-top-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Bar Statistik -->
<section class="stats-bar">
    <div class="wrap">
        <div class="stats-intro reveal">
            <span class="eyebrow">Dipercaya Banyak Pelanggan</span>
            <h2>Sewa <span>lebih mudah</span>, transaksi lebih terpercaya</h2>
        </div>
    </div>
    <div class="wrap">
        <div class="stats-grid">
            <div class="stat-item reveal">
                <div class="num">500+</div>
                <div class="label">Produk & Jasa Tersedia</div>
            </div>
            <div class="stat-item reveal reveal-delay-1">
                <div class="num">2000+</div>
                <div class="label">Booking Selesai</div>
            </div>
            <div class="stat-item reveal reveal-delay-2">
                <div class="num">24/7</div>
                <div class="label">Cek Status via Invoice</div>
            </div>
        </div>
    </div>
</section>

<!-- Banner Gradient CTA -->
<section style="background:var(--paper);padding:56px 0;">
    <div class="wrap">
        <div class="gradient-banner reveal">
            <div>
                <h3>Kenapa booking di sini lebih berbeda?</h3>
                <p>Ketersediaan real-time, invoice otomatis, dan tanpa ribet bikin akun untuk mulai transaksi.</p>
            </div>
            <a href="<?= base_url('/katalog') ?>" class="btn btn-light">Mulai Booking</a>
        </div>
    </div>
</section>

<footer id="kontak">
    <div class="wrap">
        <div class="footer-top-grid">

            <!-- Kolom 1: Brand + Sosial -->
            <div class="footer-col footer-brand-col">
                <a href="<?= base_url('/') ?>" class="logo">
                    <?= esc($bizName) ?>
                </a>
                <p>Sistem sewa barang dan jasa dalam satu platform — cek ketersediaan, booking, dan kelola riwayat
                    transaksi Anda sendiri.</p>
                <div class="footer-social">
                    <!-- Link Facebook -->
                    <a href="https://facebook.com/NAMA_AKUN_ANDA" target="_blank" rel="noopener noreferrer"
                        aria-label="Facebook">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                        </svg>
                    </a>

                    <!-- Link Instagram -->
                    <a href="https://www.instagram.com/otwgusaha?stkn=Zmttank4bDN5cDB4" target="_blank"
                        rel="noopener noreferrer" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="5" />
                            <circle cx="12" cy="12" r="4" />
                            <circle cx="17.5" cy="6.5" r="0.6" fill="currentColor" />
                        </svg>
                    </a>

                    <!-- Link WhatsApp -->
                    <a href="https://wa.me/6282384444812" target="_blank" rel="noopener noreferrer"
                        aria-label="WhatsApp">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 20l1.1-5.5A8.5 8.5 0 1 1 21 11.5z" />
                            <path d="M8.5 9.5c0 3.5 2.5 6 6 6" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Kolom 2: Tautan Cepat -->
            <div class="footer-col">
                <h4>Tautan Cepat</h4>
                <ul>
                    <li><a href="<?= base_url('/katalog') ?>">Katalog</a></li>
                    <li><a href="<?= base_url('/keranjang') ?>">Keranjang</a></li>
                    <li><a href="<?= base_url('/cek-booking') ?>">Cek Booking</a></li>
                    <li><a href="<?= site_url('account/login') ?>">Masuk / Akun Saya</a></li>
                </ul>
            </div>

            <!-- Kolom 3: Kontak -->
            <div class="footer-col">
                <h4>Hubungi Kami</h4>
                <ul class="footer-contact-list">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z" />
                            <circle cx="12" cy="10" r="3" />
                        </svg>
                        <span>
                            <?= esc($bizAddr) ?>
                        </span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 2 .7 3a2 2 0 0 1-.4 2.1L8 10.5a16 16 0 0 0 6 6l1.7-1.4a2 2 0 0 1 2.1-.4c1 .4 2 .6 3 .7a2 2 0 0 1 1.2 2z" />
                        </svg>
                        <span>
                            <?= esc($bizPhone) ?>
                        </span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2" />
                            <path d="M2 7l10 6 10-6" />
                        </svg>
                        <span>
                            <?= esc($bizEmail) ?>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Kolom 4: Peta Lokasi (Melebar penuh ke kanan) -->
            <div class="footer-col footer-map-col">
                <h4>Lokasi Kami</h4>
                <div class="footer-map-frame">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3014.384617266144!2d112.73400337357207!3d-7.314977571923019!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7e5177dd44385%3A0xde3d536a4fcc22b6!2sCV.%20OTW%20Computer%20Gusaha!5e1!3m2!1sid!2sid!4v1789364969811!5m2!1sid!2sid"
                        style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
                <a href="https://maps.google.com/maps?q=<?= $mapQuery ?>" target="_blank" rel="noopener"
                    class="footer-map-link">
                    Buka di Google Maps &rarr;
                </a>
            </div>

        </div>

        <div class="footer-bottom">
            <span>&copy;
                <?= date('Y') ?>
                <?= esc($bizName) ?>. Semua hak dilindungi.
            </span>
            <span>Dibangun dengan CodeIgniter 4</span>
        </div>
    </div>
</footer>

<script>
    // Toggle nav mobile
    document.querySelectorAll('.nav-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelector('.nav-links').classList.toggle('nav-open');
        });
    });

    // Scroll reveal
    const revealEls = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('in-view');
                revealObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.15 });
    revealEls.forEach(el => revealObserver.observe(el));

    // Counter animation
    const counters = document.querySelectorAll('.counter-item .num[data-target]');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                const el = e.target;
                const target = parseInt(el.dataset.target, 10);
                const suffix = el.dataset.suffix || '';
                let current = 0;
                const step = Math.max(1, Math.ceil(target / 60));
                const tick = () => {
                    current += step;
                    if (current >= target) { el.textContent = target + suffix; return; }
                    el.textContent = current + suffix;
                    requestAnimationFrame(tick);
                };
                tick();
                counterObserver.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(el => counterObserver.observe(el));
</script>
</body>

</html>