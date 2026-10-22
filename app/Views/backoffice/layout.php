<?php helper('backoffice'); $nav = $nav ?? ''; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Backoffice') ?> — Backoffice</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
    :root {
        --accent: #2563EB;
        --accent2: #06B6D4;
        --ink: #172554;
        --muted: #64748B;
        --paper: #F5F9FF;
        --line: #D9E7F7;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        display: flex;
        min-height: 100vh;
        background: var(--paper);
        color: var(--ink);
        font-family: 'IBM Plex Sans', sans-serif;
    }

    .sidebar {
        width: 230px;
        flex-shrink: 0;
        background: var(--ink);
        color: #fff;
        padding: 24px 14px;
        position: sticky;
        top: 0;
        height: 100vh;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .sidebar .brand {
        font-family: 'Fraunces', serif;
        font-size: 1.2rem;
        font-weight: 600;
        padding: 0 10px 18px;
    }

    .sidebar a {
        display: block;
        padding: 10px 12px;
        border-radius: 8px;
        color: #C9D4EE;
        text-decoration: none;
        font-size: .92rem;
    }

    .sidebar a:hover {
        background: rgba(255, 255, 255, .08);
        color: #fff;
    }

    .sidebar a.active {
        background: rgba(255, 255, 255, .14);
        color: #fff;
        font-weight: 600;
    }

    .sidebar .spacer {
        flex: 1;
    }

    .sidebar .who {
        padding: 10px 12px;
        font-size: .78rem;
        color: #8FA0C7;
    }

    .sidebar button {
        width: 100%;
        text-align: left;
        padding: 10px 12px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #FCA5A5;
        font: inherit;
        font-size: .92rem;
        cursor: pointer;
    }

    .sidebar button:hover {
        background: rgba(255, 255, 255, .08);
    }

    .main {
        flex: 1;
        min-width: 0;
        padding: 32px;
    }

    .page-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 22px;
    }

    h1 {
        margin: 0;
        font-family: 'Fraunces', serif;
        font-size: 1.6rem;
        font-weight: 600;
    }

    .panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 22px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 18px;
        border: 1px solid transparent;
        border-radius: 8px;
        font: inherit;
        font-weight: 600;
        font-size: .88rem;
        cursor: pointer;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(120deg, var(--accent), var(--accent2));
        color: #fff;
    }

    .btn-ghost {
        background: #fff;
        border-color: var(--line);
        color: var(--ink);
    }

    .btn-ghost:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: .8rem;
    }

    .alert {
        margin-bottom: 18px;
        padding: 12px 14px;
        border-radius: 8px;
        font-size: .88rem;
    }

    .alert-error {
        background: #FEE2E2;
        color: #B91C1C;
    }

    .alert-success {
        background: #DCFCE7;
        color: #166534;
    }

    .alert ul {
        margin: 0;
        padding-left: 18px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: .9rem;
    }

    th,
    td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid var(--line);
        vertical-align: middle;
    }

    th {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--muted);
    }

    tr:last-child td {
        border-bottom: 0;
    }

    .muted {
        color: var(--muted);
        font-size: .82rem;
    }

    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: .74rem;
        font-weight: 600;
    }

    .badge-ok {
        background: #DCFCE7;
        color: #166534;
    }

    .badge-off {
        background: #F1F5F9;
        color: #64748B;
    }

    .badge-warn {
        background: #FEF3C7;
        color: #92400E;
    }

    .filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-size: .82rem;
        font-weight: 600;
        color: var(--muted);
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--line);
        border-radius: 8px;
        background: var(--paper);
        font: inherit;
        font-size: .92rem;
        color: var(--ink);
    }

    input,
    select {
        height: 42px;
    }

    textarea {
        resize: vertical;
    }

    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 20px;
    }

    .form-grid .full {
        grid-column: 1 / -1;
    }

    .hint {
        margin-top: 4px;
        font-size: .78rem;
        color: var(--muted);
    }

    .actions {
        display: flex;
        gap: 10px;
        margin-top: 22px;
    }

    .inline {
        display: inline;
    }

    .pager {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        align-items: center;
        margin-top: 16px;
        font-size: .85rem;
        color: var(--muted);
    }

    @media (max-width:860px) {
        body {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: auto;
            position: static;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            padding: 12px;
        }

        .sidebar .brand {
            padding: 0 10px 0 0;
        }

        .sidebar .spacer,
        .sidebar .who {
            display: none;
        }

        .main {
            padding: 20px 16px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .table-wrap {
            overflow-x: auto;
        }
    }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="brand">Backoffice</div>
        <a href="<?= site_url('backoffice/dashboard') ?>"
            class="<?= $nav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <?php if (admin_can('product.view') || admin_can('product.manage')): ?>
        <a href="<?= site_url('backoffice/produk') ?>" class="<?= $nav === 'produk' ? 'active' : '' ?>">Produk</a>
        <?php endif; ?>
        <?php if (admin_can('jenis.manage')): ?>
        <a href="<?= site_url('backoffice/jenis') ?>" class="<?= $nav === 'jenis' ? 'active' : '' ?>">Jenis</a>
        <?php endif; ?>
        <?php if (admin_can('customer.verify')): ?>
        <a href="<?= site_url('backoffice/verifikasi-identitas') ?>"
            class="<?= $nav === 'verifikasi' ? 'active' : '' ?>">Verifikasi Identitas</a>
        <?php endif; ?>
        <div class="spacer"></div>
        <div class="who"><?= esc(session()->get('admin_name')) ?> · <?= esc(session()->get('admin_role')) ?></div>
        <form action="<?= site_url('backoffice/logout') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit">Keluar</button>
        </form>
    </aside>

    <main class="main">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
                <li><?= esc($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </main>
</body>

</html>