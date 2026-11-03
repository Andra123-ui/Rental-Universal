<?php

if (! function_exists('rupiah')) {
    /**
     * Format number to Indonesian Rupiah (IDR)
     */
    function rupiah($amount): string
    {
        $num = (float) ($amount ?? 0);
        return 'Rp ' . number_format($num, 0, ',', '.');
    }
}

if (! function_exists('format_datetime')) {
    /**
     * Format date & time to Indonesian standard
     */
    function format_datetime(?string $datetime, bool $withTime = true): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false || $timestamp <= 0) {
            return '-';
        }

        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $day   = date('d', $timestamp);
        $month = $months[(int) date('m', $timestamp)] ?? date('M', $timestamp);
        $year  = date('Y', $timestamp);

        if ($withTime) {
            $time = date('H:i', $timestamp) . ' WIB';
            return "{$day} {$month} {$year}, {$time}";
        }

        return "{$day} {$month} {$year}";
    }
}

if (! function_exists('format_date_short')) {
    /**
     * Format date in compact form: 01 Okt 2026
     */
    function format_date_short(?string $datetime, bool $withTime = false): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false || $timestamp <= 0) {
            return '-';
        }

        $months = [
            1 => 'Okt', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
        ];

        $day   = date('d', $timestamp);
        $month = $months[(int) date('m', $timestamp)] ?? date('M', $timestamp);
        $year  = date('Y', $timestamp);

        if ($withTime) {
            return "{$day} {$month} {$year} " . date('H:i', $timestamp);
        }

        return "{$day} {$month} {$year}";
    }
}

if (! function_exists('booking_status_badge')) {
    /**
     * Render Bootstrap 5 badge for Booking Status
     */
    function booking_status_badge(?string $status): string
    {
        $status = strtoupper(trim((string) $status));
        return match ($status) {
            'PENDING'   => '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Menunggu</span>',
            'CONFIRMED' => '<span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i>Dikonfirmasi</span>',
            'ONGOING'   => '<span class="badge bg-info text-dark"><i class="bi bi-arrow-repeat me-1"></i>Sedang Berjalan</span>',
            'COMPLETED' => '<span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>Selesai</span>',
            'CANCELLED' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Dibatalkan</span>',
            'EXPIRED'   => '<span class="badge bg-secondary"><i class="bi bi-hourglass-bottom me-1"></i>Kedaluwarsa</span>',
            default     => '<span class="badge bg-light text-dark border">' . esc($status ?: '-') . '</span>',
        };
    }
}

if (! function_exists('payment_status_badge')) {
    /**
     * Render Bootstrap 5 badge for Payment Status
     */
    function payment_status_badge(?string $status): string
    {
        $status = strtoupper(trim((string) $status));
        return match ($status) {
            'PAID'     => '<span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Lunas</span>',
            'PARTIAL'  => '<span class="badge bg-warning text-dark"><i class="bi bi-pie-chart me-1"></i>Sebagian</span>',
            'UNPAID'   => '<span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Belum Lunas</span>',
            'REFUNDED' => '<span class="badge bg-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Dikembalikan</span>',
            'PENDING'  => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu Verifikasi</span>',
            'FAILED'   => '<span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Gagal</span>',
            default    => '<span class="badge bg-light text-dark border">' . esc($status ?: '-') . '</span>',
        };
    }
}

if (! function_exists('verification_status_badge')) {
    /**
     * Render Bootstrap 5 badge for Identity Document Verification Status
     */
    function verification_status_badge(?string $status): string
    {
        $status = strtoupper(trim((string) $status));
        return match ($status) {
            'VERIFIED'   => '<span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i>Terverifikasi</span>',
            'PENDING'    => '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Menunggu Verifikasi</span>',
            'REJECTED'   => '<span class="badge bg-danger"><i class="bi bi-exclamation-octagon me-1"></i>Ditolak</span>',
            'UNVERIFIED' => '<span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Belum Terverifikasi</span>',
            default      => '<span class="badge bg-light text-dark border">' . esc($status ?: '-') . '</span>',
        };
    }
}

if (! function_exists('mask_text')) {
    /**
     * Mask sensitive strings (e.g. ID card numbers, phone numbers)
     * Example: 1234567891011125 -> 1234********1125
     */
    function mask_text(?string $text, int $startVisible = 4, int $endVisible = 4): string
    {
        if (empty($text)) {
            return '-';
        }

        $length = mb_strlen($text);
        if ($length <= ($startVisible + $endVisible)) {
            return $text;
        }

        $start = mb_substr($text, 0, $startVisible);
        $end   = mb_substr($text, -$endVisible);
        $maskCount = max(4, $length - ($startVisible + $endVisible));

        return $start . str_repeat('*', min(8, $maskCount)) . $end;
    }
}

