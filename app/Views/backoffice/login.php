<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Backoffice — <?= esc(site_business()['business_name'] ?? 'Rental Universal') ?></title>
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
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: var(--paper);
        color: var(--ink);
        font-family: 'IBM Plex Sans', sans-serif;
    }

    .card {
        width: 100%;
        max-width: 400px;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 36px 32px;
        box-shadow: 0 20px 40px -24px rgba(23, 37, 84, .3);
    }

    h1 {
        margin: 0 0 4px;
        font-family: 'Fraunces', serif;
        font-weight: 600;
        font-size: 1.5rem;
    }

    .sub {
        margin: 0 0 24px;
        color: var(--muted);
        font-size: .9rem;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-size: .82rem;
        font-weight: 600;
        color: var(--muted);
    }

    .field {
        margin-bottom: 16px;
    }

    input {
        width: 100%;
        height: 44px;
        padding: 0 12px;
        border: 1px solid var(--line);
        border-radius: 8px;
        background: var(--paper);
        font: inherit;
        font-size: .95rem;
        color: var(--ink);
    }

    input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    button {
        width: 100%;
        height: 46px;
        margin-top: 6px;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(120deg, var(--accent), var(--accent2));
        color: #fff;
        font: inherit;
        font-weight: 600;
        cursor: pointer;
    }

    button:disabled {
        opacity: .7;
        cursor: wait;
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

    .back {
        display: block;
        margin-top: 20px;
        text-align: center;
        font-size: .82rem;
        color: var(--muted);
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="card">
        <h1>Backoffice</h1>
        <p class="sub">Masuk dengan username dan password admin.</p>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <form action="<?= site_url('backoffice/login') ?>" method="post" id="boLogin">
            <?= csrf_field() ?>
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= esc(old('username'), 'attr') ?>"
                    autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" id="boBtn">Masuk</button>
        </form>

        <a href="<?= site_url('/') ?>" class="back">&larr; Kembali ke situs</a>
    </div>

    <script>
    document.getElementById('boLogin').addEventListener('submit', function() {
        const b = document.getElementById('boBtn');
        b.textContent = 'Memproses...';
        b.disabled = true;
    });
    </script>
</body>

</html>