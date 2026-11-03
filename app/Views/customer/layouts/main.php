<?php
/**
 * Layout Global Customer Portal Rental Universal
 * Bootstrap 5.3 + Bootstrap Icons
 */
$bizName = $business['business_name'] ?? 'Rental Universal';
$bizPhone = $business['phone'] ?? '';
$waUrl = !empty($bizPhone) ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $bizPhone) : '#';

$custName = trim((string)($customer['name'] ?? 'Pelanggan'));
$custPhone = $customerAccount['login_phone'] ?? ($customer['phone'] ?? '-');
$custInitial = $custName !== '' ? strtoupper(mb_substr($custName, 0, 1)) : 'U';
$custPhoto = !empty($customer['profile_photo']) ? base_url('account/profile/photo') : null;
$accountStatus = $customerAccount['status'] ?? 'ACTIVE';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Portal Pelanggan') ?> — <?= esc($bizName) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bs-primary: #2563EB;
            --bs-primary-rgb: 37, 99, 235;
            --brand-ink: #172554;
            --brand-paper: #F8FAFC;
            --brand-surface: #FFFFFF;
            --brand-line: #E2E8F0;
            --brand-accent: #2563EB;
            --brand-accent-soft: #EFF6FF;
            --brand-muted: #64748B;
        }

        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--brand-paper);
            color: var(--brand-ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, h4, .brand-font {
            font-family: 'Fraunces', Georgia, serif;
            letter-spacing: -0.01em;
        }

        /* Top Navbar */
        .portal-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--brand-line);
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .portal-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--brand-ink);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .portal-brand:hover {
            color: var(--brand-accent);
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            object-fit: cover;
            border: 2px solid #E2E8F0;
        }

        /* Sidebar Nav */
        .portal-sidebar {
            background: #FFFFFF;
            border-right: 1px solid var(--brand-line);
            min-height: calc(100vh - 64px);
            padding: 1.5rem 0.75rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--brand-muted);
            letter-spacing: 0.05em;
            padding: 0.5rem 0.75rem;
            margin-top: 0.5rem;
        }

        .portal-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            color: #334155;
            font-weight: 500;
            font-size: 0.925rem;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            margin-bottom: 0.25rem;
        }

        .portal-nav-link i {
            font-size: 1.15rem;
            color: #64748B;
            transition: color 0.15s ease-in-out;
        }

        .portal-nav-link:hover {
            background-color: var(--brand-accent-soft);
            color: var(--brand-accent);
        }
        .portal-nav-link:hover i {
            color: var(--brand-accent);
        }

        .portal-nav-link.active {
            background-color: var(--brand-accent);
            color: #FFFFFF;
            font-weight: 600;
        }
        .portal-nav-link.active i {
            color: #FFFFFF;
        }

        /* Main Content */
        .portal-main {
            padding: 1.75rem 1.5rem 3rem;
            flex: 1;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .breadcrumb {
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }
        .breadcrumb-item a {
            text-decoration: none;
            color: var(--brand-muted);
        }

        /* Cards & Components */
        .card-custom {
            background: #FFFFFF;
            border: 1px solid var(--brand-line);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: box-shadow 0.2s ease-in-out;
        }
        .card-custom:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .card-custom .card-header {
            background: transparent;
            border-bottom: 1px solid var(--brand-line);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        /* Table custom */
        .table-custom {
            margin-bottom: 0;
        }
        .table-custom th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--brand-muted);
            border-bottom: 1px solid var(--brand-line);
            padding: 0.75rem 1rem;
            background: #F8FAFC;
        }
        .table-custom td {
            padding: 0.9rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--brand-line);
            font-size: 0.925rem;
        }

        /* Empty state */
        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .empty-state-icon {
            font-size: 3rem;
            color: #94A3B8;
            margin-bottom: 1rem;
        }

        /* Footer */
        .portal-footer {
            background: #FFFFFF;
            border-top: 1px solid var(--brand-line);
            padding: 1.25rem 0;
            color: var(--brand-muted);
            font-size: 0.85rem;
            margin-top: auto;
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>

    <!-- Topbar Navigation -->
    <header class="portal-topbar d-flex align-items-center">
        <div class="container-fluid px-3 px-lg-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <!-- Mobile Sidebar Toggle -->
                <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <a href="<?= site_url('account') ?>" class="portal-brand">
                    <i class="bi bi-truck text-primary fs-4"></i>
                    <span><?= esc($bizName) ?></span>
                </a>
                <span class="badge bg-light text-primary border d-none d-md-inline-block ms-1">Customer Portal</span>
            </div>

            <!-- Right quick menu -->
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <a href="<?= site_url('catalog') ?>" class="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="bi bi-grid"></i>
                    <span>Katalog Rental</span>
                </a>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-link p-0 text-decoration-none dropdown-toggle d-flex align-items-center gap-2 text-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($custPhoto): ?>
                            <img src="<?= $custPhoto ?>" alt="<?= esc($custName) ?>" class="avatar-circle">
                        <?php else: ?>
                            <div class="avatar-circle"><?= esc($custInitial) ?></div>
                        <?php endif; ?>
                        <div class="text-start d-none d-md-block">
                            <div class="fw-semibold lh-sm text-truncate" style="max-width: 140px;"><?= esc($custName) ?></div>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= esc(mask_text($custPhone, 4, 3)) ?></small>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 220px;">
                        <li class="px-3 py-2 border-bottom mb-1">
                            <div class="fw-semibold text-truncate"><?= esc($custName) ?></div>
                            <small class="text-muted d-block"><?= esc($custPhone) ?></small>
                            <span class="badge <?= $accountStatus === 'ACTIVE' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> mt-1" style="font-size: 0.7rem;">
                                <?= $accountStatus === 'ACTIVE' ? 'Akun Aktif' : 'Status: ' . esc($accountStatus) ?>
                            </span>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= site_url('account/profile') ?>">
                                <i class="bi bi-person text-muted"></i>
                                <span>Profil Saya</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= site_url('account/security') ?>">
                                <i class="bi bi-shield-lock text-muted"></i>
                                <span>Keamanan Akun</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= site_url('account/logout') ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item py-2 text-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container Layout (Sidebar + Content) -->
    <div class="container-fluid px-0 flex-grow-1">
        <div class="row g-0">
            <!-- Desktop Sidebar -->
            <aside class="col-lg-3 col-xl-2 d-none d-lg-block portal-sidebar">
                <nav class="d-flex flex-column h-100">
                    <div class="nav-section-title">Menu Utama</div>
                    <a href="<?= site_url('account') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= site_url('account/bookings') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'bookings' ? 'active' : '' ?>">
                        <i class="bi bi-calendar2-check"></i>
                        <span>Riwayat Booking</span>
                    </a>
                    <a href="<?= site_url('account/payments') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'payments' ? 'active' : '' ?>">
                        <i class="bi bi-credit-card"></i>
                        <span>Pembayaran Saya</span>
                    </a>
                    <a href="<?= site_url('account/documents') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'documents' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Invoice & Dokumen</span>
                    </a>

                    <div class="nav-section-title mt-3">Akun & Identitas</div>
                    <a href="<?= site_url('account/profile') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'profile' ? 'active' : '' ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>Profil Saya</span>
                    </a>
                    <a href="<?= site_url('account/identity-documents') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'identity' ? 'active' : '' ?>">
                        <i class="bi bi-card-heading"></i>
                        <span>Dokumen Identitas</span>
                    </a>
                    <a href="<?= site_url('account/security') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'security' ? 'active' : '' ?>">
                        <i class="bi bi-shield-check"></i>
                        <span>Keamanan Akun</span>
                    </a>

                    <!-- Bottom Support CTA -->
                    <div class="mt-auto pt-4 px-2">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <i class="bi bi-headset fs-3 text-primary mb-2 d-block"></i>
                            <div class="fw-semibold small mb-1">Butuh Bantuan?</div>
                            <p class="text-muted small mb-2" style="font-size: 0.78rem;">Tim support kami siap membantu booking Anda.</p>
                            <a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-whatsapp"></i>
                                <span>Hubungi CS</span>
                            </a>
                        </div>
                    </div>
                </nav>
            </aside>

            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title brand-font text-primary" id="mobileSidebarLabel"><?= esc($bizName) ?></h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-3">
                    <div class="d-flex align-items-center gap-3 p-2 bg-light rounded-3 mb-3">
                        <div class="avatar-circle"><?= esc($custInitial) ?></div>
                        <div class="text-truncate">
                            <div class="fw-bold"><?= esc($custName) ?></div>
                            <small class="text-muted"><?= esc($custPhone) ?></small>
                        </div>
                    </div>

                    <div class="nav-section-title">Menu Utama</div>
                    <a href="<?= site_url('account') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= site_url('account/bookings') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'bookings' ? 'active' : '' ?>">
                        <i class="bi bi-calendar2-check"></i>
                        <span>Riwayat Booking</span>
                    </a>
                    <a href="<?= site_url('account/payments') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'payments' ? 'active' : '' ?>">
                        <i class="bi bi-credit-card"></i>
                        <span>Pembayaran Saya</span>
                    </a>
                    <a href="<?= site_url('account/documents') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'documents' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Invoice & Dokumen</span>
                    </a>

                    <div class="nav-section-title mt-3">Akun & Identitas</div>
                    <a href="<?= site_url('account/profile') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'profile' ? 'active' : '' ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>Profil Saya</span>
                    </a>
                    <a href="<?= site_url('account/identity-documents') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'identity' ? 'active' : '' ?>">
                        <i class="bi bi-card-heading"></i>
                        <span>Dokumen Identitas</span>
                    </a>
                    <a href="<?= site_url('account/security') ?>" class="portal-nav-link <?= ($activeNav ?? '') === 'security' ? 'active' : '' ?>">
                        <i class="bi bi-shield-check"></i>
                        <span>Keamanan Akun</span>
                    </a>

                    <div class="border-top my-3 pt-3">
                        <a href="<?= site_url('catalog') ?>" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-grid me-1"></i> Katalog Rental
                        </a>
                        <form action="<?= site_url('account/logout') ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-box-arrow-right me-1"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <main class="col-lg-9 col-xl-10 portal-main">
                <!-- Breadcrumbs & Header -->
                <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <div>
                        <?php if (!empty($breadcrumbs)): ?>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?= site_url('account') ?>">Portal</a></li>
                                    <?php foreach ($breadcrumbs as $label => $link): ?>
                                        <?php if ($link): ?>
                                            <li class="breadcrumb-item"><a href="<?= site_url($link) ?>"><?= esc($label) ?></a></li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item active" aria-current="page"><?= esc($label) ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ol>
                            </nav>
                        <?php endif; ?>
                        <h2 class="h3 fw-bold mb-1"><?= esc($pageTitle ?? 'Dashboard') ?></h2>
                        <?php if (!empty($pageSubtitle)): ?>
                            <p class="text-muted mb-0 small"><?= esc($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($pageAction)): ?>
                        <div class="page-action">
                            <?= $pageAction ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Flash Messages -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div><?= session()->getFlashdata('success') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= session()->getFlashdata('error') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('info')): ?>
                    <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div><?= session()->getFlashdata('info') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors') && is_array(session()->getFlashdata('errors'))): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <strong>Mohon periksa kesalahan input berikut:</strong>
                        </div>
                        <ul class="mb-0 ps-4 small">
                            <?php foreach (session()->getFlashdata('errors') as $err): ?>
                                <li><?= esc($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Page Content Section -->
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="portal-footer">
        <div class="container-fluid px-3 px-lg-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong><?= esc($bizName) ?></strong>. Seluruh hak cipta dilindungi.
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="<?= site_url('terms') ?>" class="text-decoration-none text-muted">Syarat & Ketentuan</a>
                <span class="text-muted">•</span>
                <a href="<?= site_url('help') ?>" class="text-decoration-none text-muted">Pusat Bantuan</a>
                <span class="text-muted">•</span>
                <a href="<?= esc($waUrl) ?>" target="_blank" rel="noopener" class="text-decoration-none text-success">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>

