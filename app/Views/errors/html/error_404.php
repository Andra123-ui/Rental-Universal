<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Tidak Ditemukan (404) — Rental Universal</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background-color: #F8FAFC;
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #172554;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 24px;
        }
        h1 {
            font-family: 'Fraunces', Georgia, serif;
            font-weight: 700;
            color: #172554;
            letter-spacing: -0.02em;
        }
        .error-card {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            max-width: 620px;
            width: 100%;
            padding: 48px 36px;
            text-align: center;
            border: 1px solid #E2E8F0;
        }
        .error-img {
            max-width: 280px;
            height: auto;
            margin: 0 auto 28px;
            display: block;
            object-fit: contain;
        }
    </style>
</head>
<body>

    <div class="error-card">
        <img src="<?= base_url('uploads/catalog/404.png') ?>" alt="Halaman Tidak Ditemukan" class="error-img img-fluid">
        
        <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill mb-3 fw-semibold">
            Error 404 • Not Found
        </span>
        
        <h1 class="h3 mb-3">Oops! Halaman Tidak Ditemukan</h1>
        
        <p class="text-muted mb-4 lead fs-6">
            Maaf, halaman Rental Universal yang Anda tuju tidak tersedia, telah dipindahkan, atau tautan yang Anda masukkan keliru.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= base_url('/') ?>" class="btn btn-primary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-house-door-fill"></i>
                <span>Kembali ke Beranda</span>
            </a>
            <a href="<?= base_url('catalog') ?>" class="btn btn-outline-secondary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-grid-fill"></i>
                <span>Lihat Katalog Rental</span>
            </a>
        </div>
    </div>

</body>
</html>
